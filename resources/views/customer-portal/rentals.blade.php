@extends('layouts.customer-portal')

@section('title', 'Rentals')

@section('content')
    <div class="page-header"><div><span class="eyebrow">Portal</span><h1>Rentals</h1><p>Track reserved, active, and completed rentals.</p></div></div>
    <section class="panel">
        <div class="table-responsive">
            <table class="table modern-table align-middle">
                <thead><tr><th>Rental</th><th>Period</th><th>Status</th><th>Items</th><th>Delivery</th></tr></thead>
                <tbody>
                @forelse($rentals as $rental)
                    <tr>
                        <td><strong>RTN-{{ $rental->id }}</strong></td>
                        <td>{{ $rental->rental_start_date?->format('Y-m-d') }} - {{ $rental->rental_end_date?->format('Y-m-d') }}</td>
                        <td>{{ str($rental->status)->headline() }}</td>
                        <td>{{ $rental->rentalItems->pluck('product.name')->filter()->join(', ') ?: $rental->rentalItems->count().' item(s)' }}</td>
                        <td>{{ $rental->delivery_location ?: 'Not recorded' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No rentals available.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
