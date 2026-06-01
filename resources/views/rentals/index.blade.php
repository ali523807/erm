@extends('layouts.app')

@section('title', 'Rentals')

@section('content')
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
                        @php($subtotal = (float) $rental->rentalItems->sum('total_price'))
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
                                <strong>{{ $rental->rentalItems->count() }}</strong>
                                <span class="text-muted">items</span>
                            </td>
                            <td>
                                <span class="badge badge-soft-secondary">{{ $statuses[$rental->status] ?? str($rental->status)->headline() }}</span>
                            </td>
                            <td>{{ number_format($subtotal, 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('rentals.show', $rental) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                @if(! in_array($rental->status, ['closed', 'cancelled'], true))
                                    <a href="{{ route('rentals.edit', $rental) }}" class="btn btn-sm btn-primary">Edit</a>
                                @endif
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
        </section>
    </div>
@endsection
