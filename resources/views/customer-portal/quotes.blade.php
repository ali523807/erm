@extends('layouts.customer-portal')

@section('title', 'Quotes')

@section('content')
    <div class="page-header"><div><span class="eyebrow">Portal</span><h1>Quotes</h1><p>Review and respond to open quotations.</p></div></div>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <section class="panel">
        <div class="table-responsive">
            <table class="table modern-table align-middle">
                <thead><tr><th>Quote</th><th>Dates</th><th>Status</th><th>Total</th><th></th></tr></thead>
                <tbody>
                @forelse($quotes as $quote)
                    <tr>
                        <td><strong>{{ $quote->quote_number }}</strong><span class="d-block text-muted small">{{ $quote->items->count() }} item(s)</span></td>
                        <td>{{ $quote->rental_start_date?->format('Y-m-d') }} - {{ $quote->rental_end_date?->format('Y-m-d') }}</td>
                        <td>{{ str($quote->status)->headline() }}</td>
                        <td>{{ number_format((float) $quote->total_amount, 2) }}</td>
                        <td class="text-end">
                            @if(in_array($quote->status, ['draft', 'sent'], true))
                                <form method="POST" action="{{ route('customer-portal.quotes.status', $quote) }}" class="d-inline">@csrf @method('PATCH')<input type="hidden" name="status" value="accepted"><button class="btn btn-sm btn-soft-primary">Accept</button></form>
                                <form method="POST" action="{{ route('customer-portal.quotes.status', $quote) }}" class="d-inline">@csrf @method('PATCH')<input type="hidden" name="status" value="declined"><button class="btn btn-sm btn-outline-danger">Decline</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No quotes available.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
