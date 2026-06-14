@php
    $value = fn (string $field, mixed $default = null): mixed => old($field, $quote->{$field} ?? $default);
    $dateValue = function (string $field) use ($value): ?string {
        $fieldValue = $value($field);

        if ($fieldValue instanceof \Illuminate\Support\Carbon) {
            return $fieldValue->format('Y-m-d');
        }

        return $fieldValue;
    };
@endphp

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ $action }}" class="quote-form">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <section class="panel">
        <div class="panel-header align-items-start">
            <div>
                <h2>Customer and Dates</h2>
                <p>Set who the quote is for, when it is valid, and the rental period being offered.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                <select id="customer_id" name="customer_id" class="form-select js-customer-select" required>
                    <option value="">Select customer</option>
                    @if($selectedCustomer)
                        <option value="{{ $selectedCustomer->id }}" selected>{{ collect([$selectedCustomer->company_name, $selectedCustomer->contact_person])->filter()->join(' - ') }}</option>
                    @endif
                </select>
            </div>
            <div class="col-lg-2">
                <label for="quote_date" class="form-label">Quote Date</label>
                <input id="quote_date" name="quote_date" type="date" class="form-control" value="{{ $dateValue('quote_date') }}" required>
            </div>
            <div class="col-lg-2">
                <label for="valid_until" class="form-label">Valid Until</label>
                <input id="valid_until" name="valid_until" type="date" class="form-control" value="{{ $dateValue('valid_until') }}">
            </div>
            <div class="col-lg-2">
                <label for="rental_start_date" class="form-label">Rental Start</label>
                <input id="rental_start_date" name="rental_start_date" type="date" class="form-control" value="{{ $dateValue('rental_start_date') }}" required>
            </div>
            <div class="col-lg-2">
                <label for="rental_end_date" class="form-label">Rental End</label>
                <input id="rental_end_date" name="rental_end_date" type="date" class="form-control" value="{{ $dateValue('rental_end_date') }}" required>
            </div>
            <div class="col-lg-8">
                <label for="delivery_location" class="form-label">Delivery Location</label>
                <input id="delivery_location" name="delivery_location" class="form-control" value="{{ $value('delivery_location') }}" placeholder="Customer site, branch pickup, or event venue">
            </div>
            <div class="col-lg-4">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select">
                    @foreach($statuses as $statusValue => $label)
                        <option value="{{ $statusValue }}" @selected($value('status', 'draft') === $statusValue)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label for="currency" class="form-label">Quote Currency</label>
                <select id="currency" name="currency" class="form-select">
                    @foreach($currencies as $code => $label)
                        <option value="{{ $code }}" @selected($value('currency', auth()->user()->currentCompany?->currency ?? 'USD') === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label for="exchange_rate" class="form-label">Exchange Rate</label>
                <input id="exchange_rate" name="exchange_rate" type="number" step="0.00000001" min="0.00000001" class="form-control" value="{{ $value('exchange_rate', 1) }}">
                <div class="form-text">Use 1 quote currency to {{ auth()->user()->currentCompany?->currency ?? 'USD' }}.</div>
            </div>
        </div>
    </section>

    <section class="panel mt-3">
        <div class="panel-header align-items-start">
            <div>
                <h2>Quote Items</h2>
                <p>Select each equipment asset individually. Quantity is not used for serialized rental equipment.</p>
            </div>
        </div>

        <div id="quoteItems" class="d-grid gap-3"></div>
        <button type="button" id="addQuoteItemBtn" class="btn btn-outline-primary btn-sm mt-3">
            <i class="bi bi-plus-circle"></i> Add Item
        </button>
    </section>

    <section class="panel mt-3">
        <div class="panel-header align-items-start">
            <div>
                <h2>Totals and Terms</h2>
                <p>Discount and tax are entered manually for now; invoice automation can build on this later.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-3">
                <label for="discount_amount" class="form-label">Discount</label>
                <input id="discount_amount" name="discount_amount" type="number" step="0.01" min="0" class="form-control" value="{{ $value('discount_amount', 0) }}">
            </div>
            <div class="col-lg-3">
                <label for="tax_amount" class="form-label">Tax</label>
                <input id="tax_amount" name="tax_amount" type="number" step="0.01" min="0" class="form-control" value="{{ $value('tax_amount', 0) }}">
            </div>
            <div class="col-lg-3">
                <label class="form-label">Calculated Subtotal</label>
                <div class="form-control bg-light" id="quoteSubtotal">0.00</div>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Estimated Total</label>
                <div class="form-control bg-light fw-bold" id="quoteTotal">0.00</div>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Base Total</label>
                <div class="form-control bg-light fw-bold" id="quoteBaseTotal">0.00</div>
            </div>
            <div class="col-lg-6">
                <label for="terms" class="form-label">Terms</label>
                <textarea id="terms" name="terms" rows="4" class="form-control" placeholder="Payment terms, deposit rules, cancellation policy, delivery terms.">{{ $value('terms') }}</textarea>
            </div>
            <div class="col-lg-6">
                <label for="notes" class="form-label">Internal Notes</label>
                <textarea id="notes" name="notes" rows="4" class="form-control" placeholder="Private sales or operations notes.">{{ $value('notes') }}</textarea>
            </div>
        </div>
    </section>

    <div class="d-flex justify-content-end gap-2 mt-3">
        <a href="{{ route('quotes.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-dark">{{ $submitLabel }}</button>
    </div>
</form>

@push('js')
    <script type="module">
        const customerLookupUrl = @json(route('lookups.customers'));
        const productLookupUrl = @json(route('lookups.products'));
        const selectedProducts = @json($selectedProducts);
        let quoteItems = @json($items);

        function escapeHtml(value) {
            return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        function initAjaxSelect(select, url, placeholder) {
            select.select2({
                ajax: {
                    url,
                    dataType: 'json',
                    delay: 250,
                    data: (params) => ({q: params.term || ''}),
                    processResults: (data) => data,
                },
                minimumInputLength: 1,
                placeholder,
                allowClear: true,
                theme: 'bootstrap-5',
                width: '100%',
            });
        }

        function selectedProductMeta(productId) {
            return selectedProducts[String(productId)] || selectedProducts[Number(productId)] || null;
        }

        function initProductSelects() {
            $('.quote-product').each(function () {
                const select = $(this);
                if (select.data('select2')) {
                    return;
                }

                initAjaxSelect(select, productLookupUrl, 'Search equipment by name, code, serial, or category');
                const product = selectedProductMeta(select.val());
                if (product) {
                    select.data('productMeta', product);
                }

                select.on('select2:select', function (event) {
                    $(this).data('productMeta', event.params.data);
                    $(this).trigger('change');
                });
            });
        }

        function renderItems() {
            const container = $('#quoteItems');
            container.empty();

            quoteItems.forEach((item, index) => {
                const selectedProduct = selectedProductMeta(item.product_id);
                const productOptions = selectedProduct
                    ? `<option value="${selectedProduct.id}" selected>${escapeHtml(selectedProduct.text)}</option>`
                    : '';

                container.append(`
                    <div class="inline-edit-form quote-item-row" data-index="${index}">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-3">
                                <label class="form-label">Equipment</label>
                                <select name="items[${index}][product_id]" class="form-select quote-product" required>
                                    <option value="">Select equipment</option>
                                    ${productOptions}
                                </select>
                            </div>
                            <div class="col-lg-2">
                                <label class="form-label">Start</label>
                                <input name="items[${index}][start_date]" type="date" class="form-control" value="${escapeHtml(item.start_date)}" required>
                            </div>
                            <div class="col-lg-2">
                                <label class="form-label">End</label>
                                <input name="items[${index}][end_date]" type="date" class="form-control" value="${escapeHtml(item.end_date)}" required>
                            </div>
                            <div class="col-lg-1">
                                <label class="form-label">Duration</label>
                                <input name="items[${index}][no_of_duration]" type="number" step="0.01" min="0.01" class="form-control quote-calc" value="${escapeHtml(item.no_of_duration || 1)}" required>
                            </div>
                            <div class="col-lg-1">
                                <label class="form-label">Rate Type</label>
                                <select name="items[${index}][duration_type]" class="form-select quote-rate-type">
                                    <option value="hourly" ${item.duration_type === 'hourly' ? 'selected' : ''}>Hourly</option>
                                    <option value="daily" ${['daily', 'days'].includes(item.duration_type) ? 'selected' : ''}>Daily</option>
                                    <option value="weekly" ${['weekly', 'weeks'].includes(item.duration_type) ? 'selected' : ''}>Weekly</option>
                                    <option value="monthly" ${['monthly', 'months'].includes(item.duration_type) ? 'selected' : ''}>Monthly</option>
                                    <option value="custom" ${item.duration_type === 'custom' ? 'selected' : ''}>Custom</option>
                                </select>
                            </div>
                            <div class="col-lg-1">
                                <label class="form-label">Rate</label>
                                <input name="items[${index}][rate]" type="number" step="0.01" min="0" class="form-control quote-calc quote-rate" value="${escapeHtml(item.rate || 0)}" required>
                            </div>
                            <div class="col-lg-2">
                                <button type="button" class="btn btn-outline-danger btn-sm remove-quote-item w-100">Remove</button>
                            </div>
                            <div class="col-lg-2">
                                <label class="form-label">Deposit</label>
                                <input name="items[${index}][deposit_amount]" type="number" step="0.01" min="0" class="form-control" value="${escapeHtml(item.deposit_amount || 0)}">
                            </div>
                            <div class="col-lg-10">
                                <label class="form-label">Line Notes</label>
                                <input name="items[${index}][notes]" class="form-control" value="${escapeHtml(item.notes || '')}" placeholder="Accessories, operator, delivery, or condition notes">
                            </div>
                        </div>
                    </div>
                `);
            });

            initProductSelects();
            calculateTotals();
        }

        function syncItemsFromDom() {
            quoteItems = $('.quote-item-row').map(function () {
                const row = $(this);
                return {
                    product_id: row.find('[name$="[product_id]"]').val(),
                    start_date: row.find('[name$="[start_date]"]').val(),
                    end_date: row.find('[name$="[end_date]"]').val(),
                    no_of_duration: row.find('[name$="[no_of_duration]"]').val(),
                    duration_type: row.find('[name$="[duration_type]"]').val(),
                    rate: row.find('[name$="[rate]"]').val(),
                    deposit_amount: row.find('[name$="[deposit_amount]"]').val(),
                    notes: row.find('[name$="[notes]"]').val(),
                };
            }).get();
        }

        function calculateTotals() {
            let subtotal = 0;
            $('.quote-item-row').each(function () {
                const row = $(this);
                subtotal += Number(row.find('[name$="[no_of_duration]"]').val() || 0)
                    * Number(row.find('[name$="[rate]"]').val() || 0);
            });
            const discount = Number($('#discount_amount').val() || 0);
            const tax = Number($('#tax_amount').val() || 0);
            const exchangeRate = Number($('#exchange_rate').val() || 1);
            const baseCurrency = @json(auth()->user()->currentCompany?->currency ?? 'USD');
            $('#quoteSubtotal').text(subtotal.toFixed(2));
            const total = Math.max(0, subtotal - discount + tax);
            $('#quoteTotal').text(`${$('#currency').val()} ${total.toFixed(2)}`);
            $('#quoteBaseTotal').text(`${baseCurrency} ${(total * exchangeRate).toFixed(2)}`);
        }

        $(function () {
            renderItems();

            $('#addQuoteItemBtn').on('click', function () {
                syncItemsFromDom();
                quoteItems.push({
                    product_id: '',
                    start_date: $('#rental_start_date').val(),
                    end_date: $('#rental_end_date').val(),
                    duration_type: 'daily',
                    no_of_duration: 1,
                    rate: 0,
                    deposit_amount: 0,
                    notes: '',
                });
                renderItems();
            });

            $('#quoteItems').on('click', '.remove-quote-item', function () {
                syncItemsFromDom();
                quoteItems.splice($(this).closest('.quote-item-row').data('index'), 1);
                renderItems();
            });

            $('#quoteItems').on('change', '.quote-product', function () {
                const row = $(this).closest('.quote-item-row');
                const product = $(this).data('productMeta') || selectedProductMeta($(this).val()) || {};
                const rateType = product.rateType || 'daily';
                const rates = product.rates || {};
                row.find('.quote-rate-type').val(rateType);
                if (Number(row.find('.quote-rate').val() || 0) === 0) {
                    row.find('.quote-rate').val(rates[rateType] || product.rate || 0);
                }
                if (Number(row.find('[name$="[deposit_amount]"]').val() || 0) === 0) {
                    row.find('[name$="[deposit_amount]"]').val(product.deposit || 0);
                }
                calculateTotals();
            });

            $('#quoteItems').on('change', '.quote-rate-type', function () {
                const row = $(this).closest('.quote-item-row');
                const productSelect = row.find('.quote-product');
                const product = productSelect.data('productMeta') || selectedProductMeta(productSelect.val()) || {};
                const rates = product.rates || {};
                row.find('.quote-rate').val(rates[$(this).val()] || 0);
                calculateTotals();
            });

            $('#quoteItems').on('input change', '.quote-calc, input', calculateTotals);
            $('#discount_amount, #tax_amount, #exchange_rate, #currency').on('input change', calculateTotals);
            initAjaxSelect($('#customer_id'), customerLookupUrl, 'Search customer by company, contact, email, or phone');
        });
    </script>
@endpush
