<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Set up authenticator — {{ config('app.name', 'otshare.io') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="admin-ops-surface min-h-full font-sans text-slate-200 antialiased">
    <div class="pointer-events-none fixed inset-0 bg-[radial-gradient(ellipse_50%_40%_at_50%_100%,rgba(56,189,248,0.06),transparent)]"></div>
    <div class="relative mx-auto max-w-lg px-4 py-12 sm:px-6">
        <div class="admin-panel p-8">
            <h1 class="text-xl font-bold tracking-tight text-white">Set up authenticator</h1>
            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                Account <span class="font-semibold text-slate-400">{{ $adminName }}</span>. Scan the QR code with your authenticator app (or enter the secret below), then enter a 6-digit code to finish.
            </p>

            <div class="mt-6 flex flex-col items-center rounded-xl border border-white/10 bg-white p-5 ring-1 ring-white/10">
                <p class="mb-3 text-[10px] font-semibold uppercase tracking-wider text-slate-600">Scan QR code</p>
                <img src="{{ $totpQrSrc }}" width="200" height="200" alt="Authenticator setup QR code" class="h-48 w-48 max-w-full">
            </div>

            <div class="mt-6 rounded-xl border border-white/10 bg-black/30 p-4">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Secret (manual entry)</p>
                <p class="mt-2 break-all font-mono text-sm text-sky-200">{{ $secretPlain }}</p>
            </div>

            <div class="mt-4">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Authenticator URI</p>
                <textarea readonly rows="3" class="mt-2 w-full resize-y rounded-xl border border-white/10 bg-black/40 px-3 py-2 font-mono text-[11px] leading-relaxed text-slate-400">{{ $otpauthUrl }}</textarea>
            </div>

            @if ($errors->any())
                <div class="mt-5 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ route('admin.mfa.setup.confirm') }}" class="mt-6 space-y-4" data-admin-validate="totp">
                @csrf
                <input type="hidden" name="key" value="{{ $key }}">
                <div>
                    <label for="setup-code" class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-slate-500">Confirm with 6-digit code</label>
                    <input
                        id="setup-code"
                        name="code"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        autocomplete="one-time-code"
                        autofocus
                        class="w-full rounded-xl border border-white/10 bg-black/40 px-4 py-3 text-center font-mono text-2xl tracking-[0.35em] text-white placeholder:text-slate-600 focus:border-sky-500/40 focus:outline-none focus:ring-2 focus:ring-sky-500/20"
                        placeholder="000000"
                    >
                </div>
                <button type="submit" data-admin-submit class="relative inline-flex min-h-[2.75rem] w-full items-center justify-center rounded-xl bg-sky-600 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-900/30 transition hover:bg-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-400/50 disabled:cursor-not-allowed disabled:opacity-40">
                    <span class="admin-btn-label">Save and continue</span>
                    <span class="admin-btn-spinner absolute inset-0 hidden items-center justify-center" aria-hidden="true">
                        <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </button>
            </form>
        </div>
    </div>
</body>
</html>
