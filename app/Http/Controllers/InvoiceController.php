<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Rental;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function __construct(private ActivityLogger $activity) {}

    public function index(): View
    {
        $invoices = Invoice::with(['customer', 'rental', 'payments'])
            ->latest()
            ->get();

        return view('invoices.index', [
            'invoices' => $invoices,
            'summary' => [
                'total' => (float) $invoices->sum('total_amount'),
                'paid' => (float) $invoices->sum('paid_amount'),
                'balance' => (float) $invoices->sum('balance_due'),
                'overdue' => $invoices
                    ->filter(fn (Invoice $invoice): bool => $invoice->status !== 'paid'
                        && $invoice->due_date
                        && now()->toDateString() > $invoice->due_date->toDateString())
                    ->count(),
            ],
        ]);
    }

    public function storeFromRental(Request $request, Rental $rental): RedirectResponse
    {
        abort_if($rental->invoice, 422, 'This rental already has an invoice.');

        $validated = $request->validate([
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'damage_amount' => ['nullable', 'numeric', 'min:0'],
            'late_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $rental->load(['customer', 'rentalItems']);
        $invoice = DB::transaction(fn (): Invoice => $this->createInvoice($rental, $validated));

        $this->activity->log('invoices', 'created', "Created invoice {$invoice->invoice_number}.", $invoice, [
            'rental_id' => $rental->id,
            'customer_id' => $invoice->customer_id,
            'total_amount' => $invoice->total_amount,
        ]);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} created successfully.");
    }

    public function show(Invoice $invoice): View
    {
        $invoice->recalculateTotals();
        $invoice->load(['customer', 'rental.rentalItems.product', 'payments']);

        return view('invoices.show', [
            'invoice' => $invoice,
            'paymentMethods' => $this->paymentMethods(),
        ]);
    }

    public function download(Invoice $invoice): Response
    {
        $invoice->recalculateTotals();
        $invoice->load(['customer', 'rental.rentalItems.product', 'payments']);

        return Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
        ])
            ->setPaper('a4')
            ->download($invoice->invoice_number.'.pdf');
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->status === 'paid', 422, 'Paid invoices cannot be changed.');

        $validated = $request->validate([
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'damage_amount' => ['nullable', 'numeric', 'min:0'],
            'late_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $invoice->fill($validated)->save();
        $changes = $this->activity->changesFor($invoice);
        $invoice->recalculateTotals();

        $this->activity->log('invoices', 'updated', "Updated invoice {$invoice->invoice_number}.", $invoice, [
            'changes' => $changes,
            'total_amount' => $invoice->total_amount,
            'balance_due' => $invoice->balance_due,
        ]);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }

    /**
     * @param  array<string, mixed>  $charges
     */
    private function createInvoice(Rental $rental, array $charges): Invoice
    {
        $invoice = new Invoice([
            'company_id' => $rental->company_id,
            'rental_id' => $rental->id,
            'customer_id' => $rental->customer_id,
            'invoice_number' => $this->nextInvoiceNumber(),
            'invoice_date' => now()->toDateString(),
            'due_date' => $charges['due_date'] ?? now()->addDays(14)->toDateString(),
            'status' => 'issued',
            'subtotal' => 0,
            'deposit_amount' => 0,
            'tax_amount' => $charges['tax_amount'] ?? 0,
            'discount_amount' => $charges['discount_amount'] ?? 0,
            'damage_amount' => $charges['damage_amount'] ?? 0,
            'late_fee_amount' => $charges['late_fee_amount'] ?? 0,
            'paid_amount' => 0,
            'notes' => $charges['notes'] ?? null,
        ]);

        $invoice->save();
        $invoice->recalculateTotals();

        return $invoice;
    }

    private function nextInvoiceNumber(): string
    {
        $companyId = auth()->user()->current_company_id;
        $next = Invoice::withoutGlobalScopes()->where('company_id', $companyId)->count() + 1;

        return 'INV-'.now()->format('Y').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, string>
     */
    private function paymentMethods(): array
    {
        return [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'card' => 'Card',
            'cheque' => 'Cheque',
            'online' => 'Online Payment',
        ];
    }
}
