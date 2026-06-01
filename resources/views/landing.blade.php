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
            <small>Equipment rental SaaS</small>
        </span>
    </a>

    <nav>
        <a href="#software">Software</a>
        <a href="#workflows">Workflows</a>
        <a href="#pricing">Pricing</a>
        <a href="{{ route('login') }}">Login</a>
        <a href="{{ route('register') }}" class="btn btn-dark">Start Free Trial</a>
    </nav>
</header>

<main>
    <section class="landing-hero">
        <img src="{{ asset('images/landing-equipment-yard.jpg') }}" alt="Construction equipment yard">
        <div class="landing-hero-overlay"></div>
        <div class="landing-hero-content">
            <span class="eyebrow">Global Equipment Rental Platform</span>
            <h1>ERM Cloud</h1>
            <p>Modern SaaS software for rental companies that need to control fleet availability, quotes, rentals, contracts, invoices, payments, maintenance, and client subscriptions from one clean workspace.</p>
            <div class="landing-actions">
                <a href="{{ route('register') }}" class="btn btn-light btn-lg">
                    <x-lucide-rocket class="w-4 h-4"/>
                    Register Company
                </a>
                <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg">
                    <x-lucide-log-in class="w-4 h-4"/>
                    Client Login
                </a>
            </div>
        </div>
    </section>

    <section class="landing-software-band" id="software">
        <div class="landing-section-heading">
            <span class="eyebrow">Software Glimpse</span>
            <h2>A focused operating system for rental teams.</h2>
            <p>Every screen is designed around daily rental work: know what is available, what is on rent, what needs billing, and what needs attention.</p>
        </div>

        <div class="software-preview-grid">
            <article class="software-preview is-large">
                <div class="software-window">
                    <div class="software-window-top">
                        <span></span><span></span><span></span>
                    </div>
                    <div class="software-dashboard-mock">
                        <aside>
                            <strong>ERM Cloud</strong>
                            <span class="active">Dashboard</span>
                            <span>Rentals</span>
                            <span>Fleet</span>
                            <span>Invoices</span>
                            <span>Reports</span>
                        </aside>
                        <section>
                            <div class="mock-header">
                                <div>
                                    <small>Operations Dashboard</small>
                                    <strong>Global Demo Rentals</strong>
                                </div>
                                <button>New Rental</button>
                            </div>
                            <div class="mock-metrics">
                                <div><span>Invoiced</span><strong>$18.4k</strong></div>
                                <div><span>Active Rentals</span><strong>12</strong></div>
                                <div><span>Available</span><strong>84</strong></div>
                            </div>
                            <div class="mock-table">
                                <span></span><span></span><span></span>
                                <span></span><span></span><span></span>
                                <span></span><span></span><span></span>
                            </div>
                        </section>
                    </div>
                </div>
                <h3>Live business dashboard</h3>
                <p>Track revenue, collections, active rentals, due returns, fleet utilization, and maintenance alerts from the first screen.</p>
            </article>

            <article class="software-preview">
                <x-lucide-file-signature class="w-7 h-7"/>
                <h3>Quote to rental</h3>
                <p>Create quotes, reserve equipment, and convert accepted work into rental jobs without re-entering the same data.</p>
            </article>

            <article class="software-preview">
                <x-lucide-receipt class="w-7 h-7"/>
                <h3>Invoice and receipts</h3>
                <p>Generate invoices, record payments, download PDFs, print receipts, and keep balances visible.</p>
            </article>

            <article class="software-preview">
                <x-lucide-wrench class="w-7 h-7"/>
                <h3>Fleet maintenance</h3>
                <p>Plan inspections, log repairs, track downtime, and keep unavailable equipment out of rental flow.</p>
            </article>
        </div>
    </section>

    <section class="landing-band landing-primary-band" id="workflows">
        <div class="landing-section-heading">
            <span class="eyebrow">Built For The Full Rental Lifecycle</span>
            <h2>From company registration to signed return.</h2>
        </div>
        <div class="workflow-grid">
            <article>
                <span>01</span>
                <strong>Company Onboarding</strong>
                <p>Register a rental company under a SaaS plan and keep tenant data separated by company.</p>
            </article>
            <article>
                <span>02</span>
                <strong>Fleet Setup</strong>
                <p>Create categories, flexible equipment records, custom attributes, documents, locations, and availability.</p>
            </article>
            <article>
                <span>03</span>
                <strong>Rental Operations</strong>
                <p>Build quotes, create rentals, generate agreements, check out equipment, and capture return sign-off.</p>
            </article>
            <article>
                <span>04</span>
                <strong>Billing Control</strong>
                <p>Generate invoices, record payments, download PDFs, track outstanding balances, and review reports.</p>
            </article>
        </div>
    </section>

    <section class="landing-band">
        <div class="landing-section-heading">
            <span class="eyebrow">Why Teams Use It</span>
            <h2>Designed for equipment rental companies, not generic inventory.</h2>
        </div>
        <div class="feature-grid">
            <article>
                <x-lucide-package-search class="w-6 h-6"/>
                <strong>Any equipment type</strong>
                <p>Support generators, vehicles, tools, cameras, event gear, heavy machinery, and custom category attributes.</p>
            </article>
            <article>
                <x-lucide-calendar-check class="w-6 h-6"/>
                <strong>Availability awareness</strong>
                <p>Check rental and maintenance conflicts before committing equipment to a customer.</p>
            </article>
            <article>
                <x-lucide-monitor-cog class="w-6 h-6"/>
                <strong>Platform owner panel</strong>
                <p>Separate SaaS owner login for registered companies, subscriptions, billing status, and active clients.</p>
            </article>
        </div>
    </section>

    <section class="landing-band pricing-band" id="pricing">
        <div class="landing-section-heading">
            <span class="eyebrow">Subscription Plans</span>
            <h2>Choose a SaaS plan and start your rental workspace.</h2>
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
