@extends('layouts.app')

@section('title', 'Expenses')

@section('content')
    @php
        $money = app(\App\Support\Money::class);
    @endphp

    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Finance</span>
                <h1>Expenses</h1>
                <p>Track fuel, transport, labor, cleaning, insurance, permits, site costs, and other operating expenses against rentals, equipment, and customers.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <section class="metric-card soft-rose">
                    <span>Total Expenses</span>
                    <strong>{{ $money->format($summary['total']) }}</strong>
                    <small>Non-voided operating costs</small>
                </section>
            </div>
            <div class="col-md-3">
                <section class="metric-card soft-green">
                    <span>Paid</span>
                    <strong>{{ $money->format($summary['paid']) }}</strong>
                    <small>Settled expenses</small>
                </section>
            </div>
            <div class="col-md-3">
                <section class="metric-card soft-amber">
                    <span>Unpaid</span>
                    <strong>{{ $money->format($summary['unpaid']) }}</strong>
                    <small>Payables to settle</small>
                </section>
            </div>
            <div class="col-md-3">
                <section class="metric-card soft-blue">
                    <span>Billable</span>
                    <strong>{{ $money->format($summary['billable']) }}</strong>
                    <small>{{ $money->format($summary['uninvoicedBillable']) }} not invoiced</small>
                </section>
            </div>
        </div>

        <section class="panel">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Record Expense</h2>
                    <p>Use optional links when the cost belongs to a rental job, specific customer, or equipment asset.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('expenses.store') }}">
                @csrf
                @include('expenses.partials._form-fields', ['expense' => null])
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-dark">
                        <x-lucide-plus class="w-4 h-4"/>
                        Add Expense
                    </button>
                </div>
            </form>
        </section>

        <section class="panel mt-3">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Expense Ledger</h2>
                    <p>Review operating costs by category, vendor, linked rental, equipment, payment status, and billable flag.</p>
                </div>
            </div>

            <div class="list-toolbar">
                <div></div>
                <form method="GET" action="{{ route('expenses.index') }}">
                    <div>
                        <label for="search" class="form-label">Search</label>
                        <input id="search" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Expense, vendor, RTN, asset">
                    </div>
                    <div>
                        <label for="category" class="form-label">Category</label>
                        <select id="category" name="category" class="form-select">
                            <option value="all" @selected($filters['category'] === 'all')>All categories</option>
                            @foreach($categories as $value => $label)
                                <option value="{{ $value }}" @selected($filters['category'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="payment_status" class="form-label">Status</label>
                        <select id="payment_status" name="payment_status" class="form-select">
                            <option value="all" @selected($filters['payment_status'] === 'all')>All statuses</option>
                            @foreach($paymentStatuses as $value => $label)
                                <option value="{{ $value }}" @selected($filters['payment_status'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="billable" class="form-label">Billable</label>
                        <select id="billable" name="billable" class="form-select">
                            <option value="all" @selected($filters['billable'] === 'all')>All expenses</option>
                            <option value="yes" @selected($filters['billable'] === 'yes')>Billable only</option>
                            <option value="no" @selected($filters['billable'] === 'no')>Non-billable only</option>
                        </select>
                    </div>
                    <div>
                        <label for="sort" class="form-label">Sort</label>
                        <select id="sort" name="sort" class="form-select">
                            <option value="expense_date" @selected($filters['sort'] === 'expense_date')>Expense date</option>
                            <option value="category" @selected($filters['sort'] === 'category')>Category</option>
                            <option value="vendor_name" @selected($filters['sort'] === 'vendor_name')>Vendor</option>
                            <option value="payment_status" @selected($filters['sort'] === 'payment_status')>Status</option>
                            <option value="total_amount" @selected($filters['sort'] === 'total_amount')>Total</option>
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
                        <th>Date</th>
                        <th>Expense</th>
                        <th>Linked To</th>
                        <th>Vendor</th>
                        <th>Status</th>
                        <th class="text-end">Total</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($expenses as $expense)
                        <tr>
                            <td>{{ $expense->expense_date?->format('Y-m-d') }}</td>
                            <td>
                                <strong>{{ $expense->expense_number }}</strong>
                                <div>{{ $categories[$expense->category] ?? str($expense->category)->headline() }}</div>
                                <div class="text-muted text-xs">{{ $expense->reference ?: 'No reference' }}{{ $expense->is_billable ? ' - Billable' : '' }}</div>
                                @if($expense->is_billable)
                                    <span class="badge badge-soft-info">{{ str($expense->recovery_status)->headline() }}</span>
                                @endif
                            </td>
                            <td>
                                @if($expense->rental)
                                    <a href="{{ route('rentals.show', $expense->rental) }}">RTN-{{ $expense->rental->id }}</a>
                                    <div class="text-muted text-xs">{{ $expense->rental->customer?->company_name }}</div>
                                @elseif($expense->customer)
                                    <a href="{{ route('customers.show', $expense->customer) }}">{{ $expense->customer->company_name }}</a>
                                @elseif($expense->product)
                                    <a href="{{ route('products.show', $expense->product) }}">{{ $expense->product->name }}</a>
                                @else
                                    General expense
                                @endif
                                @if($expense->product && $expense->rental)
                                    <div class="text-muted text-xs">{{ $expense->product->name }}</div>
                                @endif
                            </td>
                            <td>{{ $expense->vendor_name ?: '-' }}</td>
                            <td>
                                <span class="badge badge-soft-secondary">{{ $paymentStatuses[$expense->payment_status] ?? str($expense->payment_status)->headline() }}</span>
                                <div class="text-muted text-xs">{{ $expense->payment_method ? ($paymentMethods[$expense->payment_method] ?? str($expense->payment_method)->headline()) : 'No method' }}</div>
                            </td>
                            <td class="text-end">
                                {{ $money->format($expense->total_amount, $expense->currency) }}
                                <div class="text-muted text-xs">Tax {{ $money->format($expense->tax_amount, $expense->currency) }}</div>
                            </td>
                            <td class="text-end">
                                @if($expense->is_billable && $expense->recovery_status === 'not_invoiced' && $expense->rental?->invoice)
                                    <form method="POST" action="{{ route('expenses.add-to-invoice', $expense) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <x-lucide-file-plus class="w-4 h-4"/>
                                            Invoice
                                        </button>
                                    </form>
                                @endif
                                @if($expense->invoice)
                                    <a href="{{ route('invoices.show', $expense->invoice) }}" class="btn btn-sm btn-outline-secondary">
                                        <x-lucide-file-text class="w-4 h-4"/>
                                        Open
                                    </a>
                                @endif
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#expense-edit-{{ $expense->id }}">
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('expenses.destroy', $expense) }}" class="d-inline" onsubmit="return confirm('Delete this expense?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @if($expense->description)
                            <tr>
                                <td></td>
                                <td colspan="6" class="text-muted text-sm">{{ $expense->description }}</td>
                            </tr>
                        @endif
                        <tr class="collapse" id="expense-edit-{{ $expense->id }}">
                            <td colspan="7">
                                <form method="POST" action="{{ route('expenses.update', $expense) }}" class="inline-edit-form">
                                    @csrf
                                    @method('PUT')
                                    @include('expenses.partials._form-fields', ['expense' => $expense])
                                    <div class="d-flex justify-content-end mt-3">
                                        <button type="submit" class="btn btn-dark btn-sm">Save Expense</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No operating expenses recorded yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <x-pagination :paginator="$expenses"/>
        </section>
    </div>
@endsection

@push('js')
    <script type="module">
        function initLookupSelect(selector, url, placeholder) {
            $(selector).each(function () {
                const select = $(this);
                if (select.data('select2')) {
                    return;
                }

                select.select2({
                    ajax: {
                        url,
                        dataType: 'json',
                        delay: 250,
                        data: (params) => ({q: params.term || ''}),
                        processResults: (data) => data,
                    },
                    minimumInputLength: 1,
                    placeholder,
                    allowClear: true,
                    theme: 'bootstrap-5',
                    width: '100%',
                });
            });
        }

        $(function () {
            initLookupSelect('.js-rental-lookup', @json(route('lookups.rentals')), 'Search rental by RTN, customer, or location');
            initLookupSelect('.js-customer-lookup', @json(route('lookups.customers')), 'Search customer by company, contact, email, or phone');
            initLookupSelect('.js-product-lookup', @json(route('lookups.products')), 'Search equipment by name, code, serial, or category');
        });
    </script>
@endpush
