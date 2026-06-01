<?php

namespace App\Services;

use App\Models\MaintenanceLog;
use App\Models\Product;
use App\Models\RentalItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EquipmentAvailabilityService
{
    /**
     * @return Collection<int, array{type: string, label: string, start: string|null, end: string|null, severity: string}>
     */
    public function conflicts(Product $product, string|CarbonInterface $startsAt, string|CarbonInterface $endsAt, ?int $ignoreRentalId = null): Collection
    {
        $start = $this->date($startsAt);
        $end = $this->date($endsAt);

        return collect()
            ->merge($this->statusConflicts($product))
            ->merge($this->rentalConflicts($product, $start, $end, $ignoreRentalId))
            ->merge($this->maintenanceConflicts($product, $start, $end))
            ->values();
    }

    public function isAvailable(Product $product, string|CarbonInterface $startsAt, string|CarbonInterface $endsAt, ?int $ignoreRentalId = null): bool
    {
        return $this->conflicts($product, $startsAt, $endsAt, $ignoreRentalId)->isEmpty();
    }

    /**
     * @return Collection<int, array{product: Product, conflicts: Collection<int, array{type: string, label: string, start: string|null, end: string|null, severity: string}>}>
     */
    public function matrix(Collection $products, string|CarbonInterface $startsAt, string|CarbonInterface $endsAt): Collection
    {
        return $products->map(fn (Product $product): array => [
            'product' => $product,
            'conflicts' => $this->conflicts($product, $startsAt, $endsAt),
        ]);
    }

    /**
     * @return Collection<int, array{type: string, label: string, start: string|null, end: string|null, severity: string}>
     */
    private function statusConflicts(Product $product): Collection
    {
        if (! in_array($product->status, ['reserved', 'on_rent', 'maintenance', 'damaged', 'retired', 'lost'], true)) {
            return collect();
        }

        return collect([[
            'type' => 'status',
            'label' => 'Equipment status is '.str($product->status)->headline(),
            'start' => null,
            'end' => null,
            'severity' => 'danger',
        ]]);
    }

    /**
     * @return Collection<int, array{type: string, label: string, start: string|null, end: string|null, severity: string}>
     */
    private function rentalConflicts(Product $product, CarbonInterface $start, CarbonInterface $end, ?int $ignoreRentalId): Collection
    {
        return RentalItem::with('rental.customer')
            ->where('product_id', $product->id)
            ->when($ignoreRentalId, fn ($query) => $query->where('rental_id', '!=', $ignoreRentalId))
            ->whereHas('rental', fn ($query) => $query->whereNotIn('status', ['cancelled', 'completed', 'closed']))
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get()
            ->map(fn (RentalItem $item): array => [
                'type' => 'rental',
                'label' => 'Booked by '.($item->rental?->customer?->company_name ?: 'customer').' (RTN-'.$item->rental_id.')',
                'start' => $item->start_date,
                'end' => $item->end_date,
                'severity' => 'danger',
            ]);
    }

    /**
     * @return Collection<int, array{type: string, label: string, start: string|null, end: string|null, severity: string}>
     */
    private function maintenanceConflicts(Product $product, CarbonInterface $start, CarbonInterface $end): Collection
    {
        return MaintenanceLog::where('product_id', $product->id)
            ->where('affects_availability', true)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->where(function ($query) use ($start, $end): void {
                $query->whereBetween('scheduled_at', [$start->toDateString(), $end->toDateString()])
                    ->orWhereBetween('service_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhereBetween('completed_at', [$start->toDateString(), $end->toDateString()]);
            })
            ->get()
            ->map(fn (MaintenanceLog $log): array => [
                'type' => 'maintenance',
                'label' => ($log->title ?: str($log->type)->headline()).' is '.str($log->status)->headline(),
                'start' => $log->scheduled_at?->toDateString() ?: $log->service_date,
                'end' => $log->completed_at?->toDateString() ?: $log->next_service_due,
                'severity' => 'warning',
            ]);
    }

    private function date(string|CarbonInterface $date): CarbonInterface
    {
        return $date instanceof CarbonInterface ? $date : Carbon::parse($date);
    }
}
