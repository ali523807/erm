<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    @php
        $appUrl = rtrim(config('app.url'), '/');
        $canonicalUrl = $appUrl;
        $seoTitle = 'Equipment Rental Software Middle East | RentalHook';
        $seoDescription = 'RentalHook is equipment rental software for Middle East rental companies in the UAE, Saudi Arabia, Qatar, Oman, Bahrain, and Kuwait. Manage fleet, quotes, rentals, dispatch, invoices, payments, maintenance, and customer portals.';
        $seoKeywords = 'equipment rental software Middle East, equipment rental management software UAE, rental software Saudi Arabia, construction equipment rental software Dubai, heavy equipment rental software GCC, rental fleet management Qatar, equipment hire software Kuwait, tool rental software Oman, rental billing software Bahrain';
        $seoImage = $appUrl.'/images/landing-equipment-yard.jpg';
        $structuredData = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $appUrl.'#organization',
                    'name' => 'RentalHook',
                    'url' => $appUrl,
                    'logo' => $appUrl.'/images/rentalhook-logo.svg',
                    'email' => 'info@rentalhook.com',
                    'contactPoint' => [
                        [
                            '@type' => 'ContactPoint',
                            'email' => 'support@rentalhook.com',
                            'contactType' => 'customer support',
                            'areaServed' => ['Middle East', 'Global'],
                            'availableLanguage' => ['English'],
                        ],
                    ],
                    'areaServed' => ['United Arab Emirates', 'Saudi Arabia', 'Qatar', 'Oman', 'Bahrain', 'Kuwait', 'Middle East'],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $appUrl.'#website',
                    'url' => $appUrl,
                    'name' => 'RentalHook',
                    'publisher' => ['@id' => $appUrl.'#organization'],
                    'inLanguage' => 'en',
                ],
                [
                    '@type' => 'SoftwareApplication',
                    '@id' => $appUrl.'#software',
                    'name' => 'RentalHook',
                    'applicationCategory' => 'BusinessApplication',
                    'operatingSystem' => 'Web',
                    'description' => $seoDescription,
                    'url' => $canonicalUrl,
                    'image' => $seoImage,
                    'areaServed' => ['United Arab Emirates', 'Saudi Arabia', 'Qatar', 'Oman', 'Bahrain', 'Kuwait', 'Middle East'],
                    'offers' => [
                        '@type' => 'Offer',
                        'category' => 'SaaS subscription',
                        'availability' => 'https://schema.org/InStock',
                        'url' => $appUrl.'/register',
                    ],
                    'publisher' => ['@id' => $appUrl.'#organization'],
                ],
            ],
        ];
    @endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="keywords" content="{{ $seoKeywords }}">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="alternate" hreflang="x-default" href="{{ $canonicalUrl }}">
    <link rel="alternate" hreflang="en-AE" href="{{ $canonicalUrl }}">
    <link rel="alternate" hreflang="en-SA" href="{{ $canonicalUrl }}">
    <link rel="alternate" hreflang="en-QA" href="{{ $canonicalUrl }}">
    <link rel="alternate" hreflang="en-KW" href="{{ $canonicalUrl }}">
    <link rel="alternate" hreflang="en-OM" href="{{ $canonicalUrl }}">
    <link rel="alternate" hreflang="en-BH" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:site_name" content="RentalHook">
    <meta property="og:locale" content="en_AE">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
    <script type="application/ld+json">
        {!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
    @include('layouts.partials._favicons')

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="landing-page">
@php($money = app(\App\Support\Money::class))
@include('layouts.partials._public-nav')

<main>
    <section class="landing-hero">
        <img src="{{ asset('images/landing-equipment-yard.jpg') }}" alt="Equipment rental yard with machinery ready for dispatch">
        <div class="landing-hero-overlay"></div>
        <div class="landing-hero-content">
            <span class="eyebrow">Equipment Rental Software For The Middle East</span>
            <h1>RentalHook</h1>
            <p>Run equipment rental operations across the UAE, Saudi Arabia, Qatar, Oman, Bahrain, Kuwait, and the wider GCC with customers, fleet, quotes, rentals, dispatch, invoices, payments, maintenance, documents, and reports in one modern cloud workspace.</p>
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
                    <strong>GCC ready</strong>
                    <span>Multi-currency, tax, locations, roles, and documents</span>
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
            <h2>Built around the real flow of a Middle East rental business.</h2>
            <p>RentalHook gives equipment rental companies a complete workspace for daily operations, asset control, customer service, and finance across construction, industrial, event, tool, and heavy equipment rental teams.</p>
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

    <section class="landing-band infographic-band" id="infographics">
        <div class="landing-section-heading">
            <span class="eyebrow">RentalHook In One View</span>
            <h2>See the complete rental workflow in one clear view.</h2>
            <p>RentalHook connects owners, rental teams, finance staff, and customers from first enquiry to final payment.</p>
        </div>

        <div class="infographic-grid">
            <article class="infographic-card infographic-flow">
                <div class="infographic-card-header">
                    <span>
                        <x-lucide-route/>
                    </span>
                    <div>
                        <strong>Quote-to-cash lifecycle</strong>
                        <small>Operational flow</small>
                    </div>
                </div>
                <div class="flow-steps" aria-label="Quote to cash lifecycle">
                    <div>
                        <x-lucide-message-square-text/>
                        <strong>Enquiry</strong>
                        <span>Customer need captured</span>
                    </div>
                    <div>
                        <x-lucide-file-pen-line/>
                        <strong>Quote</strong>
                        <span>Rates, tax, terms</span>
                    </div>
                    <div>
                        <x-lucide-calendar-check/>
                        <strong>Reserve</strong>
                        <span>Availability locked</span>
                    </div>
                    <div>
                        <x-lucide-truck/>
                        <strong>Dispatch</strong>
                        <span>Asset moves out</span>
                    </div>
                    <div>
                        <x-lucide-rotate-ccw/>
                        <strong>Return</strong>
                        <span>Inspection and close</span>
                    </div>
                    <div>
                        <x-lucide-receipt-text/>
                        <strong>Invoice</strong>
                        <span>Collect payment</span>
                    </div>
                </div>
            </article>

            <article class="infographic-card">
                <div class="infographic-card-header">
                    <span>
                        <x-lucide-gauge/>
                    </span>
                    <div>
                        <strong>Owner dashboard signals</strong>
                        <small>Management view</small>
                    </div>
                </div>
                <div class="signal-stack">
                    <div>
                        <span>Fleet utilization</span>
                        <strong>74%</strong>
                        <i style="width: 74%"></i>
                    </div>
                    <div>
                        <span>Invoices collected</span>
                        <strong>82%</strong>
                        <i style="width: 82%"></i>
                    </div>
                    <div>
                        <span>Returns due today</span>
                        <strong>06</strong>
                        <i style="width: 38%"></i>
                    </div>
                </div>
            </article>

            <article class="infographic-card">
                <div class="infographic-card-header">
                    <span>
                        <x-lucide-globe-2/>
                    </span>
                    <div>
                        <strong>Global rental ready</strong>
                        <small>Regional controls</small>
                    </div>
                </div>
                <div class="region-map">
                    <span>UAE</span>
                    <span>KSA</span>
                    <span>Qatar</span>
                    <span>Oman</span>
                    <span>Kuwait</span>
                    <span>Bahrain</span>
                    <span>Global</span>
                </div>
                <p>Multi-currency, tax profiles, branches, warehouses, team permissions, and customer portal access for regional expansion.</p>
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

    <section class="landing-band" id="middle-east">
        <div class="landing-section-heading">
            <span class="eyebrow">Middle East And GCC Coverage</span>
            <h2>Rental software for growing equipment companies in the region.</h2>
            <p>Designed for rental companies operating in Dubai, Abu Dhabi, Riyadh, Jeddah, Doha, Muscat, Manama, Kuwait City, and multi-branch GCC markets where availability, billing, tax, fleet status, and customer communication must stay accurate.</p>
        </div>

        <div class="module-grid">
            <article>
                <x-lucide-map-pin class="w-5 h-5"/>
                <strong>Regional operations</strong>
                <p>Manage branches, warehouses, storage locations, teams, customers, and assets across multiple Middle East cities from one cloud workspace.</p>
            </article>
            <article>
                <x-lucide-receipt class="w-5 h-5"/>
                <strong>Tax and currency ready</strong>
                <p>Support AED, SAR, QAR, OMR, BHD, KWD, USD, exchange rates, tax profiles, invoices, receipts, credit notes, and payment tracking.</p>
            </article>
            <article>
                <x-lucide-hard-hat class="w-5 h-5"/>
                <strong>Built for asset-heavy rentals</strong>
                <p>Track heavy equipment, generators, access platforms, vehicles, tools, event gear, and custom asset categories with flexible fields and rate cards.</p>
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
            <span class="eyebrow">Equipment Rental Management Modules</span>
            <h2>A complete suite for asset-based rental operations.</h2>
            <p>RentalHook covers the operational, financial, customer, and asset-control layers needed by equipment rental companies in competitive Middle East markets.</p>
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

</main>

@include('layouts.partials._public-footer')
</body>
</html>
