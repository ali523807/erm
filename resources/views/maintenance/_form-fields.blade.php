@php
    $value = fn (string $field, mixed $default = null): mixed => old($field, $log?->{$field} ?? $default);
    $dateValue = function (string $field) use ($value): ?string {
        $fieldValue = $value($field);

        if ($fieldValue instanceof \Illuminate\Support\Carbon) {
            return $fieldValue->format('Y-m-d');
        }

        return $fieldValue;
    };
@endphp

<div class="row g-3">
    <div class="col-lg-4">
        <label class="form-label" for="product_id_{{ $log?->id ?? 'new' }}">Equipment <span class="text-danger">*</span></label>
        <select id="product_id_{{ $log?->id ?? 'new' }}" name="product_id" class="form-select" required>
            <option value="">Select equipment</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}" @selected((string) $value('product_id') === (string) $product->id)>
                    {{ $product->name }}{{ $product->equipment_code ? ' · '.$product->equipment_code : '' }}
                </option>
            @endforeach
        </select>
        <div class="form-text">The asset this service, inspection, or renewal belongs to.</div>
    </div>

    <div class="col-lg-4">
        <label class="form-label" for="title_{{ $log?->id ?? 'new' }}">Title <span class="text-danger">*</span></label>
        <input id="title_{{ $log?->id ?? 'new' }}" name="title" class="form-control" value="{{ $value('title') }}" placeholder="Monthly inspection" required>
        <div class="form-text">Short work name visible in queues and equipment history.</div>
    </div>

    <div class="col-lg-2">
        <label class="form-label" for="type_{{ $log?->id ?? 'new' }}">Type</label>
        <select id="type_{{ $log?->id ?? 'new' }}" name="type" class="form-select" required>
            @foreach($types as $typeValue => $label)
                <option value="{{ $typeValue }}" @selected($value('type', 'maintenance') === $typeValue)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-lg-2">
        <label class="form-label" for="priority_{{ $log?->id ?? 'new' }}">Priority</label>
        <select id="priority_{{ $log?->id ?? 'new' }}" name="priority" class="form-select" required>
            @foreach($priorities as $priorityValue => $label)
                <option value="{{ $priorityValue }}" @selected($value('priority', 'medium') === $priorityValue)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-lg-3">
        <label class="form-label" for="status_{{ $log?->id ?? 'new' }}">Status</label>
        <select id="status_{{ $log?->id ?? 'new' }}" name="status" class="form-select" required>
            @foreach($statuses as $statusValue => $label)
                <option value="{{ $statusValue }}" @selected($value('status', 'scheduled') === $statusValue)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="form-text">Scheduled and in-progress records can mark equipment as under maintenance.</div>
    </div>

    <div class="col-lg-3">
        <label class="form-label" for="scheduled_at_{{ $log?->id ?? 'new' }}">Scheduled Date</label>
        <input id="scheduled_at_{{ $log?->id ?? 'new' }}" name="scheduled_at" type="date" class="form-control" value="{{ $dateValue('scheduled_at') }}">
    </div>

    <div class="col-lg-3">
        <label class="form-label" for="service_date_{{ $log?->id ?? 'new' }}">Service Date</label>
        <input id="service_date_{{ $log?->id ?? 'new' }}" name="service_date" type="date" class="form-control" value="{{ $dateValue('service_date') }}">
    </div>

    <div class="col-lg-3">
        <label class="form-label" for="completed_at_{{ $log?->id ?? 'new' }}">Completed Date</label>
        <input id="completed_at_{{ $log?->id ?? 'new' }}" name="completed_at" type="date" class="form-control" value="{{ $dateValue('completed_at') }}">
    </div>

    <div class="col-lg-3">
        <label class="form-label" for="next_service_due_{{ $log?->id ?? 'new' }}">Next Service Due</label>
        <input id="next_service_due_{{ $log?->id ?? 'new' }}" name="next_service_due" type="date" class="form-control" value="{{ $dateValue('next_service_due') }}">
    </div>

    <div class="col-lg-3">
        <label class="form-label" for="service_provider_{{ $log?->id ?? 'new' }}">Provider / Technician</label>
        <input id="service_provider_{{ $log?->id ?? 'new' }}" name="service_provider" class="form-control" value="{{ $value('service_provider') }}" placeholder="Internal workshop or vendor">
    </div>

    <div class="col-lg-3">
        <label class="form-label" for="cost_{{ $log?->id ?? 'new' }}">Cost</label>
        <input id="cost_{{ $log?->id ?? 'new' }}" name="cost" type="number" step="0.01" min="0" class="form-control" value="{{ $value('cost', 0) }}">
    </div>

    <div class="col-lg-3">
        <label class="form-label" for="downtime_hours_{{ $log?->id ?? 'new' }}">Downtime Hours</label>
        <input id="downtime_hours_{{ $log?->id ?? 'new' }}" name="downtime_hours" type="number" step="0.25" min="0" class="form-control" value="{{ $value('downtime_hours', 0) }}">
    </div>

    <div class="col-lg-3 d-flex align-items-end">
        <label class="form-check mb-2">
            <input type="checkbox" name="affects_availability" value="1" class="form-check-input" @checked($value('affects_availability', true))>
            <span class="form-check-label">Affects availability</span>
        </label>
    </div>

    <div class="col-lg-6">
        <label class="form-label" for="description_{{ $log?->id ?? 'new' }}">Work Description</label>
        <textarea id="description_{{ $log?->id ?? 'new' }}" name="description" rows="3" class="form-control" placeholder="What needs to be done?">{{ $value('description') }}</textarea>
    </div>

    <div class="col-lg-6">
        <label class="form-label" for="findings_{{ $log?->id ?? 'new' }}">Findings</label>
        <textarea id="findings_{{ $log?->id ?? 'new' }}" name="findings" rows="3" class="form-control" placeholder="Inspection result or fault found.">{{ $value('findings') }}</textarea>
    </div>

    <div class="col-lg-6">
        <label class="form-label" for="part_used_{{ $log?->id ?? 'new' }}">Parts Used</label>
        <textarea id="part_used_{{ $log?->id ?? 'new' }}" name="part_used" rows="3" class="form-control" placeholder="Filters, oil, belts, sensors, tires, cables.">{{ $value('part_used') }}</textarea>
    </div>

    <div class="col-lg-6">
        <label class="form-label" for="recommendations_{{ $log?->id ?? 'new' }}">Recommendations</label>
        <textarea id="recommendations_{{ $log?->id ?? 'new' }}" name="recommendations" rows="3" class="form-control" placeholder="Follow-up actions, next inspection notes, or operating cautions.">{{ $value('recommendations') }}</textarea>
    </div>
</div>
