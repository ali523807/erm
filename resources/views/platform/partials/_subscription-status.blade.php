@php
    $status = $status ?? 'unknown';
    $class = match ($status) {
        'active' => 'badge-soft-success',
        'trialing' => 'badge-soft-info',
        'past_due' => 'badge-soft-warning',
        'cancelled' => 'badge-soft-danger',
        default => 'badge-soft-secondary',
    };
@endphp

<span class="badge {{ $class }}">{{ str($status)->headline() }}</span>
