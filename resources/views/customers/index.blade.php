@extends('layouts.app')

@section('title', 'Customers')

@section('content')
    @php($money = app(\App\Support\Money::class))
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Customer desk</span>
                <h1>Customers</h1>
                <p>Manage billing contacts, rental history, balances, documents, and portal readiness for every customer account.</p>
            </div>
            <x-button :link="route('customers.create')" color="dark">
                <x-lucide-plus class="w-4 h-4"/>
                <span>Add Customer</span>
            </x-button>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Registered</span>
                    <h2 class="mb-0">{{ $summary['total'] }}</h2>
                    <p class="text-muted mb-0">Customer accounts</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Active</span>
                    <h2 class="mb-0">{{ $summary['active'] }}</h2>
                    <p class="text-muted mb-0">With rental history</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">With Balance</span>
                    <h2 class="mb-0">{{ $summary['withBalance'] }}</h2>
                    <p class="text-muted mb-0">Open receivables</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Balance Due</span>
                    <h2 class="mb-0">{{ $money->format($summary['balanceDue']) }}</h2>
                    <p class="text-muted mb-0">Across all customers</p>
                </section>
            </div>
        </div>

        <section class="panel">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Customer Directory</h2>
                    <p>Search customers and open a full customer profile for operations and billing context.</p>
                </div>
                <form method="GET" action="{{ route('customers.index') }}" class="d-flex flex-wrap gap-2 align-items-end">
                    <div>
                        <label for="search" class="form-label mb-1">Search</label>
                        <input id="search" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Company, contact, email, phone">
                    </div>
                    <div>
                        <label for="status" class="form-label mb-1">View</label>
                        <select id="status" name="status" class="form-select">
                            <option value="all" @selected($filters['status'] === 'all')>All customers</option>
                            <option value="active" @selected($filters['status'] === 'active')>Active customers</option>
                            <option value="balance" @selected($filters['status'] === 'balance')>With balance</option>
                        </select>
                    </div>
                    <div>
                        <label for="sort" class="form-label mb-1">Sort</label>
                        <select id="sort" name="sort" class="form-select">
                            <option value="company_name" @selected($filters['sort'] === 'company_name')>Company</option>
                            <option value="contact_person" @selected($filters['sort'] === 'contact_person')>Contact</option>
                            <option value="balance" @selected($filters['sort'] === 'balance')>Balance</option>
                            <option value="rentals" @selected($filters['sort'] === 'rentals')>Rentals</option>
                            <option value="created_at" @selected($filters['sort'] === 'created_at')>Created date</option>
                        </select>
                    </div>
                    <div>
                        <label for="direction" class="form-label mb-1">Order</label>
                        <select id="direction" name="direction" class="form-select">
                            <option value="asc" @selected($filters['direction'] === 'asc')>A to Z / Low first</option>
                            <option value="desc" @selected($filters['direction'] === 'desc')>Z to A / High first</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-outline-secondary">
                        <x-lucide-search class="w-4 h-4"/>
                        Filter
                    </button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table modern-table align-middle">
                    <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Activity</th>
                        <th>Balance</th>
                        <th>Tax / License</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($customers as $customer)
                        <tr>
                            <td>
                                <a href="{{ route('customers.show', $customer) }}"><strong>{{ $customer->company_name }}</strong></a>
                                <div class="text-muted text-xs">{{ $customer->address ?: 'No address recorded' }}</div>
                            </td>
                            <td>
                                {{ $customer->contact_person ?: 'No contact' }}
                                <div class="text-muted text-xs">{{ collect([$customer->email, $customer->phone])->filter()->join(' / ') ?: 'No contact details' }}</div>
                            </td>
                            <td>
                                <span class="badge badge-soft-primary">{{ $customer->rentals_count }} rentals</span>
                                <span class="badge badge-soft-secondary">{{ $customer->quotes_count }} quotes</span>
                                <span class="badge badge-soft-info">{{ $customer->invoices_count }} invoices</span>
                            </td>
                            <td>{{ $money->format($customer->balance_due_sum) }}</td>
                            <td>
                                {{ $customer->vat_number ?: '-' }}
                                <div class="text-muted text-xs">{{ $customer->trade_license_number ?: 'No license' }}</div>
                            </td>
                            <td>
                                <div class="table-actions justify-content-end">
                                    <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-outline-secondary">
                                        <x-lucide-eye class="w-4 h-4"/>
                                        View
                                    </a>
                                    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary">
                                        <x-lucide-pencil class="w-4 h-4"/>
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No customers found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <x-pagination :paginator="$customers"/>
        </section>
    </div>
@endsection
