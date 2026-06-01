@extends('layouts.app')

@section('title', $invoice->invoice_number)

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Invoice detail</span>
                <h1>{{ $invoice->invoice_number }}</h1>
                <p>{{ $invoice->customer?->company_name }} - Rental RTN-{{ $invoice->rental_id }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <x-button :link="route('invoices.index')" color="outline-secondary">
                    <x-lucide-arrow-left class="w-4 h-4"/>
                    <span>Back</span>
                </x-button>
                <x-button :link="route('rentals.show', $invoice->rental)" color="dark">
                    <x-lucide-file-box class="w-4 h-4"/>
                    <span>Rental</span>
                </x-button>
                <x-button :link="route('invoices.download', $invoice)" color="outline-secondary">
                    <x-lucide-download class="w-4 h-4"/>
                    <span>PDF</span>
                </x-button>
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
                            <h2>Rental Charges</h2>
                            <p>Equipment lines included in this invoice.</p>
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
                            @foreach($invoice->rental->rentalItems as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->product?->name }}</strong>
                                        <div class="text-muted text-xs">{{ $item->product?->equipment_code ?: 'Equipment item' }}</div>
                                    </td>
                                    <td>{{ $item->start_date }} - {{ $item->end_date }}</td>
                                    <td>{{ number_format((float) $item->no_of_duration, 2) }} {{ $item->duration_type }}</td>
                                    <td>{{ number_format((float) $item->rate, 2) }}</td>
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
                            <h2>Balance</h2>
                            <p>Current billing status and amount due.</p>
                        </div>
                    </div>

                    <dl class="detail-grid">
                        <div>
                            <dt>Status</dt>
                            <dd>{{ str($invoice->status)->headline() }}</dd>
                        </div>
                        <div>
                            <dt>Invoice Date</dt>
                            <dd>{{ $invoice->invoice_date?->format('Y-m-d') }}</dd>
                        </div>
                        <div>
                            <dt>Due Date</dt>
                            <dd>{{ $invoice->due_date?->format('Y-m-d') ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt>Total</dt>
                            <dd>{{ number_format((float) $invoice->total_amount, 2) }}</dd>
                        </div>
                        <div>
                            <dt>Paid</dt>
                            <dd>{{ number_format((float) $invoice->paid_amount, 2) }}</dd>
                        </div>
                        <div>
                            <dt>Balance Due</dt>
                            <dd>{{ number_format((float) $invoice->balance_due, 2) }}</dd>
                        </div>
                    </dl>
                </section>
            </div>

            <div class="col-xl-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Adjust Charges</h2>
                            <p>Add tax, discounts, damage charges, late fees, and billing notes.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('invoices.update', $invoice) }}">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="tax_amount" class="form-label">Tax</label>
                                <input id="tax_amount" name="tax_amount" type="number" step="0.01" min="0" class="form-control" value="{{ old('tax_amount', $invoice->tax_amount) }}" @disabled($invoice->status === 'paid')>
                            </div>
                            <div class="col-md-6">
                                <label for="discount_amount" class="form-label">Discount</label>
                                <input id="discount_amount" name="discount_amount" type="number" step="0.01" min="0" class="form-control" value="{{ old('discount_amount', $invoice->discount_amount) }}" @disabled($invoice->status === 'paid')>
                            </div>
                            <div class="col-md-6">
                                <label for="damage_amount" class="form-label">Damage Charges</label>
                                <input id="damage_amount" name="damage_amount" type="number" step="0.01" min="0" class="form-control" value="{{ old('damage_amount', $invoice->damage_amount) }}" @disabled($invoice->status === 'paid')>
                            </div>
                            <div class="col-md-6">
                                <label for="late_fee_amount" class="form-label">Late Fees</label>
                                <input id="late_fee_amount" name="late_fee_amount" type="number" step="0.01" min="0" class="form-control" value="{{ old('late_fee_amount', $invoice->late_fee_amount) }}" @disabled($invoice->status === 'paid')>
                            </div>
                            <div class="col-md-6">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input id="due_date" name="due_date" type="date" class="form-control" value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}" @disabled($invoice->status === 'paid')>
                            </div>
                            <div class="col-12">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea id="notes" name="notes" rows="3" class="form-control" @disabled($invoice->status === 'paid')>{{ old('notes', $invoice->notes) }}</textarea>
                            </div>
                        </div>

                        @if($invoice->status !== 'paid')
                            <button type="submit" class="btn btn-dark mt-3">Update Invoice</button>
                        @endif
                    </form>
                </section>
            </div>

            <div class="col-xl-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Payments</h2>
                            <p>Record receipts and track collection history.</p>
                        </div>
                    </div>

                    @if($invoice->status !== 'paid')
                        <form method="POST" action="{{ route('invoices.payments.store', $invoice) }}" class="mb-3">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="payment_date" class="form-label">Date</label>
                                    <input id="payment_date" name="payment_date" type="date" class="form-control" value="{{ old('payment_date', now()->toDateString()) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="amount" class="form-label">Amount</label>
                                    <input id="amount" name="amount" type="number" step="0.01" min="0.01" max="{{ $invoice->balance_due }}" class="form-control" value="{{ old('amount', $invoice->balance_due) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="method" class="form-label">Method</label>
                                    <select id="method" name="method" class="form-select" required>
                                        @foreach($paymentMethods as $method => $label)
                                            <option value="{{ $method }}" @selected(old('method', 'cash') === $method)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="reference" class="form-label">Reference</label>
                                    <input id="reference" name="reference" class="form-control" value="{{ old('reference') }}" placeholder="Receipt, cheque, transfer, or card ref">
                                </div>
                                <div class="col-md-6">
                                    <label for="payment_notes" class="form-label">Notes</label>
                                    <input id="payment_notes" name="notes" class="form-control" value="{{ old('notes') }}">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-dark mt-3">Record Payment</button>
                        </form>
                    @endif

                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Amount</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($invoice->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date?->format('Y-m-d') }}</td>
                                    <td>{{ str($payment->method)->headline() }}</td>
                                    <td>{{ $payment->reference ?: '-' }}</td>
                                    <td>{{ number_format((float) $payment->amount, 2) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('payments.receipt.print', $payment) }}" class="btn btn-sm btn-outline-secondary" target="_blank">Print</a>
                                        <a href="{{ route('payments.receipt.download', $payment) }}" class="btn btn-sm btn-primary">PDF</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No payments recorded yet.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
