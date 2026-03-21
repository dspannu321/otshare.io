@php
    $isDownload = request()->is('download');
    $pageKey = $isDownload ? 'download' : 'home';
    $seoPage = config('seo.pages.'.$pageKey);
    $siteName = config('app.name', 'otshare.io');
    $canonical = url()->current();
    $title = $seoPage['title'].' — '.$siteName;
    $description = $seoPage['description'];
    $ogImage = config('seo.og_image');
    $ogW = config('seo.og_image_width', 1200);
    $ogH = config('seo.og_image_height', 630);
    $twitterHandle = config('seo.twitter_handle');
    $themeColor = config('seo.theme_color', '#060a12');
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebSite',
                '@id' => url('/').'#website',
                'name' => $siteName,
                'url' => url('/'),
                'description' => $description,
                'inLanguage' => str_replace('_', '-', app()->getLocale()),
            ],
            [
                '@type' => 'WebApplication',
                '@id' => url('/').'#webapp',
                'name' => $siteName,
                'url' => $canonical,
                'description' => $description,
                'applicationCategory' => 'UtilitiesApplication',
                'operatingSystem' => 'Web',
                'browserRequirements' => 'Requires JavaScript. Modern browser.',
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'USD',
                ],
            ],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="app-timezone" content="{{ config('app.timezone') }}">

    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="author" content="{{ $siteName }}">
    <meta name="theme-color" content="{{ $themeColor }}">

    <link rel="canonical" href="{{ $canonical }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:locale" content="{{ app()->getLocale() === 'en' ? 'en_US' : str_replace('-', '_', app()->getLocale()) }}">
    @if (! empty($ogImage))
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:image:width" content="{{ $ogW }}">
        <meta property="og:image:height" content="{{ $ogH }}">
        <meta property="og:image:alt" content="{{ $siteName }} — {{ $seoPage['title'] }}">
    @endif

    @if (! empty($twitterHandle))
        <meta name="twitter:site" content="{{ '@'.$twitterHandle }}">
        <meta name="twitter:creator" content="{{ '@'.$twitterHandle }}">
    @endif
    <meta name="twitter:card" content="{{ ! empty($ogImage) ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    @if (! empty($ogImage))
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif

    @if (config('seo.google_site_verification'))
        <meta name="google-site-verification" content="{{ config('seo.google_site_verification') }}">
    @endif
    @if (config('seo.ms_validate'))
        <meta name="msvalidate.01" content="{{ config('seo.ms_validate') }}">
    @endif

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,500&display=swap" rel="stylesheet">
    @vite(['resources/css/v2.css', 'resources/js/app.js'])

    <script type="application/ld+json">
        {!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
</head>
<body class="min-h-screen font-sans text-slate-200 antialiased">
    <div id="otshare-root"></div>
</body>
</html>
