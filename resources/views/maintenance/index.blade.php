@extends('layouts.app')

@section('title', 'Maintenance')

@php
    $statusBadge = [
        'scheduled' => 'badge-soft-info',
        'in_progress' => 'badge-soft-warning',
        'completed' => 'badge-soft-success',
        'cancelled' => 'badge-soft-secondary',
    ];
@endphp

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Asset lifecycle</span>
                <h1>Maintenance and Inspections</h1>
                <p>Schedule service, record inspections, track costs, and keep equipment readiness visible across the rental team.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <section class="panel">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Create Maintenance Record</h2>
                    <p>Use this for scheduled service, inspections, repairs, calibration, and certificate renewal work.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('maintenance.store') }}">
                @csrf
                @include('maintenance._form-fields', ['log' => null])
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-dark">
                        <x-lucide-plus class="w-4 h-4 me-1"/>
                        Add Record
                    </button>
                </div>
            </form>
        </section>

        <section class="panel mt-3">
            <div class="panel-header align-items-start">
                <div>
                    <h2>Maintenance Queue</h2>
                    <p>Upcoming, active, and completed work across all tenant equipment.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table modern-table align-middle">
                    <thead>
                    <tr>
                        <th>Work</th>
                        <th>Equipment</th>
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
                                <strong>{{ $log->title }}</strong>
                                <div class="text-muted text-xs">{{ $types[$log->type] ?? str($log->type)->headline() }} · {{ $priorities[$log->priority] ?? str($log->priority)->headline() }}</div>
                            </td>
                            <td>
                                <a href="{{ route('products.show', $log->product) }}">{{ $log->product?->name }}</a>
                                <div class="text-muted text-xs">{{ $log->product?->equipment_code ?: 'No code' }}</div>
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
                            <td>{{ number_format((float) $log->cost, 2) }}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#maintenance-edit-{{ $log->id }}">
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('maintenance.destroy', $log) }}" class="d-inline" onsubmit="return confirm('Delete this maintenance record?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <tr class="collapse" id="maintenance-edit-{{ $log->id }}">
                            <td colspan="6">
                                <form method="POST" action="{{ route('maintenance.update', $log) }}" class="inline-edit-form">
                                    @csrf
                                    @method('PUT')
                                    @include('maintenance._form-fields', ['log' => $log])
                                    <div class="d-flex justify-content-end mt-3">
                                        <button type="submit" class="btn btn-dark btn-sm">Save Record</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No maintenance records yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
