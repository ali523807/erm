<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Equipment Rental Management SaaS - {{ config('app.name') }}</title>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="landing-page">
<header class="landing-nav">
    <a href="{{ route('landing') }}" class="brand-lockup">
        <span class="brand-mark">
            <x-lucide-building-2 class="w-5 h-5"/>
        </span>
        <span>
            <strong>ERM Cloud</strong>
            <small>Rental operations SaaS</small>
        </span>
    </a>

    <nav>
        <a href="#pricing">Pricing</a>
        <a href="{{ route('login') }}">Login</a>
        <a href="{{ route('platform.login') }}">Platform Login</a>
        <a href="{{ route('register') }}" class="btn btn-dark">Start Free Trial</a>
    </nav>
</header>

<main>
    <section class="landing-hero">
        <img src="{{ asset('images/landing-equipment-yard.jpg') }}" alt="Construction equipment yard">
        <div class="landing-hero-overlay"></div>
        <div class="landing-hero-content">
            <span class="eyebrow">Equipment Rental Management</span>
            <h1>Run every rental, return, customer, and invoice from one cloud workspace.</h1>
            <p>ERM Cloud gives equipment rental companies a SaaS-ready system for fleet availability, contracts, billing, maintenance, and branch operations.</p>
            <div class="landing-actions">
                <a href="{{ route('register') }}" class="btn btn-light btn-lg">
                    <x-lucide-rocket class="w-4 h-4 me-1"/>
                    Register Company
                </a>
                <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg">
                    <x-lucide-log-in class="w-4 h-4 me-1"/>
                    Client Login
                </a>
            </div>
        </div>
    </section>

    <section class="landing-band">
        <div class="landing-section-heading">
            <span class="eyebrow">Built For Rental Teams</span>
            <h2>Core workflows ready for the SaaS journey.</h2>
        </div>
        <div class="feature-grid">
            <article>
                <x-lucide-package-search class="w-6 h-6"/>
                <strong>Equipment Control</strong>
                <p>Track equipment, status, categories, serial numbers, and yard availability.</p>
            </article>
            <article>
                <x-lucide-file-signature class="w-6 h-6"/>
                <strong>Rental Contracts</strong>
                <p>Create rentals with customers, dates, delivery locations, deposits, and line items.</p>
            </article>
            <article>
                <x-lucide-monitor-cog class="w-6 h-6"/>
                <strong>Platform Admin</strong>
                <p>Manage registered companies, plans, subscription status, and billing visibility.</p>
            </article>
        </div>
    </section>

    <section class="landing-band pricing-band" id="pricing">
        <div class="landing-section-heading">
            <span class="eyebrow">Subscription Plans</span>
            <h2>Choose the plan that fits your rental operation.</h2>
        </div>

        <div class="pricing-grid">
            @forelse($plans as $plan)
                <article class="pricing-card {{ $plan->slug === 'business' ? 'is-featured' : '' }}">
                    @if($plan->slug === 'business')
                        <span class="plan-badge">Popular</span>
                    @endif
                    <h3>{{ $plan->name }}</h3>
                    <p>{{ $plan->description }}</p>
                    <div class="plan-price">
                        <strong>${{ number_format($plan->monthly_price, 0) }}</strong>
                        <span>/ month</span>
                    </div>
                    <ul>
                        @foreach(($plan->features ?? []) as $feature)
                            <li>
                                <x-lucide-check class="w-4 h-4"/>
                                {{ $feature }}
                            </li>
                        @endforeach
                        <li>
                            <x-lucide-check class="w-4 h-4"/>
                            {{ $plan->user_limit ? number_format($plan->user_limit).' users' : 'Unlimited users' }}
                        </li>
                        <li>
                            <x-lucide-check class="w-4 h-4"/>
                            {{ $plan->equipment_limit ? number_format($plan->equipment_limit).' equipment records' : 'Unlimited equipment' }}
                        </li>
                    </ul>
                    <a href="{{ route('register', ['plan' => $plan->slug]) }}" class="btn {{ $plan->slug === 'business' ? 'btn-dark' : 'btn-outline-secondary' }} w-100">
                        Start {{ $plan->name }}
                    </a>
                </article>
            @empty
                <div class="panel">
                    <h3>No plans configured yet.</h3>
                    <p class="mb-0 text-muted">Add subscription plans in the platform database to show pricing.</p>
                </div>
            @endforelse
        </div>
    </section>
</main>

<footer class="landing-footer">
    <span>ERM Cloud</span>
    <div>
        <a href="{{ route('login') }}">Client Login</a>
        <a href="{{ route('platform.login') }}">Platform Login</a>
    </div>
</footer>
</body>
</html>
