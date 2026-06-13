@extends('layouts.app')

@section('title', $invoice->invoice_number)

@section('content')
    @php
        $money = app(\App\Support\Money::class);
        $availableCreditAmount = max(0, (float) $invoice->total_amount - (float) $invoice->creditNotes->where('status', '!=', 'voided')->sum('amount'));
    @endphp
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
                                    <td>{{ $money->format($item->rate, $invoice->currency) }}</td>
                                    <td>{{ $money->format($item->total_price, $invoice->currency) }}</td>
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
                            <dd>{{ $money->format($invoice->total_amount, $invoice->currency) }}</dd>
                        </div>
                        <div>
                            <dt>Paid</dt>
                            <dd>{{ $money->format($invoice->paid_amount, $invoice->currency) }}</dd>
                        </div>
                        <div>
                            <dt>Credits</dt>
                            <dd>{{ $money->format($invoice->creditNotes->where('status', '!=', 'voided')->sum('amount'), $invoice->currency) }}</dd>
                        </div>
                        <div>
                            <dt>Billable Expenses</dt>
                            <dd>{{ $money->format($invoice->billable_expense_amount, $invoice->currency) }}</dd>
                        </div>
                        <div>
                            <dt>Balance Due</dt>
                            <dd>{{ $money->format($invoice->balance_due, $invoice->currency) }}</dd>
                        </div>
                        <div>
                            <dt>Base Total</dt>
                            <dd>{{ $money->format($invoice->base_total_amount, $invoice->base_currency) }}</dd>
                        </div>
                        <div>
                            <dt>Base Balance</dt>
                            <dd>{{ $money->format($invoice->base_balance_due, $invoice->base_currency) }}</dd>
                        </div>
                        <div>
                            <dt>Exchange Rate</dt>
                            <dd>1 {{ $invoice->currency }} = {{ number_format((float) $invoice->exchange_rate, 8) }} {{ $invoice->base_currency }}</dd>
                        </div>
                        <div>
                            <dt>Tax Profile</dt>
                            <dd>{{ $invoice->taxProfile?->name ?: '-' }}</dd>
                        </div>
                    </dl>
                </section>
            </div>

            <div class="col-xl-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Billable Expenses</h2>
                            <p>Recoverable operating costs added from the expense ledger.</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Expense</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th class="text-end">Amount</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($invoice->expenses as $expense)
                                <tr>
                                    <td>
                                        <strong>{{ $expense->expense_number }}</strong>
                                        <div class="text-muted text-xs">{{ $expense->vendor_name ?: 'No vendor' }}</div>
                                    </td>
                                    <td>{{ str($expense->category)->headline() }}</td>
                                    <td><span class="badge badge-soft-secondary">{{ str($expense->recovery_status)->headline() }}</span></td>
                                    <td class="text-end">{{ $money->format($expense->total_amount, $invoice->currency) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No billable expenses added to this invoice.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="col-xl-6">
                @include('document-deliveries._send-form', [
                    'action' => route('invoices.send', $invoice),
                    'idPrefix' => 'invoice_email',
                    'title' => 'Email Invoice',
                    'description' => 'Send the invoice PDF and keep the delivery status in the customer communication history.',
                    'recipientEmail' => $invoice->customer?->email,
                    'recipientName' => $invoice->customer?->contact_person,
                    'subject' => 'Invoice '.$invoice->invoice_number,
                    'message' => 'Please find the attached invoice for your records.',
                    'class' => 'h-100',
                ])
            </div>

            <div class="col-xl-6">
                <section class="panel h-100">
                    <div class="panel-header align-items-start">
                        <div>
                            <h2>Online Payment Links</h2>
                            <p>Create secure customer payment links for this invoice. Gateway providers can be connected later without changing the invoice workflow.</p>
                        </div>
                    </div>

                    @if($invoice->balance_due > 0)
                        <form method="POST" action="{{ route('invoices.payment-links.store', $invoice) }}" class="mb-3">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="payment_link_amount" class="form-label">Amount</label>
                                    <input id="payment_link_amount" name="amount" type="number" step="0.01" min="0.01" max="{{ $invoice->balance_due }}" class="form-control" value="{{ old('amount', $invoice->balance_due) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="payment_link_provider" class="form-label">Provider</label>
                                    <select id="payment_link_provider" name="provider" class="form-select">
                                        <option value="" @selected(old('provider') === null)>Use Active Gateway</option>
                                        <option value="manual" @selected(old('provider') === 'manual')>Manual / Demo</option>
                                        <option value="stripe" @selected(old('provider') === 'stripe')>Stripe Ready</option>
                                        <option value="razorpay" @selected(old('provider') === 'razorpay')>Razorpay Ready</option>
                                        <option value="paypal" @selected(old('provider') === 'paypal')>PayPal Ready</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="payment_link_expires_at" class="form-label">Expires At</label>
                                    <input id="payment_link_expires_at" name="expires_at" type="datetime-local" class="form-control" value="{{ old('expires_at', now()->addDays(7)->format('Y-m-d\TH:i')) }}">
                                </div>
                                <div class="col-12">
                                    <label for="payment_link_notes" class="form-label">Internal Notes</label>
                                    <input id="payment_link_notes" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="Optional note for the billing team">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-dark mt-3">
                                <x-lucide-link class="w-4 h-4"/>
                                Generate Payment Link
                            </button>
                        </form>
                    @else
                        <div class="alert alert-light border">This invoice is fully paid. New payment links are not needed.</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Link</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Expires</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($invoice->paymentLinks->sortByDesc('created_at') as $paymentLink)
                                <tr>
                                    <td>
                                        <a href="{{ route('payment-links.show', $paymentLink->token) }}" target="_blank">{{ str($paymentLink->token)->limit(12) }}</a>
                                        <div class="text-muted text-xs">{{ route('payment-links.show', $paymentLink->token) }}</div>
                                    </td>
                                    <td>{{ $money->format($paymentLink->amount, $paymentLink->currency) }}</td>
                                    <td><span class="badge {{ $paymentLink->status === 'paid' ? 'badge-soft-success' : ($paymentLink->status === 'active' ? 'badge-soft-info' : 'badge-soft-secondary') }}">{{ str($paymentLink->status)->headline() }}</span></td>
                                    <td>{{ $paymentLink->expires_at?->format('Y-m-d H:i') ?: 'No expiry' }}</td>
                                    <td class="text-end">
                                        @if($paymentLink->status === 'active')
                                            <form method="POST" action="{{ route('payment-links.cancel', $paymentLink) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">Cancel</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No payment links generated yet.</td></tr>
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
                            <h2>Adjust Charges</h2>
                            <p>Add tax, discounts, damage charges, late fees, and billing notes.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('invoices.update', $invoice) }}">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="tax_profile_id" class="form-label">Tax Profile</label>
                                <select id="tax_profile_id" name="tax_profile_id" class="form-select" @disabled($invoice->status === 'paid')>
                                    <option value="">No tax profile</option>
                                    @foreach($taxProfiles as $profile)
                                        <option value="{{ $profile->id }}" @selected(old('tax_profile_id', $invoice->tax_profile_id) == $profile->id)>
                                            {{ $profile->name }} ({{ number_format((float) $profile->rate, 4) }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="currency" class="form-label">Invoice Currency</label>
                                <select id="currency" name="currency" class="form-select" @disabled($invoice->status === 'paid')>
                                    @foreach($currencies as $code => $label)
                                        <option value="{{ $code }}" @selected(old('currency', $invoice->currency) === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="exchange_rate" class="form-label">Exchange Rate</label>
                                <input id="exchange_rate" name="exchange_rate" type="number" step="0.00000001" min="0.00000001" class="form-control" value="{{ old('exchange_rate', $invoice->exchange_rate) }}" @disabled($invoice->status === 'paid')>
                                <div class="form-text">Use 1 {{ $invoice->currency }} to {{ auth()->user()->currentCompany?->currency ?? $invoice->base_currency }}. Base currency follows Company Setup.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="tax_amount" class="form-label">Tax</label>
                                <input id="tax_amount" name="tax_amount" type="number" step="0.01" min="0" class="form-control" value="{{ old('tax_amount', $invoice->tax_amount) }}" @disabled($invoice->status === 'paid')>
                                <div class="form-text">Changing the tax profile recalculates this amount from the selected profile rate.</div>
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
                            <h2>Credit Notes</h2>
                            <p>Issue invoice corrections, discounts, reversals, and customer refunds.</p>
                        </div>
                        <a href="{{ route('credit-notes.index') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                    </div>

                    @if($availableCreditAmount > 0)
                        <form method="POST" action="{{ route('invoices.credit-notes.store', $invoice) }}" class="mb-3">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="credit_date" class="form-label">Date</label>
                                    <input id="credit_date" name="credit_date" type="date" class="form-control" value="{{ old('credit_date', now()->toDateString()) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="credit_amount" class="form-label">Credit Amount</label>
                                    <input id="credit_amount" name="amount" type="number" step="0.01" min="0.01" max="{{ $availableCreditAmount }}" class="form-control" value="{{ old('amount') }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="reason" class="form-label">Reason</label>
                                    <select id="reason" name="reason" class="form-select" required>
                                        @foreach([
                                            'billing_correction' => 'Billing Correction',
                                            'discount_adjustment' => 'Discount Adjustment',
                                            'damage_reversal' => 'Damage Reversal',
                                            'return_adjustment' => 'Return Adjustment',
                                            'goodwill_credit' => 'Goodwill Credit',
                                            'other' => 'Other',
                                        ] as $reason => $label)
                                            <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="refund_amount" class="form-label">Refund Amount</label>
                                    <input id="refund_amount" name="refund_amount" type="number" step="0.01" min="0" max="{{ $availableCreditAmount }}" class="form-control" value="{{ old('refund_amount', 0) }}">
                                    <div class="form-text">Use only when cash or bank money is returned.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="refund_method" class="form-label">Refund Method</label>
                                    <select id="refund_method" name="refund_method" class="form-select">
                                        <option value="">No refund</option>
                                        @foreach($paymentMethods as $method => $label)
                                            <option value="{{ $method }}" @selected(old('refund_method') === $method)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="refund_reference" class="form-label">Refund Reference</label>
                                    <input id="refund_reference" name="refund_reference" class="form-control" value="{{ old('refund_reference') }}" placeholder="Transfer, cheque, or card ref">
                                </div>
                                <div class="col-12">
                                    <label for="credit_notes" class="form-label">Notes</label>
                                    <textarea id="credit_notes" name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-dark mt-3">Issue Credit Note</button>
                        </form>
                    @else
                        <div class="alert alert-light border">This invoice has already been fully credited.</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table modern-table align-middle">
                            <thead>
                            <tr>
                                <th>Date</th>
                                <th>Credit Note</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Refund</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($invoice->creditNotes as $creditNote)
                                <tr>
                                    <td>{{ $creditNote->credit_date?->format('Y-m-d') }}</td>
                                    <td><a href="{{ route('credit-notes.show', $creditNote) }}">{{ $creditNote->credit_note_number }}</a></td>
                                    <td>{{ str($creditNote->reason)->headline() }}</td>
                                    <td><span class="badge {{ $creditNote->status === 'voided' ? 'badge-soft-danger' : 'badge-soft-secondary' }}">{{ str($creditNote->status)->headline() }}</span></td>
                                    <td>{{ $money->format($creditNote->amount, $invoice->currency) }}</td>
                                    <td>{{ $money->format($creditNote->refund_amount, $invoice->currency) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('credit-notes.print', $creditNote) }}" class="btn btn-sm btn-outline-secondary" target="_blank">Print</a>
                                        @if($creditNote->status !== 'voided')
                                            <a href="{{ route('credit-notes.edit', $creditNote) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        @endif
                                        <a href="{{ route('credit-notes.download', $creditNote) }}" class="btn btn-sm btn-primary">PDF</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No credit notes issued yet.</td>
                                </tr>
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
                                    <td>{{ $money->format($payment->amount, $invoice->currency) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('payments.receipt.print', $payment) }}" class="btn btn-sm btn-outline-secondary" target="_blank">Print</a>
                                        <a href="{{ route('payments.receipt.download', $payment) }}" class="btn btn-sm btn-primary">PDF</a>
                                        <form method="POST" action="{{ route('payments.receipt.send', $payment) }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="recipient_email" value="{{ $invoice->customer?->email }}">
                                            <input type="hidden" name="recipient_name" value="{{ $invoice->customer?->contact_person }}">
                                            <input type="hidden" name="subject" value="Receipt {{ $payment->receiptNumber() }}">
                                            <input type="hidden" name="message" value="Please find the attached payment receipt for your records.">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Send</button>
                                        </form>
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
