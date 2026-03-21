<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin sign in — {{ config('app.name', 'otshare.io') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="admin-ops-surface min-h-full font-sans text-slate-200 antialiased">
    <div class="pointer-events-none fixed inset-0 bg-[radial-gradient(ellipse_50%_40%_at_50%_100%,rgba(56,189,248,0.06),transparent)]"></div>
    <div class="relative mx-auto flex min-h-full max-w-md flex-col justify-center px-4 py-12 sm:px-6">
        <div class="admin-panel p-8">
            <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-500/15 ring-1 ring-sky-400/25">
                <svg class="h-6 w-6 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h1 class="text-xl font-bold tracking-tight text-white">Operations sign in</h1>
            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                Enter the dashboard password for this admin key. Next you will set up or confirm authenticator (TOTP).
            </p>

            @if ($errors->any())
                <div class="mt-5 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ route('admin.login.post') }}" class="mt-6 space-y-4" data-admin-validate="password">
                @csrf
                <input type="hidden" name="key" value="{{ $key }}">
                <div>
                    <label for="admin-password" class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-slate-500">Password</label>
                    <input
                        id="admin-password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        autofocus
                        class="w-full rounded-xl border border-white/10 bg-black/40 px-4 py-3 font-mono text-sm text-white placeholder:text-slate-600 focus:border-sky-500/40 focus:outline-none focus:ring-2 focus:ring-sky-500/20"
                    >
                </div>
                <button type="submit" data-admin-submit class="relative inline-flex min-h-[2.75rem] w-full items-center justify-center rounded-xl bg-sky-600 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-900/30 transition hover:bg-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-400/50 disabled:cursor-not-allowed disabled:opacity-40">
                    <span class="admin-btn-label">Continue</span>
                    <span class="admin-btn-spinner absolute inset-0 hidden items-center justify-center" aria-hidden="true">
                        <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </button>
            </form>
        </div>
        <p class="mt-6 text-center text-xs text-slate-600">Session cookie expires when you close the browser.</p>
    </div>
</body>
</html>
