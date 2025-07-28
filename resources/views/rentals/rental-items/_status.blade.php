@php
    $statusOptions = [
        'Pending' => 'text-bg-secondary',
        'Dispatched' => 'text-bg-primary',
        'On Rent' => 'text-bg-info',
        'Returned' => 'text-bg-success',
        'Damaged' => 'text-bg-danger',
        'Missing' => 'text-bg-warning',
        'Under Maintenance' => 'text-bg-dark',
    ];
@endphp

<select class="form-select form-select-sm w-auto status-dropdown badge-dropdown"
        data-id="{{ $item->id }}">
    @foreach($statusOptions as $status => $class)
        <option
            value="{{ $status }}"
            data-class="{{ $class }}"
            @selected($item->status === $status)>
            {{ $status }}
        </option>
    @endforeach
</select>
