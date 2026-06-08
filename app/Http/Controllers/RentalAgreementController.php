<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Rental;
use App\Models\RentalAgreement;
use App\Models\ReturnInspection;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class RentalAgreementController extends Controller
{
    public function storeFromRental(Rental $rental): RedirectResponse
    {
        abort_if($rental->agreement, 422, 'This rental already has an agreement.');

        $rental->load(['customer', 'rentalItems.product']);

        $agreement = RentalAgreement::create([
            'company_id' => $rental->company_id,
            'rental_id' => $rental->id,
            'agreement_number' => $this->nextAgreementNumber(),
            'status' => 'draft',
            'agreement_date' => now()->toDateString(),
            'valid_until' => $rental->rental_start_date?->toDateString(),
            'signed_by_customer' => $rental->customer?->contact_person ?: 'Customer Representative',
            'terms' => $this->defaultTerms(),
        ]);

        return redirect()
            ->route('agreements.show', $agreement)
            ->with('success', "Agreement {$agreement->agreement_number} created successfully.");
    }

    public function show(RentalAgreement $agreement): View
    {
        $agreement->load(['rental.customer', 'rental.invoice', 'rental.rentalItems.product', 'returnInspections.product']);

        return view('agreements.show', [
            'agreement' => $agreement,
            'conditionStatuses' => $this->conditionStatuses(),
            'nextEquipmentStatuses' => $this->nextEquipmentStatuses(),
        ]);
    }

    public function print(RentalAgreement $agreement): View
    {
        $agreement->load(['rental.customer', 'rental.rentalItems.product']);

        return view('agreements.print', [
            'agreement' => $agreement,
        ]);
    }

    public function download(RentalAgreement $agreement): Response
    {
        $agreement->load(['rental.customer', 'rental.rentalItems.product']);

        return Pdf::loadView('agreements.pdf', [
            'agreement' => $agreement,
        ])
            ->setPaper('a4')
            ->download($agreement->agreement_number.'.pdf');
    }

    public function checkout(Request $request, RentalAgreement $agreement): RedirectResponse
    {
        $validated = $request->validate([
            'checkout_representative' => ['required', 'string', 'max:255'],
            'checkout_id_number' => ['nullable', 'string', 'max:255'],
            'checkout_condition' => ['required', 'string'],
            'checkout_accessories' => ['nullable', 'string'],
            'checkout_notes' => ['nullable', 'string'],
            'customer_accepted_checkout' => ['accepted'],
        ]);

        DB::transaction(function () use ($agreement, $validated): void {
            $agreement->update([
                ...$validated,
                'customer_accepted_checkout' => true,
                'checked_out_at' => now(),
                'signed_by_customer' => $validated['checkout_representative'],
                'signed_at' => now(),
                'status' => 'checked_out',
            ]);

            $agreement->rental()->update(['status' => 'active']);
            $agreement->rental->rentalItems()->update(['status' => 'on_rent']);
        });

        return redirect()
            ->route('agreements.show', $agreement)
            ->with('success', 'Check-out sign-off completed successfully.');
    }

    public function return(Request $request, RentalAgreement $agreement): RedirectResponse
    {
        $validated = $request->validate([
            'return_representative' => ['required', 'string', 'max:255'],
            'return_condition' => ['required', 'string'],
            'return_missing_accessories' => ['nullable', 'string'],
            'return_damage_notes' => ['nullable', 'string'],
            'damage_amount' => ['nullable', 'numeric', 'min:0'],
            'customer_accepted_return' => ['accepted'],
            'inspections' => ['nullable', 'array'],
            'inspections.*.rental_item_id' => ['required_with:inspections', 'integer'],
            'inspections.*.condition_status' => ['required_with:inspections', Rule::in(array_keys($this->conditionStatuses()))],
            'inspections.*.condition_notes' => ['nullable', 'string'],
            'inspections.*.missing_accessories' => ['nullable', 'string'],
            'inspections.*.damage_notes' => ['nullable', 'string'],
            'inspections.*.damage_amount' => ['nullable', 'numeric', 'min:0'],
            'inspections.*.next_equipment_status' => ['required_with:inspections', Rule::in(array_keys($this->nextEquipmentStatuses()))],
        ]);

        DB::transaction(function () use ($agreement, $validated): void {
            $rental = $agreement->rental()->with(['invoice', 'rentalItems.product'])->firstOrFail();
            $itemDamageAmount = $this->storeReturnInspections($agreement, $rental, $validated['inspections'] ?? []);
            $damageAmount = (float) ($validated['damage_amount'] ?? 0) + $itemDamageAmount;
            $damageNotes = $this->combinedDamageNotes($validated['return_damage_notes'] ?? null, $agreement);

            $agreement->update([
                ...collect($validated)->except('inspections')->all(),
                'damage_amount' => $damageAmount,
                'return_damage_notes' => $damageNotes,
                'customer_accepted_return' => true,
                'returned_at' => now(),
                'status' => 'returned',
            ]);

            $rental->update(['status' => 'returned']);
            $rental->rentalItems()->update(['status' => 'returned']);

            if ($damageAmount > 0) {
                $invoice = $rental->invoice ?: $this->createInvoiceForDamage($rental);
                $invoice->forceFill([
                    'damage_amount' => $damageAmount,
                    'notes' => trim(($invoice->notes ? $invoice->notes."\n\n" : '').'Damage from '.$agreement->agreement_number.': '.($damageNotes ?: 'Return damage charge.')),
                ])->save();
                $invoice->recalculateTotals();
            }
        });

        return redirect()
            ->route('agreements.show', $agreement)
            ->with('success', 'Return sign-off completed successfully.');
    }

    private function createInvoiceForDamage(Rental $rental): Invoice
    {
        $invoice = Invoice::create([
            'company_id' => $rental->company_id,
            'rental_id' => $rental->id,
            'customer_id' => $rental->customer_id,
            'invoice_number' => $this->nextInvoiceNumber(),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'issued',
        ]);

        $invoice->recalculateTotals();

        return $invoice;
    }

    private function nextAgreementNumber(): string
    {
        $companyId = auth()->user()->current_company_id;
        $next = RentalAgreement::withoutGlobalScopes()->where('company_id', $companyId)->count() + 1;

        return 'AGR-'.now()->format('Y').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function nextInvoiceNumber(): string
    {
        $companyId = auth()->user()->current_company_id;
        $next = Invoice::withoutGlobalScopes()->where('company_id', $companyId)->count() + 1;

        return 'INV-'.now()->format('Y').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function defaultTerms(): string
    {
        return 'Customer accepts responsibility for the equipment during the rental period, including safe operation, custody, loss, theft, late return, missing accessories, and damage beyond normal wear. Equipment must be returned in the same condition as received.';
    }

    /**
     * @param  array<int, array<string, mixed>>  $inspections
     */
    private function storeReturnInspections(RentalAgreement $agreement, Rental $rental, array $inspections): float
    {
        $items = $rental->rentalItems->keyBy('id');

        return collect($inspections)
            ->reduce(function (float $total, array $inspection) use ($agreement, $rental, $items): float {
                $item = $items->get((int) $inspection['rental_item_id']);

                if (! $item) {
                    return $total;
                }

                $damageAmount = (float) ($inspection['damage_amount'] ?? 0);
                $nextStatus = $inspection['next_equipment_status'] ?? $this->defaultNextEquipmentStatus($inspection['condition_status']);

                ReturnInspection::updateOrCreate(
                    [
                        'rental_agreement_id' => $agreement->id,
                        'rental_item_id' => $item->id,
                    ],
                    [
                        'company_id' => $agreement->company_id,
                        'rental_id' => $rental->id,
                        'product_id' => $item->product_id,
                        'condition_status' => $inspection['condition_status'],
                        'condition_notes' => $inspection['condition_notes'] ?? null,
                        'missing_accessories' => $inspection['missing_accessories'] ?? null,
                        'damage_notes' => $inspection['damage_notes'] ?? null,
                        'damage_amount' => $damageAmount,
                        'next_equipment_status' => $nextStatus,
                        'inspected_by' => auth()->user()?->name,
                        'inspected_at' => now(),
                    ]
                );

                if ($item->product) {
                    $item->product->update([
                        'status' => $nextStatus,
                        'condition' => $this->productConditionFromInspection($inspection['condition_status'], $inspection['condition_notes'] ?? null),
                    ]);
                }

                return $total + $damageAmount;
            }, 0.0);
    }

    private function combinedDamageNotes(?string $generalNotes, RentalAgreement $agreement): ?string
    {
        $inspectionNotes = $agreement->returnInspections()
            ->where(function ($query): void {
                $query->whereNotNull('damage_notes')
                    ->orWhere('damage_amount', '>', 0);
            })
            ->with('product')
            ->get()
            ->map(fn (ReturnInspection $inspection): string => trim(($inspection->product?->name ?: 'Equipment').': '.($inspection->damage_notes ?: 'Damage charge recorded.')))
            ->filter()
            ->join("\n");

        return collect([$generalNotes, $inspectionNotes])
            ->filter()
            ->join("\n");
    }

    /**
     * @return array<string, string>
     */
    private function conditionStatuses(): array
    {
        return [
            'good' => 'Good / normal wear',
            'dirty' => 'Dirty / cleaning needed',
            'missing_accessories' => 'Missing accessories',
            'damaged' => 'Damaged',
            'lost' => 'Lost',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function nextEquipmentStatuses(): array
    {
        return [
            'available' => 'Available',
            'maintenance' => 'Maintenance',
            'damaged' => 'Damaged',
            'retired' => 'Retired',
        ];
    }

    private function defaultNextEquipmentStatus(string $conditionStatus): string
    {
        return match ($conditionStatus) {
            'dirty', 'missing_accessories' => 'maintenance',
            'damaged', 'lost' => 'damaged',
            default => 'available',
        };
    }

    private function productConditionFromInspection(string $conditionStatus, ?string $notes): string
    {
        $label = $this->conditionStatuses()[$conditionStatus] ?? str($conditionStatus)->headline()->toString();

        return trim($label.($notes ? ' - '.$notes : ''));
    }
}
