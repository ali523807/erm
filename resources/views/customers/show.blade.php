@extends('layouts.app')

@section('title', $customer->company_name)

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Customer profile</span>
                <h1>{{ $customer->company_name }}</h1>
                <p>{{ collect([$customer->contact_person, $customer->email, $customer->phone])->filter()->join(' - ') ?: 'No contact details recorded' }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <x-button :link="route('customers.index')" color="outline-secondary">
                    <x-lucide-arrow-left class="w-4 h-4"/>
                    <span>Back</span>
                </x-button>
                <x-button :link="route('customers.statement.show', $customer)" color="outline-secondary">
                    <x-lucide-file-text class="w-4 h-4"/>
                    <span>Statement</span>
                </x-button>
                <x-button :link="route('customers.edit', $customer)" color="dark">
                    <x-lucide-pencil class="w-4 h-4"/>
                    <span>Edit</span>
                </x-button>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Quotes</span>
                    <h2 class="mb-0">{{ $summary['quotes'] }}</h2>
                    <p class="text-muted mb-0">Recent commercial activity</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Rentals</span>
                    <h2 class="mb-0">{{ $summary['rentals'] }}</h2>
                    <p class="text-muted mb-0">Rental jobs</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Invoices</span>
                    <h2 class="mb-0">{{ $summary['invoices'] }}</h2>
                    <p class="text-muted mb-0">Billing records</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Balance Due</span>
                    <h2 class="mb-0">{{ number_format($summary['balanceDue'], 2) }}</h2>
                    <p class="text-muted mb-0">Outstanding amount</p>
                </section>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-4">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Profile</h2>
                            <p>Primary customer, billing, and compliance details.</p>
                        </div>
                    </div>
                    <dl class="detail-grid">
                        <div>
                            <dt>Contact</dt>
                            <dd>{{ $customer->contact_person ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt>Email</dt>
                            <dd>{{ $customer->email ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt>Phone</dt>
                            <dd>{{ $customer->phone ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt>Trade License</dt>
                            <dd>{{ $customer->trade_license_number ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt>VAT / Tax</dt>
                            <dd>{{ $customer->vat_number ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt>Address</dt>
                            <dd>{{ $customer->address ?: '-' }}</dd>
                        </div>
                    </dl>
                    <div class="mt-3">
                        <h3 class="h6">Notes</h3>
                        <p class="text-muted mb-0">{{ $customer->notes ?: 'No internal notes recorded.' }}</p>
                    </div>
                </section>
            </div>

            <div class="col-xl-8">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Recent Rentals</h2>
                            <p>Operational history for this customer.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Rental</th>
                                <th>Period</th>
                                <th>Location</th>
                                <th>Equipment</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($customer->rentals->sortByDesc('created_at')->take(8) as $rental)
                                <tr>
                                    <td><a href="{{ route('rentals.show', $rental) }}">RTN-{{ $rental->id }}</a></td>
                                    <td>{{ $rental->rental_start_date?->format('Y-m-d') ?: '-' }} - {{ $rental->rental_end_date?->format('Y-m-d') ?: '-' }}</td>
                                    <td>{{ $rental->delivery_location ?: '-' }}</td>
                                    <td>{{ $rental->rentalItems->count() }} assets</td>
                                    <td><span class="badge badge-soft-secondary">{{ str($rental->status ?: 'reserved')->headline() }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No rentals recorded.</td></tr>
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
                            <h2>Quotes</h2>
                            <p>Recent offers and conversion state.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Quote</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($customer->quotes->take(8) as $quote)
                                <tr>
                                    <td><a href="{{ route('quotes.show', $quote) }}">{{ $quote->quote_number }}</a></td>
                                    <td>{{ $quote->quote_date?->format('Y-m-d') ?: '-' }}</td>
                                    <td><span class="badge badge-soft-secondary">{{ str($quote->status)->headline() }}</span></td>
                                    <td>{{ number_format((float) $quote->total_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No quotes recorded.</td></tr>
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
                            <h2>Invoices</h2>
                            <p>Billing and collection state for this customer.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Due</th>
                                <th>Status</th>
                                <th>Balance</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($customer->invoices->sortByDesc('invoice_date')->take(8) as $invoice)
                                <tr>
                                    <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                                    <td>{{ $invoice->due_date?->format('Y-m-d') ?: '-' }}</td>
                                    <td><span class="badge badge-soft-secondary">{{ str($invoice->status)->headline() }}</span></td>
                                    <td>{{ number_format((float) $invoice->balance_due, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No invoices recorded.</td></tr>
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
                            <h2>Documents</h2>
                            <p>Customer-level compliance and account files.</p>
                        </div>
                        <a href="{{ route('documents.index', ['owner_type' => 'customer', 'owner_id' => $customer->id]) }}" class="btn btn-sm btn-outline-secondary">Open Documents</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Document</th>
                                <th>Type</th>
                                <th>Expiry</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($documents as $document)
                                <tr>
                                    <td><a href="{{ route('documents.download', $document) }}">{{ $document->title }}</a></td>
                                    <td>{{ str($document->type)->headline() }}</td>
                                    <td>{{ $document->expires_at?->format('Y-m-d') ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">No customer documents uploaded.</td></tr>
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
                            <h2>Portal Access</h2>
                            <p>Customer portal users linked to this account.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>User</th>
                                <th>Status</th>
                                <th>Last Login</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($customer->portalUsers as $portalUser)
                                <tr>
                                    <td>
                                        <strong>{{ $portalUser->name }}</strong>
                                        <div class="text-muted text-xs">{{ $portalUser->email }}</div>
                                    </td>
                                    <td><span class="badge {{ $portalUser->is_active ? 'badge-soft-success' : 'badge-soft-secondary' }}">{{ $portalUser->is_active ? 'Active' : 'Disabled' }}</span></td>
                                    <td>{{ $portalUser->last_login_at?->format('Y-m-d H:i') ?: 'Never' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">No portal access created.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
