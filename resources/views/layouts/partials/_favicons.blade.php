@php
    $faviconVersion = collect([
        public_path('favicon.svg'),
        public_path('favicon-32x32.png'),
        public_path('apple-touch-icon.png'),
        public_path('favicon.ico'),
    ])
        ->filter(fn ($path) => file_exists($path))
        ->map(fn ($path) => filemtime($path))
        ->max() ?? time();
@endphp

<link rel="icon" type="image/svg+xml" sizes="any" href="{{ asset('favicon.svg') }}?v={{ $faviconVersion }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v={{ $faviconVersion }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v={{ $faviconVersion }}">
<link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ $faviconVersion }}">
<meta name="theme-color" content="#111827">
