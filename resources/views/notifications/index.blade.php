@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="px-3">
        <div class="page-header">
            <div>
                <span class="eyebrow">Operations</span>
                <h1>Notifications</h1>
                <p>Review reminders for invoices, rentals, quotes, maintenance, and expiring documents.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <form method="POST" action="{{ route('notifications.generate') }}">
                    @csrf
                    <button type="submit" class="btn btn-soft-primary">
                        <x-lucide-refresh-cw class="w-4 h-4 me-1"/>
                        Generate Reminders
                    </button>
                </form>
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-dark">
                        <x-lucide-check-check class="w-4 h-4 me-1"/>
                        Mark All Read
                    </button>
                </form>
            </div>
        </div>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <section class="panel mb-3">
            <form method="GET" action="{{ route('notifications.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All</option>
                        <option value="unread" @selected(($filters['status'] ?? '') === 'unread')>Unread</option>
                        <option value="read" @selected(($filters['status'] ?? '') === 'read')>Read</option>
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label for="type" class="form-label">Type</label>
                    <select id="type" name="type" class="form-select">
                        <option value="">All types</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ str($type)->headline() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-dark flex-fill">
                        <x-lucide-filter class="w-4 h-4 me-1"/>
                        Filter
                    </button>
                    <a href="{{ route('notifications.index') }}" class="btn btn-soft-secondary" title="Reset filters">
                        <x-lucide-rotate-ccw class="w-4 h-4"/>
                    </a>
                </div>
            </form>
        </section>

        <div class="d-flex flex-column gap-3">
            @forelse($notifications as $notification)
                <section class="panel {{ $notification->isUnread() ? 'border-start border-4 border-success' : '' }}">
                    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge text-bg-{{ $notification->severity === 'danger' ? 'danger' : ($notification->severity === 'warning' ? 'warning' : 'info') }}">
                                    {{ str($notification->severity)->headline() }}
                                </span>
                                <span class="badge text-bg-light">{{ str($notification->type)->headline() }}</span>
                                @if($notification->isUnread())
                                    <span class="badge text-bg-success">Unread</span>
                                @endif
                            </div>
                            <h2 class="mb-1">{{ $notification->title }}</h2>
                            @if($notification->body)
                                <p class="text-muted mb-2">{{ $notification->body }}</p>
                            @endif
                            <p class="text-muted small mb-0">
                                Created {{ $notification->created_at->format('M d, Y h:i A') }}
                                @if($notification->due_at)
                                    · Due {{ $notification->due_at->format('M d, Y') }}
                                @endif
                            </p>
                        </div>

                        <div class="d-flex gap-2">
                            @if($notification->action_url)
                                <a href="{{ $notification->action_url }}" class="btn btn-soft-primary">
                                    {{ $notification->action_label ?? 'Open' }}
                                </a>
                            @endif
                            @if($notification->isUnread())
                                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-dark">
                                        <x-lucide-check class="w-4 h-4 me-1"/>
                                        Mark Read
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </section>
            @empty
                <section class="panel text-center text-muted py-5">
                    No notifications found.
                </section>
            @endforelse
        </div>

        <div class="mt-3">
            <x-pagination :paginator="$notifications"/>
        </div>
    </div>
@endsection
