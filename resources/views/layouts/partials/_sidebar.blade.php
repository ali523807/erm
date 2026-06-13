<aside id="sidebar" class="js-sidebar">
    @php
        $currentUser = auth()->user();
        $canUseRentalDesk = $currentUser->hasCurrentCompanyPermission('quotes.manage')
            || $currentUser->hasCurrentCompanyPermission('rentals.manage')
            || $currentUser->hasCurrentCompanyPermission('dispatch.manage')
            || $currentUser->hasCurrentCompanyPermission('availability.view');
        $canUseFleet = $currentUser->hasCurrentCompanyPermission('categories.manage')
            || $currentUser->hasCurrentCompanyPermission('equipment.manage')
            || $currentUser->hasCurrentCompanyPermission('maintenance.manage');
        $canUseFinance = $currentUser->hasCurrentCompanyPermission('invoices.manage')
            || $currentUser->hasCurrentCompanyPermission('payments.manage');
        $canUseAdministration = $currentUser->hasCurrentCompanyPermission('company.manage')
            || $currentUser->hasCurrentCompanyPermission('documents.manage')
            || $currentUser->hasCurrentCompanyPermission('locations.manage')
            || $currentUser->hasCurrentCompanyPermission('team.manage')
            || $currentUser->hasCurrentCompanyPermission('roles.manage');
    @endphp

    <div class="h-100 d-flex flex-column">
        <div class="sidebar-logo">
            <a href="{{ route('home') }}" class="brand-lockup">
                <span class="brand-mark">
                    <x-lucide-building-2 class="w-5 h-5"/>
                </span>
                <span>
                    <strong>ERM Cloud</strong>
                    <small>{{ auth()->user()->currentCompany?->name ?? 'Workspace' }}</small>
                </span>
            </a>
        </div>

        <ul class="sidebar-nav flex-fill">
            @if($currentUser->hasCurrentCompanyPermission('dashboard.view'))
                <li class="sidebar-nav-heading">Overview</li>

                <li class="sidebar-item">
                    <a href="{{ route('home') }}" wire:navigate
                       class="sidebar-link {{ request()->routeIs('home') ? 'active' : '' }}">
                        <x-lucide-layout-dashboard class="w-4 h-4"/>
                        <span>Dashboard</span>
                    </a>
                </li>
            @endif

            <li class="sidebar-item">
                <a href="{{ route('notifications.index') }}" wire:navigate
                   class="sidebar-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                    <x-lucide-bell class="w-4 h-4"/>
                    <span>Notifications</span>
                </a>
            </li>

            @if($canUseRentalDesk)
                <li class="sidebar-nav-heading">Rental Desk</li>
            @endif

            @if($currentUser->hasCurrentCompanyPermission('quotes.manage'))
                <li class="sidebar-item">
                    <a href="{{ route('quotes.index') }}" wire:navigate
                       class="sidebar-link {{ request()->routeIs('quotes.*') ? 'active' : '' }}">
                        <x-lucide-file-signature class="w-4 h-4"/>
                        <span>Quotes</span>
                    </a>
                </li>
            @endif

            @if($currentUser->hasCurrentCompanyPermission('rentals.manage'))
                <li class="sidebar-item">
                    <a href="{{ route('rentals.index') }}" wire:navigate
                       class="sidebar-link {{ request()->routeIs('rentals.*') || request()->routeIs('agreements.*') ? 'active' : '' }}">
                        <x-lucide-file-box class="w-4 h-4"/>
                        <span>Rentals</span>
                    </a>
                </li>
            @endif

            @if($currentUser->hasCurrentCompanyPermission('dispatch.manage'))
                <li class="sidebar-item">
                    <a href="{{ route('dispatch-returns.index') }}" wire:navigate
                       class="sidebar-link {{ request()->routeIs('dispatch-returns.*') ? 'active' : '' }}">
                        <x-lucide-truck class="w-4 h-4"/>
                        <span>Dispatch & Returns</span>
                    </a>
                </li>
            @endif

            @if($currentUser->hasCurrentCompanyPermission('availability.view'))
                <li class="sidebar-item">
                    <a href="{{ route('availability.index') }}" wire:navigate
                       class="sidebar-link {{ request()->routeIs('availability.*') ? 'active' : '' }}">
                        <x-lucide-calendar-check class="w-4 h-4"/>
                        <span>Availability</span>
                    </a>
                </li>
            @endif

            @if($canUseFleet)
                <li class="sidebar-nav-heading">Fleet</li>
            @endif

            @if($currentUser->hasCurrentCompanyPermission('categories.manage'))
                <li class="sidebar-item">
                    <a href="{{ route('categories.index') }}" wire:navigate
                       class="sidebar-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                        <x-lucide-layout-grid class="w-4 h-4"/>
                        <span>Categories</span>
                    </a>
                </li>
            @endif

            @if($currentUser->hasCurrentCompanyPermission('equipment.manage'))
                <li class="sidebar-item">
                    <a href="{{ route('products.index') }}" wire:navigate
                       class="sidebar-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                        <x-lucide-package-search class="w-4 h-4"/>
                        <span>Equipment</span>
                    </a>
                </li>
            @endif

            @if($currentUser->hasCurrentCompanyPermission('maintenance.manage'))
                <li class="sidebar-item">
                    <a href="{{ route('maintenance.index') }}" wire:navigate
                       class="sidebar-link {{ request()->routeIs('maintenance.*') ? 'active' : '' }}">
                        <x-lucide-wrench class="w-4 h-4"/>
                        <span>Maintenance</span>
                    </a>
                </li>
            @endif

            @if($currentUser->hasCurrentCompanyPermission('customers.manage'))
                <li class="sidebar-nav-heading">Customers</li>

                <li class="sidebar-item">
                    <a href="{{ route('customers.index') }}" wire:navigate
                       class="sidebar-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                        <x-lucide-users class="w-4 h-4"/>
                        <span>Customers</span>
                    </a>
                </li>
            @endif

            @if($canUseFinance)
                <li class="sidebar-nav-heading">Finance</li>
            @endif

            @if($currentUser->hasCurrentCompanyPermission('invoices.manage'))
                <li class="sidebar-item">
                    <a href="{{ route('invoices.index') }}" wire:navigate
                       class="sidebar-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                        <x-lucide-file-text class="w-4 h-4"/>
                        <span>Invoices</span>
                    </a>
                </li>
            @endif

            @if($currentUser->hasCurrentCompanyPermission('payments.manage'))
                <li class="sidebar-item">
                    <a href="{{ route('payments.index') }}" wire:navigate
                       class="sidebar-link {{ request()->routeIs('payments.*') ? 'active' : '' }}">
                        <x-lucide-credit-card class="w-4 h-4"/>
                        <span>Payments</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a href="{{ route('expenses.index') }}" wire:navigate
                       class="sidebar-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
                        <x-lucide-receipt class="w-4 h-4"/>
                        <span>Expenses</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a href="{{ route('deposits.index') }}" wire:navigate
                       class="sidebar-link {{ request()->routeIs('deposits.*') ? 'active' : '' }}">
                        <x-lucide-wallet class="w-4 h-4"/>
                        <span>Deposits</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a href="{{ route('credit-notes.index') }}" wire:navigate
                       class="sidebar-link {{ request()->routeIs('credit-notes.*') ? 'active' : '' }}">
                        <x-lucide-receipt-text class="w-4 h-4"/>
                        <span>Credit Notes</span>
                    </a>
                </li>
            @endif

            @if($currentUser->hasCurrentCompanyPermission('reports.view') || $currentUser->hasCurrentCompanyPermission('roles.manage'))
                <li class="sidebar-nav-heading">Insights</li>

                @if($currentUser->hasCurrentCompanyPermission('reports.view'))
                    <li class="sidebar-item">
                        <a href="{{ route('reports.index') }}" wire:navigate
                           class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                            <x-lucide-bar-chart-3 class="w-4 h-4"/>
                            <span>Reports</span>
                        </a>
                    </li>
                @endif

                @if($currentUser->hasCurrentCompanyPermission('roles.manage'))
                    <li class="sidebar-item">
                        <a href="{{ route('activity-logs.index') }}" wire:navigate
                           class="sidebar-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}">
                            <x-lucide-list-checks class="w-4 h-4"/>
                            <span>Activity Logs</span>
                        </a>
                    </li>
                @endif
            @endif

            @if($canUseAdministration)
                <li class="sidebar-nav-heading">Administration</li>
            @endif

            @if($currentUser->hasCurrentCompanyPermission('company.manage'))
                <li class="sidebar-item">
                    <a href="{{ route('settings.company') }}" wire:navigate
                       class="sidebar-link {{ request()->routeIs('settings.company') ? 'active' : '' }}">
                        <x-lucide-settings class="w-4 h-4"/>
                        <span>Company Setup</span>
                    </a>
                </li>
            @endif

            @if($currentUser->hasCurrentCompanyPermission('documents.manage'))
                <li class="sidebar-item">
                    <a href="{{ route('documents.index') }}" wire:navigate
                       class="sidebar-link {{ request()->routeIs('documents.*') ? 'active' : '' }}">
                        <x-lucide-folder-open class="w-4 h-4"/>
                        <span>Documents</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a href="{{ route('document-deliveries.index') }}" wire:navigate
                       class="sidebar-link {{ request()->routeIs('document-deliveries.*') ? 'active' : '' }}">
                        <x-lucide-send class="w-4 h-4"/>
                        <span>Delivery Log</span>
                    </a>
                </li>
            @endif

            @if($currentUser->hasCurrentCompanyPermission('locations.manage'))
                <li class="sidebar-item">
                    <a href="{{ route('settings.locations') }}" wire:navigate
                       class="sidebar-link {{ request()->routeIs('settings.locations') ? 'active' : '' }}">
                        <x-lucide-warehouse class="w-4 h-4"/>
                        <span>Locations</span>
                    </a>
                </li>
            @endif

            @if($currentUser->hasCurrentCompanyPermission('team.manage'))
                <li class="sidebar-item">
                    <a href="{{ route('settings.team') }}" wire:navigate
                       class="sidebar-link {{ request()->routeIs('settings.team') ? 'active' : '' }}">
                        <x-lucide-users-round class="w-4 h-4"/>
                        <span>Team & Roles</span>
                    </a>
                </li>
            @endif

            @if($currentUser->hasCurrentCompanyPermission('roles.manage'))
                <li class="sidebar-item">
                    <a href="{{ route('settings.roles') }}" wire:navigate
                       class="sidebar-link {{ request()->routeIs('settings.roles') ? 'active' : '' }}">
                        <x-lucide-shield-check class="w-4 h-4"/>
                        <span>Roles & Permissions</span>
                    </a>
                </li>
            @endif

            <li class="sidebar-item">
                <a href="{{ route('settings.profile') }}" wire:navigate
                   class="sidebar-link {{ request()->routeIs('settings.profile*') ? 'active' : '' }}">
                    <x-lucide-user-cog class="w-4 h-4"/>
                    <span>My Profile</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <span class="status-dot"></span>
            <span>Tenant scoped workspace</span>
        </div>
    </div>
</aside>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
