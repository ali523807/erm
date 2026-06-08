<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\RentalItem;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class DispatchReturnsController extends Controller
{
    public function __construct(private ActivityLogger $activity) {}

    public function __invoke(Request $request): View
    {
        $date = Carbon::parse($request->input('date', now()->toDateString()))->startOfDay();
        $today = now()->startOfDay();
        $windowDays = min(max((int) $request->input('window', 7), 7), 30);
        $windowEnd = $date->copy()->addDays($windowDays - 1);
        $movement = $request->input('movement', 'all');
        $status = $request->input('status', 'all');
        $customerId = $request->integer('customer_id') ?: null;
        $dispatchStatuses = ['reserved'];
        $onRentStatuses = ['active', 'on_rent', 'open'];
        $closedStatuses = ['returned', 'closed', 'cancelled'];

        $baseRentalQuery = Rental::with(['customer', 'agreement', 'rentalItems.product'])
            ->whereNotIn('status', $closedStatuses)
            ->when($customerId, fn ($query) => $query->where('customer_id', $customerId));

        $todayDispatches = (clone $baseRentalQuery)
            ->whereIn('status', $dispatchStatuses)
            ->where(function ($query) use ($date): void {
                $query->whereDate('delivery_date', $date->toDateString())
                    ->orWhere(function ($query) use ($date): void {
                        $query->whereNull('delivery_date')
                            ->whereDate('rental_start_date', $date->toDateString());
                    });
            })
            ->orderBy('delivery_date')
            ->get();

        $upcomingDispatches = (clone $baseRentalQuery)
            ->whereIn('status', $dispatchStatuses)
            ->where(function ($query) use ($date, $windowEnd): void {
                $query->where(function ($query) use ($date, $windowEnd): void {
                    $query->whereDate('delivery_date', '>', $date->toDateString())
                        ->whereDate('delivery_date', '<=', $windowEnd->toDateString());
                })->orWhere(function ($query) use ($date, $windowEnd): void {
                    $query->whereNull('delivery_date')
                        ->whereDate('rental_start_date', '>', $date->toDateString())
                        ->whereDate('rental_start_date', '<=', $windowEnd->toDateString());
                });
            })
            ->orderBy('delivery_date')
            ->limit(10)
            ->get();

        $dueReturns = (clone $baseRentalQuery)
            ->whereIn('status', $onRentStatuses)
            ->where(function ($query) use ($date, $windowEnd): void {
                $query->where(function ($query) use ($date, $windowEnd): void {
                    $query->whereDate('pickup_date', '>=', $date->toDateString())
                        ->whereDate('pickup_date', '<=', $windowEnd->toDateString());
                })->orWhere(function ($query) use ($date, $windowEnd): void {
                    $query->whereNull('pickup_date')
                        ->whereDate('rental_end_date', '>=', $date->toDateString())
                        ->whereDate('rental_end_date', '<=', $windowEnd->toDateString());
                });
            })
            ->orderBy('pickup_date')
            ->get();

        $overdueReturns = (clone $baseRentalQuery)
            ->whereIn('status', $onRentStatuses)
            ->where(function ($query) use ($date): void {
                $query->whereDate('pickup_date', '<', $date->toDateString())
                    ->orWhere(function ($query) use ($date): void {
                        $query->whereNull('pickup_date')
                            ->whereDate('rental_end_date', '<', $date->toDateString());
                    });
            })
            ->orderBy('pickup_date')
            ->get();

        $onRentItems = RentalItem::with(['rental.customer', 'rental.agreement', 'product'])
            ->where(function ($query) use ($onRentStatuses): void {
                $query->whereIn('status', ['on_rent', 'dispatched'])
                    ->orWhereHas('rental', fn ($query) => $query->whereIn('status', $onRentStatuses));
            })
            ->latest()
            ->limit(25)
            ->get();

        $scheduleRentals = $this->scheduleRentalQuery(
            clone $baseRentalQuery,
            $date,
            $windowEnd,
            $movement,
            $status
        )->get();
        $scheduleEvents = $this->scheduleEvents($scheduleRentals, $date, $windowEnd, $movement);
        $calendarDays = $this->calendarDays($date, $windowEnd, $scheduleEvents);

        return view('operations.dispatch-returns.index', [
            'date' => $date,
            'windowDays' => $windowDays,
            'windowEnd' => $windowEnd,
            'filters' => [
                'movement' => $movement,
                'status' => $status,
                'customer_id' => $customerId,
            ],
            'summary' => [
                'todayDispatches' => $todayDispatches->count(),
                'upcomingDispatches' => $upcomingDispatches->count(),
                'dueReturns' => $dueReturns->count(),
                'overdueReturns' => $overdueReturns->count(),
                'onRentItems' => $onRentItems->count(),
            ],
            'todayDispatches' => $todayDispatches,
            'upcomingDispatches' => $upcomingDispatches,
            'dueReturns' => $dueReturns,
            'overdueReturns' => $overdueReturns,
            'onRentItems' => $onRentItems,
            'scheduleEvents' => $scheduleEvents,
            'calendarDays' => $calendarDays,
            'customers' => Rental::with('customer')
                ->select('customer_id')
                ->whereNotNull('customer_id')
                ->distinct()
                ->get()
                ->pluck('customer')
                ->filter()
                ->sortBy('company_name')
                ->values(),
            'isToday' => $date->isSameDay($today),
        ]);
    }

    public function updateMovementStatus(Request $request, Rental $rental): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['dispatch', 'return'])],
        ]);

        $oldStatus = $rental->status;
        $newStatus = $validated['action'] === 'dispatch' ? 'active' : 'returned';
        $itemStatus = $validated['action'] === 'dispatch' ? 'on_rent' : 'returned';

        abort_if($validated['action'] === 'dispatch' && $rental->status !== 'reserved', 422, 'Only reserved rentals can be dispatched.');
        abort_if($validated['action'] === 'return' && ! in_array($rental->status, ['active', 'on_rent', 'open'], true), 422, 'Only active rentals can be returned.');

        $rental->update(['status' => $newStatus]);
        $rental->rentalItems()->update(['status' => $itemStatus]);

        $this->activity->log('dispatch', $validated['action'], ucfirst($validated['action'])." rental RTN-{$rental->id}.", $rental, [
            'status' => [
                'old' => $oldStatus,
                'new' => $newStatus,
            ],
        ]);

        return back()->with('success', $validated['action'] === 'dispatch'
            ? 'Rental marked as dispatched.'
            : 'Rental marked as returned.');
    }

    private function scheduleRentalQuery($query, Carbon $date, Carbon $windowEnd, string $movement, string $status)
    {
        return $query
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->where(function ($query) use ($date, $windowEnd, $movement): void {
                if (in_array($movement, ['all', 'delivery'], true)) {
                    $query->orWhere(function ($query) use ($date, $windowEnd): void {
                        $query->whereBetween('delivery_date', [$date->toDateString(), $windowEnd->toDateString()])
                            ->orWhere(function ($query) use ($date, $windowEnd): void {
                                $query->whereNull('delivery_date')
                                    ->whereBetween('rental_start_date', [$date->toDateString(), $windowEnd->toDateString()]);
                            });
                    });
                }

                if (in_array($movement, ['all', 'pickup'], true)) {
                    $query->orWhere(function ($query) use ($date, $windowEnd): void {
                        $query->whereBetween('pickup_date', [$date->toDateString(), $windowEnd->toDateString()])
                            ->orWhere(function ($query) use ($date, $windowEnd): void {
                                $query->whereNull('pickup_date')
                                    ->whereBetween('rental_end_date', [$date->toDateString(), $windowEnd->toDateString()]);
                            });
                    });
                }
            })
            ->orderBy('delivery_date')
            ->orderBy('pickup_date');
    }

    private function scheduleEvents($rentals, Carbon $date, Carbon $windowEnd, string $movement)
    {
        return $rentals
            ->flatMap(function (Rental $rental) use ($date, $windowEnd, $movement) {
                $events = collect();
                $deliveryDate = $rental->delivery_date ?: $rental->rental_start_date;
                $pickupDate = $rental->pickup_date ?: $rental->rental_end_date;

                if ($deliveryDate && in_array($movement, ['all', 'delivery'], true) && $deliveryDate->betweenIncluded($date, $windowEnd)) {
                    $events->push(['type' => 'delivery', 'date' => $deliveryDate->copy(), 'rental' => $rental]);
                }

                if ($pickupDate && in_array($movement, ['all', 'pickup'], true) && $pickupDate->betweenIncluded($date, $windowEnd)) {
                    $events->push(['type' => 'pickup', 'date' => $pickupDate->copy(), 'rental' => $rental]);
                }

                return $events;
            })
            ->sortBy(fn (array $event): string => $event['date']->format('Y-m-d').$event['type'])
            ->values();
    }

    private function calendarDays(Carbon $date, Carbon $windowEnd, $scheduleEvents)
    {
        return collect(range(0, $date->diffInDays($windowEnd)))
            ->map(function (int $offset) use ($date, $scheduleEvents): array {
                $day = $date->copy()->addDays($offset);
                $dayEvents = $scheduleEvents->filter(fn (array $event): bool => $event['date']->isSameDay($day));

                return [
                    'date' => $day,
                    'deliveries' => $dayEvents->where('type', 'delivery')->count(),
                    'pickups' => $dayEvents->where('type', 'pickup')->count(),
                    'total' => $dayEvents->count(),
                ];
            });
    }
}
