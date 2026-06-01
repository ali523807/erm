<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Rental;
use App\Services\EquipmentAvailabilityService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuotesController extends Controller
{
    /**
     * @var array<string, string>
     */
    private array $statuses = [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'accepted' => 'Accepted',
        'declined' => 'Declined',
        'expired' => 'Expired',
        'converted' => 'Converted',
    ];

    public function index(): View
    {
        return view('quotes.index', [
            'quotes' => Quote::with(['customer', 'items.product', 'rental'])
                ->latest()
                ->get(),
            'statuses' => $this->statuses,
        ]);
    }

    public function create(): View
    {
        return view('quotes.create', $this->formData(new Quote([
            'quote_date' => now(),
            'valid_until' => now()->addDays(14),
            'rental_start_date' => now(),
            'rental_end_date' => now()->addDay(),
            'status' => 'draft',
        ])));
    }

    public function store(Request $request, EquipmentAvailabilityService $availability): RedirectResponse
    {
        $validated = $this->validatedData($request);
        $items = $this->normalizedItems($validated['items']);
        $this->validateAvailability($items, $availability);
        $totals = $this->totals($items, (float) ($validated['discount_amount'] ?? 0), (float) ($validated['tax_amount'] ?? 0));

        $quote = DB::transaction(function () use ($validated, $items, $totals): Quote {
            $quote = Quote::create([
                ...collect($validated)->except('items')->all(),
                ...$totals,
                'quote_number' => $this->nextQuoteNumber(),
            ]);

            foreach ($items as $item) {
                $quote->items()->create($item);
            }

            return $quote;
        });

        return redirect()
            ->route('quotes.show', $quote)
            ->with('success', 'Quote created successfully.');
    }

    public function show(Quote $quote): View
    {
        $quote->load(['customer', 'items.product', 'rental']);

        return view('quotes.show', [
            'quote' => $quote,
            'statuses' => $this->statuses,
        ]);
    }

    public function edit(Quote $quote): View
    {
        abort_if($quote->status === 'converted', 422, 'Converted quotes cannot be edited.');

        $quote->load('items');

        return view('quotes.edit', $this->formData($quote));
    }

    public function update(Request $request, Quote $quote, EquipmentAvailabilityService $availability): RedirectResponse
    {
        abort_if($quote->status === 'converted', 422, 'Converted quotes cannot be edited.');

        $validated = $this->validatedData($request, $quote);
        $items = $this->normalizedItems($validated['items']);
        $this->validateAvailability($items, $availability);
        $totals = $this->totals($items, (float) ($validated['discount_amount'] ?? 0), (float) ($validated['tax_amount'] ?? 0));

        DB::transaction(function () use ($quote, $validated, $items, $totals): void {
            $quote->update([
                ...collect($validated)->except('items')->all(),
                ...$totals,
            ]);

            $quote->items()->delete();

            foreach ($items as $item) {
                $quote->items()->create($item);
            }
        });

        return redirect()
            ->route('quotes.show', $quote)
            ->with('success', 'Quote updated successfully.');
    }

    public function updateStatus(Request $request, Quote $quote): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys($this->statusUpdateOptions()))],
        ]);

        abort_if($quote->status === 'converted', 422, 'Converted quotes cannot be changed.');

        $quote->update($validated);

        return redirect()
            ->route('quotes.show', $quote)
            ->with('success', 'Quote status updated successfully.');
    }

    public function convert(Quote $quote, EquipmentAvailabilityService $availability): RedirectResponse
    {
        abort_if($quote->status === 'converted' && $quote->rental_id, 422, 'Quote already converted.');

        $quote->load(['items.product']);
        $this->validateAvailability($quote->items->map(fn ($item): array => [
            'product_id' => $item->product_id,
            'start_date' => $item->start_date->toDateString(),
            'end_date' => $item->end_date->toDateString(),
        ])->all(), $availability);

        $rental = DB::transaction(function () use ($quote): Rental {
            $rental = Rental::create([
                'company_id' => $quote->company_id,
                'customer_id' => $quote->customer_id,
                'rental_start_date' => $quote->rental_start_date->toDateString(),
                'rental_end_date' => $quote->rental_end_date->toDateString(),
                'delivery_location' => $quote->delivery_location,
                'delivery_date' => $quote->rental_start_date->toDateString(),
                'pickup_date' => $quote->rental_end_date->toDateString(),
                'status' => 'reserved',
                'notes' => "Converted from quote {$quote->quote_number}.\n\n".$quote->notes,
            ]);

            foreach ($quote->items as $item) {
                $rental->rentalItems()->create([
                    'company_id' => $quote->company_id,
                    'product_id' => $item->product_id,
                    'start_date' => $item->start_date->toDateString(),
                    'end_date' => $item->end_date->toDateString(),
                    'duration_type' => $item->duration_type,
                    'no_of_duration' => $item->no_of_duration,
                    'rate_type' => $item->duration_type,
                    'rate' => $item->rate,
                    'deposit_amount' => $item->deposit_amount,
                    'total_days' => $item->no_of_duration,
                    'total_price' => $item->line_total,
                    'status' => 'reserved',
                ]);
            }

            $quote->update([
                'status' => 'converted',
                'rental_id' => $rental->id,
            ]);

            return $rental;
        });

        return redirect()
            ->route('rentals.show', $rental)
            ->with('success', "Quote {$quote->quote_number} converted to rental RTN-{$rental->id}.");
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        abort_if($quote->status === 'converted', 422, 'Converted quotes cannot be deleted.');

        $quote->delete();

        return redirect()
            ->route('quotes.index')
            ->with('success', 'Quote deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Quote $quote): array
    {
        return [
            'quote' => $quote,
            'customers' => Customer::orderBy('company_name')->get(),
            'products' => Product::with('category')->orderBy('name')->get(),
            'statuses' => $this->statuses,
            'items' => old('items', $quote->exists ? $quote->items->map(fn ($item): array => [
                'product_id' => $item->product_id,
                'start_date' => $item->start_date?->format('Y-m-d'),
                'end_date' => $item->end_date?->format('Y-m-d'),
                'duration_type' => $item->duration_type,
                'quantity' => $item->quantity,
                'no_of_duration' => $item->no_of_duration,
                'rate' => $item->rate,
                'deposit_amount' => $item->deposit_amount,
                'notes' => $item->notes,
            ])->values()->all() : [[
                'product_id' => '',
                'start_date' => $quote->rental_start_date?->format('Y-m-d') ?? now()->toDateString(),
                'end_date' => $quote->rental_end_date?->format('Y-m-d') ?? now()->addDay()->toDateString(),
                'duration_type' => 'days',
                'quantity' => 1,
                'no_of_duration' => 1,
                'rate' => 0,
                'deposit_amount' => 0,
                'notes' => '',
            ]]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Quote $quote = null): array
    {
        $companyId = auth()->user()->current_company_id;

        return $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'quote_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:quote_date'],
            'rental_start_date' => ['required', 'date'],
            'rental_end_date' => ['required', 'date', 'after_or_equal:rental_start_date'],
            'delivery_location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys($this->statuses))],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'terms' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'items.*.start_date' => ['required', 'date'],
            'items.*.end_date' => ['required', 'date', 'after_or_equal:items.*.start_date'],
            'items.*.duration_type' => ['required', Rule::in(['days', 'weeks', 'months'])],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.no_of_duration' => ['required', 'numeric', 'min:0.01'],
            'items.*.rate' => ['required', 'numeric', 'min:0'],
            'items.*.deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
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
                $quantity = (float) $item['quantity'];
                $duration = (float) $item['no_of_duration'];
                $rate = (float) $item['rate'];

                return [
                    'product_id' => $item['product_id'],
                    'start_date' => $item['start_date'],
                    'end_date' => $item['end_date'],
                    'duration_type' => $item['duration_type'],
                    'quantity' => $quantity,
                    'no_of_duration' => $duration,
                    'rate' => $rate,
                    'deposit_amount' => $item['deposit_amount'] ?? 0,
                    'line_total' => $quantity * $duration * $rate,
                    'notes' => $item['notes'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{subtotal: float, discount_amount: float, tax_amount: float, total_amount: float}
     */
    private function totals(array $items, float $discount, float $tax): array
    {
        $subtotal = collect($items)->sum('line_total');

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'total_amount' => max(0, $subtotal - $discount + $tax),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     *
     * @throws ValidationException
     */
    private function validateAvailability(array $items, EquipmentAvailabilityService $availability): void
    {
        $errors = [];

        foreach ($items as $index => $item) {
            $product = Product::find((int) $item['product_id']);

            if (! $product) {
                continue;
            }

            $conflicts = $availability->conflicts($product, $item['start_date'], $item['end_date']);

            if ($conflicts->isNotEmpty()) {
                $errors["items.{$index}.product_id"] = $product->name.' is not available: '.$conflicts->pluck('label')->join('; ');
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function nextQuoteNumber(): string
    {
        $companyId = auth()->user()->current_company_id;
        $next = Quote::withoutGlobalScopes()->where('company_id', $companyId)->count() + 1;

        return 'QTE-'.now()->format('Y').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, string>
     */
    private function statusUpdateOptions(): array
    {
        return collect($this->statuses)
            ->except('converted')
            ->all();
    }
}
