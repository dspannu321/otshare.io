@php
    $siteName = config('app.name', 'otshare.io');
    $seoPage = config('seo.pages.landing');
    $canonical = url('/');
    $title = $seoPage['title'].' — '.$siteName;
    $description = $seoPage['description'];
    $ogImage = config('seo.og_image') ?: url('/og-default.svg');
    $ogW = config('seo.og_image_width', 1200);
    $ogH = config('seo.og_image_height', 630);
    $ogType = str_ends_with(strtolower((string) $ogImage), '.svg') ? 'image/svg+xml' : 'image/png';
    $twitterHandle = config('seo.twitter_handle');
    $themeColor = config('seo.theme_color', '#060a12');
    $ga4Id = config('seo.ga4_measurement_id');
    $ga4Enabled = app()->environment('production') && is_string($ga4Id) && $ga4Id !== '';

    $faq = [
        [
            'q' => 'Do recipients need an account?',
            'a' => 'No. They only need the pickup code you send, or the unlock link or QR code. Everything runs in the browser.',
        ],
        [
            'q' => 'What can I share?',
            'a' => 'Files up to 100MB each, or plain text you paste in. You choose when the share expires and how many times it can be unlocked (up to five).',
        ],
        [
            'q' => 'How is this different from email or chat?',
            'a' => 'You do not rely on the other person logging into a specific app. You give them one code or link that works until it expires or hits the unlock limit.',
        ],
        [
            'q' => 'Is otshare free?',
            'a' => 'Yes. The service is free to use within the posted limits.',
        ],
        [
            'q' => 'How long can a share stay available?',
            'a' => 'You pick an expiry between one minute and seven days when you create the share.',
        ],
        [
            'q' => 'What does the unlock link do?',
            'a' => 'It opens the unlock page with your pickup code filled in, so the recipient does not have to type it. It works the same as knowing the code.',
        ],
    ];

    $faqEntities = array_map(static function (array $item) {
        return [
            '@type' => 'Question',
            'name' => $item['q'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $item['a'],
            ],
        ];
    }, $faq);

    $limitMetrics = [
        ['value' => '100 MB', 'label' => 'Max size per file', 'hint' => 'Generous limit for documents, images & archives'],
        ['value' => '5×', 'label' => 'Unlocks per share', 'hint' => 'You control how many times it can be opened'],
        ['value' => '7 days', 'label' => 'Maximum lifetime', 'hint' => 'From 1 minute up to one week'],
        ['value' => '$0', 'label' => 'Free to use', 'hint' => 'No subscription — share within the limits'],
    ];

    $features = [
        [
            'title' => 'Files or text',
            'body' => 'Upload up to 100MB or paste text. One pickup code ties it all together.',
            'icon' => 'doc',
        ],
        [
            'title' => 'Link & QR',
            'body' => 'Send a code, copy an unlock link, or show a QR — recipients skip typing when they can.',
            'icon' => 'link',
        ],
        [
            'title' => 'Timed & limited',
            'body' => 'Set expiry and unlock count so shares do not live forever in inboxes.',
            'icon' => 'clock',
        ],
        [
            'title' => 'Browser-based',
            'body' => 'Recipients unlock in the browser. No app install and no account on their side.',
            'icon' => 'browser',
        ],
    ];

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
                '@type' => 'Organization',
                '@id' => url('/').'#organization',
                'name' => $siteName,
                'url' => url('/'),
            ],
            [
                '@type' => 'FAQPage',
                '@id' => url('/').'#faq',
                'mainEntity' => $faqEntities,
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
        <meta property="og:image:type" content="{{ $ogType }}">
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
    @vite(['resources/css/v2.css'])

    <script type="application/ld+json">
        {!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
</head>
<body class="v2-shell v2-noise min-h-screen font-sans text-slate-200 antialiased">
    <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(56,189,248,0.12),transparent_55%)]"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_50%_40%_at_100%_0%,rgba(99,102,241,0.08),transparent_50%)]"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_45%_35%_at_0%_80%,rgba(14,165,233,0.06),transparent_45%)]"></div>
    </div>

    <header class="relative z-20 w-full border-b border-white/[0.06] bg-[#060a12]/70 backdrop-blur-md">
        <div class="mx-auto flex w-full max-w-[1400px] flex-wrap items-center justify-between gap-4 px-5 py-4 lg:px-10">
            <a href="{{ url('/') }}" class="group flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-400/25 to-sky-600/10 ring-1 ring-sky-400/30 transition group-hover:ring-sky-400/50">
                    <svg class="h-5 w-5 text-sky-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </span>
                <span class="text-lg font-bold tracking-tight text-white">{{ $siteName }}</span>
            </a>
            <nav class="flex flex-wrap items-center justify-end gap-2 sm:gap-3" aria-label="Primary">
                <a href="{{ url('/download') }}" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-400 transition hover:bg-white/[0.05] hover:text-white">Unlock</a>
                <a href="{{ url('/app') }}" class="rounded-xl bg-gradient-to-r from-sky-500 to-sky-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-sky-500/20 ring-1 ring-white/10 transition hover:brightness-110">Create a share</a>
            </nav>
        </div>
    </header>

    <main id="main-content" class="relative z-10 w-full">
        {{-- Hero --}}
        <section class="w-full px-5 pb-16 pt-14 lg:px-10 lg:pb-24 lg:pt-20" aria-labelledby="hero-heading">
            <div class="mx-auto max-w-[1400px]">
                <div class="mx-auto max-w-4xl text-center">
                    <p class="mb-6 inline-flex items-center gap-2 rounded-full border border-sky-400/25 bg-sky-400/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-sky-200/95">
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400 shadow-[0_0_12px_rgba(52,211,153,0.8)]"></span>
                        No signup · Timed pickup codes
                    </p>
                    <h1 id="hero-heading" class="text-balance text-4xl font-bold leading-[1.1] tracking-tight text-white sm:text-5xl lg:text-6xl lg:leading-[1.08]">
                        Share files and text without forcing anyone to log in
                    </h1>
                    <p class="mx-auto mt-8 max-w-2xl text-pretty text-lg leading-relaxed text-slate-400 sm:text-xl">
                        Upload a file or paste text, set an expiry and how many unlocks are allowed, then send a <strong class="font-semibold text-slate-200">pickup code</strong>, a <strong class="font-semibold text-slate-200">link</strong>, or a <strong class="font-semibold text-slate-200">QR code</strong>. Recipients unlock in the browser — up to 100MB per file, no account required.
                    </p>
                    <div class="mx-auto mt-12 flex w-full max-w-lg flex-col gap-4 sm:max-w-none sm:flex-row sm:justify-center sm:gap-4">
                        <div class="sm:w-52 sm:shrink-0">
                            <a href="{{ url('/app') }}" class="v2-btn-primary text-base shadow-xl shadow-sky-500/10">Start sharing</a>
                        </div>
                        <div class="sm:w-52 sm:shrink-0">
                            <a href="{{ url('/download') }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-white/15 bg-white/[0.04] px-6 py-3.5 text-center text-base font-semibold text-slate-100 ring-1 ring-white/5 transition hover:border-sky-400/35 hover:bg-sky-400/10">I have a code</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Metrics: live totals + product limits --}}
        <section class="w-full border-y border-white/[0.06] bg-black/25 px-5 py-14 lg:px-10" aria-labelledby="metrics-heading">
            <div class="mx-auto w-full max-w-[1400px]">
                <h2 id="metrics-heading" class="sr-only">Usage statistics and product limits</h2>
                <div class="text-center">
                    <p class="inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-200/95">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 shadow-[0_0_10px_rgba(52,211,153,0.7)]"></span>
                        Live on {{ $siteName }}
                    </p>
                    <p class="mx-auto mt-3 max-w-xl text-sm text-slate-500">Real totals from shares on our servers — your next handoff adds to the story.</p>
                </div>
                <div class="mt-10 grid grid-cols-1 gap-10 sm:grid-cols-3 sm:gap-8">
                    <div class="rounded-2xl border border-white/[0.07] bg-gradient-to-b from-white/[0.05] to-transparent px-6 py-8 text-center ring-1 ring-white/[0.04] sm:py-10">
                        <p class="font-mono text-4xl font-semibold tabular-nums tracking-tight text-white sm:text-5xl">{{ $landingStats['share_count_display'] }}</p>
                        <p class="mt-3 text-sm font-semibold text-slate-100">Shares created</p>
                        <p class="mt-1.5 text-xs leading-relaxed text-slate-500">Pickup codes issued through the tool</p>
                    </div>
                    <div class="rounded-2xl border border-sky-400/15 bg-gradient-to-b from-sky-400/10 to-transparent px-6 py-8 text-center ring-1 ring-sky-400/10 sm:py-10">
                        <p class="font-mono text-4xl font-semibold tabular-nums tracking-tight text-sky-100 sm:text-5xl">{{ $landingStats['data_volume_display'] }}</p>
                        <p class="mt-3 text-sm font-semibold text-slate-100">Data staged for pickup</p>
                        <p class="mt-1.5 text-xs leading-relaxed text-slate-500">Combined size of files &amp; text shares on record</p>
                    </div>
                    <div class="rounded-2xl border border-white/[0.07] bg-gradient-to-b from-white/[0.05] to-transparent px-6 py-8 text-center ring-1 ring-white/[0.04] sm:py-10">
                        <p class="font-mono text-4xl font-semibold tabular-nums tracking-tight text-white sm:text-5xl">{{ $landingStats['unlock_count_display'] }}</p>
                        <p class="mt-3 text-sm font-semibold text-slate-100">Successful unlocks</p>
                        <p class="mt-1.5 text-xs leading-relaxed text-slate-500">Times recipients opened a share end-to-end</p>
                    </div>
                </div>
                <div class="mt-12 border-t border-white/[0.08] pt-12">
                    <p class="text-center text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">What you get</p>
                    <div class="mt-8 grid grid-cols-2 gap-8 lg:grid-cols-4 lg:gap-12">
                        @foreach ($limitMetrics as $m)
                            <div class="text-center lg:text-left">
                                <p class="font-mono text-2xl font-semibold tabular-nums tracking-tight text-slate-200 sm:text-3xl">{{ $m['value'] }}</p>
                                <p class="mt-2 text-sm font-semibold text-slate-300">{{ $m['label'] }}</p>
                                <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ $m['hint'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- Features --}}
        <section class="w-full px-5 py-20 lg:px-10 lg:py-28" aria-labelledby="features-heading">
            <div class="mx-auto w-full max-w-[1400px]">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 id="features-heading" class="text-3xl font-bold tracking-tight text-white sm:text-4xl">Built for quick, controlled handoffs</h2>
                    <p class="mt-4 text-lg text-slate-400">Everything you need to share once — without another inbox or app install.</p>
                </div>
                <div class="mt-16 grid gap-5 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6">
                    @foreach ($features as $f)
                        <div class="group flex flex-col rounded-2xl border border-white/[0.08] bg-gradient-to-b from-white/[0.06] to-transparent p-6 ring-1 ring-white/[0.04] transition hover:border-sky-400/20 hover:ring-sky-400/10">
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-sky-400/10 ring-1 ring-sky-400/20">
                                @switch($f['icon'])
                                    @case('doc')
                                        <svg class="h-6 w-6 text-sky-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 8.25H8.25m0 0H5.625c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h10.5c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                        @break
                                    @case('link')
                                        <svg class="h-6 w-6 text-sky-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
                                        @break
                                    @case('clock')
                                        <svg class="h-6 w-6 text-sky-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        @break
                                    @default
                                        <svg class="h-6 w-6 text-sky-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                                @endswitch
                            </div>
                            <h3 class="text-lg font-semibold text-white">{{ $f['title'] }}</h3>
                            <p class="mt-2 flex-1 text-sm leading-relaxed text-slate-400">{{ $f['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- How it works --}}
        <section class="w-full border-t border-white/[0.06] px-5 py-20 lg:px-10 lg:py-24" aria-labelledby="how-heading">
            <div class="mx-auto w-full max-w-[1400px]">
                <h2 id="how-heading" class="text-center text-3xl font-bold tracking-tight text-white sm:text-4xl">How it works</h2>
                <p class="mx-auto mt-3 max-w-xl text-center text-slate-400">Three steps from upload to unlock — you stay in control the whole time.</p>
                <ol class="mt-16 grid gap-8 lg:grid-cols-3 lg:gap-6">
                    <li class="relative rounded-2xl border border-white/[0.08] bg-black/20 p-8 pt-10 ring-1 ring-white/[0.04]">
                        <span class="absolute left-8 top-0 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-gradient-to-br from-sky-400 to-sky-600 text-sm font-bold text-white shadow-lg shadow-sky-500/30">1</span>
                        <p class="font-semibold text-white">Create a share</p>
                        <p class="mt-3 text-sm leading-relaxed text-slate-400">Choose a file or text, pick when it expires, and how many times it can be unlocked (up to five).</p>
                    </li>
                    <li class="relative rounded-2xl border border-white/[0.08] bg-black/20 p-8 pt-10 ring-1 ring-white/[0.04]">
                        <span class="absolute left-8 top-0 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-gradient-to-br from-sky-400 to-sky-600 text-sm font-bold text-white shadow-lg shadow-sky-500/30">2</span>
                        <p class="font-semibold text-white">Send the code or link</p>
                        <p class="mt-3 text-sm leading-relaxed text-slate-400">Copy the pickup code, the unlock link, or show a QR code — whatever fits your workflow.</p>
                    </li>
                    <li class="relative rounded-2xl border border-white/[0.08] bg-black/20 p-8 pt-10 ring-1 ring-white/[0.04]">
                        <span class="absolute left-8 top-0 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-gradient-to-br from-sky-400 to-sky-600 text-sm font-bold text-white shadow-lg shadow-sky-500/30">3</span>
                        <p class="font-semibold text-white">They unlock before it expires</p>
                        <p class="mt-3 text-sm leading-relaxed text-slate-400">They use your link or enter the code. Each successful unlock counts toward your limit.</p>
                    </li>
                </ol>
            </div>
        </section>

        {{-- FAQ --}}
        <section class="w-full px-5 py-20 lg:px-10 lg:py-24" aria-labelledby="faq-heading">
            <div class="mx-auto w-full max-w-[1400px]">
                <h2 id="faq-heading" class="text-center text-3xl font-bold tracking-tight text-white sm:text-4xl">Common questions</h2>
                <p class="mx-auto mt-3 max-w-xl text-center text-slate-400">Straight answers — the same information is marked up for search and assistants.</p>
                <div class="mt-14 grid gap-5 lg:grid-cols-2 lg:gap-6">
                    @foreach ($faq as $item)
                        <div class="rounded-2xl border border-white/[0.08] bg-gradient-to-br from-white/[0.04] to-transparent p-6 ring-1 ring-white/[0.04]">
                            <h3 class="text-base font-semibold leading-snug text-white">{{ $item['q'] }}</h3>
                            <p class="mt-3 text-sm leading-relaxed text-slate-400">{{ $item['a'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- CTA band --}}
        <section class="w-full border-t border-sky-500/20 bg-gradient-to-r from-sky-500/10 via-indigo-500/10 to-sky-500/10 px-5 py-16 lg:px-10 lg:py-20" aria-labelledby="cta-heading">
            <div class="mx-auto flex w-full max-w-[1400px] flex-col items-center justify-between gap-8 text-center lg:flex-row lg:text-left">
                <div class="max-w-xl">
                    <h2 id="cta-heading" class="text-2xl font-bold text-white sm:text-3xl">Ready to send your next share?</h2>
                    <p class="mt-2 text-slate-400">Open the tool — no signup. Your recipient only needs the code or link.</p>
                </div>
                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
                    <div class="sm:w-48">
                        <a href="{{ url('/app') }}" class="v2-btn-primary text-base">Create a share</a>
                    </div>
                    <a href="{{ url('/download') }}" class="inline-flex items-center justify-center rounded-2xl border border-white/20 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10">I have a code</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="relative z-10 w-full border-t border-white/[0.06] bg-[#060a12]/80 px-5 py-12 lg:px-10">
        <div class="mx-auto flex w-full max-w-[1400px] flex-col items-center justify-between gap-6 sm:flex-row">
            <div class="text-center sm:text-left">
                <p class="text-sm font-semibold text-slate-300">{{ $siteName }}</p>
                <p class="mt-1 text-xs text-slate-500">Max 100MB · File or text · Up to 5 unlocks per share</p>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-xs text-slate-500">
                <a href="{{ url('/privacy') }}" class="transition hover:text-slate-300">Privacy</a>
                <a href="{{ url('/terms') }}" class="transition hover:text-slate-300">Terms</a>
                <a href="{{ url('/app') }}" class="transition hover:text-sky-300">App</a>
            </div>
        </div>
    </footer>

    @if ($ga4Enabled)
        <div id="analytics-consent-banner" class="fixed inset-x-0 bottom-0 z-50 hidden border-t border-white/10 bg-[#0b1120]/95 backdrop-blur">
            <div class="mx-auto flex w-full max-w-6xl flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                <p class="text-sm text-slate-300">
                    We use analytics to understand traffic and improve otshare. Accept analytics cookies?
                </p>
                <div class="flex items-center gap-2">
                    <button id="analytics-consent-decline" type="button" class="rounded-lg border border-white/15 px-3 py-2 text-xs font-semibold text-slate-300 transition hover:bg-white/5">
                        Decline
                    </button>
                    <button id="analytics-consent-accept" type="button" class="rounded-lg bg-sky-500 px-3 py-2 text-xs font-semibold text-white transition hover:bg-sky-400">
                        Accept
                    </button>
                </div>
            </div>
        </div>
        <script>
            (function () {
                const KEY = 'otshare_analytics_consent';
                const ACCEPTED = 'granted';
                const DENIED = 'denied';
                const id = @json($ga4Id);
                const banner = document.getElementById('analytics-consent-banner');

                function hideBanner() {
                    if (banner) banner.classList.add('hidden');
                }

                function showBanner() {
                    if (banner) banner.classList.remove('hidden');
                }

                function loadGa() {
                    if (!id || window.__otshareGaLoaded) return;
                    window.__otshareGaLoaded = true;
                    window.dataLayer = window.dataLayer || [];
                    window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
                    window.gtag('js', new Date());
                    window.gtag('config', id, { anonymize_ip: true });

                    const s = document.createElement('script');
                    s.async = true;
                    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
                    document.head.appendChild(s);
                }

                const value = localStorage.getItem(KEY);
                if (value === ACCEPTED) {
                    hideBanner();
                    loadGa();
                } else if (value === DENIED) {
                    hideBanner();
                } else {
                    showBanner();
                }

                const acceptBtn = document.getElementById('analytics-consent-accept');
                const declineBtn = document.getElementById('analytics-consent-decline');

                if (acceptBtn) {
                    acceptBtn.addEventListener('click', function () {
                        localStorage.setItem(KEY, ACCEPTED);
                        hideBanner();
                        loadGa();
                    });
                }

                if (declineBtn) {
                    declineBtn.addEventListener('click', function () {
                        localStorage.setItem(KEY, DENIED);
                        hideBanner();
                    });
                }
            })();
        </script>
    @endif
</body>
</html>
