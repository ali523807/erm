@php
    $suffix = $expense?->id ?? 'new';
    $fieldValue = fn (string $field, mixed $default = null): mixed => old($field, $expense?->{$field} ?? $default);
@endphp

<div class="row g-3">
    <div class="col-md-3">
        <label for="expense_number_{{ $suffix }}" class="form-label">Expense Number</label>
        <input id="expense_number_{{ $suffix }}" name="expense_number" class="form-control" value="{{ $fieldValue('expense_number') }}" placeholder="Auto generated">
    </div>
    <div class="col-md-3">
        <label for="expense_date_{{ $suffix }}" class="form-label">Expense Date</label>
        <input id="expense_date_{{ $suffix }}" name="expense_date" type="date" class="form-control" value="{{ $fieldValue('expense_date', now()->toDateString()) instanceof \Illuminate\Support\Carbon ? $fieldValue('expense_date')->toDateString() : $fieldValue('expense_date', now()->toDateString()) }}" required>
    </div>
    <div class="col-md-3">
        <label for="category_{{ $suffix }}" class="form-label">Category</label>
        <select id="category_{{ $suffix }}" name="category" class="form-select" required>
            @foreach($categories as $category => $label)
                <option value="{{ $category }}" @selected($fieldValue('category', 'fuel') === $category)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label for="vendor_name_{{ $suffix }}" class="form-label">Vendor / Payee</label>
        <input id="vendor_name_{{ $suffix }}" name="vendor_name" class="form-control" value="{{ $fieldValue('vendor_name') }}" placeholder="Fuel station, driver, supplier">
    </div>

    <div class="col-md-4">
        <label for="rental_id_{{ $suffix }}" class="form-label">Rental Job</label>
        <select id="rental_id_{{ $suffix }}" name="rental_id" class="form-select">
            <option value="">General / Not linked</option>
            @foreach($rentals as $rental)
                <option value="{{ $rental->id }}" @selected((string) $fieldValue('rental_id') === (string) $rental->id)>RTN-{{ $rental->id }} - {{ $rental->customer?->company_name }}</option>
            @endforeach
        </select>
        <div class="form-text">Use this for job profitability and rental-level cost history.</div>
    </div>
    <div class="col-md-4">
        <label for="customer_id_{{ $suffix }}" class="form-label">Customer</label>
        <select id="customer_id_{{ $suffix }}" name="customer_id" class="form-select">
            <option value="">No customer link</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" @selected((string) $fieldValue('customer_id') === (string) $customer->id)>{{ $customer->company_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label for="product_id_{{ $suffix }}" class="form-label">Equipment</label>
        <select id="product_id_{{ $suffix }}" name="product_id" class="form-select">
            <option value="">No equipment link</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}" @selected((string) $fieldValue('product_id') === (string) $product->id)>{{ $product->name }}{{ $product->equipment_code ? ' - '.$product->equipment_code : '' }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-2">
        <label for="currency_{{ $suffix }}" class="form-label">Currency</label>
        <input id="currency_{{ $suffix }}" name="currency" class="form-control text-uppercase" maxlength="3" value="{{ $fieldValue('currency', auth()->user()->currentCompany?->currency ?? 'USD') }}" required>
    </div>
    <div class="col-md-2">
        <label for="amount_{{ $suffix }}" class="form-label">Amount</label>
        <input id="amount_{{ $suffix }}" name="amount" type="number" step="0.01" min="0" class="form-control" value="{{ $fieldValue('amount', 0) }}" required>
    </div>
    <div class="col-md-2">
        <label for="tax_amount_{{ $suffix }}" class="form-label">Tax</label>
        <input id="tax_amount_{{ $suffix }}" name="tax_amount" type="number" step="0.01" min="0" class="form-control" value="{{ $fieldValue('tax_amount', 0) }}">
    </div>
    <div class="col-md-3">
        <label for="payment_status_{{ $suffix }}" class="form-label">Payment Status</label>
        <select id="payment_status_{{ $suffix }}" name="payment_status" class="form-select" required>
            @foreach($paymentStatuses as $status => $label)
                <option value="{{ $status }}" @selected($fieldValue('payment_status', 'paid') === $status)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label for="payment_method_{{ $suffix }}" class="form-label">Payment Method</label>
        <select id="payment_method_{{ $suffix }}" name="payment_method" class="form-select">
            <option value="">Not selected</option>
            @foreach($paymentMethods as $method => $label)
                <option value="{{ $method }}" @selected($fieldValue('payment_method') === $method)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label for="reference_{{ $suffix }}" class="form-label">Reference</label>
        <input id="reference_{{ $suffix }}" name="reference" class="form-control" value="{{ $fieldValue('reference') }}" placeholder="Receipt, bill, card, or transfer reference">
    </div>
    <div class="col-md-8">
        <label for="description_{{ $suffix }}" class="form-label">Description</label>
        <input id="description_{{ $suffix }}" name="description" class="form-control" value="{{ $fieldValue('description') }}" placeholder="Short notes for finance and operations">
    </div>
    <div class="col-12">
        <label class="form-check">
            <input name="is_billable" value="1" type="checkbox" class="form-check-input" @checked((bool) $fieldValue('is_billable', false))>
            <span class="form-check-label">Billable or recoverable from customer</span>
        </label>
    </div>
</div>
