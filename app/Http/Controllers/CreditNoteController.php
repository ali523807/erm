<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class CreditNoteController extends Controller
{
    public function __construct(private ActivityLogger $activity) {}

    public function index(): View
    {
        $creditNotes = CreditNote::with(['invoice', 'customer'])
            ->latest('credit_date')
            ->latest()
            ->paginate(25);
        $creditTotals = CreditNote::query()
            ->leftJoin('invoices', 'credit_notes.invoice_id', '=', 'invoices.id')
            ->where('credit_notes.status', '!=', 'voided')
            ->selectRaw('
                COALESCE(SUM(credit_notes.amount * COALESCE(invoices.exchange_rate, 1)), 0) as credited,
                COALESCE(SUM(credit_notes.refund_amount * COALESCE(invoices.exchange_rate, 1)), 0) as refunded
            ')
            ->first();

        return view('credit-notes.index', [
            'creditNotes' => $creditNotes,
            'summary' => [
                'credited' => (float) $creditTotals->credited,
                'refunded' => (float) $creditTotals->refunded,
                'count' => CreditNote::count(),
                'open' => CreditNote::where('status', 'applied')->count(),
            ],
        ]);
    }

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoice->recalculateTotals();
        $availableCreditAmount = (float) $invoice->total_amount - (float) $invoice->creditNotes()->where('status', '!=', 'voided')->sum('amount');
        abort_if($availableCreditAmount <= 0, 422, 'This invoice has already been fully credited.');

        $validated = $request->validate([
            'credit_date' => ['required', 'date'],
            'reason' => ['required', Rule::in(['billing_correction', 'discount_adjustment', 'damage_reversal', 'return_adjustment', 'goodwill_credit', 'other'])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$availableCreditAmount],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'refund_method' => ['nullable', Rule::in(['cash', 'bank_transfer', 'card', 'cheque', 'online'])],
            'refund_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $refundAmount = (float) ($validated['refund_amount'] ?? 0);
        abort_if($refundAmount > (float) $validated['amount'], 422, 'Refund cannot be greater than the credit amount.');

        $creditNote = $invoice->creditNotes()->create([
            ...$validated,
            'company_id' => $invoice->company_id,
            'customer_id' => $invoice->customer_id,
            'credit_note_number' => $this->nextCreditNoteNumber($invoice->company_id),
            'refund_amount' => $refundAmount,
            'refund_method' => $refundAmount > 0 ? ($validated['refund_method'] ?? null) : null,
            'refund_reference' => $refundAmount > 0 ? ($validated['refund_reference'] ?? null) : null,
            'status' => $refundAmount > 0 ? 'refunded' : 'applied',
        ]);

        $invoice->recalculateTotals();

        $this->activity->log('credit_notes', 'created', "Created credit note {$creditNote->credit_note_number}.", $creditNote, [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'amount' => $creditNote->amount,
            'refund_amount' => $creditNote->refund_amount,
            'balance_due' => $invoice->balance_due,
        ]);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', "Credit note {$creditNote->credit_note_number} created successfully.");
    }

    public function show(CreditNote $creditNote): View
    {
        $creditNote->load(['invoice.rental', 'customer']);

        return view('credit-notes.show', [
            'creditNote' => $creditNote,
        ]);
    }

    public function edit(CreditNote $creditNote): View
    {
        abort_if($creditNote->status === 'voided', 422, 'Voided credit notes cannot be edited.');
        $creditNote->load(['invoice.rental', 'customer']);

        return view('credit-notes.edit', [
            'creditNote' => $creditNote,
            'reasons' => $this->reasons(),
            'refundMethods' => $this->refundMethods(),
            'availableCreditAmount' => $this->availableCreditAmount($creditNote),
        ]);
    }

    public function update(Request $request, CreditNote $creditNote): RedirectResponse
    {
        abort_if($creditNote->status === 'voided', 422, 'Voided credit notes cannot be edited.');

        $validated = $this->validateCreditNote($request, $this->availableCreditAmount($creditNote));
        $refundAmount = (float) ($validated['refund_amount'] ?? 0);
        abort_if($refundAmount > (float) $validated['amount'], 422, 'Refund cannot be greater than the credit amount.');

        $creditNote->fill([
            ...$validated,
            'refund_amount' => $refundAmount,
            'refund_method' => $refundAmount > 0 ? ($validated['refund_method'] ?? null) : null,
            'refund_reference' => $refundAmount > 0 ? ($validated['refund_reference'] ?? null) : null,
            'status' => $refundAmount > 0 ? 'refunded' : 'applied',
        ])->save();

        $changes = $this->activity->changesFor($creditNote);
        $creditNote->invoice?->recalculateTotals();

        $this->activity->log('credit_notes', 'updated', "Updated credit note {$creditNote->credit_note_number}.", $creditNote, [
            'changes' => $changes,
            'invoice_id' => $creditNote->invoice_id,
            'amount' => $creditNote->amount,
            'refund_amount' => $creditNote->refund_amount,
            'balance_due' => $creditNote->invoice?->balance_due,
        ]);

        return redirect()
            ->route('credit-notes.show', $creditNote)
            ->with('success', 'Credit note updated successfully.');
    }

    public function void(Request $request, CreditNote $creditNote): RedirectResponse
    {
        abort_if($creditNote->status === 'voided', 422, 'This credit note is already voided.');

        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:500'],
        ]);

        $creditNote->forceFill([
            'status' => 'voided',
            'notes' => trim(collect([$creditNote->notes, 'Void reason: '.$validated['void_reason']])->filter()->join("\n\n")),
        ])->save();

        $creditNote->invoice?->recalculateTotals();

        $this->activity->log('credit_notes', 'voided', "Voided credit note {$creditNote->credit_note_number}.", $creditNote, [
            'invoice_id' => $creditNote->invoice_id,
            'amount' => $creditNote->amount,
            'void_reason' => $validated['void_reason'],
            'balance_due' => $creditNote->invoice?->balance_due,
        ]);

        return redirect()
            ->route('credit-notes.show', $creditNote)
            ->with('success', 'Credit note voided successfully.');
    }

    public function print(CreditNote $creditNote): View
    {
        $creditNote->load(['invoice.rental', 'customer']);

        return view('credit-notes.print', [
            'creditNote' => $creditNote,
        ]);
    }

    public function download(CreditNote $creditNote): Response
    {
        $creditNote->load(['invoice.rental', 'customer']);

        return Pdf::loadView('credit-notes.pdf', [
            'creditNote' => $creditNote,
        ])
            ->setPaper('a4')
            ->download($creditNote->credit_note_number.'.pdf');
    }

    private function nextCreditNoteNumber(int $companyId): string
    {
        $next = CreditNote::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->count() + 1;

        return 'CRN-'.now()->format('Y').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, string>
     */
    private function reasons(): array
    {
        return [
            'billing_correction' => 'Billing Correction',
            'discount_adjustment' => 'Discount Adjustment',
            'damage_reversal' => 'Damage Reversal',
            'return_adjustment' => 'Return Adjustment',
            'goodwill_credit' => 'Goodwill Credit',
            'other' => 'Other',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function refundMethods(): array
    {
        return [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'card' => 'Card',
            'cheque' => 'Cheque',
            'online' => 'Online Payment',
        ];
    }

    private function availableCreditAmount(CreditNote $creditNote): float
    {
        $invoice = $creditNote->invoice;
        $otherCredits = (float) $invoice?->creditNotes()
            ->where('id', '!=', $creditNote->id)
            ->where('status', '!=', 'voided')
            ->sum('amount');

        return max(0.01, (float) $invoice?->total_amount - $otherCredits);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCreditNote(Request $request, float $availableCreditAmount): array
    {
        return $request->validate([
            'credit_date' => ['required', 'date'],
            'reason' => ['required', Rule::in(array_keys($this->reasons()))],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$availableCreditAmount],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'refund_method' => ['nullable', Rule::in(array_keys($this->refundMethods()))],
            'refund_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
