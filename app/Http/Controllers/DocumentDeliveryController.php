<?php

namespace App\Http\Controllers;

use App\Mail\DocumentDeliveryMail;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\DocumentDelivery;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Quote;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class DocumentDeliveryController extends Controller
{
    public function __construct(private ActivityLogger $activity) {}

    public function index(): View
    {
        return view('document-deliveries.index', [
            'deliveries' => DocumentDelivery::with(['sender', 'deliverable'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function quote(Request $request, Quote $quote): RedirectResponse
    {
        $quote->load(['customer', 'items.product']);

        return $this->send(
            $request,
            $quote,
            'quote',
            "Quote {$quote->quote_number}",
            $quote->customer?->email,
            $quote->customer?->contact_person,
            Pdf::loadView('quotes.pdf', ['quote' => $quote])->setPaper('a4')->output(),
            $quote->quote_number.'.pdf',
        );
    }

    public function invoice(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoice->recalculateTotals();
        $invoice->load(['customer', 'rental.rentalItems.product', 'payments', 'creditNotes']);

        return $this->send(
            $request,
            $invoice,
            'invoice',
            "Invoice {$invoice->invoice_number}",
            $invoice->customer?->email,
            $invoice->customer?->contact_person,
            Pdf::loadView('invoices.pdf', ['invoice' => $invoice])->setPaper('a4')->output(),
            $invoice->invoice_number.'.pdf',
        );
    }

    public function receipt(Request $request, InvoicePayment $payment): RedirectResponse
    {
        $payment->load(['invoice.customer', 'invoice.rental']);

        return $this->send(
            $request,
            $payment,
            'receipt',
            'Receipt '.$payment->receiptNumber(),
            $payment->invoice?->customer?->email,
            $payment->invoice?->customer?->contact_person,
            Pdf::loadView('payments.receipt-pdf', ['payment' => $payment])->setPaper('a4')->output(),
            'Receipt-'.$payment->receiptNumber().'.pdf',
        );
    }

    public function creditNote(Request $request, CreditNote $creditNote): RedirectResponse
    {
        $creditNote->load(['invoice.rental', 'customer']);

        return $this->send(
            $request,
            $creditNote,
            'credit_note',
            "Credit Note {$creditNote->credit_note_number}",
            $creditNote->customer?->email,
            $creditNote->customer?->contact_person,
            Pdf::loadView('credit-notes.pdf', ['creditNote' => $creditNote])->setPaper('a4')->output(),
            $creditNote->credit_note_number.'.pdf',
        );
    }

    public function statement(Request $request, Customer $customer): RedirectResponse
    {
        $asOfDate = Carbon::parse($request->input('as_of', now()->toDateString()))->endOfDay();
        $fromDate = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : null;
        $data = $this->statementData($customer, $asOfDate, $fromDate);

        return $this->send(
            $request,
            $customer,
            'statement',
            'Statement - '.$customer->company_name,
            $customer->email,
            $customer->contact_person,
            Pdf::loadView('customers.statement-pdf', $data)->setPaper('a4')->output(),
            'Statement-'.$customer->id.'-'.$asOfDate->format('Y-m-d').'.pdf',
        );
    }

    private function send(
        Request $request,
        Model $deliverable,
        string $type,
        string $defaultSubject,
        ?string $defaultEmail,
        ?string $defaultName,
        string $attachmentData,
        string $attachmentName,
    ): RedirectResponse {
        $validated = $request->validate([
            'recipient_email' => ['nullable', 'email', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
        ]);

        $recipientEmail = $validated['recipient_email'] ?? $defaultEmail;
        abort_if(! $recipientEmail, 422, 'Recipient email is required.');

        $delivery = DocumentDelivery::create([
            'company_id' => $deliverable->company_id ?? auth()->user()->current_company_id,
            'sent_by' => auth()->id(),
            'deliverable_type' => $deliverable::class,
            'deliverable_id' => $deliverable->getKey(),
            'type' => $type,
            'recipient_email' => $recipientEmail,
            'recipient_name' => $validated['recipient_name'] ?? $defaultName,
            'subject' => $validated['subject'] ?? $defaultSubject,
            'message' => $validated['message'] ?? 'Please find the attached document for your records.',
            'attachment_name' => $attachmentName,
            'status' => 'pending',
        ]);

        try {
            Mail::to($delivery->recipient_email)->send(new DocumentDeliveryMail($delivery, $attachmentData, $attachmentName));

            $delivery->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $this->activity->log('document_delivery', 'sent', "Sent {$type} email to {$delivery->recipient_email}.", $delivery, [
                'deliverable_type' => $deliverable::class,
                'deliverable_id' => $deliverable->getKey(),
                'attachment' => $attachmentName,
            ]);

            return back()->with('success', 'Document email sent successfully.');
        } catch (\Throwable $exception) {
            $delivery->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            return back()->withErrors(['email' => 'Document email failed: '.$exception->getMessage()]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function statementData(Customer $customer, Carbon $asOfDate, ?Carbon $fromDate): array
    {
        $invoices = $customer->invoices()
            ->with(['payments', 'creditNotes'])
            ->when($fromDate, fn ($query) => $query->whereDate('invoice_date', '>=', $fromDate->toDateString()))
            ->whereDate('invoice_date', '<=', $asOfDate->toDateString())
            ->orderBy('invoice_date')
            ->get()
            ->each(fn (Invoice $invoice) => $invoice->recalculateTotals());

        return [
            'customer' => $customer,
            'invoices' => $invoices,
            'asOfDate' => $asOfDate,
            'fromDate' => $fromDate,
            'summary' => [
                'invoiceTotal' => (float) $invoices->sum('total_amount'),
                'paidTotal' => (float) $invoices->sum('paid_amount'),
                'creditTotal' => (float) $invoices->sum(fn (Invoice $invoice): float => (float) $invoice->creditNotes->where('status', '!=', 'voided')->sum('amount')),
                'balanceDue' => (float) $invoices->sum('balance_due'),
                'openInvoices' => $invoices->where('balance_due', '>', 0)->count(),
            ],
            'aging' => $this->agingBuckets($invoices, $asOfDate),
            'transactions' => collect(),
        ];
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return array{current: float, days_1_30: float, days_31_60: float, days_61_90: float, days_90_plus: float}
     */
    private function agingBuckets(Collection $invoices, Carbon $asOfDate): array
    {
        $buckets = [
            'current' => 0.0,
            'days_1_30' => 0.0,
            'days_31_60' => 0.0,
            'days_61_90' => 0.0,
            'days_90_plus' => 0.0,
        ];

        foreach ($invoices as $invoice) {
            $balance = (float) $invoice->balance_due;

            if ($balance <= 0) {
                continue;
            }

            $dueDate = $invoice->due_date ?: $invoice->invoice_date;
            $daysPastDue = $dueDate ? (int) $dueDate->diffInDays($asOfDate, false) : 0;

            if ($daysPastDue <= 0) {
                $buckets['current'] += $balance;
            } elseif ($daysPastDue <= 30) {
                $buckets['days_1_30'] += $balance;
            } elseif ($daysPastDue <= 60) {
                $buckets['days_31_60'] += $balance;
            } elseif ($daysPastDue <= 90) {
                $buckets['days_61_90'] += $balance;
            } else {
                $buckets['days_90_plus'] += $balance;
            }
        }

        return $buckets;
    }
}
