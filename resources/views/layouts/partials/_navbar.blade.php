<nav class="navbar navbar-expand app-navbar">
    @php
        $currentUser = auth()->user();
        $unreadNotifications = \App\Models\TenantNotification::query()
            ->whereNull('read_at')
            ->where(function ($query) use ($currentUser): void {
                $query->whereNull('user_id')->orWhere('user_id', $currentUser->id);
            })
            ->latest('due_at')
            ->latest()
            ->limit(5)
            ->get();
        $unreadNotificationCount = \App\Models\TenantNotification::query()
            ->whereNull('read_at')
            ->where(function ($query) use ($currentUser): void {
                $query->whereNull('user_id')->orWhere('user_id', $currentUser->id);
            })
            ->count();
    @endphp

    <button class="btn icon-button" id="sidebar-toggle" type="button">
        <x-lucide-panel-left class="w-4 h-4 text-slate-600"/>
    </button>
    <div class="navbar-collapse">
        <div class="topbar-title">
            <span>{{ auth()->user()->currentCompany?->name ?? 'ERM Workspace' }}</span>
            <small>Equipment rental SaaS</small>
        </div>
        <ul class="navbar-nav">
            <li class="nav-item dropdown me-2">
                <a href="#" data-bs-toggle="dropdown" class="btn icon-button position-relative" aria-label="Notifications">
                    <x-lucide-bell class="w-4 h-4 text-slate-600"/>
                    @if($unreadNotificationCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            {{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}
                        </span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end border shadow-sm py-0" style="min-width: 340px;">
                    <div class="d-flex align-items-center justify-content-between border-bottom p-2">
                        <div>
                            <strong class="text-sm">Notifications</strong>
                            <p class="mb-0 text-muted text-xs">{{ $unreadNotificationCount }} unread</p>
                        </div>
                        <a href="{{ route('notifications.index') }}" wire:navigate class="btn btn-soft-primary btn-sm">View All</a>
                    </div>

                    <div class="py-1" style="max-height: 320px; overflow-y: auto;">
                        @forelse($unreadNotifications as $notification)
                            <a href="{{ $notification->action_url ?: route('notifications.index') }}" class="dropdown-item py-2">
                                <div class="d-flex gap-2">
                                    <span class="badge rounded-pill text-bg-{{ $notification->severity === 'danger' ? 'danger' : ($notification->severity === 'warning' ? 'warning' : 'info') }} align-self-start mt-1">&nbsp;</span>
                                    <span>
                                        <strong class="d-block text-sm">{{ $notification->title }}</strong>
                                        <span class="d-block text-muted small">{{ str($notification->body)->limit(70) }}</span>
                                    </span>
                                </div>
                            </a>
                        @empty
                            <div class="p-3 text-center text-muted small">No unread notifications.</div>
                        @endforelse
                    </div>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a href="#" data-bs-toggle="dropdown" class="nav-icon pe-md-0">
                    <x-user-avatar class="w-8 h-8 bg-slate-400" text-size="text-xs" color="gray-100" shape="rounded" :user="auth()->user()" />
                </a>
                <div class="dropdown-menu dropdown-menu-end border shadow-sm py-0">
                    <div class="d-flex align-items-center gap-2 border-bottom p-2">
                        <x-user-avatar class="w-8 h-8" text-size="text-xs" color="gray-100" shape="rounded" :user="auth()->user()" />
                        <div>
                            <p class="mb-0 text-truncate text-sm" style="font-weight: 500;">{{ auth()->user()->name }}</p>
                            <p class="mb-1 text-muted text-xs text-truncate">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                    <div class="m-1">
                        <a href="{{ route('settings.profile') }}" wire:navigate class="rounded dropdown-item p-1 text-sm">
                            <x-lucide-settings class="w-4 h-4 text-slate-600" /><span class="ms-3"> Settings</span>
                        </a>
                    </div>
                    <hr class="m-0" style="color: lightgray;">
                    <div class="m-1">
                        <a href="#" onclick="event.preventDefault();$('#logout-form').submit();" class="rounded dropdown-item p-1 text-sm">
                            <x-lucide-log-out class="w-4 h-4 text-slate-600" /><span class="ms-3"> Logout </span>
                        </a>
                    </div>
                    <form method="POST" id="logout-form" action="{{ route('logout') }}" class="d-none">
                        @csrf
                    </form>
                </div>
            </li>
        </ul>
    </div>
</nav>
