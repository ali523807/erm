@extends('layouts.app')

@section('title', 'RTN-'.$rental->id)

@section('content')
    @php
        $money = app(\App\Support\Money::class);
        $depositRequired = $rental->depositRequiredAmount();
        $depositCollected = $rental->depositCollectedAmount();
        $depositRefunded = $rental->depositRefundedAmount();
        $depositApplied = $rental->depositAppliedAmount();
        $depositHeld = $rental->depositHeldAmount();
        $depositOutstanding = $rental->depositOutstandingAmount();
        $invoiceBalance = $rental->invoice ? (float) $rental->invoice->balance_due : 0;
        $depositApplicationLimit = min($depositHeld, $invoiceBalance);
        $canManageDeposits = auth()->user()->hasCurrentCompanyPermission('payments.manage');
    @endphp
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
                                    <td>{{ $money->format($item->total_price) }}</td>
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
                            <dd>{{ $money->format($totals['subtotal']) }}</dd>
                        </div>
                        <div>
                            <dt>Deposit</dt>
                            <dd>{{ $money->format($totals['deposit']) }}</dd>
                        </div>
                    </dl>

                    @if($nextStatuses)
                        <div class="d-grid gap-2 mt-3">
                            @foreach($nextStatuses as $status => $label)
                                <form method="POST" action="{{ route('rentals.status.update', $rental) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $status }}">
                                    <button
                                        type="submit"
                                        class="btn btn-outline-secondary w-100"
                                        @disabled($status === 'closed' && ! $closeOutChecklist['canClose'])
                                    >
                                        {{ $label }}
                                    </button>
                                </form>
                            @endforeach
                        </div>
                        @if(isset($nextStatuses['closed']) && ! $closeOutChecklist['canClose'])
                            <p class="text-muted text-sm mt-2 mb-0">Complete the close-out checklist before closing this rental.</p>
                        @endif
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

            <div class="col-12">
                <section class="panel">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Close-Out Checklist</h2>
                            <p>Confirm returned assets, final billing, billable expenses, deposit settlement, and customer return records before closing the rental.</p>
                        </div>
                        <span class="badge {{ $closeOutChecklist['canClose'] ? 'badge-soft-success' : 'badge-soft-warning' }}">
                            {{ $closeOutChecklist['passedCount'] }} / {{ $closeOutChecklist['totalCount'] }} ready
                        </span>
                    </div>

                    <div class="alert {{ $closeOutChecklist['canClose'] ? 'alert-success' : 'alert-warning' }} mb-3">
                        @if($closeOutChecklist['canClose'])
                            This rental is ready for close-out.
                        @else
                            Resolve the required items marked Needs Action before closing this rental.
                        @endif
                    </div>

                    <div class="row g-3">
                        @foreach($closeOutChecklist['items'] as $item)
                            <div class="col-md-6 col-xl-4">
                                <div class="sub-panel h-100">
                                    <div class="d-flex align-items-start justify-content-between gap-3">
                                        <div>
                                            <h3 class="mb-1">{{ $item['label'] }}</h3>
                                            <p class="text-muted mb-0">{{ $item['help'] }}</p>
                                        </div>
                                        @if($item['passed'])
                                            <span class="badge badge-soft-success">Ready</span>
                                        @elseif($item['blocking'])
                                            <span class="badge badge-soft-warning">Needs Action</span>
                                        @else
                                            <span class="badge badge-soft-secondary">Advisory</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="col-12">
                <section class="panel">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Security Deposit</h2>
                            <p>Track deposit required for assets, money collected from the customer, funds still held, and settlement against refunds or invoice balance.</p>
                        </div>
                        @if($canManageDeposits)
                            <a href="{{ route('deposits.index') }}" class="btn btn-outline-secondary">
                                <x-lucide-wallet class="w-4 h-4"/>
                                <span>Deposit Ledger</span>
                            </a>
                        @endif
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="metric-card soft-blue">
                                <span>Required</span>
                                <strong>{{ $money->format($depositRequired) }}</strong>
                                <small>From equipment lines</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card soft-green">
                                <span>Collected</span>
                                <strong>{{ $money->format($depositCollected) }}</strong>
                                <small>{{ $money->format($depositOutstanding) }} outstanding</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card soft-amber">
                                <span>Held</span>
                                <strong>{{ $money->format($depositHeld) }}</strong>
                                <small>Available for refund or invoice</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card soft-rose">
                                <span>Settled</span>
                                <strong>{{ $money->format($depositRefunded + $depositApplied) }}</strong>
                                <small>{{ $money->format($depositRefunded) }} refunded, {{ $money->format($depositApplied) }} applied</small>
                            </div>
                        </div>
                    </div>

                    @if($canManageDeposits)
                        <div class="row g-3">
                            <div class="col-lg-4">
                                <div class="sub-panel h-100">
                                    <h3>Collect Deposit</h3>
                                    <p class="text-muted">Record cash, card, transfer, or cheque deposit collected before dispatch or at check-out.</p>
                                    @if($depositOutstanding > 0)
                                        <form method="POST" action="{{ route('rentals.deposits.collect', $rental) }}" class="row g-2">
                                            @csrf
                                            <div class="col-6">
                                                <label for="collect_transaction_date" class="form-label">Date</label>
                                                <input id="collect_transaction_date" name="transaction_date" type="date" class="form-control" value="{{ old('transaction_date', now()->toDateString()) }}" required>
                                            </div>
                                            <div class="col-6">
                                                <label for="collect_amount" class="form-label">Amount</label>
                                                <input id="collect_amount" name="amount" type="number" step="0.01" min="0.01" max="{{ $depositOutstanding }}" class="form-control" value="{{ old('amount', $depositOutstanding) }}" required>
                                            </div>
                                            <div class="col-12">
                                                <label for="collect_payment_method" class="form-label">Method</label>
                                                <select id="collect_payment_method" name="payment_method" class="form-select" required>
                                                    @foreach(['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'card' => 'Card', 'cheque' => 'Cheque', 'online' => 'Online', 'other' => 'Other'] as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label for="collect_reference" class="form-label">Reference</label>
                                                <input id="collect_reference" name="reference" class="form-control" placeholder="Receipt, transfer, or approval reference">
                                            </div>
                                            <div class="col-12">
                                                <label for="collect_notes" class="form-label">Notes</label>
                                                <textarea id="collect_notes" name="notes" rows="2" class="form-control" placeholder="Condition, authorization, or collection notes"></textarea>
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary w-100">Record Collection</button>
                                            </div>
                                        </form>
                                    @else
                                        <div class="empty-state py-3">Required deposit is fully collected.</div>
                                    @endif
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="sub-panel h-100">
                                    <h3>Apply to Invoice</h3>
                                    <p class="text-muted">Use held deposit to settle damage, late fees, or unpaid invoice balance.</p>
                                    @if($depositApplicationLimit > 0)
                                        <form method="POST" action="{{ route('rentals.deposits.apply', $rental) }}" class="row g-2">
                                            @csrf
                                            <div class="col-6">
                                                <label for="apply_transaction_date" class="form-label">Date</label>
                                                <input id="apply_transaction_date" name="transaction_date" type="date" class="form-control" value="{{ now()->toDateString() }}" required>
                                            </div>
                                            <div class="col-6">
                                                <label for="apply_amount" class="form-label">Amount</label>
                                                <input id="apply_amount" name="amount" type="number" step="0.01" min="0.01" max="{{ $depositApplicationLimit }}" class="form-control" value="{{ $depositApplicationLimit }}" required>
                                            </div>
                                            <div class="col-12">
                                                <label for="apply_reference" class="form-label">Reference</label>
                                                <input id="apply_reference" name="reference" class="form-control" placeholder="Deposit settlement reference">
                                            </div>
                                            <div class="col-12">
                                                <label for="apply_notes" class="form-label">Notes</label>
                                                <textarea id="apply_notes" name="notes" rows="2" class="form-control" placeholder="Reason for applying deposit to invoice"></textarea>
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-dark w-100">Apply to Invoice</button>
                                            </div>
                                        </form>
                                    @else
                                        <div class="empty-state py-3">
                                            @if(! $rental->invoice)
                                                Generate an invoice before applying deposits.
                                            @elseif($depositHeld <= 0)
                                                No held deposit is available to apply.
                                            @else
                                                Invoice has no balance due.
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="sub-panel h-100">
                                    <h3>Refund Deposit</h3>
                                    <p class="text-muted">Return unused deposit to the customer after equipment inspection and close-out.</p>
                                    @if($depositHeld > 0)
                                        <form method="POST" action="{{ route('rentals.deposits.refund', $rental) }}" class="row g-2">
                                            @csrf
                                            <div class="col-6">
                                                <label for="refund_transaction_date" class="form-label">Date</label>
                                                <input id="refund_transaction_date" name="transaction_date" type="date" class="form-control" value="{{ now()->toDateString() }}" required>
                                            </div>
                                            <div class="col-6">
                                                <label for="refund_amount" class="form-label">Amount</label>
                                                <input id="refund_amount" name="amount" type="number" step="0.01" min="0.01" max="{{ $depositHeld }}" class="form-control" value="{{ $depositHeld }}" required>
                                            </div>
                                            <div class="col-12">
                                                <label for="refund_payment_method" class="form-label">Method</label>
                                                <select id="refund_payment_method" name="payment_method" class="form-select" required>
                                                    @foreach(['bank_transfer' => 'Bank Transfer', 'cash' => 'Cash', 'card' => 'Card', 'cheque' => 'Cheque', 'online' => 'Online', 'other' => 'Other'] as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label for="refund_reference" class="form-label">Reference</label>
                                                <input id="refund_reference" name="reference" class="form-control" placeholder="Refund transfer or cheque reference">
                                            </div>
                                            <div class="col-12">
                                                <label for="refund_notes" class="form-label">Notes</label>
                                                <textarea id="refund_notes" name="notes" rows="2" class="form-control" placeholder="Refund authorization or inspection notes"></textarea>
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-outline-secondary w-100">Record Refund</button>
                                            </div>
                                        </form>
                                    @else
                                        <div class="empty-state py-3">No held deposit is available to refund.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info mb-3">Finance permission is required to collect, apply, or refund deposits.</div>
                    @endif

                    <div class="table-responsive mt-3">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Invoice</th>
                                <th>Recorded By</th>
                                <th class="text-end">Amount</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($rental->depositTransactions->sortByDesc('transaction_date') as $transaction)
                                <tr>
                                    <td>{{ $transaction->transaction_date?->format('Y-m-d') }}</td>
                                    <td><span class="badge badge-soft-secondary">{{ str($transaction->type)->headline() }}</span></td>
                                    <td>{{ $transaction->payment_method ? str($transaction->payment_method)->headline() : '-' }}</td>
                                    <td>{{ $transaction->reference ?: '-' }}</td>
                                    <td>
                                        @if($transaction->invoice)
                                            <a href="{{ route('invoices.show', $transaction->invoice) }}">{{ $transaction->invoice->invoice_number }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $transaction->creator?->name ?: '-' }}</td>
                                    <td class="text-end">{{ $money->format($transaction->amount) }}</td>
                                </tr>
                                @if($transaction->notes)
                                    <tr>
                                        <td></td>
                                        <td colspan="6" class="text-muted text-sm">{{ $transaction->notes }}</td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No security deposit movement recorded yet.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
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
