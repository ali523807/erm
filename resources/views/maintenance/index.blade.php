@extends('layouts.app')

@section('title', 'Maintenance Work Orders')

@php
    $money = app(\App\Support\Money::class);
    $statusBadge = [
        'open' => 'badge-soft-primary',
        'scheduled' => 'badge-soft-info',
        'in_progress' => 'badge-soft-warning',
        'waiting_parts' => 'badge-soft-danger',
        'completed' => 'badge-soft-success',
        'cancelled' => 'badge-soft-secondary',
    ];
@endphp

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Asset lifecycle</span>
                <h1>Maintenance Work Orders</h1>
                <p>Assign technicians, track repair work, manage parts and labor costs, and return equipment to service after inspection or damage.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Open Work</span>
                    <h2 class="mb-0">{{ $summary['open'] }}</h2>
                    <p class="text-muted mb-0">Active maintenance load</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Urgent</span>
                    <h2 class="mb-0">{{ $summary['urgent'] }}</h2>
                    <p class="text-muted mb-0">High attention items</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Waiting Parts</span>
                    <h2 class="mb-0">{{ $summary['waitingParts'] }}</h2>
                    <p class="text-muted mb-0">Blocked by procurement</p>
                </section>
            </div>
            <div class="col-md-3">
                <section class="panel h-100">
                    <span class="eyebrow">Completed</span>
                    <h2 class="mb-0">{{ $summary['completed'] }}</h2>
                    <p class="text-muted mb-0">Closed work orders</p>
                </section>
            </div>
        </div>

        <section class="panel">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Create Work Order</h2>
                    <p>Use this for repairs, service, inspections, calibration, certificate renewal, and work generated from return inspections.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('maintenance.store') }}">
                @csrf
                @include('maintenance._form-fields', ['log' => null])
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-dark">
                        <x-lucide-plus class="w-4 h-4 me-1"/>
                        Add Work Order
                    </button>
                </div>
            </form>
        </section>

        <section class="panel mt-3">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Work Order Queue</h2>
                    <p>Open, active, blocked, and completed work across all tenant equipment.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table modern-table align-middle">
                    <thead>
                    <tr>
                        <th>Work Order</th>
                        <th>Equipment</th>
                        <th>Assigned</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th>Cost</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>
                                <strong>{{ $log->work_order_number ?: 'Unnumbered' }}</strong>
                                <div>{{ $log->title }}</div>
                                <div class="text-muted text-xs">{{ $types[$log->type] ?? str($log->type)->headline() }} - {{ $priorities[$log->priority] ?? str($log->priority)->headline() }}</div>
                                @if($log->returnInspection)
                                    <div class="text-warning text-xs">From return inspection RTN-{{ $log->returnInspection->rental_id }}</div>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('products.show', $log->product) }}">{{ $log->product?->name }}</a>
                                <div class="text-muted text-xs">{{ $log->product?->equipment_code ?: 'No code' }}</div>
                            </td>
                            <td>
                                {{ $log->assignee?->name ?: 'Unassigned' }}
                                <div class="text-muted text-xs">{{ $log->service_provider ?: 'No provider' }}</div>
                            </td>
                            <td>
                                {{ $log->scheduled_at?->format('Y-m-d') ?: 'Not scheduled' }}
                                <div class="text-muted text-xs">Next: {{ $log->next_service_due ?: 'Not set' }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $statusBadge[$log->status] ?? 'badge-soft-secondary' }}">
                                    {{ $statuses[$log->status] ?? str($log->status)->headline() }}
                                </span>
                            </td>
                            <td>
                                {{ $money->format($log->cost) }}
                                <div class="text-muted text-xs">P {{ $money->format($log->parts_cost) }} / L {{ $money->format($log->labor_cost) }} / V {{ $money->format($log->vendor_cost) }}</div>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#maintenance-edit-{{ $log->id }}">
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('maintenance.destroy', $log) }}" class="d-inline" onsubmit="return confirm('Delete this work order?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <tr class="collapse" id="maintenance-edit-{{ $log->id }}">
                            <td colspan="7">
                                <form method="POST" action="{{ route('maintenance.update', $log) }}" class="inline-edit-form">
                                    @csrf
                                    @method('PUT')
                                    @include('maintenance._form-fields', ['log' => $log])
                                    <div class="d-flex justify-content-end mt-3">
                                        <button type="submit" class="btn btn-dark btn-sm">Save Work Order</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No maintenance work orders yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-3 pb-3">
                <x-pagination :paginator="$logs"/>
            </div>
        </section>
    </div>
@endsection

@push('js')
    <script type="module">
        function initLookupSelect(selector, url, placeholder) {
            $(selector).each(function () {
                const select = $(this);
                if (select.data('select2')) {
                    return;
                }

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
            });
        }

        $(function () {
            initLookupSelect('.js-product-lookup', @json(route('lookups.products')), 'Search equipment by name, code, serial, or category');
            initLookupSelect('.js-team-lookup', @json(route('lookups.team-members')), 'Search team member by name or email');
        });
    </script>
@endpush
