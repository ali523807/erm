<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Rental;
use App\Models\TaxProfile;
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

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $status = (string) $request->input('status', 'all');
        $sort = (string) $request->input('sort', 'invoice_date');
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['invoice_date', 'due_date', 'customer', 'status', 'total_amount', 'balance_due'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'invoice_date';

        $invoices = Invoice::query()
            ->with(['customer', 'rental', 'payments', 'creditNotes'])
            ->when($search !== '', function ($query) use ($search): void {
                $rentalId = (int) str_replace('RTN-', '', strtoupper($search));

                $query->where(function ($query) use ($search, $rentalId): void {
                    $query->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('rental_id', $rentalId)
                        ->orWhereHas('customer', function ($query) use ($search): void {
                            $query->where('company_name', 'like', "%{$search}%")
                                ->orWhere('contact_person', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== 'all', function ($query) use ($status): void {
                if ($status === 'overdue') {
                    $query->where('status', '!=', 'paid')
                        ->whereNotNull('due_date')
                        ->whereDate('due_date', '<', now()->toDateString());

                    return;
                }

                $query->where('status', $status);
            });

        if ($sort === 'customer') {
            $invoices->leftJoin('customers', 'invoices.customer_id', '=', 'customers.id')
                ->select('invoices.*')
                ->orderBy('customers.company_name', $direction);
        } else {
            $invoices->orderBy($sort, $direction);
        }

        $invoices = $invoices->orderByDesc('invoices.id')->paginate(25)->withQueryString();
        $total = (float) Invoice::sum('base_total_amount');
        $balance = (float) Invoice::sum('base_balance_due');

        return view('invoices.index', [
            'invoices' => $invoices,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'summary' => [
                'total' => $total,
                'paid' => max(0, $total - $balance),
                'balance' => $balance,
                'overdue' => Invoice::where('status', '!=', 'paid')
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->count(),
            ],
        ]);
    }

    public function storeFromRental(Request $request, Rental $rental): RedirectResponse
    {
        abort_if($rental->invoice, 422, 'This rental already has an invoice.');

        $validated = $request->validate([
            'tax_profile_id' => ['nullable', 'exists:tax_profiles,id'],
            'currency' => ['nullable', 'string', 'size:3'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.00000001'],
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
        $invoice->load(['customer', 'rental.rentalItems.product', 'payments', 'creditNotes', 'paymentLinks.creator', 'expenses']);

        return view('invoices.show', [
            'invoice' => $invoice,
            'paymentMethods' => $this->paymentMethods(),
            'taxProfiles' => TaxProfile::where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(),
            'currencies' => $this->currencies(),
        ]);
    }

    public function download(Invoice $invoice): Response
    {
        $invoice->recalculateTotals();
        $invoice->load(['customer', 'rental.rentalItems.product', 'payments', 'creditNotes', 'expenses']);

        return Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
        ])
            ->setPaper('a4')
            ->download($invoice->invoice_number.'.pdf');
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->status === 'paid', 422, 'Paid invoices cannot be changed.');
        $originalTaxProfileId = $invoice->tax_profile_id;

        $validated = $request->validate([
            'tax_profile_id' => ['nullable', 'exists:tax_profiles,id'],
            'currency' => ['nullable', 'string', 'size:3'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.00000001'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'damage_amount' => ['nullable', 'numeric', 'min:0'],
            'late_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['currency'] = strtoupper($validated['currency'] ?? $invoice->currency ?? auth()->user()->currentCompany?->currency ?? 'USD');
        $validated['base_currency'] = auth()->user()->currentCompany?->currency ?? $invoice->base_currency ?? 'USD';
        $validated['exchange_rate'] = $validated['exchange_rate'] ?? $invoice->exchange_rate ?? 1;
        $invoice->fill($validated)->save();
        $taxProfileChanged = (string) ($validated['tax_profile_id'] ?? '') !== (string) ($originalTaxProfileId ?? '');
        if ($taxProfileChanged || ! array_key_exists('tax_amount', $validated) || $validated['tax_amount'] === null) {
            $invoice->tax_amount = $this->calculatedTaxAmount($invoice);
            $invoice->save();
        }
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
            'tax_profile_id' => $charges['tax_profile_id'] ?? TaxProfile::where('is_default', true)->value('id'),
            'invoice_number' => $this->nextInvoiceNumber(),
            'currency' => strtoupper($charges['currency'] ?? auth()->user()->currentCompany?->currency ?? 'USD'),
            'base_currency' => auth()->user()->currentCompany?->currency ?? 'USD',
            'exchange_rate' => $charges['exchange_rate'] ?? 1,
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
        if (! array_key_exists('tax_amount', $charges)) {
            $invoice->tax_amount = $this->calculatedTaxAmount($invoice);
            $invoice->save();
            $invoice->recalculateTotals();
        }

        return $invoice;
    }

    private function calculatedTaxAmount(Invoice $invoice): float
    {
        $profile = $invoice->taxProfile;

        if (! $profile) {
            return 0.0;
        }

        $taxableAmount = max(0, (float) $invoice->subtotal + (float) $invoice->damage_amount + (float) $invoice->late_fee_amount - (float) $invoice->discount_amount);

        return round($taxableAmount * ((float) $profile->rate / 100), 2);
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

    /**
     * @return array<string, string>
     */
    private function currencies(): array
    {
        return [
            'USD' => 'USD',
            'AED' => 'AED',
            'INR' => 'INR',
            'GBP' => 'GBP',
            'EUR' => 'EUR',
            'CAD' => 'CAD',
            'AUD' => 'AUD',
            'SAR' => 'SAR',
            'SGD' => 'SGD',
            'ZAR' => 'ZAR',
        ];
    }
}
