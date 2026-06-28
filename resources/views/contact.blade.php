<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    @php
        $appUrl = rtrim(config('app.url'), '/');
        $canonicalUrl = $appUrl.'/contact';
        $seoTitle = 'Contact RentalHook | Equipment Rental Software Support';
        $seoDescription = 'Contact RentalHook for equipment rental software support, product information, onboarding, pricing, and SaaS setup for rental companies in the Middle East and global markets.';
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'ContactPage',
            'name' => 'Contact RentalHook',
            'url' => $canonicalUrl,
            'description' => $seoDescription,
            'mainEntity' => [
                '@type' => 'Organization',
                'name' => 'RentalHook',
                'url' => $appUrl,
                'email' => 'info@rentalhook.com',
                'contactPoint' => [
                    [
                        '@type' => 'ContactPoint',
                        'email' => 'support@rentalhook.com',
                        'contactType' => 'customer support',
                        'areaServed' => ['Middle East', 'Global'],
                        'availableLanguage' => ['English'],
                    ],
                    [
                        '@type' => 'ContactPoint',
                        'email' => 'info@rentalhook.com',
                        'contactType' => 'sales',
                        'areaServed' => ['Middle East', 'Global'],
                        'availableLanguage' => ['English'],
                    ],
                ],
            ],
        ];
    @endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:site_name" content="RentalHook">
    <script type="application/ld+json">
        {!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
    @include('layouts.partials._favicons')

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="landing-page contact-page">
@include('layouts.partials._public-nav')

<main>
    <section class="contact-hero">
        <div>
            <span class="eyebrow">Contact RentalHook</span>
            <h1>Let’s talk about your rental operation.</h1>
            <p>Reach the RentalHook team for product questions, onboarding help, support, pricing, and SaaS setup for equipment rental companies.</p>
            <div class="landing-actions">
                <a href="mailto:support@rentalhook.com" class="btn btn-dark btn-lg">
                    <x-lucide-life-buoy class="w-4 h-4"/>
                    Contact Support
                </a>
                <a href="mailto:info@rentalhook.com" class="btn btn-outline-secondary btn-lg">
                    <x-lucide-mail class="w-4 h-4"/>
                    Sales Enquiry
                </a>
            </div>
        </div>

        <aside class="contact-summary-card">
            <span class="contact-summary-icon">
                <x-lucide-headphones/>
            </span>
            <strong>RentalHook support desk</strong>
            <p>For setup, login help, billing questions, platform onboarding, and product guidance.</p>
            <div>
                <span>Typical response</span>
                <strong>Within 1 business day</strong>
            </div>
            <div>
                <span>Best for</span>
                <strong>Setup, billing, plans, and onboarding</strong>
            </div>
        </aside>
    </section>

    <section class="landing-band contact-band">
        <div class="landing-section-heading">
            <span class="eyebrow">How We Can Help</span>
            <h2>Choose the right contact path.</h2>
        </div>

        <div class="contact-card-grid">
            <article>
                <span><x-lucide-rocket/></span>
                <strong>New company setup</strong>
                <p>Ask about registration, trial setup, subscription plans, modules, and onboarding your rental team.</p>
                <a href="mailto:info@rentalhook.com">info@rentalhook.com</a>
            </article>
            <article>
                <span><x-lucide-life-buoy/></span>
                <strong>Product support</strong>
                <p>Get help with login, company workspace, equipment setup, billing, PDFs, customer portal, or system questions.</p>
                <a href="mailto:support@rentalhook.com">support@rentalhook.com</a>
            </article>
            <article>
                <span><x-lucide-globe-2/></span>
                <strong>Regional rollout</strong>
                <p>Discuss multi-branch, multi-currency, tax, users, permissions, and Middle East rental workflow needs.</p>
                <a href="mailto:info@rentalhook.com">info@rentalhook.com</a>
            </article>
        </div>
    </section>
</main>

@include('layouts.partials._public-footer')
</body>
</html>
