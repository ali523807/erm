<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Customer Portal - {{ config('app.name') }}</title>
    @routes
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
<div class="platform-shell">
    <aside class="platform-sidebar">
        <a href="{{ route('customer-portal.dashboard') }}" class="brand-lockup">
            <span class="brand-mark"><x-lucide-briefcase-business class="w-5 h-5"/></span>
            <span>
                <strong>Customer Portal</strong>
                <small>{{ auth('customer')->user()->company->name }}</small>
            </span>
        </a>

        <nav class="platform-nav">
            <a href="{{ route('customer-portal.dashboard') }}" class="{{ request()->routeIs('customer-portal.dashboard') ? 'active' : '' }}"><x-lucide-layout-dashboard class="w-4 h-4"/>Dashboard</a>
            <a href="{{ route('customer-portal.quotes') }}" class="{{ request()->routeIs('customer-portal.quotes') ? 'active' : '' }}"><x-lucide-file-signature class="w-4 h-4"/>Quotes</a>
            <a href="{{ route('customer-portal.rentals') }}" class="{{ request()->routeIs('customer-portal.rentals') ? 'active' : '' }}"><x-lucide-file-box class="w-4 h-4"/>Rentals</a>
            <a href="{{ route('customer-portal.invoices') }}" class="{{ request()->routeIs('customer-portal.invoices') ? 'active' : '' }}"><x-lucide-file-text class="w-4 h-4"/>Invoices</a>
            <a href="{{ route('customer-portal.documents') }}" class="{{ request()->routeIs('customer-portal.documents') ? 'active' : '' }}"><x-lucide-folder-open class="w-4 h-4"/>Documents</a>
        </nav>

        <form method="POST" action="{{ route('customer-portal.logout') }}" class="mt-auto">
            @csrf
            <button type="submit" class="platform-logout"><x-lucide-log-out class="w-4 h-4"/>Logout</button>
        </form>
    </aside>

    <main class="platform-main">
        <header class="platform-topbar">
            <div>
                <strong>{{ auth('customer')->user()->name }}</strong>
                <small>{{ auth('customer')->user()->customer->company_name }}</small>
            </div>
        </header>

        <div class="platform-content">
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
