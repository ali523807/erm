@extends('layouts.app')

@section('title', 'Invoices')

@section('content')
    @php($money = app(\App\Support\Money::class))
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Billing</span>
                <h1>Invoices</h1>
                <p>Track rental invoices, customer balances, overdue bills, and payment progress.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Invoiced</span>
                    <h2 class="mb-0">{{ $money->format($summary['total']) }}</h2>
                    <p class="text-muted mb-0">Total billed</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Collected</span>
                    <h2 class="mb-0">{{ $money->format($summary['paid']) }}</h2>
                    <p class="text-muted mb-0">Payments received</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Outstanding</span>
                    <h2 class="mb-0">{{ $money->format($summary['balance']) }}</h2>
                    <p class="text-muted mb-0">Balance due</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Overdue</span>
                    <h2 class="mb-0">{{ $summary['overdue'] }}</h2>
                    <p class="text-muted mb-0">Need collection</p>
                </section>
            </div>
        </div>

        <section class="panel">
            <div class="table-responsive">
                <table class="table modern-table align-middle">
                    <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Rental</th>
                        <th>Due</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Balance</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>
                                <strong>{{ $invoice->invoice_number }}</strong>
                                <div class="text-muted text-xs">{{ $invoice->invoice_date?->format('Y-m-d') }}</div>
                            </td>
                            <td>{{ $invoice->customer?->company_name ?: 'Unknown customer' }}</td>
                            <td>
                                @if($invoice->rental)
                                    <a href="{{ route('rentals.show', $invoice->rental) }}">RTN-{{ $invoice->rental->id }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $invoice->due_date?->format('Y-m-d') ?: '-' }}</td>
                            <td><span class="badge badge-soft-secondary">{{ str($invoice->status)->headline() }}</span></td>
                            <td>{{ $money->format($invoice->total_amount, $invoice->currency) }}</td>
                            <td>{{ $money->format($invoice->balance_due, $invoice->currency) }}</td>
                            <td>
                                <div class="table-actions justify-content-end">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-outline-secondary">
                                        <x-lucide-eye class="w-4 h-4"/>
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No invoices yet. Open a rental and generate the first invoice.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
