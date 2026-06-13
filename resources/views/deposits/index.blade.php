@extends('layouts.app')

@section('title', 'Security Deposits')

@section('content')
    @php($money = app(\App\Support\Money::class))
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Finance</span>
                <h1>Security Deposits</h1>
                <p>Monitor deposit collections, held balances, invoice applications, and customer refunds across rental jobs.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <section class="metric-card soft-green">
                    <span>Collected</span>
                    <strong>{{ $money->format($summary['collected']) }}</strong>
                    <small>Total deposit receipts</small>
                </section>
            </div>
            <div class="col-md-3">
                <section class="metric-card soft-amber">
                    <span>Held</span>
                    <strong>{{ $money->format($summary['held']) }}</strong>
                    <small>Liability still on hand</small>
                </section>
            </div>
            <div class="col-md-3">
                <section class="metric-card soft-blue">
                    <span>Applied</span>
                    <strong>{{ $money->format($summary['applied']) }}</strong>
                    <small>Moved to invoice payments</small>
                </section>
            </div>
            <div class="col-md-3">
                <section class="metric-card soft-rose">
                    <span>Refunded</span>
                    <strong>{{ $money->format($summary['refunded']) }}</strong>
                    <small>Returned to customers</small>
                </section>
            </div>
        </div>

        <section class="panel">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Deposit Ledger</h2>
                    <p>Every deposit movement is listed in date order with customer, rental, invoice, and reference details.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table modern-table align-middle">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Customer</th>
                        <th>Rental</th>
                        <th>Invoice</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Recorded By</th>
                        <th class="text-end">Amount</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_date?->format('Y-m-d') }}</td>
                            <td><span class="badge badge-soft-secondary">{{ str($transaction->type)->headline() }}</span></td>
                            <td>{{ $transaction->customer?->company_name ?: $transaction->rental?->customer?->company_name ?: 'Unknown customer' }}</td>
                            <td>
                                @if($transaction->rental)
                                    <a href="{{ route('rentals.show', $transaction->rental) }}">RTN-{{ $transaction->rental->id }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($transaction->invoice)
                                    <a href="{{ route('invoices.show', $transaction->invoice) }}">{{ $transaction->invoice->invoice_number }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $transaction->payment_method ? str($transaction->payment_method)->headline() : '-' }}</td>
                            <td>{{ $transaction->reference ?: '-' }}</td>
                            <td>{{ $transaction->creator?->name ?: '-' }}</td>
                            <td class="text-end">{{ $money->format($transaction->amount) }}</td>
                        </tr>
                        @if($transaction->notes)
                            <tr>
                                <td></td>
                                <td colspan="8" class="text-muted text-sm">{{ $transaction->notes }}</td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No security deposits recorded yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
