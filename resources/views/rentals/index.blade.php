@extends('layouts.app')

@section('title', 'Rentals')

@section('content')
    @php($money = app(\App\Support\Money::class))
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Rental operations</span>
                <h1>Rentals</h1>
                <p>Manage reservations, active check-outs, returns, and closed rental jobs from one operational list.</p>
            </div>

            <x-button :link="route('rentals.create')" color="dark">
                <x-lucide-plus class="w-4 h-4"/>
                <span class="d-none d-sm-inline-block">New Rental</span>
            </x-button>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Total</span>
                    <h2 class="mb-0">{{ $summary['total'] }}</h2>
                    <p class="text-muted mb-0">Rental jobs</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Reserved</span>
                    <h2 class="mb-0">{{ $summary['reserved'] }}</h2>
                    <p class="text-muted mb-0">Waiting for check-out</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Active</span>
                    <h2 class="mb-0">{{ $summary['active'] }}</h2>
                    <p class="text-muted mb-0">Currently on rent</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Overdue</span>
                    <h2 class="mb-0">{{ $summary['overdue'] }}</h2>
                    <p class="text-muted mb-0">Need follow-up</p>
                </section>
            </div>
        </div>

        <section class="panel">
            <div class="list-toolbar">
                <div>
                    <h2 class="mb-1">Rental Register</h2>
                    <p class="text-muted mb-0">Filter reservations, active rentals, returns, and closed jobs.</p>
                </div>
                <form method="GET" action="{{ route('rentals.index') }}">
                    <div>
                        <label for="search" class="form-label">Search</label>
                        <input id="search" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="RTN, customer, location">
                    </div>
                    <div>
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="all" @selected($filters['status'] === 'all')>All statuses</option>
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="sort" class="form-label">Sort</label>
                        <select id="sort" name="sort" class="form-select">
                            <option value="rental_start_date" @selected($filters['sort'] === 'rental_start_date')>Start date</option>
                            <option value="rental_end_date" @selected($filters['sort'] === 'rental_end_date')>End date</option>
                            <option value="customer" @selected($filters['sort'] === 'customer')>Customer</option>
                            <option value="status" @selected($filters['sort'] === 'status')>Status</option>
                            <option value="amount" @selected($filters['sort'] === 'amount')>Amount</option>
                            <option value="created_at" @selected($filters['sort'] === 'created_at')>Created date</option>
                        </select>
                    </div>
                    <div>
                        <label for="direction" class="form-label">Order</label>
                        <select id="direction" name="direction" class="form-select">
                            <option value="desc" @selected($filters['direction'] === 'desc')>Newest / High first</option>
                            <option value="asc" @selected($filters['direction'] === 'asc')>Oldest / Low first</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-outline-secondary">
                        <x-lucide-search class="w-4 h-4"/>
                        Apply
                    </button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table modern-table align-middle">
                    <thead>
                    <tr>
                        <th>Rental</th>
                        <th>Customer</th>
                        <th>Period</th>
                        <th>Equipment</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($rentals as $rental)
                        @php($subtotal = (float) $rental->rental_items_total_price)
                        <tr>
                            <td>
                                <strong>RTN-{{ $rental->id }}</strong>
                                <div class="text-muted text-xs">
                                    {{ $rental->quote ? 'From '.$rental->quote->quote_number : 'Direct rental' }}
                                </div>
                            </td>
                            <td>{{ $rental->customer?->company_name ?: 'Unknown customer' }}</td>
                            <td>{{ $rental->rental_start_date?->format('Y-m-d') ?: '-' }} - {{ $rental->rental_end_date?->format('Y-m-d') ?: '-' }}</td>
                            <td>
                                <strong>{{ $rental->rental_items_count }}</strong>
                                <span class="text-muted">items</span>
                            </td>
                            <td>
                                <span class="badge badge-soft-secondary">{{ $statuses[$rental->status] ?? str($rental->status)->headline() }}</span>
                            </td>
                            <td>{{ $money->format($subtotal) }}</td>
                            <td>
                                <div class="table-actions justify-content-end">
                                <a href="{{ route('rentals.show', $rental) }}" class="btn btn-sm btn-outline-secondary">
                                    <x-lucide-eye class="w-4 h-4"/>
                                    View
                                </a>
                                @if(! in_array($rental->status, ['closed', 'cancelled'], true))
                                    <a href="{{ route('rentals.edit', $rental) }}" class="btn btn-sm btn-primary">
                                        <x-lucide-pencil class="w-4 h-4"/>
                                        Edit
                                    </a>
                                @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No rentals yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <x-pagination :paginator="$rentals"/>
        </section>
    </div>
@endsection
