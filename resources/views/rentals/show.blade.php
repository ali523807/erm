@extends('layouts.app')

@section('title', 'RTN-'.$rental->id)

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Rental detail</span>
                <h1>RTN-{{ $rental->id }}</h1>
                <p>{{ $rental->customer?->company_name }} - {{ $rental->rental_start_date?->format('Y-m-d') ?: '-' }} to {{ $rental->rental_end_date?->format('Y-m-d') ?: '-' }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <x-button :link="route('rentals.index')" color="outline-secondary">
                    <x-lucide-arrow-left class="w-4 h-4"/>
                    <span>Back</span>
                </x-button>
                @if(! in_array($rental->status, ['closed', 'cancelled'], true))
                    <x-button :link="route('rentals.edit', $rental)" color="dark">
                        <x-lucide-pencil class="w-4 h-4"/>
                        <span>Edit</span>
                    </x-button>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row g-3">
            <div class="col-xl-8">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Equipment Lines</h2>
                            <p>Items reserved or checked out for this rental job.</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Equipment</th>
                                <th>Period</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($rental->rentalItems as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->product?->name }}</strong>
                                        <div class="text-muted text-xs">{{ $item->product?->equipment_code ?: $item->product?->category?->name }}</div>
                                    </td>
                                    <td>{{ $item->start_date ?: '-' }} - {{ $item->end_date ?: '-' }}</td>
                                    <td>{{ number_format((float) $item->no_of_duration, 2) }} {{ $item->duration_type ?: $item->rate_type }}</td>
                                    <td><span class="badge badge-soft-secondary">{{ str($item->status ?: 'reserved')->headline() }}</span></td>
                                    <td>{{ number_format((float) $item->total_price, 2) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="col-xl-4">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Status and Billing</h2>
                            <p>Move the rental through reservation, check-out, return, and close-out.</p>
                        </div>
                    </div>

                    <dl class="detail-grid">
                        <div>
                            <dt>Status</dt>
                            <dd>{{ $statuses[$rental->status] ?? str($rental->status)->headline() }}</dd>
                        </div>
                        <div>
                            <dt>Source</dt>
                            <dd>
                                @if($rental->quote)
                                    <a href="{{ route('quotes.show', $rental->quote) }}">{{ $rental->quote->quote_number }}</a>
                                @else
                                    Direct rental
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Delivery</dt>
                            <dd>{{ $rental->delivery_date?->format('Y-m-d') ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt>Pickup</dt>
                            <dd>{{ $rental->pickup_date?->format('Y-m-d') ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt>Subtotal</dt>
                            <dd>{{ number_format($totals['subtotal'], 2) }}</dd>
                        </div>
                        <div>
                            <dt>Deposit</dt>
                            <dd>{{ number_format($totals['deposit'], 2) }}</dd>
                        </div>
                    </dl>

                    @if($nextStatuses)
                        <div class="d-grid gap-2 mt-3">
                            @foreach($nextStatuses as $status => $label)
                                <form method="POST" action="{{ route('rentals.status.update', $rental) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $status }}">
                                    <button type="submit" class="btn btn-outline-secondary w-100">{{ $label }}</button>
                                </form>
                            @endforeach
                        </div>
                    @endif

                    @if($rental->invoice)
                        <a href="{{ route('invoices.show', $rental->invoice) }}" class="btn btn-dark w-100 mt-3">Open Invoice {{ $rental->invoice->invoice_number }}</a>
                    @else
                        <form method="POST" action="{{ route('rentals.invoices.store', $rental) }}" class="mt-3">
                            @csrf
                            <input type="hidden" name="due_date" value="{{ now()->addDays(14)->toDateString() }}">
                            <button type="submit" class="btn btn-dark w-100">Generate Invoice</button>
                        </form>
                    @endif

                    @if($rental->agreement)
                        <a href="{{ route('agreements.show', $rental->agreement) }}" class="btn btn-outline-secondary w-100 mt-2">Open Agreement {{ $rental->agreement->agreement_number }}</a>
                    @else
                        <form method="POST" action="{{ route('rentals.agreements.store', $rental) }}" class="mt-2">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary w-100">Generate Agreement</button>
                        </form>
                    @endif
                </section>
            </div>

            <div class="col-lg-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Customer</h2>
                            <p>Primary billing and job contact information.</p>
                        </div>
                    </div>
                    <dl class="detail-grid">
                        <div>
                            <dt>Company</dt>
                            <dd>{{ $rental->customer?->company_name ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt>Contact</dt>
                            <dd>{{ $rental->customer?->contact_person ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt>Email</dt>
                            <dd>{{ $rental->customer?->email ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt>Phone</dt>
                            <dd>{{ $rental->customer?->phone ?: '-' }}</dd>
                        </div>
                    </dl>
                </section>
            </div>

            <div class="col-lg-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Delivery and Notes</h2>
                            <p>Location and internal instructions for operations.</p>
                        </div>
                    </div>
                    <dl class="detail-grid">
                        <div>
                            <dt>Location</dt>
                            <dd>{{ $rental->delivery_location ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt>Notes</dt>
                            <dd>{{ $rental->notes ?: 'No notes recorded.' }}</dd>
                        </div>
                    </dl>
                </section>
            </div>
        </div>
    </div>
@endsection
