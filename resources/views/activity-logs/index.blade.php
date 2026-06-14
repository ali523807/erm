@extends('layouts.app')

@section('title', 'Activity logs')

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Audit Trail</span>
                <h1>Activity Logs</h1>
                <p>Track important team, permission, rental, quote, invoice, and payment actions inside this company.</p>
            </div>
        </div>

        <section class="panel mb-3">
            <form method="GET" action="{{ route('activity-logs.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-2">
                    <label for="module" class="form-label">Module</label>
                    <select id="module" name="module" class="form-select">
                        <option value="">All modules</option>
                        @foreach($modules as $module)
                            <option value="{{ $module }}" @selected(($filters['module'] ?? '') === $module)>{{ str($module)->headline() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <label for="action" class="form-label">Action</label>
                    <select id="action" name="action" class="form-select">
                        <option value="">All actions</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ str($action)->headline() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <label for="user_id" class="form-label">User</label>
                    <select id="user_id" name="user_id" class="form-select">
                        <option value="">All users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <label for="date_from" class="form-label">From</label>
                    <input id="date_from" name="date_from" type="date" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>

                <div class="col-12 col-md-2">
                    <label for="date_to" class="form-label">To</label>
                    <input id="date_to" name="date_to" type="date" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>

                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark flex-fill">
                        <x-lucide-filter class="w-4 h-4 me-1"/>
                        Filter
                    </button>
                    <a href="{{ route('activity-logs.index') }}" class="btn btn-soft-secondary" title="Reset filters">
                        <x-lucide-rotate-ccw class="w-4 h-4"/>
                    </a>
                </div>
            </form>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>Recent Activity</h2>
                    <p>Newest events are shown first. Use filters for investigations and operational review.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table modern-table align-middle">
                    <thead>
                    <tr>
                        <th>When</th>
                        <th>User</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Activity</th>
                        <th>Details</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>
                                <strong>{{ $log->created_at->format('M d, Y') }}</strong>
                                <span class="d-block text-muted small">{{ $log->created_at->format('h:i A') }}</span>
                            </td>
                            <td>
                                {{ $log->user?->name ?? 'System' }}
                                @if($log->user?->email)
                                    <span class="d-block text-muted small">{{ $log->user->email }}</span>
                                @endif
                            </td>
                            <td><span class="badge text-bg-light">{{ str($log->module)->headline() }}</span></td>
                            <td><span class="badge text-bg-success">{{ str($log->action)->headline() }}</span></td>
                            <td>{{ $log->description }}</td>
                            <td style="min-width: 260px;">
                                @if($log->properties)
                                    <details>
                                        <summary class="text-muted small">View details</summary>
                                        <pre class="bg-light border rounded-3 p-2 mt-2 mb-0 small">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @else
                                    <span class="text-muted small">No extra details</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No activity logs found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <x-pagination :paginator="$logs"/>
            </div>
        </section>
    </div>
@endsection
