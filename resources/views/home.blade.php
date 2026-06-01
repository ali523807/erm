@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="container-fluid erm-page">
        <div class="page-header">
            <div>
                <span class="eyebrow">Operations Dashboard</span>
                <h1>{{ auth()->user()->currentCompany?->name ?? 'Equipment Rental Management' }}</h1>
                <p>Live tenant statistics for rentals, billing, fleet availability, returns, and maintenance.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('rentals.create') }}" class="btn btn-dark">
                    <x-lucide-file-plus-2 class="w-4 h-4 me-1"/>
                    New Rental
                </a>
                <a href="{{ route('quotes.create') }}" class="btn btn-outline-secondary">
                    <x-lucide-file-signature class="w-4 h-4 me-1"/>
                    New Quote
                </a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
                <section class="metric-card">
                    <div class="metric-icon bg-green-50 text-green-700">
                        <x-lucide-receipt class="w-5 h-5"/>
                    </div>
                    <span>This Month Invoiced</span>
                    <strong>{{ number_format($summary['monthRevenue'], 2) }}</strong>
                    <small>{{ number_format($summary['monthCollected'], 2) }} collected</small>
                </section>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <section class="metric-card">
                    <div class="metric-icon bg-red-50 text-red-700">
                        <x-lucide-circle-dollar-sign class="w-5 h-5"/>
                    </div>
                    <span>Outstanding Balance</span>
                    <strong>{{ number_format($summary['outstandingBalance'], 2) }}</strong>
                    <small>Open invoice balance</small>
                </section>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <section class="metric-card">
                    <div class="metric-icon bg-blue-50 text-blue-700">
                        <x-lucide-file-box class="w-5 h-5"/>
                    </div>
                    <span>Active Rentals</span>
                    <strong>{{ number_format($summary['activeRentals']) }}</strong>
                    <small>{{ number_format($summary['dueReturns']) }} due within 7 days</small>
                </section>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <section class="metric-card">
                    <div class="metric-icon bg-yellow-50 text-yellow-700">
                        <x-lucide-wrench class="w-5 h-5"/>
                    </div>
                    <span>Pending Maintenance</span>
                    <strong>{{ number_format($summary['pendingMaintenance']) }}</strong>
                    <small>{{ number_format($fleet['maintenance']) }} equipment in maintenance</small>
                </section>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-8">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Fleet Availability</h2>
                            <p>Current equipment status and utilization across this tenant.</p>
                        </div>
                        <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary">View Fleet</a>
                    </div>

                    <div class="fleet-meter mb-3">
                        <div class="fleet-meter-bar" style="width: {{ $fleet['utilizationRate'] }}%"></div>
                    </div>

                    <div class="dashboard-stat-grid">
                        <div>
                            <span>Total Equipment</span>
                            <strong>{{ number_format($fleet['total']) }}</strong>
                        </div>
                        <div>
                            <span>Available</span>
                            <strong>{{ number_format($fleet['available']) }}</strong>
                        </div>
                        <div>
                            <span>On Rent</span>
                            <strong>{{ number_format($fleet['onRent']) }}</strong>
                        </div>
                        <div>
                            <span>Utilization</span>
                            <strong>{{ $fleet['utilizationRate'] }}%</strong>
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-4">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Workspace Health</h2>
                            <p>Tenant setup and current workload.</p>
                        </div>
                    </div>
                    <div class="health-list">
                        <div>
                            <span>Customers</span>
                            <strong>{{ number_format($summary['customers']) }}</strong>
                        </div>
                        <div>
                            <span>Open Quotes</span>
                            <strong>{{ number_format($summary['openQuotes']) }}</strong>
                        </div>
                        <div>
                            <span>Rental Lines</span>
                            <strong>{{ number_format($rentalItemCount) }}</strong>
                        </div>
                        <div>
                            <span>Subscription</span>
                            <strong>{{ $subscription?->plan?->name ?? 'No Plan' }}</strong>
                            <small>{{ str($subscription?->status ?? 'not configured')->headline() }}</small>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Due Returns</h2>
                            <p>Active rentals ending in the next 7 days.</p>
                        </div>
                        <a href="{{ route('rentals.index') }}" class="btn btn-sm btn-outline-secondary">Rentals</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Rental</th>
                                <th>Customer</th>
                                <th>Due Date</th>
                                <th>Items</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($dueReturns as $rental)
                                <tr>
                                    <td><a href="{{ route('rentals.show', $rental) }}">RTN-{{ $rental->id }}</a></td>
                                    <td>{{ $rental->customer?->company_name ?? 'Walk-in Customer' }}</td>
                                    <td>{{ $rental->rental_end_date?->format('M d, Y') ?? '-' }}</td>
                                    <td>{{ $rental->rentalItems->count() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No returns due this week.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Maintenance Alerts</h2>
                            <p>Scheduled and in-progress service tasks.</p>
                        </div>
                        <a href="{{ route('maintenance.index') }}" class="btn btn-sm btn-outline-secondary">Maintenance</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Equipment</th>
                                <th>Task</th>
                                <th>Scheduled</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($maintenanceAlerts as $alert)
                                <tr>
                                    <td>{{ $alert->product?->name ?? 'Unknown equipment' }}</td>
                                    <td>{{ $alert->title ?? str($alert->type)->headline() }}</td>
                                    <td>{{ $alert->scheduled_at?->format('M d, Y') ?? '-' }}</td>
                                    <td><span class="badge badge-soft-warning">{{ str($alert->status)->headline() }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No maintenance alerts.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-xl-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Recent Rentals</h2>
                            <p>Latest rental activity in this company.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Rental</th>
                                <th>Customer</th>
                                <th>Period</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($recentRentals as $rental)
                                <tr>
                                    <td><a href="{{ route('rentals.show', $rental) }}">RTN-{{ $rental->id }}</a></td>
                                    <td>{{ $rental->customer?->company_name ?? 'Walk-in Customer' }}</td>
                                    <td>{{ $rental->rental_start_date?->format('M d') ?? '-' }} - {{ $rental->rental_end_date?->format('M d') ?? '-' }}</td>
                                    <td><span class="badge badge-soft-info">{{ str($rental->status ?? 'draft')->headline() }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No rentals yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Recent Invoices</h2>
                            <p>Latest billing documents and balances.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Balance</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($recentInvoices as $invoice)
                                <tr>
                                    <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                                    <td>{{ $invoice->customer?->company_name ?? 'Walk-in Customer' }}</td>
                                    <td>{{ number_format((float) $invoice->total_amount, 2) }}</td>
                                    <td>{{ number_format((float) $invoice->balance_due, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No invoices yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
