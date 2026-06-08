<nav class="nav flex-column mb-4">
    @php($currentUser = auth()->user())
    <div class="h-100">
        <ul class="sidebar-nav">
            @if($currentUser->hasCurrentCompanyPermission('company.manage'))
                <li class="sidebar-item">
                    <a href="{{ route('settings.company') }}" class="sidebar-link text-gray-600 font-bold {{ request()->routeIs('settings.company') ? 'active' : '' }}"
                       wire:navigate>
                        Company Setup
                    </a>
                </li>
            @endif
            @if($currentUser->hasCurrentCompanyPermission('locations.manage'))
                <li class="sidebar-item">
                    <a href="{{ route('settings.locations') }}" class="sidebar-link text-gray-600 font-bold {{ request()->routeIs('settings.locations') ? 'active' : '' }}"
                       wire:navigate>
                        Locations
                    </a>
                </li>
            @endif
            @if($currentUser->hasCurrentCompanyPermission('team.manage'))
                <li class="sidebar-item">
                    <a href="{{ route('settings.team') }}" class="sidebar-link text-gray-600 font-bold {{ request()->routeIs('settings.team') ? 'active' : '' }}"
                       wire:navigate>
                        Team & Roles
                    </a>
                </li>
            @endif
            @if($currentUser->hasCurrentCompanyPermission('roles.manage'))
                <li class="sidebar-item">
                    <a href="{{ route('settings.roles') }}" class="sidebar-link text-gray-600 font-bold {{ request()->routeIs('settings.roles') ? 'active' : '' }}"
                       wire:navigate>
                        Roles & Permissions
                    </a>
                </li>
            @endif
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
