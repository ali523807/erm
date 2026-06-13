@extends('layouts.app')

@section('title', 'Quotes')

@section('content')
    @php($money = app(\App\Support\Money::class))
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Pre-booking</span>
                <h1>Quotes</h1>
                <p>Create customer quotes, validate equipment availability, and convert accepted quotes into reserved rentals.</p>
            </div>

            <x-button :link="route('quotes.create')" color="dark">
                <x-lucide-plus class="w-4 h-4"/>
                <span>New Quote</span>
            </x-button>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <section class="panel">
            <div class="table-responsive">
                <table class="table modern-table align-middle">
                    <thead>
                    <tr>
                        <th>Quote</th>
                        <th>Customer</th>
                        <th>Rental Period</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($quotes as $quote)
                        <tr>
                            <td>
                                <strong>{{ $quote->quote_number }}</strong>
                                <div class="text-muted text-xs">{{ $quote->quote_date?->format('Y-m-d') }} · valid until {{ $quote->valid_until?->format('Y-m-d') ?: 'open' }}</div>
                            </td>
                            <td>{{ $quote->customer?->company_name ?: 'Unknown customer' }}</td>
                            <td>{{ $quote->rental_start_date?->format('Y-m-d') }} - {{ $quote->rental_end_date?->format('Y-m-d') }}</td>
                            <td><span class="badge badge-soft-secondary">{{ $statuses[$quote->status] ?? str($quote->status)->headline() }}</span></td>
                            <td>
                                {{ $money->format($quote->total_amount, $quote->currency) }}
                                <div class="text-muted text-xs">Base {{ $money->format($quote->base_total_amount, $quote->base_currency) }}</div>
                            </td>
                            <td>
                                <div class="table-actions justify-content-end">
                                <a href="{{ route('quotes.show', $quote) }}" class="btn btn-sm btn-outline-secondary">
                                    <x-lucide-eye class="w-4 h-4"/>
                                    View
                                </a>
                                @if($quote->status !== 'converted')
                                    <a href="{{ route('quotes.edit', $quote) }}" class="btn btn-sm btn-primary">
                                        <x-lucide-pencil class="w-4 h-4"/>
                                        Edit
                                    </a>
                                @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No quotes yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
