@extends('layouts.app')

@section('title', 'Delivery & Pickup Schedule')

@php
    $displayDate = fn ($value): string => $value ? $value->format('M d, Y') : '-';
    $movementDate = fn ($rental, string $primary, string $fallback): string => $displayDate($rental->{$primary} ?: $rental->{$fallback});
    $statusBadge = fn (?string $status): string => match ($status) {
        'active', 'on_rent', 'checked_out', 'open' => 'badge-soft-info',
        'reserved', 'draft' => 'badge-soft-primary',
        'returned', 'closed' => 'badge-soft-success',
        'cancelled' => 'badge-soft-secondary',
        default => 'badge-soft-warning',
    };
    $eventBadge = fn (string $type): string => $type === 'delivery' ? 'badge-soft-primary' : 'badge-soft-success';
@endphp

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Operations</span>
                <h1>Delivery & Pickup Schedule</h1>
                <p>Plan equipment movement, dispatch reserved rentals, follow up due returns, and keep the operations desk aligned by work date.</p>
            </div>
            <form method="GET" action="{{ route('dispatch-returns.index') }}" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label for="date" class="form-label mb-1">Work Date</label>
                    <input id="date" name="date" type="date" class="form-control" value="{{ $date->toDateString() }}">
                </div>
                <div>
                    <label for="window" class="form-label mb-1">Window</label>
                    <select id="window" name="window" class="form-select">
                        <option value="7" @selected($windowDays === 7)>7 days</option>
                        <option value="14" @selected($windowDays === 14)>14 days</option>
                        <option value="30" @selected($windowDays === 30)>30 days</option>
                    </select>
                </div>
                <div>
                    <label for="movement" class="form-label mb-1">Movement</label>
                    <select id="movement" name="movement" class="form-select">
                        <option value="all" @selected($filters['movement'] === 'all')>All</option>
                        <option value="delivery" @selected($filters['movement'] === 'delivery')>Deliveries</option>
                        <option value="pickup" @selected($filters['movement'] === 'pickup')>Pickups</option>
                    </select>
                </div>
                <div>
                    <label for="status" class="form-label mb-1">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="all" @selected($filters['status'] === 'all')>All open</option>
                        <option value="reserved" @selected($filters['status'] === 'reserved')>Reserved</option>
                        <option value="active" @selected($filters['status'] === 'active')>Checked out</option>
                        <option value="on_rent" @selected($filters['status'] === 'on_rent')>On rent</option>
                        <option value="open" @selected($filters['status'] === 'open')>Open</option>
                    </select>
                </div>
                <div>
                    <label for="customer_id" class="form-label mb-1">Customer</label>
                    <select id="customer_id" name="customer_id" class="form-select">
                        <option value="">All customers</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((int) $filters['customer_id'] === (int) $customer->id)>
                                {{ $customer->company_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-dark">
                    <x-lucide-search class="w-4 h-4"/>
                    Apply
                </button>
            </form>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Dispatch Today</span>
                    <h2 class="mb-0">{{ $summary['todayDispatches'] }}</h2>
                    <p class="text-muted mb-0">{{ $date->format('M d, Y') }}</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Upcoming</span>
                    <h2 class="mb-0">{{ $summary['upcomingDispatches'] }}</h2>
                    <p class="text-muted mb-0">Through {{ $windowEnd->format('M d') }}</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Due Returns</span>
                    <h2 class="mb-0">{{ $summary['dueReturns'] }}</h2>
                    <p class="text-muted mb-0">Return window</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Overdue Returns</span>
                    <h2 class="mb-0">{{ $summary['overdueReturns'] }}</h2>
                    <p class="text-muted mb-0">{{ $summary['onRentItems'] }} on-rent lines</p>
                </section>
            </div>
        </div>

        <section class="panel mb-3">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Movement Calendar</h2>
                    <p>Delivery and pickup workload from {{ $date->format('M d, Y') }} to {{ $windowEnd->format('M d, Y') }}.</p>
                </div>
            </div>
            <div class="row g-2">
                @foreach($calendarDays as $day)
                    <div class="col-6 col-md-3 col-xl">
                        <div class="border rounded-3 p-3 h-100 {{ $day['date']->isToday() ? 'bg-light' : '' }}">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <div class="text-muted text-xs text-uppercase">{{ $day['date']->format('D') }}</div>
                                    <strong>{{ $day['date']->format('M d') }}</strong>
                                </div>
                                <span class="badge {{ $day['total'] > 0 ? 'badge-soft-primary' : 'badge-soft-secondary' }}">{{ $day['total'] }}</span>
                            </div>
                            <div class="d-flex flex-wrap gap-1 mt-3">
                                <span class="badge badge-soft-primary">{{ $day['deliveries'] }} delivery</span>
                                <span class="badge badge-soft-success">{{ $day['pickups'] }} pickup</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="panel mb-3">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Schedule Queue</h2>
                    <p>Chronological movement list for dispatchers, drivers, and counter teams.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table modern-table align-middle">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Movement</th>
                        <th>Rental</th>
                        <th>Customer</th>
                        <th>Location</th>
                        <th>Equipment</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($scheduleEvents as $event)
                        @php($rental = $event['rental'])
                        <tr>
                            <td>
                                <strong>{{ $event['date']->format('M d, Y') }}</strong>
                                <div class="text-muted text-xs">{{ $event['date']->format('l') }}</div>
                            </td>
                            <td><span class="badge {{ $eventBadge($event['type']) }}">{{ str($event['type'])->headline() }}</span></td>
                            <td>
                                <a href="{{ route('rentals.show', $rental) }}"><strong>RTN-{{ $rental->id }}</strong></a>
                                <div class="text-muted text-xs">{{ $rental->rental_start_date?->format('M d') }} - {{ $rental->rental_end_date?->format('M d') }}</div>
                            </td>
                            <td>{{ $rental->customer?->company_name ?? 'Unknown customer' }}</td>
                            <td>{{ $rental->delivery_location ?: 'No movement location' }}</td>
                            <td>
                                <strong>{{ $rental->rentalItems->count() }}</strong>
                                <span class="text-muted">assets</span>
                                @if($rental->rentalItems->isNotEmpty())
                                    <div class="text-muted text-xs">{{ $rental->rentalItems->pluck('product.name')->filter()->take(2)->join(', ') }}</div>
                                @endif
                            </td>
                            <td><span class="badge {{ $statusBadge($rental->status) }}">{{ str($rental->status ?: 'reserved')->headline() }}</span></td>
                            <td>
                                <div class="table-actions justify-content-end">
                                    @if($event['type'] === 'delivery' && $rental->status === 'reserved')
                                        <form method="POST" action="{{ route('dispatch-returns.status.update', $rental) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="action" value="dispatch">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                                <x-lucide-truck class="w-4 h-4"/>
                                                Dispatch
                                            </button>
                                        </form>
                                    @endif
                                    @if($event['type'] === 'pickup' && in_array($rental->status, ['active', 'on_rent', 'open'], true))
                                        <form method="POST" action="{{ route('dispatch-returns.status.update', $rental) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="action" value="return">
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <x-lucide-undo-2 class="w-4 h-4"/>
                                                Return
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('rentals.show', $rental) }}" class="btn btn-sm btn-outline-secondary">
                                        <x-lucide-eye class="w-4 h-4"/>
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No delivery or pickup movement found for this schedule window.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="row g-3 mb-3">
            <div class="col-xl-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Today's Dispatches</h2>
                            <p>Reserved rentals scheduled to leave on the selected work date.</p>
                        </div>
                    </div>
                    @include('operations.dispatch-returns._rental-table', [
                        'rentals' => $todayDispatches,
                        'dateColumn' => 'Dispatch',
                        'dateResolver' => fn ($rental) => $movementDate($rental, 'delivery_date', 'rental_start_date'),
                        'emptyText' => 'No dispatches scheduled for this date.',
                        'actionMode' => 'dispatch',
                    ])
                </section>
            </div>

            <div class="col-xl-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Upcoming Dispatches</h2>
                            <p>Reserved rentals scheduled in the selected window.</p>
                        </div>
                    </div>
                    @include('operations.dispatch-returns._rental-table', [
                        'rentals' => $upcomingDispatches,
                        'dateColumn' => 'Dispatch',
                        'dateResolver' => fn ($rental) => $movementDate($rental, 'delivery_date', 'rental_start_date'),
                        'emptyText' => 'No upcoming dispatches in this window.',
                        'actionMode' => 'dispatch',
                    ])
                </section>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Due Returns</h2>
                            <p>Active rentals expected back in the selected window.</p>
                        </div>
                    </div>
                    @include('operations.dispatch-returns._rental-table', [
                        'rentals' => $dueReturns,
                        'dateColumn' => 'Return',
                        'dateResolver' => fn ($rental) => $movementDate($rental, 'pickup_date', 'rental_end_date'),
                        'emptyText' => 'No returns due in this window.',
                        'actionMode' => 'return',
                    ])
                </section>
            </div>

            <div class="col-xl-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Overdue Returns</h2>
                            <p>Active rentals whose return date has already passed.</p>
                        </div>
                    </div>
                    @include('operations.dispatch-returns._rental-table', [
                        'rentals' => $overdueReturns,
                        'dateColumn' => 'Expected',
                        'dateResolver' => fn ($rental) => $movementDate($rental, 'pickup_date', 'rental_end_date'),
                        'emptyText' => 'No overdue returns.',
                        'actionMode' => 'return',
                    ])
                </section>
            </div>
        </div>

        <section class="panel">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Equipment Currently On Rent</h2>
                    <p>Item-level visibility for assets that are out with customers.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table modern-table align-middle">
                    <thead>
                    <tr>
                        <th>Equipment</th>
                        <th>Rental</th>
                        <th>Customer</th>
                        <th>Period</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($onRentItems as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->product?->name ?? 'Unknown equipment' }}</strong>
                                <div class="text-muted text-xs">{{ $item->product?->equipment_code ?: 'No asset code' }}</div>
                            </td>
                            <td>
                                @if($item->rental)
                                    <a href="{{ route('rentals.show', $item->rental) }}">RTN-{{ $item->rental->id }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $item->rental?->customer?->company_name ?? 'Unknown customer' }}</td>
                            <td>{{ $item->start_date ?: '-' }} - {{ $item->end_date ?: '-' }}</td>
                            <td><span class="badge {{ $statusBadge($item->status ?: $item->rental?->status) }}">{{ str($item->status ?: $item->rental?->status ?: 'on rent')->headline() }}</span></td>
                            <td>
                                <div class="table-actions justify-content-end">
                                    @if($item->rental?->agreement)
                                        <a href="{{ route('agreements.show', $item->rental->agreement) }}" class="btn btn-sm btn-outline-secondary">
                                            <x-lucide-file-signature class="w-4 h-4"/>
                                            Agreement
                                        </a>
                                    @endif
                                    <a href="{{ route('maintenance.index') }}" class="btn btn-sm btn-outline-primary">
                                        <x-lucide-wrench class="w-4 h-4"/>
                                        Maintenance
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No equipment currently on rent.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
