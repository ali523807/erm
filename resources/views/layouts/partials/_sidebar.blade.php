<aside id="sidebar" class="js-sidebar">
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
            <li class="sidebar-nav-heading">Overview</li>

            <li class="sidebar-item">
                <a href="{{ route('home') }}" wire:navigate
                   class="sidebar-link {{ request()->routeIs('home') ? 'active' : '' }}">
                    <x-lucide-layout-dashboard class="w-4 h-4"/>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="sidebar-nav-heading">Rental Desk</li>

            <li class="sidebar-item">
                <a href="{{ route('quotes.index') }}" wire:navigate
                   class="sidebar-link {{ request()->routeIs('quotes.*') ? 'active' : '' }}">
                    <x-lucide-file-signature class="w-4 h-4"/>
                    <span>Quotes</span>
                </a>
            </li>

            <li class="sidebar-item">
                <a href="{{ route('rentals.index') }}" wire:navigate
                   class="sidebar-link {{ request()->routeIs('rentals.*') || request()->routeIs('agreements.*') ? 'active' : '' }}">
                    <x-lucide-file-box class="w-4 h-4"/>
                    <span>Rentals</span>
                </a>
            </li>

            <li class="sidebar-item">
                <a href="{{ route('availability.index') }}" wire:navigate
                   class="sidebar-link {{ request()->routeIs('availability.*') ? 'active' : '' }}">
                    <x-lucide-calendar-check class="w-4 h-4"/>
                    <span>Availability</span>
                </a>
            </li>

            <li class="sidebar-nav-heading">Fleet</li>

            <li class="sidebar-item">
                <a href="{{ route('categories.index') }}" wire:navigate
                   class="sidebar-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                    <x-lucide-layout-grid class="w-4 h-4"/>
                    <span>Categories</span>
                </a>
            </li>

            <li class="sidebar-item">
                <a href="{{ route('products.index') }}" wire:navigate
                   class="sidebar-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                    <x-lucide-package-search class="w-4 h-4"/>
                    <span>Equipment</span>
                </a>
            </li>

            <li class="sidebar-item">
                <a href="{{ route('maintenance.index') }}" wire:navigate
                   class="sidebar-link {{ request()->routeIs('maintenance.*') ? 'active' : '' }}">
                    <x-lucide-wrench class="w-4 h-4"/>
                    <span>Maintenance</span>
                </a>
            </li>

            <li class="sidebar-nav-heading">Customers</li>

            <li class="sidebar-item">
                <a href="{{ route('customers.index') }}" wire:navigate
                   class="sidebar-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                    <x-lucide-users class="w-4 h-4"/>
                    <span>Customers</span>
                </a>
            </li>

            <li class="sidebar-nav-heading">Finance</li>

            <li class="sidebar-item">
                <a href="{{ route('invoices.index') }}" wire:navigate
                   class="sidebar-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                    <x-lucide-file-text class="w-4 h-4"/>
                    <span>Invoices</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ route('payments.index') }}" wire:navigate
                   class="sidebar-link {{ request()->routeIs('payments.*') ? 'active' : '' }}">
                    <x-lucide-credit-card class="w-4 h-4"/>
                    <span>Payments</span>
                </a>
            </li>

            <li class="sidebar-nav-heading">Insights</li>

            <li class="sidebar-item">
                <a href="{{ route('reports.index') }}" wire:navigate
                   class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <x-lucide-bar-chart-3 class="w-4 h-4"/>
                    <span>Reports</span>
                </a>
            </li>

            <li class="sidebar-nav-heading">Administration</li>

            <li class="sidebar-item">
                <a href="{{ route('settings.company') }}" wire:navigate
                   class="sidebar-link {{ request()->routeIs('settings.company') ? 'active' : '' }}">
                    <x-lucide-settings class="w-4 h-4"/>
                    <span>Company Setup</span>
                </a>
            </li>

            <li class="sidebar-item">
                <a href="{{ route('settings.locations') }}" wire:navigate
                   class="sidebar-link {{ request()->routeIs('settings.locations') ? 'active' : '' }}">
                    <x-lucide-warehouse class="w-4 h-4"/>
                    <span>Locations</span>
                </a>
            </li>

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
