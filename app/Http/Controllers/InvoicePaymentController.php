<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class InvoicePaymentController extends Controller
{
    public function index(): View
    {
        $payments = InvoicePayment::with(['invoice.customer', 'invoice.rental'])
            ->latest('payment_date')
            ->latest()
            ->get();

        return view('payments.index', [
            'payments' => $payments,
            'summary' => [
                'total' => (float) $payments->sum('amount'),
                'count' => $payments->count(),
                'cash' => (float) $payments->where('method', 'cash')->sum('amount'),
                'bank' => (float) $payments->where('method', 'bank_transfer')->sum('amount'),
            ],
        ]);
    }

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->status === 'paid', 422, 'This invoice is already paid.');

        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$invoice->balance_due],
            'method' => ['required', Rule::in(['cash', 'bank_transfer', 'card', 'cheque', 'online'])],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $invoice->payments()->create([
            ...$validated,
            'company_id' => $invoice->company_id,
        ]);

        $invoice->recalculateTotals();

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
