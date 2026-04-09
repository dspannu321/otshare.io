@php
    $siteName = config('app.name', 'otshare.io');
    $seoPage = config('seo.pages.terms');
    $canonical = url('/terms');
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
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-white sm:text-4xl">Terms of use</h1>
        <p class="mt-4 text-sm leading-relaxed text-slate-400">
            These Terms of Use (“Terms”) govern your access to and use of the website and services operated by <strong class="text-slate-300">{{ $operatorName }}</strong> at <strong class="text-slate-300">{{ parse_url(url('/'), PHP_URL_HOST) ?? $siteName }}</strong> (the “Service”). By using the Service, you agree to these Terms.
        </p>

        <article class="mt-10 text-sm leading-relaxed text-slate-400">
            <h2 class="text-lg font-semibold text-white">1. The Service</h2>
            <p class="mt-3">{{ $siteName }} provides temporary file and text sharing using pickup codes, optional unlock links, and related features. The Service may be offered free of charge within posted limits. We may change, suspend, or discontinue features with or without notice.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">2. Eligibility and acceptable use</h2>
            <p class="mt-3">You may use the Service only in compliance with applicable laws and these Terms. You represent that you have the right to upload and share any content you submit. You must not use the Service to harm others, harass, or distribute content you are not authorized to share.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">3. Prohibited conduct</h2>
            <p class="mt-3">You agree not to:</p>
            <ul class="mt-3 list-disc space-y-2 pl-5">
                <li>Upload or share malware, exploits, or content designed to damage systems or evade security</li>
                <li>Violate intellectual property, privacy, or publicity rights of others</li>
                <li>Attempt to gain unauthorized access to our systems, other users’ data, or administrative areas</li>
                <li>Probe, scrape, or overload the Service in a way that impairs stability (including circumventing rate limits)</li>
                <li>Use the Service for illegal activity, fraud, or to distribute illegal content</li>
                <li>Misrepresent your identity or affiliation in a way that could deceive recipients or us</li>
            </ul>
            <p class="mt-3">We may block requests, remove content, or take other technical measures when we believe abuse or risk to the Service or others is occurring.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">4. Your content</h2>
            <p class="mt-3">You retain ownership of content you upload. To operate the Service, you grant us a limited license to host, process, transmit, and display that content solely for providing sharing and redemption — including storage on our infrastructure and delivery to recipients who present a valid code or link.</p>
            <p class="mt-3">You are responsible for the legality and appropriateness of what you share. We do not pre-screen all uploads; you acknowledge that recipients may access content you share.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">5. No warranty</h2>
            <p class="mt-3">THE SERVICE IS PROVIDED “AS IS” AND “AS AVAILABLE.” TO THE MAXIMUM EXTENT PERMITTED BY LAW, WE DISCLAIM ALL WARRANTIES, WHETHER EXPRESS, IMPLIED, OR STATUTORY, INCLUDING MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NON-INFRINGEMENT. WE DO NOT WARRANT THAT THE SERVICE WILL BE UNINTERRUPTED, ERROR-FREE, OR THAT CONTENT WILL REMAIN AVAILABLE FOR ANY PARTICULAR DURATION BEYOND WHAT YOU CONFIGURE OR WHAT OUR SYSTEMS ENFORCE.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">6. Limitation of liability</h2>
            <p class="mt-3">TO THE MAXIMUM EXTENT PERMITTED BY LAW, {{ $operatorName }} AND ITS SUPPLIERS SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, OR ANY LOSS OF PROFITS, DATA, OR GOODWILL, ARISING FROM YOUR USE OF THE SERVICE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.</p>
            <p class="mt-3">OUR TOTAL LIABILITY FOR ANY CLAIM ARISING OUT OF THESE TERMS OR THE SERVICE SHALL NOT EXCEED THE GREATER OF (A) THE AMOUNTS YOU PAID US FOR THE SERVICE IN THE TWELVE (12) MONTHS BEFORE THE CLAIM (IF ANY), OR (B) FIFTY U.S. DOLLARS (US $50), EXCEPT WHERE PROHIBITED BY LAW.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">7. Indemnity</h2>
            <p class="mt-3">You will defend and indemnify {{ $operatorName }} and its operators, contractors, and affiliates against any third-party claims, damages, and expenses (including reasonable attorneys’ fees) arising from your content, your use of the Service, or your violation of these Terms.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">8. Fair use and limits</h2>
            <p class="mt-3">We may impose limits (for example maximum file size, number of shares, redemption attempts, and rate limits) to keep the Service fair and stable. You agree to respect those limits and any instructions we publish.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">9. Third-party services</h2>
            <p class="mt-3">The Service may integrate with or link to third parties (for example analytics or font providers). Their terms and privacy practices apply to those services.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">10. Privacy</h2>
            <p class="mt-3">Our <a href="{{ url('/privacy') }}" class="text-sky-400 underline decoration-sky-500/40 hover:text-sky-300">Privacy Policy</a> explains how we handle information. It is incorporated into these Terms by reference.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">11. Termination</h2>
            <p class="mt-3">We may suspend or terminate access to the Service at any time, with or without cause or notice. Provisions that by their nature should survive (including disclaimers, limitations of liability, and indemnity) will survive.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">12. Governing law</h2>
            <p class="mt-3">These Terms are governed by the laws applicable to the operator of the Service, excluding conflict-of-law rules that would apply another jurisdiction’s laws, unless mandatory consumer protections in your country say otherwise.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">13. Changes to these Terms</h2>
            <p class="mt-3">We may update these Terms from time to time. The “Last updated” date will change when we do. If you continue to use the Service after changes become effective, you accept the revised Terms.</p>

            <h2 class="mt-10 text-lg font-semibold text-white">14. Contact</h2>
            <p class="mt-3">@if ($contactEmail) For questions about these Terms, contact us at <a href="mailto:{{ $contactEmail }}" class="text-sky-400 underline decoration-sky-500/40 hover:text-sky-300">{{ $contactEmail }}</a>.@else If we publish a contact address for legal or support inquiries, you may use it for questions about these Terms.@endif</p>

            <h2 class="mt-10 text-lg font-semibold text-white">15. Disclaimer</h2>
            <p class="mt-3">These Terms are a practical template for a small web service. They are not a substitute for legal advice tailored to your entity, jurisdiction, and risk profile. Consult a qualified attorney if you need binding certainty.</p>
        </article>

        <p class="mt-12 border-t border-white/[0.08] pt-8 text-sm text-slate-500">
            <a href="{{ url('/') }}" class="text-sky-400 hover:text-sky-300">Home</a>
            ·
            <a href="{{ url('/privacy') }}" class="text-sky-400 hover:text-sky-300">Privacy Policy</a>
            ·
            <a href="{{ url('/app') }}" class="text-sky-400 hover:text-sky-300">Create a share</a>
        </p>
    </main>
</body>
</html>
