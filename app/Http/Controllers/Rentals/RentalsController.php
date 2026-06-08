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

    public function index(): View
    {
        $rentals = Rental::with(['customer', 'rentalItems.product', 'quote'])
            ->latest()
            ->get();

        return view('rentals.index', [
            'rentals' => $rentals,
            'statuses' => $this->statuses,
            'summary' => [
                'total' => $rentals->count(),
                'active' => $rentals->whereIn('status', ['active', 'on_rent', 'open'])->count(),
                'reserved' => $rentals->where('status', 'reserved')->count(),
                'overdue' => $rentals
                    ->filter(fn (Rental $rental): bool => ! in_array($rental->status, ['returned', 'closed', 'cancelled'], true)
                        && $rental->rental_end_date
                        && now()->toDateString() > $rental->rental_end_date->toDateString())
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
        $rental->load(['customer', 'quote', 'invoice', 'agreement', 'rentalItems.product.category']);

        return view('rentals.show', [
            'rental' => $rental,
            'statuses' => $this->statuses,
            'totals' => $this->totals($rental),
            'nextStatuses' => $this->nextStatuses($rental->status),
        ]);
    }

    public function edit(Rental $rental): View
    {
        $rental->load('rentalItems');

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
        return [
            'rental' => $rental,
            'customers' => Customer::orderBy('company_name')->get(),
            'products' => Product::with('category')->orderBy('name')->get(),
            'statuses' => $this->statuses,
            'items' => old('items', $rental->exists ? $rental->rentalItems->map(fn ($item): array => [
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
            ]]),
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
