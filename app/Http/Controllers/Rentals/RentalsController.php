<?php

namespace App\Http\Controllers\Rentals;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Rental;
use App\Services\ActivityLogger;
use App\Services\EquipmentAvailabilityService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RentalsController extends Controller
{
    public function __construct(private ActivityLogger $activity) {}

    /**
     * @var array<string, string>
     */
    private array $statuses = [
        'reserved' => 'Reserved',
        'active' => 'Checked Out',
        'returned' => 'Returned',
        'closed' => 'Closed',
        'cancelled' => 'Cancelled',
    ];

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $status = (string) $request->input('status', 'all');
        $sort = (string) $request->input('sort', 'rental_start_date');
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['rental_start_date', 'rental_end_date', 'customer', 'status', 'amount', 'created_at'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'rental_start_date';

        $rentals = Rental::query()
            ->with(['customer', 'quote'])
            ->withCount('rentalItems')
            ->withSum('rentalItems as rental_items_total_price', 'total_price')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('id', (int) str_replace('RTN-', '', strtoupper($search)))
                        ->orWhere('delivery_location', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($query) use ($search): void {
                            $query->where('company_name', 'like', "%{$search}%")
                                ->orWhere('contact_person', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== 'all' && array_key_exists($status, $this->statuses), fn ($query) => $query->where('status', $status));

        if ($sort === 'customer') {
            $rentals->leftJoin('customers', 'rentals.customer_id', '=', 'customers.id')
                ->select('rentals.*')
                ->orderBy('customers.company_name', $direction);
        } elseif ($sort === 'amount') {
            $rentals->orderBy('rental_items_total_price', $direction);
        } else {
            $rentals->orderBy($sort, $direction);
        }

        $rentals = $rentals->orderByDesc('rentals.id')->paginate(25)->withQueryString();

        return view('rentals.index', [
            'rentals' => $rentals,
            'statuses' => $this->statuses,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'summary' => [
                'total' => Rental::count(),
                'active' => Rental::whereIn('status', ['active', 'on_rent', 'open'])->count(),
                'reserved' => Rental::where('status', 'reserved')->count(),
                'overdue' => Rental::whereNotIn('status', ['returned', 'closed', 'cancelled'])
                    ->whereNotNull('rental_end_date')
                    ->whereDate('rental_end_date', '<', now()->toDateString())
                    ->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('rentals.create', $this->formData(new Rental([
            'rental_start_date' => now()->toDateString(),
            'rental_end_date' => now()->addDay()->toDateString(),
            'delivery_date' => now()->toDateString(),
            'pickup_date' => now()->addDay()->toDateString(),
            'status' => 'reserved',
        ])));
    }

    public function store(Request $request, EquipmentAvailabilityService $availability): RedirectResponse
    {
        $validated = $this->validatedData($request);
        $items = $this->normalizedItems($validated['items']);
        $this->validateAvailability($items, $availability);

        $rental = DB::transaction(function () use ($validated, $items): Rental {
            $rental = Rental::create(collect($validated)->except('items')->all());

            foreach ($items as $item) {
                $rental->rentalItems()->create($item);
            }

            return $rental;
        });

        $this->activity->log('rentals', 'created', "Created rental RTN-{$rental->id}.", $rental, [
            'customer_id' => $rental->customer_id,
            'status' => $rental->status,
            'items' => count($items),
        ]);

        return redirect()
            ->route('rentals.show', $rental)
            ->with('success', 'Rental created successfully.');
    }

    public function show(Rental $rental): View
    {
        $rental->load([
            'customer',
            'quote',
            'invoice.payments',
            'invoice.creditNotes',
            'agreement.returnInspections',
            'rentalItems.product.category',
            'depositTransactions.invoice',
            'depositTransactions.creator',
            'expenses.invoice',
        ]);

        return view('rentals.show', [
            'rental' => $rental,
            'statuses' => $this->statuses,
            'totals' => $this->totals($rental),
            'nextStatuses' => $this->nextStatuses($rental->status),
            'closeOutChecklist' => $this->closeOutChecklist($rental),
        ]);
    }

    public function edit(Rental $rental): View
    {
        $rental->load(['customer', 'rentalItems.product.category']);

        return view('rentals.edit', $this->formData($rental));
    }

    public function update(Request $request, Rental $rental, EquipmentAvailabilityService $availability): RedirectResponse
    {
        abort_if(in_array($rental->status, ['closed', 'cancelled'], true), 422, 'Closed or cancelled rentals cannot be edited.');

        $validated = $this->validatedData($request);
        $items = $this->normalizedItems($validated['items']);
        $this->validateAvailability($items, $availability, $rental->id);

        DB::transaction(function () use ($rental, $validated, $items): void {
            $rental->update(collect($validated)->except('items')->all());
            $rental->rentalItems()->delete();

            foreach ($items as $item) {
                $rental->rentalItems()->create($item);
            }
        });

        $this->activity->log('rentals', 'updated', "Updated rental RTN-{$rental->id}.", $rental, [
            'customer_id' => $rental->customer_id,
            'status' => $rental->status,
            'items' => count($items),
        ]);

        return redirect()
            ->route('rentals.show', $rental)
            ->with('success', 'Rental updated successfully.');
    }

    public function updateStatus(Request $request, Rental $rental): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys($this->statuses))],
        ]);

        if ($validated['status'] !== $rental->status && ! array_key_exists($validated['status'], $this->nextStatuses($rental->status))) {
            throw ValidationException::withMessages([
                'status' => 'This rental cannot move from '.$this->statusLabel($rental->status).' to '.$this->statusLabel($validated['status']).'.',
            ]);
        }

        if ($validated['status'] === 'closed') {
            $checklist = $this->closeOutChecklist($rental);

            if (! $checklist['canClose']) {
                throw ValidationException::withMessages([
                    'status' => 'Resolve close-out items before closing: '.collect($checklist['blockingItems'])->pluck('label')->join(', '),
                ]);
            }
        }

        $oldStatus = $rental->status;
        $rental->update($validated);
        $rental->rentalItems()->update(['status' => $this->itemStatusForRental($validated['status'])]);

        $this->activity->log('rentals', 'status_changed', "Changed rental RTN-{$rental->id} status.", $rental, [
            'status' => [
                'old' => $oldStatus,
                'new' => $rental->status,
            ],
        ]);

        return redirect()
            ->route('rentals.show', $rental)
            ->with('success', 'Rental status updated successfully.');
    }

    public function destroy(Rental $rental): RedirectResponse
    {
        abort_if(! in_array($rental->status, ['reserved', 'cancelled'], true), 422, 'Only reserved or cancelled rentals can be deleted.');

        $rentalId = $rental->id;
        $rental->delete();

        $this->activity->log('rentals', 'deleted', "Deleted rental RTN-{$rentalId}.", null, [
            'rental_id' => $rentalId,
        ]);

        return redirect()
            ->route('rentals.index')
            ->with('success', 'Rental deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Rental $rental): array
    {
        $items = old('items', $rental->exists ? $rental->rentalItems->map(fn ($item): array => [
            'product_id' => $item->product_id,
            'start_date' => $item->start_date,
            'end_date' => $item->end_date,
            'duration_type' => $item->duration_type ?: 'days',
            'no_of_duration' => $item->no_of_duration,
            'rate' => $item->rate,
            'deposit_amount' => $item->deposit_amount,
            'status' => $item->status ?: 'reserved',
        ])->values()->all() : [[
            'product_id' => '',
            'start_date' => $rental->rental_start_date?->toDateString() ?: now()->toDateString(),
            'end_date' => $rental->rental_end_date?->toDateString() ?: now()->addDay()->toDateString(),
            'duration_type' => 'daily',
            'no_of_duration' => 1,
            'rate' => 0,
            'deposit_amount' => 0,
            'status' => 'reserved',
        ]]);
        $customerId = old('customer_id', $rental->customer_id);
        $selectedCustomer = $customerId ? Customer::find($customerId) : null;
        $selectedProducts = Product::with('category')
            ->whereIn('id', collect($items)->pluck('product_id')->filter()->unique()->values())
            ->get()
            ->mapWithKeys(fn (Product $product): array => [$product->id => $this->productSelectPayload($product)])
            ->all();

        return [
            'rental' => $rental,
            'selectedCustomer' => $selectedCustomer,
            'selectedProducts' => $selectedProducts,
            'statuses' => $this->statuses,
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function productSelectPayload(Product $product): array
    {
        return [
            'id' => $product->id,
            'text' => collect([
                $product->equipment_code,
                $product->name,
                $product->category?->name,
                str($product->status ?? 'available')->headline(),
            ])->filter()->join(' - '),
            'rate' => (float) $product->default_rate,
            'rateType' => $product->default_rate_type,
            'deposit' => (float) $product->default_deposit_amount,
            'rates' => [
                'hourly' => (float) $product->hourly_rate,
                'daily' => (float) $product->daily_rate,
                'weekly' => (float) $product->weekly_rate,
                'monthly' => (float) $product->monthly_rate,
                'custom' => (float) $product->custom_rate,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        $companyId = auth()->user()->current_company_id;

        return $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'rental_start_date' => ['required', 'date'],
            'rental_end_date' => ['required', 'date', 'after_or_equal:rental_start_date'],
            'delivery_location' => ['nullable', 'string', 'max:255'],
            'delivery_date' => ['nullable', 'date'],
            'pickup_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys($this->statuses))],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'items.*.start_date' => ['required', 'date'],
            'items.*.end_date' => ['required', 'date', 'after_or_equal:items.*.start_date'],
            'items.*.duration_type' => ['required', Rule::in(['hourly', 'daily', 'weekly', 'monthly', 'custom', 'days', 'weeks', 'months'])],
            'items.*.no_of_duration' => ['required', 'numeric', 'min:0.01'],
            'items.*.rate' => ['required', 'numeric', 'min:0'],
            'items.*.deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.status' => ['nullable', Rule::in(['reserved', 'on_rent', 'returned', 'cancelled'])],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizedItems(array $items): array
    {
        return collect($items)
            ->filter(fn (array $item): bool => ! empty($item['product_id']))
            ->map(function (array $item): array {
                $duration = (float) $item['no_of_duration'];
                $rate = (float) $item['rate'];

                return [
                    'company_id' => auth()->user()->current_company_id,
                    'product_id' => $item['product_id'],
                    'start_date' => $item['start_date'],
                    'end_date' => $item['end_date'],
                    'duration_type' => $this->normalizedRateType($item['duration_type']),
                    'no_of_duration' => $duration,
                    'rate_type' => $this->normalizedRateType($item['duration_type']),
                    'rate' => $rate,
                    'deposit_amount' => $item['deposit_amount'] ?? 0,
                    'total_days' => $duration,
                    'total_price' => $duration * $rate,
                    'status' => $item['status'] ?? 'reserved',
                ];
            })
            ->values()
            ->all();
    }

    private function normalizedRateType(string $rateType): string
    {
        return match ($rateType) {
            'days' => 'daily',
            'weeks' => 'weekly',
            'months' => 'monthly',
            default => $rateType,
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     *
     * @throws ValidationException
     */
    private function validateAvailability(array $items, EquipmentAvailabilityService $availability, ?int $ignoreRentalId = null): void
    {
        $errors = [];
        $selectedProducts = [];

        foreach ($items as $index => $item) {
            $product = Product::find((int) $item['product_id']);

            if (! $product) {
                continue;
            }

            if (in_array($product->id, $selectedProducts, true)) {
                $errors["items.{$index}.product_id"] = $product->name.' is already selected. Add each asset only once.';
            }

            $selectedProducts[] = $product->id;

            $conflicts = $availability->conflicts($product, $item['start_date'], $item['end_date'], $ignoreRentalId);

            if ($conflicts->isNotEmpty()) {
                $errors["items.{$index}.product_id"] = $product->name.' is not available: '.$conflicts->pluck('label')->join('; ');
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @return array{subtotal: float, deposit: float}
     */
    private function totals(Rental $rental): array
    {
        return [
            'subtotal' => (float) $rental->rentalItems->sum('total_price'),
            'deposit' => (float) $rental->rentalItems->sum('deposit_amount'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function nextStatuses(?string $status): array
    {
        return match ($status) {
            'reserved' => collect($this->statuses)->only(['active', 'cancelled'])->all(),
            'active', 'on_rent', 'open' => collect($this->statuses)->only(['returned', 'cancelled'])->all(),
            'returned' => collect($this->statuses)->only(['closed'])->all(),
            default => [],
        };
    }

    /**
     * @return array{items: array<int, array{key: string, label: string, help: string, passed: bool, blocking: bool}>, canClose: bool, blockingItems: array<int, array{key: string, label: string, help: string, passed: bool, blocking: bool}>, passedCount: int, totalCount: int}
     */
    private function closeOutChecklist(Rental $rental): array
    {
        $rental->loadMissing([
            'invoice.payments',
            'invoice.creditNotes',
            'agreement.returnInspections',
            'rentalItems',
            'depositTransactions',
            'expenses.invoice',
        ]);

        $itemsReturned = $rental->status === 'returned'
            && $rental->rentalItems->isNotEmpty()
            && $rental->rentalItems->every(fn ($item): bool => $item->status === 'returned');
        $invoice = $rental->invoice;
        $invoiceSettled = $invoice && (float) $invoice->balance_due <= 0;
        $unbilledBillableExpenses = $rental->expenses
            ->where('is_billable', true)
            ->where('recovery_status', 'not_invoiced')
            ->count();
        $depositHeld = $rental->depositHeldAmount();
        $agreement = $rental->agreement;
        $returnInspectionsRequired = $agreement !== null;
        $returnInspectionsComplete = ! $returnInspectionsRequired
            || $agreement->returnInspections->count() >= $rental->rentalItems->count();

        $items = [
            [
                'key' => 'equipment_returned',
                'label' => 'Equipment returned',
                'help' => 'All rental assets must be marked returned before close-out.',
                'passed' => $itemsReturned,
                'blocking' => true,
            ],
            [
                'key' => 'invoice_generated',
                'label' => 'Invoice generated',
                'help' => 'Create the final customer invoice from this rental.',
                'passed' => $invoice !== null,
                'blocking' => true,
            ],
            [
                'key' => 'invoice_settled',
                'label' => 'Invoice settled',
                'help' => 'The linked invoice must have no remaining balance due.',
                'passed' => (bool) $invoiceSettled,
                'blocking' => true,
            ],
            [
                'key' => 'billable_expenses_reviewed',
                'label' => 'Billable expenses reviewed',
                'help' => 'Add billable rental expenses to the invoice or mark them non-billable before closing.',
                'passed' => $unbilledBillableExpenses === 0,
                'blocking' => true,
            ],
            [
                'key' => 'deposit_settled',
                'label' => 'Security deposit settled',
                'help' => 'Refund the held deposit or apply it to the invoice before close-out.',
                'passed' => $depositHeld <= 0,
                'blocking' => true,
            ],
            [
                'key' => 'return_signoff',
                'label' => 'Return sign-off',
                'help' => $agreement ? 'Customer return acceptance should be recorded on the agreement.' : 'No agreement is linked to this direct rental.',
                'passed' => ! $agreement || ($agreement->status === 'returned' && $agreement->customer_accepted_return),
                'blocking' => $agreement !== null,
            ],
            [
                'key' => 'return_inspection',
                'label' => 'Return inspection',
                'help' => $agreement ? 'Record return inspection results for each rental asset.' : 'No agreement inspection record is linked to this direct rental.',
                'passed' => $returnInspectionsComplete,
                'blocking' => $returnInspectionsRequired,
            ],
        ];

        $blockingItems = collect($items)
            ->filter(fn (array $item): bool => $item['blocking'] && ! $item['passed'])
            ->values()
            ->all();

        return [
            'items' => $items,
            'canClose' => $blockingItems === [],
            'blockingItems' => $blockingItems,
            'passedCount' => collect($items)->where('passed', true)->count(),
            'totalCount' => count($items),
        ];
    }

    private function statusLabel(?string $status): string
    {
        return $this->statuses[$status] ?? str($status ?: 'unknown')->headline()->toString();
    }

    private function itemStatusForRental(string $status): string
    {
        return match ($status) {
            'active' => 'on_rent',
            'returned', 'closed' => 'returned',
            'cancelled' => 'cancelled',
            default => 'reserved',
        };
    }
}
