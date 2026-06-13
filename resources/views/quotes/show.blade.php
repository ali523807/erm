@extends('layouts.app')

@section('title', $quote->quote_number)

@section('content')
    @php
        $hasConvertedRental = $quote->status === 'converted' && $quote->rental;
        $money = app(\App\Support\Money::class);
    @endphp

    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Quote detail</span>
                <h1>{{ $quote->quote_number }}</h1>
                <p>{{ $quote->customer?->company_name }} · {{ $quote->rental_start_date?->format('Y-m-d') }} to {{ $quote->rental_end_date?->format('Y-m-d') }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <x-button :link="route('quotes.index')" color="outline-secondary">
                    <x-lucide-arrow-left class="w-4 h-4"/>
                    <span>Back</span>
                </x-button>
                <x-button :link="route('quotes.print', $quote)" color="outline-secondary" target="_blank">
                    <x-lucide-printer class="w-4 h-4"/>
                    <span>Print</span>
                </x-button>
                <x-button :link="route('quotes.download', $quote)" color="outline-secondary">
                    <x-lucide-download class="w-4 h-4"/>
                    <span>PDF</span>
                </x-button>
                @if($quote->status !== 'converted')
                    <x-button :link="route('quotes.edit', $quote)" color="dark">
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
                            <h2>Quote Items</h2>
                            <p>Equipment and pricing offered to the customer.</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Equipment</th>
                                <th>Period</th>
                                <th>Duration</th>
                                <th>Rate</th>
                                <th>Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($quote->items as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->product?->name }}</strong>
                                        <div class="text-muted text-xs">{{ $item->notes ?: $item->product?->equipment_code }}</div>
                                    </td>
                                    <td>{{ $item->start_date?->format('Y-m-d') }} - {{ $item->end_date?->format('Y-m-d') }}</td>
                                    <td>1 asset x {{ number_format((float) $item->no_of_duration, 2) }} {{ $item->duration_type }}</td>
                                    <td>{{ $money->format($item->rate, $quote->currency) }}</td>
                                    <td>{{ $money->format($item->line_total, $quote->currency) }}</td>
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
                            <h2>Status and Totals</h2>
                            <p>Manage quote lifecycle and conversion.</p>
                        </div>
                    </div>

                    <dl class="detail-grid">
                        <div>
                            <dt>Status</dt>
                            <dd>{{ $statuses[$quote->status] ?? str($quote->status)->headline() }}</dd>
                        </div>
                        <div>
                            <dt>Valid Until</dt>
                            <dd>{{ $quote->valid_until?->format('Y-m-d') ?: 'Open' }}</dd>
                        </div>
                        <div>
                            <dt>Subtotal</dt>
                            <dd>{{ $money->format($quote->subtotal, $quote->currency) }}</dd>
                        </div>
                        <div>
                            <dt>Discount</dt>
                            <dd>{{ $money->format($quote->discount_amount, $quote->currency) }}</dd>
                        </div>
                        <div>
                            <dt>Tax</dt>
                            <dd>{{ $money->format($quote->tax_amount, $quote->currency) }}</dd>
                        </div>
                        <div>
                            <dt>Total</dt>
                            <dd>{{ $money->format($quote->total_amount, $quote->currency) }}</dd>
                        </div>
                        <div>
                            <dt>Base Total</dt>
                            <dd>{{ $money->format($quote->base_total_amount, $quote->base_currency) }}</dd>
                        </div>
                        <div>
                            <dt>Exchange Rate</dt>
                            <dd>1 {{ $quote->currency }} = {{ number_format((float) $quote->exchange_rate, 8) }} {{ $quote->base_currency }}</dd>
                        </div>
                    </dl>

                    @if(! $hasConvertedRental)
                        @if($quote->status === 'converted')
                            <div class="alert alert-warning mt-3">
                                This quote was marked converted, but no rental was created yet. Use Convert to Rental to finish the booking.
                            </div>
                        @endif

                        <form method="POST" action="{{ route('quotes.status.update', $quote) }}" class="mt-3">
                            @csrf
                            @method('PATCH')
                            <label for="status" class="form-label">Update Status</label>
                            <div class="input-group">
                                <select id="status" name="status" class="form-select">
                                    @foreach($statuses as $value => $label)
                                        @continue($value === 'converted')
                                        <option value="{{ $value }}" @selected($quote->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-outline-secondary">Save</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('quotes.convert', $quote) }}" class="mt-3" onsubmit="return confirm('Convert this quote to a reserved rental?')">
                            @csrf
                            <button type="submit" class="btn btn-dark w-100">Convert to Rental</button>
                        </form>
                    @else
                        <a href="{{ route('rentals.show', $quote->rental) }}" class="btn btn-outline-secondary w-100 mt-3">Open Rental RTN-{{ $quote->rental->id }}</a>
                    @endif
                </section>
            </div>

            <div class="col-12">
                @include('document-deliveries._send-form', [
                    'action' => route('quotes.send', $quote),
                    'idPrefix' => 'quote_email',
                    'title' => 'Email Quote',
                    'description' => 'Send the quote PDF to the customer and record the delivery attempt.',
                    'recipientEmail' => $quote->customer?->email,
                    'recipientName' => $quote->customer?->contact_person,
                    'subject' => 'Quote '.$quote->quote_number,
                    'message' => 'Please find the attached quote for your review.',
                ])
            </div>

            <div class="col-12">
                <section class="panel">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Terms and Notes</h2>
                            <p>Customer-facing terms and private operational notes.</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <h3 class="section-subtitle">Terms</h3>
                            <p class="text-muted mt-2 mb-0">{{ $quote->terms ?: 'No terms recorded.' }}</p>
                        </div>
                        <div class="col-lg-6">
                            <h3 class="section-subtitle">Notes</h3>
                            <p class="text-muted mt-2 mb-0">{{ $quote->notes ?: 'No internal notes.' }}</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
