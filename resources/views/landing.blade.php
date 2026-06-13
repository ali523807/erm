<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>ERM Cloud - Global Equipment Rental Management SaaS</title>

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
        <a href="#platform">Platform</a>
        <a href="#modules">Modules</a>
        <a href="#pricing">Pricing</a>
        <a href="{{ route('login') }}">Login</a>
        <a href="{{ route('register') }}" class="btn btn-dark">Start Free Trial</a>
    </nav>
</header>

<main>
    <section class="landing-hero">
        <img src="{{ asset('images/landing-equipment-yard.jpg') }}" alt="Equipment rental yard with machinery ready for dispatch">
        <div class="landing-hero-overlay"></div>
        <div class="landing-hero-content">
            <span class="eyebrow">Global Equipment Rental Management SaaS</span>
            <h1>ERM Cloud</h1>
            <p>Run rental companies, customers, equipment, quotes, rentals, agreements, invoices, payments, deposits, maintenance, documents, reports, and subscriptions from one modern cloud platform.</p>
            <div class="landing-actions">
                <a href="{{ route('register') }}" class="btn btn-light btn-lg">
                    <x-lucide-rocket class="w-4 h-4"/>
                    Register Company
                </a>
                <a href="#pricing" class="btn btn-outline-light btn-lg">
                    <x-lucide-layers-3 class="w-4 h-4"/>
                    View Plans
                </a>
            </div>
            <div class="hero-proof-grid" aria-label="Platform highlights">
                <div>
                    <strong>Multi-tenant</strong>
                    <span>Separate SaaS owner and tenant workspaces</span>
                </div>
                <div>
                    <strong>Global ready</strong>
                    <span>Currency, tax, locations, roles, and documents</span>
                </div>
                <div>
                    <strong>Full lifecycle</strong>
                    <span>Quote to rental, return, invoice, payment, and close-out</span>
                </div>
            </div>
        </div>
    </section>

    <section class="landing-software-band" id="platform">
        <div class="landing-section-heading">
            <span class="eyebrow">Software Glimpse</span>
            <h2>Built around the real flow of a rental business.</h2>
            <p>ERM Cloud separates platform ownership from tenant operations, then gives each rental company a complete workspace for daily operations and finance.</p>
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
                            <span>Customers</span>
                            <span>Fleet</span>
                            <span>Rentals</span>
                            <span>Finance</span>
                            <span>Reports</span>
                        </aside>
                        <section>
                            <div class="mock-header">
                                <div>
                                    <small>Operations Dashboard</small>
                                    <strong>Global Demo Rentals</strong>
                                </div>
                                <button>New Quote</button>
                            </div>
                            <div class="mock-metrics">
                                <div><span>Revenue</span><strong>$42.8k</strong></div>
                                <div><span>Active Rentals</span><strong>18</strong></div>
                                <div><span>Utilization</span><strong>74%</strong></div>
                            </div>
                            <div class="mock-schedule">
                                <div><span>Today</span><strong>3 dispatches</strong><small>2 returns due</small></div>
                                <div><span>Finance</span><strong>$6.4k due</strong><small>5 open invoices</small></div>
                            </div>
                            <div class="mock-table">
                                <span></span><span></span><span></span>
                                <span></span><span></span><span></span>
                                <span></span><span></span><span></span>
                            </div>
                        </section>
                    </div>
                </div>
                <h3>Tenant operations workspace</h3>
                <p>Each rental company manages its own customers, equipment, availability, rental lifecycle, billing, users, permissions, and reports.</p>
            </article>

            <article class="software-preview">
                <x-lucide-monitor-cog class="w-7 h-7"/>
                <h3>Platform owner control</h3>
                <p>Manage registered companies, subscriptions, billing dates, plan status, and active client accounts from a separate owner panel.</p>
            </article>

            <article class="software-preview">
                <x-lucide-package-search class="w-7 h-7"/>
                <h3>Flexible equipment setup</h3>
                <p>Support any asset type with categories, attribute templates, rate cards, documents, locations, and availability checks.</p>
            </article>

            <article class="software-preview">
                <x-lucide-receipt-text class="w-7 h-7"/>
                <h3>Finance and documents</h3>
                <p>Create PDFs for quotes, invoices, receipts, credit notes, statements, and agreements with payment links and delivery logs.</p>
            </article>
        </div>
    </section>

    <section class="landing-band landing-primary-band">
        <div class="landing-section-heading">
            <span class="eyebrow">Three Workspaces</span>
            <h2>Clear separation for SaaS ownership, rental teams, and customers.</h2>
        </div>
        <div class="workspace-grid">
            <article>
                <x-lucide-shield-check class="w-6 h-6"/>
                <strong>Platform Owner</strong>
                <p>Track client companies, subscriptions, plan changes, billing status, and recurring revenue.</p>
            </article>
            <article>
                <x-lucide-hard-hat class="w-6 h-6"/>
                <strong>Rental Company</strong>
                <p>Run fleet, customers, quotes, rentals, dispatch, returns, finance, maintenance, roles, and reports.</p>
            </article>
            <article>
                <x-lucide-user-round-check class="w-6 h-6"/>
                <strong>Customer Portal</strong>
                <p>Let customers view quotes, rentals, invoices, documents, and online payment actions.</p>
            </article>
        </div>
    </section>

    <section class="landing-band" id="modules">
        <div class="landing-section-heading">
            <span class="eyebrow">Modules Covered</span>
            <h2>A complete suite for asset-based rental operations.</h2>
            <p>The product is no longer just rentals and invoices. It now covers the operational, financial, customer, and SaaS layers needed for a real subscription product.</p>
        </div>

        <div class="module-grid">
            <article>
                <x-lucide-layout-dashboard class="w-5 h-5"/>
                <strong>Dashboard and reports</strong>
                <p>Actual statistics, profitability, margin, utilization, payments, expenses, and operating summaries.</p>
            </article>
            <article>
                <x-lucide-tags class="w-5 h-5"/>
                <strong>Categories and attributes</strong>
                <p>Flexible templates for generators, vehicles, tools, event gear, medical devices, or any custom equipment type.</p>
            </article>
            <article>
                <x-lucide-truck class="w-5 h-5"/>
                <strong>Equipment and availability</strong>
                <p>Asset records, rate cards, documents, status, maintenance blocks, and date conflict checks.</p>
            </article>
            <article>
                <x-lucide-users class="w-5 h-5"/>
                <strong>Customers and portal</strong>
                <p>Customer records, statements, portal users, invoice access, documents, and payment actions.</p>
            </article>
            <article>
                <x-lucide-file-signature class="w-5 h-5"/>
                <strong>Quotes and agreements</strong>
                <p>Quote PDFs, approvals, conversion to rentals, agreement PDFs, checkout sign-off, and return acceptance.</p>
            </article>
            <article>
                <x-lucide-calendar-check class="w-5 h-5"/>
                <strong>Rental operations</strong>
                <p>Reservations, active rentals, dispatch, returns, close-out checklist, deposits, transport and billable expenses.</p>
            </article>
            <article>
                <x-lucide-wallet-cards class="w-5 h-5"/>
                <strong>Invoices and payments</strong>
                <p>Tax, currency, exchange rates, payment links, receipts, credit notes, deposits, and PDF downloads.</p>
            </article>
            <article>
                <x-lucide-wrench class="w-5 h-5"/>
                <strong>Maintenance and work orders</strong>
                <p>Inspection history, repair status, downtime, return damage, and asset health visibility.</p>
            </article>
            <article>
                <x-lucide-lock-keyhole class="w-5 h-5"/>
                <strong>Team and permissions</strong>
                <p>Team members, passwords, role templates, permissions, activity logs, and safer access control.</p>
            </article>
        </div>
    </section>

    <section class="landing-band workflow-band" id="workflows">
        <div class="landing-section-heading">
            <span class="eyebrow">End-to-End Flow</span>
            <h2>From signup to final close-out.</h2>
        </div>
        <div class="workflow-grid">
            <article>
                <span>01</span>
                <strong>Register company</strong>
                <p>Choose a plan, create the tenant workspace, and assign the owner user.</p>
            </article>
            <article>
                <span>02</span>
                <strong>Configure fleet</strong>
                <p>Add categories, attributes, equipment, rates, tax, currency, users, and locations.</p>
            </article>
            <article>
                <span>03</span>
                <strong>Operate rentals</strong>
                <p>Quote, reserve, dispatch, return, inspect, maintain, and prevent double booking.</p>
            </article>
            <article>
                <span>04</span>
                <strong>Collect revenue</strong>
                <p>Invoice, send payment links, receive payments, manage credits, deposits, and expenses.</p>
            </article>
            <article>
                <span>05</span>
                <strong>Close and report</strong>
                <p>Use close-out checks, statements, profitability, reports, and document delivery history.</p>
            </article>
        </div>
    </section>

    <section class="landing-band pricing-band" id="pricing">
        <div class="landing-section-heading pricing-heading">
            <div>
                <span class="eyebrow">Subscription Plans</span>
                <h2>Pick the right plan for each rental company.</h2>
                <p>Plans are managed from the platform database and appear here automatically for new company registration.</p>
            </div>
            <a href="{{ route('register') }}" class="btn btn-outline-secondary">
                <x-lucide-building-2 class="w-4 h-4"/>
                Register Company
            </a>
        </div>

        <div class="pricing-grid">
            @forelse($plans as $plan)
                @php
                    $moduleCatalog = app(\App\Support\SubscriptionModuleCatalog::class);
                    $isFeatured = $plan->slug === 'business';
                    $audience = match ($plan->slug) {
                        'starter' => 'Small teams starting with core rentals',
                        'business' => 'Growing rental companies with finance and operations',
                        'enterprise' => 'Multi-branch teams needing scale and control',
                        default => 'Rental teams ready for a cloud workspace',
                    };
                    $extraFeatures = match ($plan->slug) {
                        'starter' => ['Customer and equipment records', 'Core rental workflow', 'Basic billing visibility'],
                        'business' => ['Quotes, rentals, invoices, payments', 'Maintenance, deposits, reports', 'Customer portal and documents'],
                        'enterprise' => ['Unlimited operating scale', 'Advanced controls and analytics', 'Priority rollout support'],
                        default => ['Tenant workspace', 'Rental operations', 'Subscription billing'],
                    };
                    $includedModules = $moduleCatalog->featureLabelsForPlan($plan);
                @endphp
                <article class="pricing-card {{ $isFeatured ? 'is-featured' : '' }}">
                    @if($isFeatured)
                        <span class="plan-badge">Recommended</span>
                    @endif
                    <span class="plan-audience">{{ $audience }}</span>
                    <h3>{{ $plan->name }}</h3>
                    <p>{{ $plan->description }}</p>
                    <div class="plan-price">
                        <strong>${{ number_format($plan->monthly_price, 0) }}</strong>
                        <span>/ month</span>
                    </div>
                    @if($plan->yearly_price)
                        <div class="plan-yearly">${{ number_format($plan->yearly_price, 0) }} billed yearly</div>
                    @endif
                    <div class="plan-module-list">
                        <span>Included modules</span>
                        <div>
                            @foreach($includedModules as $module)
                                <small>{{ $module }}</small>
                            @endforeach
                        </div>
                    </div>
                    <ul>
                        @foreach(array_values(array_unique(array_merge($extraFeatures, $plan->features ?? []))) as $feature)
                            <li>
                                <x-lucide-check class="w-4 h-4"/>
                                {{ $feature }}
                            </li>
                        @endforeach
                        <li>
                            <x-lucide-check class="w-4 h-4"/>
                            {{ $plan->user_limit ? number_format($plan->user_limit).' users included' : 'Unlimited users' }}
                        </li>
                        <li>
                            <x-lucide-check class="w-4 h-4"/>
                            {{ $plan->equipment_limit ? number_format($plan->equipment_limit).' equipment records' : 'Unlimited equipment records' }}
                        </li>
                    </ul>
                    <a href="{{ route('register', ['plan' => $plan->slug]) }}" class="btn {{ $isFeatured ? 'btn-dark' : 'btn-outline-secondary' }} w-100">
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
        <a href="{{ route('login') }}">Login</a>
        <a href="{{ route('customer-portal.login') }}">Customer Portal</a>
    </div>
</footer>
</body>
</html>
