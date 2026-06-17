    <!DOCTYPE html>
<html lang="en" data-bs-theme="light" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>@yield('title') - {{ config('app.name') }}</title>
    @include('layouts.partials._favicons')

    @vite('resources/js/jquery.js')
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>

<body class="h-100 auth-bg">

<main class="auth-shell">
    <section class="auth-visual" aria-label="RentalHook preview">
        <img src="{{ asset('images/landing-equipment-yard.jpg') }}" alt="Equipment rental yard">
        <div class="auth-visual-overlay"></div>
        <div class="auth-visual-content">
            <a href="{{ route('landing') }}" class="auth-brand">
                <span class="brand-mark">
                    <x-application-logo/>
                </span>
                <span>
                    <strong>RentalHook</strong>
                    <small>The Complete Equipment Rental Platform</small>
                </span>
            </a>
            <div>
                <span class="eyebrow">Rental operations platform</span>
                <h1>The Complete Equipment Rental Platform.</h1>
                <p>Fleet, customers, quotes, rentals, agreements, invoices, payments, deposits, maintenance, reports, and customer portal access in one workspace.</p>
            </div>
            <div class="auth-proof-grid">
                <div>
                    <strong>Global ready</strong>
                    <span>Currency, tax, branches, roles</span>
                </div>
                <div>
                    <strong>Asset based</strong>
                    <span>Built for equipment, not stock quantity</span>
                </div>
            </div>
        </div>
    </section>

    <section class="auth-panel">
        <div class="auth-container">
            <div class="auth-card">
                @yield('content')
            </div>
        </div>
    </section>
</main>
@stack('js')
@livewireScripts

</body>
