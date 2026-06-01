<nav class="nav flex-column mb-4">
    <div class="h-100">
        <ul class="sidebar-nav">
            <li class="sidebar-item">
                <a href="{{ route('settings.company') }}" class="sidebar-link text-gray-600 font-bold {{ request()->routeIs('settings.company') ? 'active' : '' }}"
                   wire:navigate>
                    Company Setup
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ route('settings.locations') }}" class="sidebar-link text-gray-600 font-bold {{ request()->routeIs('settings.locations') ? 'active' : '' }}"
                   wire:navigate>
                    Locations
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ route('settings.profile') }}" class="sidebar-link text-gray-600 font-bold {{ request()->routeIs('settings.profile') ? 'active' : '' }}"
                   wire:navigate>
                    Profile
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ route('settings.profile.password-update') }}" class="sidebar-link text-gray-600 font-bold {{ request()->routeIs('settings.profile.password-update') ? 'active' : '' }}"
                   wire:navigate>
                    Password
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ route('settings.profile.appearance') }}" class="sidebar-link text-gray-600 font-bold {{ request()->routeIs('settings.profile.appearance') ? 'active' : '' }}"
                   wire:navigate>
                    Appearance
                </a>
            </li>
        </ul>
    </div>
</nav>
