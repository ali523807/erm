@php($money = app(\App\Support\Money::class))

<div class="row g-3">
    <div class="col-md-4">
        <label for="credit_date" class="form-label">Credit Date</label>
        <input id="credit_date" name="credit_date" type="date" class="form-control" value="{{ old('credit_date', $creditNote->credit_date?->format('Y-m-d')) }}" required>
        <div class="form-text">Accounting date for this customer adjustment.</div>
    </div>
    <div class="col-md-4">
        <label for="amount" class="form-label">Credit Amount</label>
        <input id="amount" name="amount" type="number" step="0.01" min="0.01" max="{{ $availableCreditAmount }}" class="form-control" value="{{ old('amount', $creditNote->amount) }}" required>
        <div class="form-text">Maximum available: {{ $money->format($availableCreditAmount, $creditNote->invoice?->currency) }}.</div>
    </div>
    <div class="col-md-4">
        <label for="reason" class="form-label">Reason</label>
        <select id="reason" name="reason" class="form-select" required>
            @foreach($reasons as $reason => $label)
                <option value="{{ $reason }}" @selected(old('reason', $creditNote->reason) === $reason)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="form-text">Used for reports and audit review.</div>
    </div>
    <div class="col-md-4">
        <label for="refund_amount" class="form-label">Refund Amount</label>
        <input id="refund_amount" name="refund_amount" type="number" step="0.01" min="0" max="{{ $availableCreditAmount }}" class="form-control" value="{{ old('refund_amount', $creditNote->refund_amount) }}">
        <div class="form-text">Enter only when money is returned to the customer.</div>
    </div>
    <div class="col-md-4">
        <label for="refund_method" class="form-label">Refund Method</label>
        <select id="refund_method" name="refund_method" class="form-select">
            <option value="">No refund</option>
            @foreach($refundMethods as $method => $label)
                <option value="{{ $method }}" @selected(old('refund_method', $creditNote->refund_method) === $method)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="form-text">How the refund was paid, if applicable.</div>
    </div>
    <div class="col-md-4">
        <label for="refund_reference" class="form-label">Refund Reference</label>
        <input id="refund_reference" name="refund_reference" class="form-control" value="{{ old('refund_reference', $creditNote->refund_reference) }}" placeholder="Transfer, cheque, or card ref">
        <div class="form-text">Optional banking or receipt reference.</div>
    </div>
    <div class="col-12">
        <label for="notes" class="form-label">Notes</label>
        <textarea id="notes" name="notes" rows="4" class="form-control">{{ old('notes', $creditNote->notes) }}</textarea>
        <div class="form-text">Internal explanation that appears on the credit note record.</div>
    </div>
</div>
