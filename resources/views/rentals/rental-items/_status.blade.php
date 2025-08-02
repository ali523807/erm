@php
    $statusOptions = [
         'Pending' => 'badge-soft-secondary',
         'Dispatched' => 'badge-soft-primary',
         'On Rent' => 'badge-soft-info',
         'Returned' => 'badge-soft-success',
         'Damaged' => 'badge-soft-danger',
         'Missing' => 'badge-soft-warning',
         'Under Maintenance' => 'badge-soft-dark',
     ];
@endphp

<div class="dropdown">
    <span class="badge dropdown-toggle {{ $statusOptions[$item->status] ?? 'text-bg-light' }}"
            type="button"
            id="statusDropdown{{ $item->id }}"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            data-id="{{ $item->id }}"
            data-status="{{ $item->status }}">
        {{ $item->status }}
    </span>
    <ul class="dropdown-menu" aria-labelledby="statusDropdown{{ $item->id }}">
        @foreach($statusOptions as $status => $class)
            <li>
                <a href="#"
                   class="dropdown-item toggleStatus"
                   data-id="{{ $item->id }}"
                   data-status="{{ $status }}"
                   data-class="{{ $class }}">
                    <span class="badge {{ $class }} w-100">{{ $status }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>

