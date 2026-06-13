@extends('layouts.app')

@section('title', 'Reports')

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Analytics</span>
                <h1>Reports</h1>
                <p>Review rental revenue, collections, utilization, customer performance, damage, maintenance costs, and operating expenses.</p>
            </div>
        </div>

        <section class="panel mb-3">
            <form method="GET" action="{{ route('reports.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input id="start_date" name="start_date" type="date" class="form-control" value="{{ $startDate->toDateString() }}">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">End Date</label>
                    <input id="end_date" name="end_date" type="date" class="form-control" value="{{ $endDate->toDateString() }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-dark">Apply Filter</button>
                    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </section>

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Invoiced</span>
                    <h2 class="mb-0">{{ number_format($summary['invoiced'], 2) }}</h2>
                    <p class="text-muted mb-0">Within selected range</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Collected</span>
                    <h2 class="mb-0">{{ number_format($summary['collected'], 2) }}</h2>
                    <p class="text-muted mb-0">Payments received</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Outstanding</span>
                    <h2 class="mb-0">{{ number_format($summary['outstanding'], 2) }}</h2>
                    <p class="text-muted mb-0">Open balance all time</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Active Rentals</span>
                    <h2 class="mb-0">{{ $summary['activeRentals'] }}</h2>
                    <p class="text-muted mb-0">{{ $summary['overdueInvoices'] }} overdue invoices</p>
                </section>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <section class="metric-card soft-rose">
                    <span>Operating Expenses</span>
                    <strong>{{ number_format($summary['operatingCost'], 2) }}</strong>
                    <small>General rental operating costs</small>
                </section>
            </div>
            <div class="col-md-4">
                <section class="metric-card soft-amber">
                    <span>Maintenance Cost</span>
                    <strong>{{ number_format($summary['maintenanceCost'], 2) }}</strong>
                    <small>Asset service and repair spend</small>
                </section>
            </div>
            <div class="col-md-4">
                <section class="metric-card soft-green">
                    <span>Net Profit</span>
                    <strong>{{ number_format($summary['netProfit'], 2) }}</strong>
                    <small>{{ number_format($summary['marginPercent'], 2) }}% margin after costs and credits</small>
                </section>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <section class="metric-card soft-blue">
                    <span>Credits</span>
                    <strong>{{ number_format($summary['credits'], 2) }}</strong>
                    <small>Customer credits reducing revenue</small>
                </section>
            </div>
            <div class="col-md-4">
                <section class="metric-card soft-amber">
                    <span>Unrecovered Billable</span>
                    <strong>{{ number_format($summary['unrecoveredBillable'], 2) }}</strong>
                    <small>Billable expenses not fully recovered</small>
                </section>
            </div>
            <div class="col-md-4">
                <section class="metric-card {{ $summary['lossRentals'] > 0 ? 'soft-rose' : 'soft-green' }}">
                    <span>Loss Rentals</span>
                    <strong>{{ $summary['lossRentals'] }}</strong>
                    <small>Rental jobs below zero margin</small>
                </section>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-12">
                <section class="panel">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Rental Profitability</h2>
                            <p>Rental jobs ranked by lowest net margin so risky jobs are visible first.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Rental</th>
                                <th>Customer</th>
                                <th>Revenue</th>
                                <th>Credits</th>
                                <th>Expenses</th>
                                <th>Maintenance</th>
                                <th>Net</th>
                                <th>Margin</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($rentalProfitability as $row)
                                <tr>
                                    <td><a href="{{ route('rentals.show', $row['rental_id']) }}">RTN-{{ $row['rental_id'] }}</a></td>
                                    <td>{{ $row['customer'] }}</td>
                                    <td>{{ number_format($row['revenue'], 2) }}</td>
                                    <td>{{ number_format($row['credits'], 2) }}</td>
                                    <td>{{ number_format($row['expenses'], 2) }}</td>
                                    <td>{{ number_format($row['maintenance'], 2) }}</td>
                                    <td>{{ number_format($row['net'], 2) }}</td>
                                    <td>{{ number_format($row['margin'], 2) }}%</td>
                                    <td>
                                        <span class="badge {{ $row['status'] === 'Loss' ? 'badge-soft-danger' : ($row['status'] === 'Low Margin' ? 'badge-soft-warning' : 'badge-soft-success') }}">
                                            {{ $row['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-4">No rental profitability data in this range.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Customer Profitability</h2>
                            <p>Customers ranked by net contribution after rental costs.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Rentals</th>
                                <th>Revenue</th>
                                <th>Cost</th>
                                <th>Net</th>
                                <th>Margin</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($customerProfitability as $row)
                                <tr>
                                    <td>{{ $row['customer'] }}</td>
                                    <td>{{ $row['rentals'] }}</td>
                                    <td>{{ number_format($row['revenue'], 2) }}</td>
                                    <td>{{ number_format($row['cost'], 2) }}</td>
                                    <td>{{ number_format($row['net'], 2) }}</td>
                                    <td>{{ number_format($row['margin'], 2) }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No customer profitability data in this range.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="col-xl-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Equipment Profitability</h2>
                            <p>Equipment ranked by revenue after linked expenses and maintenance.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Equipment</th>
                                <th>Rentals</th>
                                <th>Revenue</th>
                                <th>Costs</th>
                                <th>Net</th>
                                <th>Margin</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($equipmentProfitability as $row)
                                <tr>
                                    <td>{{ $row['equipment'] }}</td>
                                    <td>{{ $row['rentals'] }}</td>
                                    <td>{{ number_format($row['revenue'], 2) }}</td>
                                    <td>{{ number_format($row['expenses'] + $row['maintenance'], 2) }}</td>
                                    <td>{{ number_format($row['net'], 2) }}</td>
                                    <td>{{ number_format($row['margin'], 2) }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No equipment profitability data in this range.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Monthly Revenue</h2>
                            <p>Invoice totals grouped by month in the selected range.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Month</th>
                                <th>Revenue</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($monthlyRevenue as $row)
                                <tr>
                                    <td>{{ $row['month'] }}</td>
                                    <td>{{ number_format($row['amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-4">No invoice revenue in this range.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="col-md-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Rental Status</h2>
                            <p>Rental jobs started within the selected range.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($rentalStatus as $status => $count)
                                <tr>
                                    <td>{{ str($status)->headline() }}</td>
                                    <td>{{ $count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-4">No rentals in this range.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Top Customers</h2>
                            <p>Customers ranked by invoiced amount.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Invoiced</th>
                                <th>Balance</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($topCustomers as $row)
                                <tr>
                                    <td>{{ $row['customer'] }}</td>
                                    <td>{{ number_format($row['invoiced'], 2) }}</td>
                                    <td>{{ number_format($row['balance'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">No customer invoice data in this range.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="col-xl-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Equipment Utilization</h2>
                            <p>Equipment usage ranked by rental line revenue.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Equipment</th>
                                <th>Rentals</th>
                                <th>Days</th>
                                <th>Revenue</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($equipmentUtilization as $row)
                                <tr>
                                    <td>{{ $row['equipment'] }}</td>
                                    <td>{{ $row['rentals'] }}</td>
                                    <td>{{ number_format($row['days'], 2) }}</td>
                                    <td>{{ number_format($row['revenue'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No equipment usage in this range.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <section class="panel h-100">
                    <span class="eyebrow">Damage Charges</span>
                    <h2 class="mb-0">{{ number_format($summary['damage'], 2) }}</h2>
                    <p class="text-muted mb-0">Invoice damage charges in range</p>
                </section>
            </div>
            <div class="col-md-8">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Operating Expense Categories</h2>
                            <p>General expenses grouped by category in the selected range.</p>
                        </div>
                        <div class="text-end">
                            <span class="eyebrow">Total Cost</span>
                            <h2 class="mb-0">{{ number_format($summary['operatingCost'], 2) }}</h2>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Category</th>
                                <th>Entries</th>
                                <th>Cost</th>
                                <th>Billable</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($expenseSummary as $row)
                                <tr>
                                    <td>{{ str($row['category'])->headline() }}</td>
                                    <td>{{ $row['count'] }}</td>
                                    <td>{{ number_format($row['cost'], 2) }}</td>
                                    <td>{{ number_format($row['billable'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No operating expenses in this range.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-8">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Maintenance Cost</h2>
                            <p>Maintenance events grouped by equipment.</p>
                        </div>
                        <div class="text-end">
                            <span class="eyebrow">Total Cost</span>
                            <h2 class="mb-0">{{ number_format($summary['maintenanceCost'], 2) }}</h2>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Equipment</th>
                                <th>Events</th>
                                <th>Cost</th>
                                <th>Downtime</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($maintenanceSummary as $row)
                                <tr>
                                    <td>{{ $row['equipment'] }}</td>
                                    <td>{{ $row['count'] }}</td>
                                    <td>{{ number_format($row['cost'], 2) }}</td>
                                    <td>{{ number_format($row['downtime'], 2) }} hrs</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No maintenance events in this range.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
