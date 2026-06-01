<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>@yield('title') - Platform - {{ config('app.name') }}</title>

    @routes

    @vite('resources/js/jquery.js')
    @vite('resources/js/jqueryui.js')
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"/>
    @stack('css')
</head>

<body>
<div class="platform-shell">
    <aside class="platform-sidebar">
        <a href="{{ route('platform.dashboard') }}" class="brand-lockup">
            <span class="brand-mark">
                <x-lucide-monitor-cog class="w-5 h-5"/>
            </span>
            <span>
                <strong>ERM Platform</strong>
                <small>SaaS owner panel</small>
            </span>
        </a>

        <nav class="platform-nav">
            <a href="{{ route('platform.dashboard') }}" class="{{ request()->routeIs('platform.dashboard') ? 'active' : '' }}">
                <x-lucide-layout-dashboard class="w-4 h-4"/>
                Dashboard
            </a>
            <a href="{{ route('platform.companies.index') }}" class="{{ request()->routeIs('platform.companies.*') ? 'active' : '' }}">
                <x-lucide-building-2 class="w-4 h-4"/>
                Companies
            </a>
        </nav>

        <form method="POST" action="{{ route('platform.logout') }}" class="mt-auto">
            @csrf
            <button type="submit" class="platform-logout">
                <x-lucide-log-out class="w-4 h-4"/>
                Logout
            </button>
        </form>
    </aside>

    <main class="platform-main">
        <header class="platform-topbar">
            <div>
                <strong>{{ auth('platform')->user()->name }}</strong>
                <small>{{ auth('platform')->user()->email }}</small>
            </div>
        </header>

        <div class="platform-content">
            @yield('content')
        </div>
    </main>
</div>
@stack('js')
@livewireScripts
</body>
</html>
