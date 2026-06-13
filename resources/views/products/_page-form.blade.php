@php
    $fieldValue = fn (string $field, mixed $default = null): mixed => old($field, $product->{$field} ?? $default);

    $dateValue = function (string $field) use ($fieldValue): ?string {
        $value = $fieldValue($field);

        if ($value instanceof \Illuminate\Support\Carbon) {
            return $value->format('Y-m-d');
        }

        return $value;
    };
@endphp

<form method="POST" action="{{ $action }}" class="equipment-form">
    @csrf

    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-3">
        <div class="col-12">
            <div class="panel">
                <div class="panel-header align-items-start">
                    <div>
                        <h2>Basic Identity</h2>
                        <p>Name the asset clearly and connect it to the right category for reporting, pricing, and availability.</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-4">
                        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                        <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                            <option value="">Select category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) $fieldValue('category_id') === (string) $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Use categories broadly, such as Excavators, Camera Gear, Tables, Vehicles, Generators, or Safety Equipment. Category templates can suggest specifications below.</div>
                        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-4">
                        <label for="name" class="form-label">Equipment Name <span class="text-danger">*</span></label>
                        <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ $fieldValue('name') }}" placeholder="Example: 20 kVA Generator Kit" required>
                        <div class="form-text">Use the name your team and customers will recognize in quotations and rentals.</div>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-4">
                        <label for="equipment_code" class="form-label">Equipment / Asset Code</label>
                        <input id="equipment_code" name="equipment_code" class="form-control @error('equipment_code') is-invalid @enderror" value="{{ $fieldValue('equipment_code') }}" placeholder="EQ-0001">
                        <div class="form-text">Optional internal code, barcode, RFID tag, plate number, or inventory reference.</div>
                        @error('equipment_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-4">
                        <label for="serial_number" class="form-label">Serial Number</label>
                        <input id="serial_number" name="serial_number" class="form-control @error('serial_number') is-invalid @enderror" value="{{ $fieldValue('serial_number') }}" placeholder="Manufacturer serial, VIN, IMEI, batch no.">
                        <div class="form-text">Leave blank when the item has no unique manufacturer number.</div>
                        @error('serial_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-4">
                        <label for="brand" class="form-label">Brand / Make</label>
                        <input id="brand" name="brand" class="form-control @error('brand') is-invalid @enderror" value="{{ $fieldValue('brand') }}" placeholder="JCB, Canon, IKEA, CAT">
                        <div class="form-text">Works for manufacturer, make, supplier brand, or custom-built assets.</div>
                        @error('brand')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-4">
                        <label for="model" class="form-label">Model / Variant</label>
                        <input id="model" name="model" class="form-control @error('model') is-invalid @enderror" value="{{ $fieldValue('model') }}" placeholder="Model, size, version, or kit type">
                        <div class="form-text">Use this for model numbers, dimensions, capacity, edition, or package variant.</div>
                        @error('model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea id="description" name="description" rows="4" class="form-control @error('description') is-invalid @enderror" placeholder="Describe what is included, main capacity, accessories, restrictions, or customer-facing notes." required>{{ $fieldValue('description') }}</textarea>
                        <div class="form-text">Keep this useful for operations and future customer documents. For kits, mention included parts.</div>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="panel">
                <div class="panel-header align-items-start">
                    <div>
                        <h2>Availability and Location</h2>
                        <p>Set the operational state and where the asset physically belongs when it is not on rent.</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-3">
                        <label for="status" class="form-label">Equipment Status <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                            @foreach($equipmentStatuses as $value => $label)
                                <option value="{{ $value }}" @selected($fieldValue('status', 'available') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Use this for rental readiness, maintenance, damage, retirement, or loss tracking.</div>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-3">
                        <label for="condition" class="form-label">Condition</label>
                        <input id="condition" name="condition" class="form-control @error('condition') is-invalid @enderror" value="{{ $fieldValue('condition') }}" placeholder="New, Good, Service Due">
                        <div class="form-text">Short physical condition note shown to the operations team.</div>
                        @error('condition')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    @if($canManageLocations)
                        <div class="col-lg-3">
                            <label for="branch_id" class="form-label">Branch</label>
                            <select id="branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                                <option value="">Unassigned</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) $fieldValue('branch_id') === (string) $branch->id)>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Primary business branch responsible for this asset.</div>
                            @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-lg-3">
                            <label for="warehouse_id" class="form-label">Warehouse / Yard</label>
                            <select id="warehouse_id" name="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror">
                                <option value="">Unassigned</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" @selected((string) $fieldValue('warehouse_id') === (string) $warehouse->id)>
                                        {{ $warehouse->branch?->name }} / {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Use for yards, depots, stores, showrooms, rooms, or physical stock areas.</div>
                            @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-lg-6">
                            <label for="storage_location_id" class="form-label">Storage Location</label>
                            <select id="storage_location_id" name="storage_location_id" class="form-select @error('storage_location_id') is-invalid @enderror">
                                <option value="">Unassigned</option>
                                @foreach($storageLocations as $location)
                                    <option value="{{ $location->id }}" @selected((string) $fieldValue('storage_location_id') === (string) $location->id)>
                                        {{ $location->warehouse?->name }} / {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Shelf, bay, rack, room, parking slot, bin, floor zone, or any exact storage point.</div>
                            @error('storage_location_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endif

                    <div class="{{ $canManageLocations ? 'col-lg-6' : 'col-lg-3' }}">
                        <label for="unit_of_measure" class="form-label">Unit of Measure <span class="text-danger">*</span></label>
                        <input id="unit_of_measure" name="unit_of_measure" class="form-control @error('unit_of_measure') is-invalid @enderror" value="{{ $fieldValue('unit_of_measure', 'unit') }}" placeholder="unit, set, pair, meter, hour, kit" required>
                        <div class="form-text">This keeps the system flexible for single assets, grouped kits, lengths, bundles, services, and consumable-like rental items.</div>
                        @error('unit_of_measure')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="panel">
                <div class="panel-header align-items-start">
                    <div>
                        <h2>Ownership, Value, and Compliance</h2>
                        <p>Record who owns the asset, what it is worth, and any warranty or certificate dates that matter operationally.</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-3">
                        <label for="ownership_type" class="form-label">Ownership Type <span class="text-danger">*</span></label>
                        <select id="ownership_type" name="ownership_type" class="form-select @error('ownership_type') is-invalid @enderror" required>
                            @foreach($ownershipTypes as $value => $label)
                                <option value="{{ $value }}" @selected($fieldValue('ownership_type', 'owned') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Supports owned fleet, leased assets, consignment stock, and customer-owned equipment.</div>
                        @error('ownership_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-3">
                        <label for="acquisition_date" class="form-label">Acquisition Date</label>
                        <input id="acquisition_date" name="acquisition_date" type="date" class="form-control @error('acquisition_date') is-invalid @enderror" value="{{ $dateValue('acquisition_date') }}">
                        <div class="form-text">When the asset entered your rental fleet or became available to manage.</div>
                        @error('acquisition_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-3">
                        <label for="purchase_date" class="form-label">Purchase Date</label>
                        <input id="purchase_date" name="purchase_date" type="date" class="form-control @error('purchase_date') is-invalid @enderror" value="{{ $dateValue('purchase_date') }}">
                        <div class="form-text">Use only when purchase date differs from acquisition or onboarding date.</div>
                        @error('purchase_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-3">
                        <label for="warranty_expiry" class="form-label">Warranty Expiry</label>
                        <input id="warranty_expiry" name="warranty_expiry" type="date" class="form-control @error('warranty_expiry') is-invalid @enderror" value="{{ $dateValue('warranty_expiry') }}">
                        <div class="form-text">Manufacturer, supplier, service, or parts warranty expiry.</div>
                        @error('warranty_expiry')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-3">
                        <label for="certificate_expires_at" class="form-label">Certificate Expiry</label>
                        <input id="certificate_expires_at" name="certificate_expires_at" type="date" class="form-control @error('certificate_expires_at') is-invalid @enderror" value="{{ $dateValue('certificate_expires_at') }}">
                        <div class="form-text">Inspection, fitness, calibration, safety, insurance, or compliance certificate date.</div>
                        @error('certificate_expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-3">
                        <label for="acquisition_cost" class="form-label">Acquisition Cost</label>
                        <input id="acquisition_cost" name="acquisition_cost" type="number" step="0.01" min="0" class="form-control @error('acquisition_cost') is-invalid @enderror" value="{{ $fieldValue('acquisition_cost', 0) }}">
                        <div class="form-text">Original cost, buy-in value, onboarding value, or estimated book cost.</div>
                        @error('acquisition_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-3">
                        <label for="replacement_value" class="form-label">Replacement Value</label>
                        <input id="replacement_value" name="replacement_value" type="number" step="0.01" min="0" class="form-control @error('replacement_value') is-invalid @enderror" value="{{ $fieldValue('replacement_value', 0) }}">
                        <div class="form-text">Useful for deposit rules, damage billing, insurance, and loss recovery.</div>
                        @error('replacement_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="panel">
                <div class="panel-header align-items-start">
                    <div>
                        <h2>Rate Card</h2>
                        <p>Set standard rental prices for this asset. Weekly and monthly rates can be discounted instead of calculated automatically.</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-3">
                        <label for="default_rate_type" class="form-label">Default Rate Type</label>
                        <select id="default_rate_type" name="default_rate_type" class="form-select @error('default_rate_type') is-invalid @enderror">
                            <option value="">No default rate</option>
                            @foreach($rateTypes as $value => $label)
                                <option value="{{ $value }}" @selected($fieldValue('default_rate_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Choose hourly, daily, weekly, monthly, or custom depending on how this asset is normally rented.</div>
                        @error('default_rate_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-3">
                        <label for="hourly_rate" class="form-label">Hourly Rate</label>
                        <input id="hourly_rate" name="hourly_rate" type="number" step="0.01" min="0" class="form-control @error('hourly_rate') is-invalid @enderror" value="{{ $fieldValue('hourly_rate', 0) }}">
                        <div class="form-text">Useful for short rentals and small tools.</div>
                        @error('hourly_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-3">
                        <label for="daily_rate" class="form-label">Daily Rate</label>
                        <input id="daily_rate" name="daily_rate" type="number" step="0.01" min="0" class="form-control @error('daily_rate') is-invalid @enderror" value="{{ $fieldValue('daily_rate', $fieldValue('default_rate', 0)) }}">
                        <div class="form-text">Most common base rate for equipment rentals.</div>
                        @error('daily_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-3">
                        <label for="weekly_rate" class="form-label">Weekly Rate</label>
                        <input id="weekly_rate" name="weekly_rate" type="number" step="0.01" min="0" class="form-control @error('weekly_rate') is-invalid @enderror" value="{{ $fieldValue('weekly_rate', 0) }}">
                        <div class="form-text">Set manually for discounted weekly rentals.</div>
                        @error('weekly_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-3">
                        <label for="monthly_rate" class="form-label">Monthly Rate</label>
                        <input id="monthly_rate" name="monthly_rate" type="number" step="0.01" min="0" class="form-control @error('monthly_rate') is-invalid @enderror" value="{{ $fieldValue('monthly_rate', 0) }}">
                        <div class="form-text">Set manually for long-term rentals.</div>
                        @error('monthly_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-3">
                        <label for="custom_rate" class="form-label">Custom Rate</label>
                        <input id="custom_rate" name="custom_rate" type="number" step="0.01" min="0" class="form-control @error('custom_rate') is-invalid @enderror" value="{{ $fieldValue('custom_rate', 0) }}">
                        <div class="form-text">Optional project or negotiated base rate.</div>
                        @error('custom_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-3">
                        <label for="default_deposit_amount" class="form-label">Default Deposit</label>
                        <input id="default_deposit_amount" name="default_deposit_amount" type="number" step="0.01" min="0" class="form-control @error('default_deposit_amount') is-invalid @enderror" value="{{ $fieldValue('default_deposit_amount', 0) }}">
                        <div class="form-text">Suggested security deposit for quotes and rentals.</div>
                        @error('default_deposit_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="panel">
                <div class="panel-header align-items-start">
                    <div>
                        <h2>Custom Attributes</h2>
                        <p>Add flexible specifications without changing the database every time a new equipment type appears.</p>
                    </div>
                </div>

                <div id="attributeWrapper">
                    <div id="templateAttributeRows" class="row g-3"></div>

                    <hr class="my-4">

                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <h3 class="section-subtitle">Extra Attributes</h3>
                            <div class="form-text">Use these only for one-off details that do not belong in the category template.</div>
                        </div>
                    </div>

                    <div id="extraAttributeRows" class="d-grid gap-2 mt-3"></div>

                    <button type="button" class="btn btn-outline-primary btn-sm mt-3" id="addAttributeBtn">
                        <i class="bi bi-plus-circle"></i> Add Attribute
                    </button>
                    <div class="form-text mt-2">Examples: fuel type, color, capacity, size, phase, lens mount, event theme, voltage, plate number, material, or included accessories.</div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="panel">
                <div class="panel-header align-items-start">
                    <div>
                        <h2>Internal Notes</h2>
                        <p>Keep private operating details visible to your team only.</p>
                    </div>
                </div>

                <label for="notes" class="form-label">Notes</label>
                <textarea id="notes" name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror" placeholder="Maintenance reminders, loading instructions, supplier notes, setup cautions, or special handling.">{{ $fieldValue('notes') }}</textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-end gap-2">
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-dark">{{ $submitLabel }}</button>
            </div>
        </div>
    </div>
</form>

@push('js')
    <script type="module">
        const existingAttributes = @json($attributes);
        const templatesByCategory = @json($categoryTemplates);
        let extraAttributes = [];

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function slugValue(value) {
            return String(value ?? '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
        }

        function selectedTemplates() {
            return templatesByCategory[$('#category_id').val()] || [];
        }

        function attributeMatchesTemplate(attribute, template) {
            return attribute.key === template.key
                || attribute.key === template.name
                || slugValue(attribute.key) === template.key;
        }

        function valueForTemplate(template) {
            const existing = existingAttributes.find((attribute) => attributeMatchesTemplate(attribute, template));

            return existing?.value ?? template.value ?? '';
        }

        function fieldInputForTemplate(template, index, value) {
            const required = template.isRequired ? 'required' : '';
            const placeholder = escapeHtml(template.placeholder || '');
            const safeValue = escapeHtml(value);

            if (template.type === 'select') {
                const options = (template.options || []).map((option) => {
                    const safeOption = escapeHtml(option);
                    const selected = String(option) === String(value) ? 'selected' : '';

                    return `<option value="${safeOption}" ${selected}>${safeOption}</option>`;
                }).join('');

                return `
                    <select class="form-select" name="attributes[${index}][value]" ${required}>
                        <option value="">Select ${escapeHtml(template.name)}</option>
                        ${options}
                    </select>
                `;
            }

            if (template.type === 'boolean') {
                return `
                    <select class="form-select" name="attributes[${index}][value]" ${required}>
                        <option value="">Select ${escapeHtml(template.name)}</option>
                        <option value="1" ${String(value) === '1' ? 'selected' : ''}>Yes</option>
                        <option value="0" ${String(value) === '0' ? 'selected' : ''}>No</option>
                    </select>
                `;
            }

            const inputType = {
                number: 'number',
                decimal: 'number',
                date: 'date',
            }[template.type] || 'text';
            const step = template.type === 'decimal' ? 'step="0.01"' : '';

            return `<input type="${inputType}" ${step} class="form-control" name="attributes[${index}][value]" value="${safeValue}" placeholder="${placeholder}" ${required}>`;
        }

        function renderTemplateAttributes() {
            const container = $('#templateAttributeRows');
            const templates = selectedTemplates();

            container.empty();

            if (!templates.length) {
                container.append(`
                    <div class="col-12">
                        <div class="text-muted border rounded p-3">
                            Select a category with templates to show structured fields here, or add extra attributes below.
                        </div>
                    </div>
                `);

                return;
            }

            templates.forEach((template, index) => {
                const value = valueForTemplate(template);
                const unit = template.unit ? `<span class="text-muted">(${escapeHtml(template.unit)})</span>` : '';
                const required = template.isRequired ? '<span class="text-danger">*</span>' : '';

                container.append(`
                    <div class="col-lg-4 template-attribute-row">
                        <label class="form-label">
                            ${escapeHtml(template.name)} ${unit} ${required}
                        </label>
                        <input type="hidden" name="attributes[${index}][key]" value="${escapeHtml(template.key)}">
                        ${fieldInputForTemplate(template, index, value)}
                        ${template.helpText ? `<div class="form-text">${escapeHtml(template.helpText)}</div>` : ''}
                    </div>
                `);
            });
        }

        function syncExtraAttributesFromExisting() {
            const templates = selectedTemplates();

            extraAttributes = existingAttributes.filter((attribute) => {
                const hasValue = (attribute.key || attribute.value);
                const belongsToTemplate = templates.some((template) => attributeMatchesTemplate(attribute, template));

                return hasValue && !belongsToTemplate;
            });
        }

        function renderExtraAttributes() {
            const container = $('#extraAttributeRows');
            const templateCount = selectedTemplates().length;

            container.empty();

            extraAttributes.forEach((attr, offset) => {
                const index = templateCount + offset;

                container.append(`
                    <div class="row g-2 align-items-start extra-attribute-row" data-offset="${offset}">
                        <div class="col-lg-5">
                            <label class="form-label" for="extra_attribute_key_${offset}">Attribute Name</label>
                            <input id="extra_attribute_key_${offset}" type="text" class="form-control extra-attribute-key" name="attributes[${index}][key]" value="${escapeHtml(attr.key)}" placeholder="Example: Fuel type">
                        </div>
                        <div class="col-lg-5">
                            <label class="form-label" for="extra_attribute_value_${offset}">Attribute Value</label>
                            <input id="extra_attribute_value_${offset}" type="text" class="form-control extra-attribute-value" name="attributes[${index}][value]" value="${escapeHtml(attr.value)}" placeholder="Example: Diesel">
                        </div>
                        <div class="col-lg-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-attribute w-100 mt-lg-4">
                                Remove
                            </button>
                        </div>
                    </div>
                `);
            });
        }

        $(function () {
            function renderAllAttributes() {
                renderTemplateAttributes();
                renderExtraAttributes();
            }

            syncExtraAttributesFromExisting();
            renderAllAttributes();

            $('#category_id').on('change', function () {
                syncExtraAttributesFromExisting();
                renderAllAttributes();
            });

            $('#addAttributeBtn').on('click', function () {
                extraAttributes.push({ key: '', value: '' });
                renderExtraAttributes();
            });

            $('#extraAttributeRows').on('input', 'input', function () {
                const row = $(this).closest('.extra-attribute-row');
                const offset = row.data('offset');

                extraAttributes[offset] = {
                    key: row.find('.extra-attribute-key').val(),
                    value: row.find('.extra-attribute-value').val(),
                };
            });

            $('#extraAttributeRows').on('click', '.remove-attribute', function () {
                const offset = $(this).closest('.extra-attribute-row').data('offset');
                extraAttributes.splice(offset, 1);
                renderExtraAttributes();
            });
        });
    </script>
@endpush
