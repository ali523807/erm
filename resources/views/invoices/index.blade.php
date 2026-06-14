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
            <div class="list-toolbar">
                <div>
                    <h2 class="mb-1">Invoice Register</h2>
                    <p class="text-muted mb-0">Find invoices by customer, invoice number, rental, amount, or collection status.</p>
                </div>
                <form method="GET" action="{{ route('invoices.index') }}">
                    <div>
                        <label for="search" class="form-label">Search</label>
                        <input id="search" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Invoice, RTN, customer">
                    </div>
                    <div>
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="all" @selected($filters['status'] === 'all')>All statuses</option>
                            <option value="issued" @selected($filters['status'] === 'issued')>Issued</option>
                            <option value="partial" @selected($filters['status'] === 'partial')>Partial</option>
                            <option value="paid" @selected($filters['status'] === 'paid')>Paid</option>
                            <option value="overdue" @selected($filters['status'] === 'overdue')>Overdue</option>
                        </select>
                    </div>
                    <div>
                        <label for="sort" class="form-label">Sort</label>
                        <select id="sort" name="sort" class="form-select">
                            <option value="invoice_date" @selected($filters['sort'] === 'invoice_date')>Invoice date</option>
                            <option value="due_date" @selected($filters['sort'] === 'due_date')>Due date</option>
                            <option value="customer" @selected($filters['sort'] === 'customer')>Customer</option>
                            <option value="status" @selected($filters['sort'] === 'status')>Status</option>
                            <option value="total_amount" @selected($filters['sort'] === 'total_amount')>Total</option>
                            <option value="balance_due" @selected($filters['sort'] === 'balance_due')>Balance</option>
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

            <x-pagination :paginator="$invoices"/>
        </section>
    </div>
@endsection
