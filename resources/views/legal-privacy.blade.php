@php
    $siteName = config('app.name', 'otshare.io');
    $seoPage = config('seo.pages.privacy');
    $canonical = url('/privacy');
    $title = $seoPage['title'].' — '.$siteName;
    $description = $seoPage['description'];
    $themeColor = config('seo.theme_color', '#060a12');
    $lastUpdated = config('seo.legal.last_updated');
    $contactEmail = config('seo.legal.contact_email');
    $operatorName = config('seo.legal.operator_name') ?: $siteName;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="{{ $themeColor }}">
    <link rel="canonical" href="{{ $canonical }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,500&display=swap" rel="stylesheet">
    @vite(['resources/css/v2.css'])
</head>
<body class="v2-shell v2-noise min-h-screen font-sans text-slate-200 antialiased">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_60%_40%_at_50%_0%,rgba(56,189,248,0.06),transparent)]" aria-hidden="true"></div>
    <header class="relative z-10 mx-auto max-w-3xl px-4 py-8 sm:px-8">
        <a href="{{ url('/') }}" class="text-sm font-semibold text-sky-300 hover:text-sky-200">← {{ $siteName }}</a>
    </header>
    <main class="relative z-10 mx-auto max-w-3xl px-4 pb-20 sm:px-8">
        <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Last updated: {{ $lastUpdated }}</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-white sm:text-4xl">Privacy Policy</h1>
        <p class="mt-4 text-sm leading-relaxed text-slate-400">
            This Privacy Policy describes how <strong class="text-slate-300">{{ $operatorName }}</strong> (“we”, “us”) collects, uses, and shares information when you use the website and service at <strong class="text-slate-300">{{ parse_url(url('/'), PHP_URL_HOST) ?? $siteName }}</strong> (the “Service”) — including creating temporary shares (files or text), redeeming pickup codes, and browsing our marketing pages.
        </p>

        <article class="mt-10 text-sm leading-relaxed text-slate-400">
            <h2 class="text-lg font-semibold text-white">1. Who we are</h2>
            <p class="mt-3">The Service is operated by {{ $operatorName }}.@if ($contactEmail) For privacy-related requests, contact us at <a href="mailto:{{ $contactEmail }}" class="text-sky-400 underline decoration-sky-500/40 hover:text-sky-300">{{ $contactEmail }}</a>.@else If we publish a contact email for privacy requests, you may use it to reach us regarding this Policy.@endif</p>

            <h2 class="mt-10 text-lg font-semibold text-white">2. What the Service does</h2>
            <p class="mt-3">{{ $siteName }} lets you share files or text for a limited time using a pickup code, optional unlock link, or QR code. Recipients can retrieve content in the browser. You do not need a user account to use the core sharing features.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">3. Information we collect</h2>
            <p class="mt-3"><strong class="text-slate-300">3.1 Content you upload.</strong> When you create a share, we store the file or text content you provide, along with metadata needed to operate the Service (for example: original filename where applicable, size, MIME type, expiry time, and limits on how many times the share can be unlocked).</p>
            <p class="mt-3"><strong class="text-slate-300">3.2 Pickup codes and tokens.</strong> We derive secure identifiers (for example, short IDs and hashed pickup codes, and one-time download tokens) so that only people with the correct code or link can access a share. We do not intend to expose raw secrets in URLs longer than necessary; unlock links may remove the code from the address bar after load where implemented.</p>
            <p class="mt-3"><strong class="text-slate-300">3.3 Technical and security data.</strong> Like most websites, our servers and infrastructure may process:</p>
            <ul class="mt-3 list-disc space-y-2 pl-5">
                <li>IP addresses and request metadata (for example to enforce rate limits, prevent abuse, and diagnose errors)</li>
                <li>Timestamps and operational logs needed to run and secure the Service</li>
                <li>Session identifiers if we use server-side sessions (for example for administrative areas separate from public sharing)</li>
            </ul>
            <p class="mt-3"><strong class="text-slate-300">3.4 Analytics (optional).</strong> In production we may load <strong class="text-slate-300">Google Analytics 4</strong> only after you accept analytics via our consent banner. If you decline, we do not load GA4 as implemented on the site. Acceptance may be stored in your browser’s <strong class="text-slate-300">local storage</strong> to remember your choice. GA4 may process usage data according to Google’s policies; we configure IP anonymization where supported.</p>
            <p class="mt-3"><strong class="text-slate-300">3.5 Accounts.</strong> The public sharing flow does not require you to create an account. Optional or administrative features (if enabled) may use separate authentication and logging.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">4. How we use information</h2>
            <p class="mt-3">We use the information above to:</p>
            <ul class="mt-3 list-disc space-y-2 pl-5">
                <li>Provide, host, and deliver shares and pickup-code redemption</li>
                <li>Enforce expiry, download limits, and abuse protections (including temporary lockouts after repeated failed attempts where implemented)</li>
                <li>Maintain security, prevent fraud and misuse, and comply with law</li>
                <li>Understand aggregate usage and improve the Service when you consent to analytics</li>
            </ul>

            <h2 class="mt-10 text-lg font-semibold text-white">5. Retention and deletion</h2>
            <p class="mt-3">Shares are <strong class="text-slate-300">temporary by design</strong>. Content and associated metadata are retained until the share expires, is fully consumed according to your limits, or is removed by automated purge or administrative processes. After deletion, residual backups may persist for a limited period depending on hosting configuration.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">6. Sharing with third parties</h2>
            <p class="mt-3">We use infrastructure providers (for example hosting and storage) to operate the Service. If you consent to analytics, Google may process analytics data as described in their policies. We do not sell your personal information as a commodity; we use service providers to run the site.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">7. Security</h2>
            <p class="mt-3">We implement reasonable technical and organizational measures appropriate to the Service (including HTTPS in production, access controls for administrative tools, and secure handling of secrets). No method of transmission or storage is 100% secure; use the Service for content whose sensitivity matches your own risk assessment.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">8. Your rights</h2>
            <p class="mt-3">Depending on where you live, you may have rights to access, correct, delete, or restrict processing of personal data, or to object to certain processing. Because the Service is built around <strong class="text-slate-300">temporary</strong> shares without a general user profile, many requests may be limited to what we can verify from logs or remaining records. @if ($contactEmail) To exercise a request, contact us at <a href="mailto:{{ $contactEmail }}" class="text-sky-400 underline decoration-sky-500/40 hover:text-sky-300">{{ $contactEmail }}</a>.@endif</p>

            <h2 class="mt-10 text-lg font-semibold text-white">9. Children</h2>
            <p class="mt-3">The Service is not directed at children under 13 (or the minimum age in your jurisdiction). Do not upload content about children or encourage minors to share personal information through the Service.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">10. International users</h2>
            <p class="mt-3">If you access the Service from outside the country where our servers or processors are located, your information may be processed in those regions. By using the Service, you understand that cross-border transfer may occur as needed to operate the Service.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">11. Changes</h2>
            <p class="mt-3">We may update this Privacy Policy from time to time. The “Last updated” date at the top will change when we do. Continued use of the Service after changes means you accept the updated Policy.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">12. Disclaimer</h2>
            <p class="mt-3">This document is provided for transparency. It is not a substitute for professional legal advice. If you need certainty for compliance (for example GDPR, CCPA, or sector-specific rules), consult a qualified attorney in your jurisdiction.</p>
        </article>

        <p class="mt-12 border-t border-white/[0.08] pt-8 text-sm text-slate-500">
            <a href="{{ url('/') }}" class="text-sky-400 hover:text-sky-300">Home</a>
            ·
            <a href="{{ url('/terms') }}" class="text-sky-400 hover:text-sky-300">Terms of use</a>
            ·
            <a href="{{ url('/app') }}" class="text-sky-400 hover:text-sky-300">Create a share</a>
        </p>
    </main>
</body>
</html>
