@php
    $printMode = $printMode ?? false;
    $money = app(\App\Support\Money::class);
    $currency = $creditNote->invoice?->currency;
@endphp

<div class="{{ $printMode ? 'document-grid' : 'row g-3' }}">
    <div class="{{ $printMode ? 'document-col' : 'col-xl-7' }}">
        <section class="{{ $printMode ? 'box' : 'panel h-100' }}">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Credit Details</h2>
                    <p>Invoice adjustment and customer account impact.</p>
                </div>
            </div>
            <dl class="detail-grid">
                <div>
                    <dt>Credit Note</dt>
                    <dd>{{ $creditNote->credit_note_number }}</dd>
                </div>
                <div>
                    <dt>Date</dt>
                    <dd>{{ $creditNote->credit_date?->format('Y-m-d') }}</dd>
                </div>
                <div>
                    <dt>Invoice</dt>
                    <dd>{{ $creditNote->invoice?->invoice_number ?: '-' }}</dd>
                </div>
                <div>
                    <dt>Reason</dt>
                    <dd>{{ str($creditNote->reason)->headline() }}</dd>
                </div>
                <div>
                    <dt>Status</dt>
                    <dd>
                        <span class="badge {{ $creditNote->status === 'voided' ? 'badge-soft-danger' : 'badge-soft-secondary' }}">
                            {{ str($creditNote->status)->headline() }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt>Rental</dt>
                    <dd>{{ $creditNote->invoice?->rental_id ? 'RTN-'.$creditNote->invoice->rental_id : '-' }}</dd>
                </div>
            </dl>

            @if($creditNote->notes)
                <div class="mt-3">
                    <h3 class="h6">Notes</h3>
                    <p class="text-muted mb-0">{{ $creditNote->notes }}</p>
                </div>
            @endif
        </section>
    </div>

    <div class="{{ $printMode ? 'document-col right' : 'col-xl-5' }}">
        <section class="{{ $printMode ? 'box' : 'panel h-100' }}">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Amount</h2>
                    <p>Credit applied and refund recorded.</p>
                </div>
            </div>
            <dl class="detail-grid">
                <div>
                    <dt>Credit Amount</dt>
                    <dd>{{ $money->format($creditNote->amount, $currency) }}</dd>
                </div>
                <div>
                    <dt>Refund Amount</dt>
                    <dd>{{ $money->format($creditNote->refund_amount, $currency) }}</dd>
                </div>
                <div>
                    <dt>Refund Method</dt>
                    <dd>{{ $creditNote->refund_method ? str($creditNote->refund_method)->headline() : '-' }}</dd>
                </div>
                <div>
                    <dt>Refund Reference</dt>
                    <dd>{{ $creditNote->refund_reference ?: '-' }}</dd>
                </div>
                <div>
                    <dt>Invoice Balance</dt>
                    <dd>{{ $money->format($creditNote->invoice?->balance_due, $currency) }}</dd>
                </div>
            </dl>
        </section>
    </div>
</div>
