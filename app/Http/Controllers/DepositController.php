<?php

namespace App\Http\Controllers;

use App\Models\DepositTransaction;
use App\Models\InvoicePayment;
use App\Models\Rental;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DepositController extends Controller
{
    public function __construct(private ActivityLogger $activity) {}

    public function index(): View
    {
        $transactions = DepositTransaction::with(['rental.customer', 'invoice', 'creator'])
            ->latest('transaction_date')
            ->latest()
            ->paginate(25);
        $collected = (float) DepositTransaction::where('type', 'collected')->sum('amount');
        $refunded = (float) DepositTransaction::where('type', 'refunded')->sum('amount');
        $applied = (float) DepositTransaction::where('type', 'applied')->sum('amount');

        return view('deposits.index', [
            'transactions' => $transactions,
            'summary' => [
                'collected' => $collected,
                'refunded' => $refunded,
                'applied' => $applied,
                'held' => $collected - $refunded - $applied,
            ],
        ]);
    }

    public function collect(Request $request, Rental $rental): RedirectResponse
    {
        $rental->loadMissing('customer', 'rentalItems', 'depositTransactions');
        $outstanding = $rental->depositOutstandingAmount();
        abort_if($outstanding <= 0, 422, 'The required security deposit has already been collected.');

        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$outstanding],
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'card', 'cheque', 'online', 'other'])],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $transaction = $this->recordTransaction($rental, 'collected', $validated);

        $this->activity->log('deposits', 'collected', "Collected security deposit for rental RTN-{$rental->id}.", $transaction, [
            'rental_id' => $rental->id,
            'customer_id' => $rental->customer_id,
            'amount' => $transaction->amount,
        ]);

        return redirect()
            ->route('rentals.show', $rental)
            ->with('success', 'Security deposit collected successfully.');
    }

    public function refund(Request $request, Rental $rental): RedirectResponse
    {
        $rental->loadMissing('customer', 'depositTransactions');
        $held = $rental->depositHeldAmount();
        abort_if($held <= 0, 422, 'There is no held security deposit to refund.');

        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$held],
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'card', 'cheque', 'online', 'other'])],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $transaction = $this->recordTransaction($rental, 'refunded', $validated);

        $this->activity->log('deposits', 'refunded', "Refunded security deposit for rental RTN-{$rental->id}.", $transaction, [
            'rental_id' => $rental->id,
            'customer_id' => $rental->customer_id,
            'amount' => $transaction->amount,
        ]);

        return redirect()
            ->route('rentals.show', $rental)
            ->with('success', 'Security deposit refund recorded successfully.');
    }

    public function apply(Request $request, Rental $rental): RedirectResponse
    {
        $rental->loadMissing('customer', 'invoice', 'depositTransactions');
        abort_unless($rental->invoice, 422, 'Generate an invoice before applying a security deposit.');

        $rental->invoice->recalculateTotals();
        $held = $rental->depositHeldAmount();
        $maximum = min($held, (float) $rental->invoice->balance_due);
        abort_if($maximum <= 0, 422, 'There is no deposit or invoice balance available to apply.');

        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$maximum],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        [$transaction, $payment] = DB::transaction(function () use ($rental, $validated): array {
            $transaction = $this->recordTransaction($rental, 'applied', [
                ...$validated,
                'payment_method' => 'deposit',
                'invoice_id' => $rental->invoice->id,
            ]);

            $payment = InvoicePayment::create([
                'company_id' => $rental->company_id,
                'invoice_id' => $rental->invoice->id,
                'payment_date' => $validated['transaction_date'],
                'amount' => $validated['amount'],
                'method' => 'deposit',
                'reference' => $validated['reference'] ?: 'DEP-'.$transaction->id,
                'notes' => $validated['notes'] ?: 'Security deposit applied to invoice balance.',
            ]);

            $transaction->update([
                'metadata' => ['invoice_payment_id' => $payment->id],
            ]);

            $rental->invoice->recalculateTotals();

            return [$transaction, $payment];
        });

        $this->activity->log('deposits', 'applied', "Applied security deposit to invoice {$rental->invoice->invoice_number}.", $transaction, [
            'rental_id' => $rental->id,
            'invoice_id' => $rental->invoice->id,
            'invoice_payment_id' => $payment->id,
            'amount' => $transaction->amount,
        ]);

        return redirect()
            ->route('rentals.show', $rental)
            ->with('success', 'Security deposit applied to invoice successfully.');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function recordTransaction(Rental $rental, string $type, array $attributes): DepositTransaction
    {
        return DepositTransaction::create([
            'company_id' => $rental->company_id,
            'rental_id' => $rental->id,
            'customer_id' => $rental->customer_id,
            'invoice_id' => $attributes['invoice_id'] ?? null,
            'created_by' => auth()->id(),
            'type' => $type,
            'transaction_date' => $attributes['transaction_date'],
            'amount' => $attributes['amount'],
            'payment_method' => $attributes['payment_method'] ?? null,
            'reference' => $attributes['reference'] ?? null,
            'notes' => $attributes['notes'] ?? null,
        ]);
    }
}
