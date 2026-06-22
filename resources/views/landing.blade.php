<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>RentalHook - The Complete Equipment Rental Platform</title>
    @include('layouts.partials._favicons')

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="landing-page">
@php($money = app(\App\Support\Money::class))
<header class="landing-nav">
    <a href="{{ route('landing') }}" class="brand-lockup">
        <span class="brand-mark">
            <x-application-logo/>
        </span>
        <span>
            <strong>RentalHook</strong>
            <small>The Complete Equipment Rental Platform</small>
        </span>
    </a>

    <button class="landing-nav-toggle" type="button" aria-expanded="false" aria-controls="landing-menu" aria-label="Toggle navigation">
        <x-lucide-menu class="landing-nav-icon landing-nav-icon-menu"/>
        <x-lucide-x class="landing-nav-icon landing-nav-icon-close"/>
    </button>

    <nav id="landing-menu" class="landing-menu">
        <a href="#software">Software</a>
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
            <span class="eyebrow">The Complete Equipment Rental Platform</span>
            <h1>RentalHook</h1>
            <p>Run your rental company with customers, equipment, quotes, rentals, agreements, invoices, payments, deposits, maintenance, documents, and reports in one modern cloud workspace.</p>
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
            <div class="hero-proof-grid" aria-label="Software highlights">
                <div>
                    <strong>Company workspace</strong>
                    <span>Your team, fleet, customers, and finance in one place</span>
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

    <section class="landing-software-band" id="software">
        <div class="landing-section-heading">
            <span class="eyebrow">Software Glimpse</span>
            <h2>Built around the real flow of a rental business.</h2>
            <p>RentalHook gives equipment rental companies a complete workspace for daily operations, asset control, customer service, and finance.</p>
        </div>

        <div class="software-preview-grid">
            <article class="software-preview is-large">
                <div class="software-window">
                    <div class="software-window-top">
                        <span></span><span></span><span></span>
                    </div>
                    <div class="software-dashboard-mock">
                        <aside>
                            <strong>RentalHook</strong>
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
                <h3>Rental operations workspace</h3>
                <p>Manage customers, equipment, availability, rental lifecycle, billing, users, permissions, and reports from one company account.</p>
            </article>

            <article class="software-preview">
                <span class="software-preview-icon">
                    <x-lucide-briefcase-business/>
                </span>
                <h3>Owner control</h3>
                <p>Give rental business owners clear visibility over revenue, active rentals, payments, teams, reports, and operating performance.</p>
            </article>

            <article class="software-preview">
                <span class="software-preview-icon">
                    <x-lucide-package-search/>
                </span>
                <h3>Flexible equipment setup</h3>
                <p>Support any asset type with categories, attribute templates, rate cards, documents, locations, and availability checks.</p>
            </article>

            <article class="software-preview">
                <span class="software-preview-icon">
                    <x-lucide-receipt-text/>
                </span>
                <h3>Finance and documents</h3>
                <p>Create PDFs for quotes, invoices, receipts, credit notes, statements, and agreements with payment links and delivery logs.</p>
            </article>
        </div>
    </section>

    <section class="landing-band landing-primary-band">
        <div class="landing-section-heading">
            <span class="eyebrow">Built For Rental Companies</span>
            <h2>Clear tools for owners, teams, and customers.</h2>
        </div>
        <div class="workspace-grid">
            <article>
                <x-lucide-briefcase-business class="w-6 h-6"/>
                <strong>Business Owner</strong>
                <p>Track revenue, payments, outstanding invoices, equipment utilization, reports, and team activity.</p>
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
            <p>The product is no longer just rentals and invoices. It now covers the operational, financial, customer, and asset-control layers needed by a growing rental company.</p>
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
                <p>Choose a plan, create your company workspace, and invite the right users.</p>
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
                <p>Start with the modules your rental company needs today and upgrade as your operations grow.</p>
            </div>
            <a href="{{ route('register') }}" class="btn btn-outline-secondary">
                <x-lucide-building-2 class="w-4 h-4"/>
                Register Company
            </a>
        </div>

        <div class="pricing-grid">
            <?php $moduleCatalog = app(\App\Support\SubscriptionModuleCatalog::class); ?>
            <?php if ($plans->isNotEmpty()) { ?>
                <?php foreach ($plans as $plan) { ?>
                    <?php
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
                            default => ['Company workspace', 'Rental operations', 'Subscription billing'],
                        };
                        $includedModules = $moduleCatalog->featureLabelsForPlan($plan);
                        $featureList = array_values(array_unique(array_merge($extraFeatures, $plan->features ?? [])));
                    ?>
                    <article class="pricing-card <?php echo e($isFeatured ? 'is-featured' : ''); ?>">
                        <?php if ($isFeatured) { ?>
                            <span class="plan-badge">Recommended</span>
                        <?php } ?>
                        <span class="plan-audience"><?php echo e($audience); ?></span>
                        <h3><?php echo e($plan->name); ?></h3>
                        <p><?php echo e($plan->description); ?></p>
                        <div class="plan-price">
                            <strong><?php echo e($money->format($plan->monthly_price, 'USD')); ?></strong>
                            <span>/ month</span>
                        </div>
                        <?php if ($plan->yearly_price) { ?>
                            <div class="plan-yearly"><?php echo e($money->format($plan->yearly_price, 'USD')); ?> billed yearly</div>
                        <?php } ?>
                        <div class="plan-module-list">
                            <span>Included modules</span>
                            <div>
                                <?php foreach ($includedModules as $module) { ?>
                                    <small><?php echo e($module); ?></small>
                                <?php } ?>
                            </div>
                        </div>
                        <ul>
                            <?php foreach ($featureList as $feature) { ?>
                                <li>
                                    <span aria-hidden="true">&check;</span>
                                    <?php echo e($feature); ?>
                                </li>
                            <?php } ?>
                            <li>
                                <span aria-hidden="true">&check;</span>
                                <?php echo e($plan->user_limit ? number_format($plan->user_limit).' users included' : 'Unlimited users'); ?>
                            </li>
                            <li>
                                <span aria-hidden="true">&check;</span>
                                <?php echo e($plan->equipment_limit ? number_format($plan->equipment_limit).' equipment records' : 'Unlimited equipment records'); ?>
                            </li>
                        </ul>
                        <a href="<?php echo e(route('register', ['plan' => $plan->slug])); ?>" class="btn <?php echo e($isFeatured ? 'btn-dark' : 'btn-outline-secondary'); ?> w-100">
                            Start <?php echo e($plan->name); ?>
                        </a>
                    </article>
                <?php } ?>
            <?php } else { ?>
                <div class="panel">
                    <h3>No plans configured yet.</h3>
                    <p class="mb-0 text-muted">Subscription plans will appear here once pricing is configured.</p>
                </div>
            <?php } ?>
        </div>
    </section>
</main>

<footer class="landing-footer">
    <span>RentalHook</span>
    <div>
        <a href="{{ route('login') }}">Login</a>
        <a href="{{ route('customer-portal.login') }}">Customer Portal</a>
    </div>
</footer>
</body>
</html>
