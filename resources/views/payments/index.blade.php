@extends('layouts.app')

@section('title', 'Payments')

@section('content')
    @php($money = app(\App\Support\Money::class))
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Collections</span>
                <h1>Payments</h1>
                <p>Review every payment received across invoices, customers, methods, and rental jobs.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Collected</span>
                    <h2 class="mb-0">{{ $money->format($summary['total']) }}</h2>
                    <p class="text-muted mb-0">Total receipts</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Receipts</span>
                    <h2 class="mb-0">{{ $summary['count'] }}</h2>
                    <p class="text-muted mb-0">Payment entries</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Cash</span>
                    <h2 class="mb-0">{{ $money->format($summary['cash']) }}</h2>
                    <p class="text-muted mb-0">Cash collected</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Bank</span>
                    <h2 class="mb-0">{{ $money->format($summary['bank']) }}</h2>
                    <p class="text-muted mb-0">Transfers received</p>
                </section>
            </div>
        </div>

        <section class="panel">
            <div class="table-responsive">
                <table class="table modern-table align-middle">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Rental</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Amount</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date?->format('Y-m-d') }}</td>
                            <td>
                                @if($payment->invoice)
                                    <a href="{{ route('invoices.show', $payment->invoice) }}">{{ $payment->invoice->invoice_number }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $payment->invoice?->customer?->company_name ?: 'Unknown customer' }}</td>
                            <td>
                                @if($payment->invoice?->rental)
                                    <a href="{{ route('rentals.show', $payment->invoice->rental) }}">RTN-{{ $payment->invoice->rental->id }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td><span class="badge badge-soft-secondary">{{ str($payment->method)->headline() }}</span></td>
                            <td>{{ $payment->reference ?: '-' }}</td>
                            <td>{{ $money->format($payment->amount, $payment->invoice?->currency) }}</td>
                            <td>
                                <div class="table-actions justify-content-end">
                                @if($payment->invoice)
                                    <a href="{{ route('invoices.show', $payment->invoice) }}" class="btn btn-sm btn-outline-secondary">
                                        <x-lucide-file-text class="w-4 h-4"/>
                                        Invoice
                                    </a>
                                @endif
                                <a href="{{ route('payments.receipt.print', $payment) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                                    <x-lucide-printer class="w-4 h-4"/>
                                    Print
                                </a>
                                <a href="{{ route('payments.receipt.download', $payment) }}" class="btn btn-sm btn-primary">
                                    <x-lucide-file-down class="w-4 h-4"/>
                                    PDF
                                </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No payments recorded yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
