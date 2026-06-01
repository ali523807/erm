@php
    $value = fn (string $field, mixed $default = null): mixed => old($field, $rental->{$field} ?? $default);
    $dateValue = function (string $field) use ($value): ?string {
        $fieldValue = $value($field);

        if ($fieldValue instanceof \Illuminate\Support\Carbon) {
            return $fieldValue->format('Y-m-d');
        }

        return $fieldValue;
    };
    $rentalProductOptions = $products->map(fn ($product) => [
        'id' => $product->id,
        'name' => $product->name,
        'code' => $product->equipment_code,
        'rate' => $product->default_rate,
        'rateType' => $product->default_rate_type,
    ])->values();
@endphp

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ $action }}" class="rental-form">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <section class="panel">
        <div class="panel-header align-items-start">
            <div>
                <h2>Customer and Schedule</h2>
                <p>Set the customer, rental period, movement dates, and delivery or pickup instructions.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                <select id="customer_id" name="customer_id" class="form-select" required>
                    <option value="">Select customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) $value('customer_id') === (string) $customer->id)>{{ $customer->company_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label for="rental_start_date" class="form-label">Rental Start</label>
                <input id="rental_start_date" name="rental_start_date" type="date" class="form-control" value="{{ $dateValue('rental_start_date') }}" required>
            </div>
            <div class="col-lg-2">
                <label for="rental_end_date" class="form-label">Rental End</label>
                <input id="rental_end_date" name="rental_end_date" type="date" class="form-control" value="{{ $dateValue('rental_end_date') }}" required>
            </div>
            <div class="col-lg-2">
                <label for="delivery_date" class="form-label">Delivery Date</label>
                <input id="delivery_date" name="delivery_date" type="date" class="form-control" value="{{ $dateValue('delivery_date') }}">
            </div>
            <div class="col-lg-2">
                <label for="pickup_date" class="form-label">Pickup Date</label>
                <input id="pickup_date" name="pickup_date" type="date" class="form-control" value="{{ $dateValue('pickup_date') }}">
            </div>
            <div class="col-lg-8">
                <label for="delivery_location" class="form-label">Delivery Location</label>
                <input id="delivery_location" name="delivery_location" class="form-control" value="{{ $value('delivery_location') }}" placeholder="Customer site, branch pickup, warehouse, or event venue">
            </div>
            <div class="col-lg-4">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select">
                    @foreach($statuses as $statusValue => $label)
                        <option value="{{ $statusValue }}" @selected($value('status', 'reserved') === $statusValue)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label for="notes" class="form-label">Operational Notes</label>
                <textarea id="notes" name="notes" rows="3" class="form-control" placeholder="Delivery instructions, special terms, crew notes, or return expectations.">{{ $value('notes') }}</textarea>
            </div>
        </div>
    </section>

    <section class="panel mt-3">
        <div class="panel-header align-items-start">
            <div>
                <h2>Rental Items</h2>
                <p>Add each equipment line with its own rental period, rate, deposit, and item status.</p>
            </div>
        </div>

        <div id="rentalItems" class="d-grid gap-3"></div>
        <button type="button" id="addRentalItemBtn" class="btn btn-outline-primary btn-sm mt-3">
            <i class="bi bi-plus-circle"></i> Add Item
        </button>
    </section>

    <section class="panel mt-3">
        <div class="panel-header align-items-start">
            <div>
                <h2>Totals</h2>
                <p>Amounts are calculated from the equipment lines and can later feed invoices and deposits.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <label class="form-label">Rental Subtotal</label>
                <div class="form-control bg-light fw-bold" id="rentalSubtotal">0.00</div>
            </div>
            <div class="col-lg-4">
                <label class="form-label">Deposit Total</label>
                <div class="form-control bg-light" id="rentalDeposit">0.00</div>
            </div>
            <div class="col-lg-4">
                <label class="form-label">Grand Total</label>
                <div class="form-control bg-light fw-bold" id="rentalGrandTotal">0.00</div>
            </div>
        </div>
    </section>

    <div class="d-flex justify-content-end gap-2 mt-3">
        <a href="{{ $rental->exists ? route('rentals.show', $rental) : route('rentals.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-dark">{{ $submitLabel }}</button>
    </div>
</form>

@push('js')
    <script type="module">
        const products = @json($rentalProductOptions);
        let rentalItems = @json($items);

        function escapeHtml(value) {
            return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        function renderItems() {
            const container = $('#rentalItems');
            container.empty();

            rentalItems.forEach((item, index) => {
                const productOptions = products.map((product) => {
                    const selected = String(product.id) === String(item.product_id) ? 'selected' : '';
                    return `<option value="${product.id}" data-rate="${product.rate || 0}" data-rate-type="${product.rateType || 'days'}" ${selected}>${escapeHtml(product.name)}${product.code ? ` - ${escapeHtml(product.code)}` : ''}</option>`;
                }).join('');

                container.append(`
                    <div class="inline-edit-form rental-item-row" data-index="${index}">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-3">
                                <label class="form-label">Equipment</label>
                                <select name="items[${index}][product_id]" class="form-select rental-product" required>
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
                                <input name="items[${index}][no_of_duration]" type="number" step="0.01" min="0.01" class="form-control rental-calc" value="${escapeHtml(item.no_of_duration || 1)}" required>
                            </div>
                            <div class="col-lg-1">
                                <label class="form-label">Type</label>
                                <select name="items[${index}][duration_type]" class="form-select">
                                    <option value="days" ${item.duration_type === 'days' ? 'selected' : ''}>Days</option>
                                    <option value="weeks" ${item.duration_type === 'weeks' ? 'selected' : ''}>Weeks</option>
                                    <option value="months" ${item.duration_type === 'months' ? 'selected' : ''}>Months</option>
                                </select>
                            </div>
                            <div class="col-lg-1">
                                <label class="form-label">Rate</label>
                                <input name="items[${index}][rate]" type="number" step="0.01" min="0" class="form-control rental-calc rental-rate" value="${escapeHtml(item.rate || 0)}" required>
                            </div>
                            <div class="col-lg-1">
                                <label class="form-label">Deposit</label>
                                <input name="items[${index}][deposit_amount]" type="number" step="0.01" min="0" class="form-control rental-calc" value="${escapeHtml(item.deposit_amount || 0)}">
                            </div>
                            <div class="col-lg-1">
                                <button type="button" class="btn btn-outline-danger btn-sm remove-rental-item w-100">Remove</button>
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label">Item Status</label>
                                <select name="items[${index}][status]" class="form-select">
                                    <option value="reserved" ${item.status === 'reserved' ? 'selected' : ''}>Reserved</option>
                                    <option value="on_rent" ${item.status === 'on_rent' ? 'selected' : ''}>On Rent</option>
                                    <option value="returned" ${item.status === 'returned' ? 'selected' : ''}>Returned</option>
                                    <option value="cancelled" ${item.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label">Line Total</label>
                                <div class="form-control bg-light line-total">0.00</div>
                            </div>
                        </div>
                    </div>
                `);
            });

            calculateTotals();
        }

        function syncItemsFromDom() {
            rentalItems = $('.rental-item-row').map(function () {
                const row = $(this);
                return {
                    product_id: row.find('[name$="[product_id]"]').val(),
                    start_date: row.find('[name$="[start_date]"]').val(),
                    end_date: row.find('[name$="[end_date]"]').val(),
                    no_of_duration: row.find('[name$="[no_of_duration]"]').val(),
                    duration_type: row.find('[name$="[duration_type]"]').val(),
                    rate: row.find('[name$="[rate]"]').val(),
                    deposit_amount: row.find('[name$="[deposit_amount]"]').val(),
                    status: row.find('[name$="[status]"]').val(),
                };
            }).get();
        }

        function calculateTotals() {
            let subtotal = 0;
            let deposit = 0;

            $('.rental-item-row').each(function () {
                const row = $(this);
                const lineTotal = Number(row.find('[name$="[no_of_duration]"]').val() || 0)
                    * Number(row.find('[name$="[rate]"]').val() || 0);

                subtotal += lineTotal;
                deposit += Number(row.find('[name$="[deposit_amount]"]').val() || 0);
                row.find('.line-total').text(lineTotal.toFixed(2));
            });

            $('#rentalSubtotal').text(subtotal.toFixed(2));
            $('#rentalDeposit').text(deposit.toFixed(2));
            $('#rentalGrandTotal').text((subtotal + deposit).toFixed(2));
        }

        $(function () {
            renderItems();

            $('#addRentalItemBtn').on('click', function () {
                syncItemsFromDom();
                rentalItems.push({
                    product_id: '',
                    start_date: $('#rental_start_date').val(),
                    end_date: $('#rental_end_date').val(),
                    duration_type: 'days',
                    no_of_duration: 1,
                    rate: 0,
                    deposit_amount: 0,
                    status: $('#status').val() === 'active' ? 'on_rent' : 'reserved',
                });
                renderItems();
            });

            $('#rentalItems').on('click', '.remove-rental-item', function () {
                syncItemsFromDom();
                rentalItems.splice($(this).closest('.rental-item-row').data('index'), 1);
                renderItems();
            });

            $('#rentalItems').on('change', '.rental-product', function () {
                const selected = $(this).find(':selected');
                const row = $(this).closest('.rental-item-row');
                if (Number(row.find('.rental-rate').val() || 0) === 0) {
                    row.find('.rental-rate').val(selected.data('rate') || 0);
                }
                calculateTotals();
            });

            $('#rentalItems').on('input change', '.rental-calc', calculateTotals);
        });
    </script>
@endpush
