@extends('layouts.customer-portal')

@section('title', 'Invoices')

@section('content')
    <div class="page-header"><div><span class="eyebrow">Portal</span><h1>Invoices</h1><p>View invoice totals, balances, and payment history.</p></div></div>
    <section class="panel">
        <div class="table-responsive">
            <table class="table modern-table align-middle">
                <thead><tr><th>Invoice</th><th>Due</th><th>Status</th><th>Total</th><th>Balance</th></tr></thead>
                <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td><strong>{{ $invoice->invoice_number }}</strong></td>
                        <td>{{ $invoice->due_date?->format('Y-m-d') ?: 'Not set' }}</td>
                        <td>{{ str($invoice->status)->headline() }}</td>
                        <td>{{ number_format((float) $invoice->total_amount, 2) }}</td>
                        <td>{{ number_format((float) $invoice->balance_due, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No invoices available.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
