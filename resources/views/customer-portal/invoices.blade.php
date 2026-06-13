@extends('layouts.customer-portal')

@section('title', 'Invoices')

@section('content')
    @php($money = app(\App\Support\Money::class))
    <div class="page-header"><div><span class="eyebrow">Portal</span><h1>Invoices</h1><p>View invoice totals, balances, and payment history.</p></div></div>
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    <section class="panel">
        <div class="table-responsive">
            <table class="table modern-table align-middle">
                <thead><tr><th>Invoice</th><th>Due</th><th>Status</th><th>Total</th><th>Balance</th><th></th></tr></thead>
                <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td><strong>{{ $invoice->invoice_number }}</strong></td>
                        <td>{{ $invoice->due_date?->format('Y-m-d') ?: 'Not set' }}</td>
                        <td>{{ str($invoice->status)->headline() }}</td>
                        <td>{{ $money->format($invoice->total_amount, $invoice->currency) }}</td>
                        <td>{{ $money->format($invoice->balance_due, $invoice->currency) }}</td>
                        <td class="text-end">
                            @if($invoice->balance_due > 0)
                                <form method="POST" action="{{ route('customer-portal.invoices.pay', $invoice) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <x-lucide-credit-card class="w-4 h-4"/>
                                        Pay
                                    </button>
                                </form>
                            @else
                                <span class="badge badge-soft-success">Paid</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No invoices available.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
