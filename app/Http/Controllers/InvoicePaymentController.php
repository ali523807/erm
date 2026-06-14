<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class InvoicePaymentController extends Controller
{
    public function __construct(private ActivityLogger $activity) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $method = (string) $request->input('method', 'all');
        $sort = (string) $request->input('sort', 'payment_date');
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['payment_date', 'invoice', 'customer', 'method', 'amount'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'payment_date';

        $payments = InvoicePayment::query()
            ->with(['invoice.customer', 'invoice.rental'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('reference', 'like', "%{$search}%")
                        ->orWhereHas('invoice', function ($query) use ($search): void {
                            $query->where('invoice_number', 'like', "%{$search}%")
                                ->orWhereHas('customer', function ($query) use ($search): void {
                                    $query->where('company_name', 'like', "%{$search}%")
                                        ->orWhere('contact_person', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->when($method !== 'all', fn ($query) => $query->where('method', $method));

        if ($sort === 'invoice') {
            $payments->leftJoin('invoices', 'invoice_payments.invoice_id', '=', 'invoices.id')
                ->select('invoice_payments.*')
                ->orderBy('invoices.invoice_number', $direction);
        } elseif ($sort === 'customer') {
            $payments->leftJoin('invoices', 'invoice_payments.invoice_id', '=', 'invoices.id')
                ->leftJoin('customers', 'invoices.customer_id', '=', 'customers.id')
                ->select('invoice_payments.*')
                ->orderBy('customers.company_name', $direction);
        } else {
            $payments->orderBy($sort, $direction);
        }

        $payments = $payments->orderByDesc('invoice_payments.id')->paginate(25)->withQueryString();
        $paymentTotals = InvoicePayment::query()
            ->leftJoin('invoices', 'invoice_payments.invoice_id', '=', 'invoices.id')
            ->selectRaw("
                COALESCE(SUM(invoice_payments.amount * COALESCE(invoices.exchange_rate, 1)), 0) as total,
                COALESCE(SUM(CASE WHEN invoice_payments.method = 'cash' THEN invoice_payments.amount * COALESCE(invoices.exchange_rate, 1) ELSE 0 END), 0) as cash,
                COALESCE(SUM(CASE WHEN invoice_payments.method = 'bank_transfer' THEN invoice_payments.amount * COALESCE(invoices.exchange_rate, 1) ELSE 0 END), 0) as bank
            ")
            ->first();

        return view('payments.index', [
            'payments' => $payments,
            'filters' => [
                'search' => $search,
                'method' => $method,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'methods' => $this->paymentMethods(),
            'summary' => [
                'total' => (float) $paymentTotals->total,
                'count' => InvoicePayment::count(),
                'cash' => (float) $paymentTotals->cash,
                'bank' => (float) $paymentTotals->bank,
            ],
        ]);
    }

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->status === 'paid', 422, 'This invoice is already paid.');

        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$invoice->balance_due],
            'method' => ['required', Rule::in(['cash', 'bank_transfer', 'card', 'cheque', 'online', 'deposit'])],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $payment = $invoice->payments()->create([
            ...$validated,
            'company_id' => $invoice->company_id,
        ]);

        $invoice->recalculateTotals();

        $this->activity->log('payments', 'created', "Recorded payment for invoice {$invoice->invoice_number}.", $payment, [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'amount' => $payment->amount,
            'method' => $payment->method,
            'balance_due' => $invoice->balance_due,
        ]);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Payment recorded successfully.');
    }

    public function print(InvoicePayment $payment): View
    {
        $payment->load(['invoice.customer', 'invoice.rental']);

        return view('payments.receipt-print', [
            'payment' => $payment,
        ]);
    }

    public function download(InvoicePayment $payment): Response
    {
        $payment->load(['invoice.customer', 'invoice.rental']);

        return Pdf::loadView('payments.receipt-pdf', [
            'payment' => $payment,
        ])
            ->setPaper('a4')
            ->download('Receipt-'.$payment->receiptNumber().'.pdf');
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
            'online' => 'Online',
            'deposit' => 'Deposit',
        ];
    }
}
