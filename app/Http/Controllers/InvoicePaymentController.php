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

    public function index(): View
    {
        $payments = InvoicePayment::with(['invoice.customer', 'invoice.rental'])
            ->latest('payment_date')
            ->latest()
            ->get();
        $baseAmount = fn (InvoicePayment $payment): float => (float) $payment->amount * (float) ($payment->invoice?->exchange_rate ?: 1);

        return view('payments.index', [
            'payments' => $payments,
            'summary' => [
                'total' => (float) $payments->sum($baseAmount),
                'count' => $payments->count(),
                'cash' => (float) $payments->where('method', 'cash')->sum($baseAmount),
                'bank' => (float) $payments->where('method', 'bank_transfer')->sum($baseAmount),
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
}
