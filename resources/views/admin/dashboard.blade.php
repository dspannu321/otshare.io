@php
    $appTz = config('app.timezone');

    $fmtBytes = function (?int $bytes): string {
        if ($bytes === null || $bytes < 0) {
            return '—';
        }
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 1).' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024).' KB';
        }

        return $bytes.' B';
    };

    $envLower = strtolower($appEnv);
    $envBadgeClass = match (true) {
        in_array($envLower, ['production', 'prod'], true) => 'bg-emerald-500/15 text-emerald-200 ring-emerald-400/25',
        $envLower === 'staging' => 'bg-amber-500/15 text-amber-200 ring-amber-400/25',
        default => 'bg-slate-500/10 text-slate-300 ring-slate-500/20',
    };

    $dlPct = function ($s): int {
        $max = max(1, (int) $s->max_downloads);

        return (int) min(100, round(((int) $s->download_count / $max) * 100));
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Ops — {{ config('app.name', 'otshare.io') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,500&display=swap" rel="stylesheet">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="admin-ops-surface flex min-h-full flex-col font-sans text-slate-200 antialiased">
    <div class="pointer-events-none fixed inset-0 bg-[radial-gradient(ellipse_50%_40%_at_50%_100%,rgba(56,189,248,0.06),transparent)]"></div>

    <div class="relative mx-auto flex min-h-0 w-full max-w-7xl flex-1 flex-col px-4 pb-8 pt-8 sm:px-6 lg:px-8 lg:pb-12 lg:pt-10">
        {{-- Header --}}
        <header class="mb-10 flex flex-col gap-6 border-b border-white/10 pb-8 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-500/15 ring-1 ring-sky-400/25">
                    <svg class="h-6 w-6 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">Operations</h1>
                    <p class="mt-1 max-w-xl text-sm leading-relaxed text-slate-500">
                        Live platform metrics · read-only · no file contents or pickup secrets
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $envBadgeClass }}">
                    {{ $appEnv }}
                </span>
                <time id="admin-ops-clock" class="font-mono text-xs text-slate-600 tabular-nums" datetime="{{ now()->toIso8601String() }}" data-tz="{{ $appTz }}">
                    {{ now()->timezone($appTz)->format('Y-m-d H:i:s T') }}
                </time>
            </div>
        </header>

        @if ($mfaSessionActive && $mfaExpiresAt)
            <div class="mb-6 flex flex-col gap-3 rounded-xl border border-sky-500/25 bg-sky-500/10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-sky-100">
                    <span class="font-semibold text-sky-200">Authenticator session</span> trusted until
                    <time class="font-mono text-sky-200" datetime="{{ $mfaExpiresAt->toIso8601String() }}">{{ $mfaExpiresAt->format('Y-m-d H:i T') }}</time>
                </p>
                <form method="post" action="{{ route('admin.logout') }}" class="shrink-0" data-admin-validate="none">
                    @csrf
                    <input type="hidden" name="key" value="{{ request('key') }}">
                    <button type="submit" data-admin-submit class="relative inline-flex min-h-[2.25rem] min-w-[5.5rem] items-center justify-center rounded-lg border border-sky-500/30 bg-sky-500/10 px-3 py-1.5 text-xs font-semibold text-sky-200 transition hover:bg-sky-500/20 disabled:cursor-not-allowed disabled:opacity-40">
                        <span class="admin-btn-label">Sign out</span>
                        <span class="admin-btn-spinner absolute inset-0 hidden items-center justify-center" aria-hidden="true">
                            <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                </form>
            </div>
        @endif

        @if (session('purge_status'))
            <div class="mb-6 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100" role="status">
                {{ session('purge_status') }}
            </div>
        @endif
        @if (session('purge_warnings') && is_array(session('purge_warnings')) && count(session('purge_warnings')))
            <div class="mb-6 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-100" role="alert">
                <p class="font-semibold text-amber-200">Storage warnings</p>
                <ul class="mt-2 list-inside list-disc text-xs text-amber-100/90">
                    @foreach (session('purge_warnings') as $w)
                        <li class="font-mono">{{ $w }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100" role="alert">
                <ul class="list-inside list-disc text-xs">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex min-h-0 flex-1 flex-col gap-2" data-admin-tabs>
            <div class="sticky top-0 z-20 -mx-4 mb-1 border-b border-white/10 bg-[#060a12]/90 px-4 backdrop-blur-md sm:-mx-6 sm:px-6" role="tablist" aria-label="Dashboard sections">
                <div class="flex flex-wrap gap-1">
                    <button type="button" class="admin-tab admin-tab-active" role="tab" aria-selected="true" data-admin-tab="overview" id="admin-tab-overview">Overview</button>
                    <button type="button" class="admin-tab" role="tab" aria-selected="false" data-admin-tab="system" id="admin-tab-system" tabindex="-1">System</button>
                    <button type="button" class="admin-tab" role="tab" aria-selected="false" data-admin-tab="access" id="admin-tab-access" tabindex="-1">Access log</button>
                    <button type="button" class="admin-tab" role="tab" aria-selected="false" data-admin-tab="shares" id="admin-tab-shares" tabindex="-1">Shares</button>
                    <button type="button" class="admin-tab" role="tab" aria-selected="false" data-admin-tab="danger" id="admin-tab-danger" tabindex="-1">Danger zone</button>
                </div>
            </div>

            <div class="min-h-0 flex-1">
                <div role="tabpanel" aria-labelledby="admin-tab-overview" data-admin-panel="overview" class="max-h-[min(75vh,56rem)] space-y-6 overflow-y-auto pb-2 pr-1">
        {{-- Primary KPIs --}}
        <section class="mb-6 grid gap-4 md:grid-cols-3" aria-label="Primary metrics">
            <article class="admin-stat-card relative overflow-hidden p-6 md:p-7">
                <div class="pointer-events-none absolute -right-8 -top-8 h-32 w-32 rounded-full bg-emerald-500/10 blur-2xl"></div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Active shares</p>
                <p class="mt-2 font-mono text-4xl font-semibold tracking-tight text-emerald-400 tabular-nums">{{ number_format($activeShares) }}</p>
                <p class="mt-3 text-xs text-slate-500">Has file · not expired · under download quota</p>
            </article>
            <article class="admin-stat-card relative overflow-hidden p-6 md:p-7">
                <div class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full bg-violet-500/10 blur-2xl"></div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Share volume</p>
                <div class="mt-3 flex flex-wrap items-baseline gap-x-6 gap-y-2">
                    <div>
                        <p class="font-mono text-3xl font-semibold tracking-tight text-white tabular-nums">{{ number_format($sharesTotal) }}</p>
                        <p class="text-xs text-slate-500">Total created</p>
                    </div>
                    <div class="h-10 w-px bg-white/10 max-sm:hidden" aria-hidden="true"></div>
                    <div>
                        <p class="font-mono text-3xl font-semibold tracking-tight text-violet-300 tabular-nums">{{ number_format($sharesWithFile) }}</p>
                        <p class="text-xs text-slate-500">With file stored</p>
                    </div>
                </div>
            </article>
            <article class="admin-stat-card relative overflow-hidden p-6 md:p-7">
                <div class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full bg-sky-500/10 blur-2xl"></div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Delivery &amp; storage</p>
                <div class="mt-3 flex flex-wrap items-baseline gap-x-6 gap-y-2">
                    <div>
                        <p class="font-mono text-3xl font-semibold tracking-tight text-sky-300 tabular-nums">{{ number_format($totalDownloads) }}</p>
                        <p class="text-xs text-slate-500">Downloads counted</p>
                    </div>
                    <div class="h-10 w-px bg-white/10 max-sm:hidden" aria-hidden="true"></div>
                    <div>
                        <p class="font-mono text-2xl font-semibold tracking-tight text-slate-200 tabular-nums">{{ $fmtBytes((int) $totalStorageBytes) }}</p>
                        <p class="text-xs text-slate-500">Bytes stored</p>
                    </div>
                </div>
            </article>
        </section>

        {{-- Activity strip --}}
        <section class="mb-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6" aria-label="Activity">
            <div class="admin-stat-card px-4 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">24h</p>
                <p class="font-mono text-xl font-semibold text-sky-300 tabular-nums">{{ number_format($sharesLast24h) }}</p>
            </div>
            <div class="admin-stat-card px-4 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">7d</p>
                <p class="font-mono text-xl font-semibold text-sky-300 tabular-nums">{{ number_format($sharesLast7d) }}</p>
            </div>
            <div class="admin-stat-card px-4 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Expired</p>
                <p class="font-mono text-xl font-semibold text-slate-400 tabular-nums">{{ number_format($expired) }}</p>
            </div>
            <div class="admin-stat-card px-4 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Locked</p>
                <p class="font-mono text-xl font-semibold text-amber-300/90 tabular-nums">{{ number_format($lockedNow) }}</p>
            </div>
            <div class="admin-stat-card px-4 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Disk free</p>
                <p class="font-mono text-lg font-semibold leading-snug text-slate-200 tabular-nums">{{ $fmtBytes($diskFreeBytes) }}</p>
            </div>
            <div class="admin-stat-card px-4 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Admin keys</p>
                <p class="font-mono text-xl font-semibold text-slate-400 tabular-nums">{{ number_format($adminCredentialsCount) }}</p>
            </div>
        </section>

        {{-- Risk + tokens --}}
        <section class="mb-8 grid gap-4 lg:grid-cols-3" aria-label="Security signals">
            <div class="admin-stat-card px-5 py-4">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-amber-500/80">Failed redeem</p>
                <p class="mt-1 font-mono text-2xl font-semibold text-amber-200 tabular-nums">{{ number_format($failedRedeemAttempts) }}</p>
            </div>
            <div class="admin-stat-card px-5 py-4">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-amber-500/80">Passcode failures</p>
                <p class="mt-1 font-mono text-2xl font-semibold text-amber-200 tabular-nums">{{ number_format($passcodeFailures) }}</p>
            </div>
            <div class="admin-stat-card px-5 py-4 lg:col-span-1">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Download tokens</p>
                <div class="mt-2 flex flex-wrap gap-4 font-mono text-sm tabular-nums">
                    <div><span class="text-slate-500">created</span> <span class="font-semibold text-slate-200">{{ number_format($tokensCreated) }}</span></div>
                    <div><span class="text-slate-500">used</span> <span class="font-semibold text-slate-200">{{ number_format($tokensUsed) }}</span></div>
                    <div><span class="text-slate-500">valid</span> <span class="font-semibold text-emerald-300/90">{{ number_format($tokensValid) }}</span></div>
                </div>
            </div>
        </section>
                </div>

                <div role="tabpanel" aria-labelledby="admin-tab-system" data-admin-panel="system" class="hidden max-h-[min(75vh,56rem)] overflow-y-auto pb-2 pr-1" hidden>
        {{-- Runtime + stack --}}
        <div class="mb-8 grid gap-6 xl:grid-cols-2">
            <section class="admin-panel" aria-labelledby="runtime-heading">
                <div class="border-b border-white/[0.06] px-5 py-4 sm:px-6">
                    <h2 id="runtime-heading" class="text-sm font-semibold text-white">Runtime</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Stack this instance is using right now</p>
                </div>
                <dl class="grid gap-3 p-5 sm:grid-cols-2 sm:p-6">
                    <div class="rounded-xl border border-white/[0.06] bg-black/20 px-4 py-3">
                        <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Laravel</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-200">{{ $laravelVersion }}</dd>
                    </div>
                    <div class="rounded-xl border border-white/[0.06] bg-black/20 px-4 py-3">
                        <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">PHP</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-200">{{ $phpVersion }}</dd>
                    </div>
                    <div class="rounded-xl border border-white/[0.06] bg-black/20 px-4 py-3 sm:col-span-2">
                        <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Database</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-200">{{ $dbDriver }} <span class="text-slate-600">·</span> {{ $dbConnectionName }}</dd>
                    </div>
                    <div class="rounded-xl border border-white/[0.06] bg-black/20 px-4 py-3 sm:col-span-2">
                        <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Cache · queue · session</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-200">{{ $cacheStore }} <span class="text-slate-600">·</span> {{ $queueConnection }} <span class="text-slate-600">·</span> {{ $sessionDriver }}</dd>
                    </div>
                    <div class="rounded-xl border border-white/[0.06] bg-black/20 px-4 py-3 sm:col-span-2">
                        <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Share storage disk</dt>
                        <dd class="mt-1 font-mono text-sm text-sky-300/90">{{ $otshareStorageDisk }}</dd>
                    </div>
                </dl>
            </section>

            <section class="admin-panel flex flex-col" aria-labelledby="credentials-heading">
                <div class="border-b border-white/[0.06] px-5 py-4 sm:px-6">
                    <h2 id="credentials-heading" class="text-sm font-semibold text-white">Admin credentials</h2>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Keys and passwords are hashed server-side. New admin:
                        <code class="rounded bg-black/40 px-1.5 py-0.5 font-mono text-[11px] text-slate-400">php artisan otshare:admin-create "label" "password"</code>
                    </p>
                </div>
                <div class="flex flex-1 flex-col p-5 sm:p-6">
                    <div class="admin-table-wrap flex-1 overflow-hidden">
                        <div class="max-h-64 overflow-auto">
                            <table class="w-full min-w-[280px] text-left text-sm">
                                <thead class="sticky top-0 z-10 border-b border-white/10 bg-[#080c16]/95 backdrop-blur-md">
                                    <tr>
                                        <th class="px-4 py-2.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Name</th>
                                        <th class="px-4 py-2.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Created</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/[0.04]">
                                    @forelse($adminAccounts as $a)
                                        <tr>
                                            <td class="px-4 py-2.5 font-medium text-slate-200">{{ $a->name }}</td>
                                            <td class="px-4 py-2.5 font-mono text-xs text-slate-500">{{ $a->created_at->format('Y-m-d H:i') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="px-4 py-8 text-center text-sm text-slate-500">
                                                No DB admins. Set <code class="font-mono text-xs text-slate-400">OTSHARE_ADMIN_SECRET</code> to the issued key and run the artisan command above.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
                </div>

                <div role="tabpanel" aria-labelledby="admin-tab-access" data-admin-panel="access" class="hidden max-h-[min(75vh,56rem)] overflow-y-auto pb-2 pr-1" hidden>
        <section class="admin-panel" aria-labelledby="admin-audit-heading">
            <div class="border-b border-white/[0.06] px-5 py-4 sm:px-6">
                <h2 id="admin-audit-heading" class="text-sm font-semibold text-white">Admin access log</h2>
                <p class="mt-0.5 text-xs text-slate-500">Append-only sign-in events for this credential (not purgeable from this app).</p>
            </div>
            <div class="p-4 sm:p-6 sm:pt-2">
                <div class="admin-table-wrap">
                    <div class="max-h-[min(65vh,36rem)] overflow-auto">
                        <table class="w-full min-w-[560px] text-left text-sm">
                            <thead class="sticky top-0 z-10 border-b border-white/10 bg-[#080c16]/95 backdrop-blur-md">
                                <tr>
                                    <th class="px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Time</th>
                                    <th class="px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Admin</th>
                                    <th class="px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Event</th>
                                    <th class="px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-500">IP</th>
                                    <th class="px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Session</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/[0.04]">
                                @forelse($adminAccessLogs as $log)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs text-slate-500">{{ $log->created_at->copy()->timezone($appTz)->format('Y-m-d H:i:s') }}</td>
                                        <td class="px-4 py-2.5 text-sm font-medium text-slate-300">{{ $log->admin_name ?? '—' }}</td>
                                        <td class="px-4 py-2.5 font-mono text-xs text-slate-300">{{ $log->event }}</td>
                                        <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs text-slate-400">{{ $log->ip_address ?? '—' }}</td>
                                        <td class="max-w-[140px] truncate px-4 py-2.5 font-mono text-[11px] text-slate-500" title="{{ $log->session_id }}">{{ $log->session_id ? \Illuminate\Support\Str::limit($log->session_id, 12, '…') : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No events recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
                </div>

                <div role="tabpanel" aria-labelledby="admin-tab-shares" data-admin-panel="shares" class="hidden max-h-[min(75vh,56rem)] overflow-y-auto pb-2 pr-1" hidden>
        <section class="admin-panel" aria-labelledby="shares-heading">
            <div class="flex flex-col gap-2 border-b border-white/[0.06] px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h2 id="shares-heading" class="text-sm font-semibold text-white">Recent shares</h2>
                    <p class="text-xs text-slate-500">Latest 50 by created time · short IDs only</p>
                </div>
            </div>
            <div class="p-4 sm:p-6 sm:pt-2">
                <div class="admin-table-wrap">
                    <div class="max-h-[min(28rem,70vh)] overflow-auto">
                        <table class="w-full min-w-[640px] text-left text-sm">
                            <thead class="sticky top-0 z-10 border-b border-white/10 bg-[#080c16]/95 backdrop-blur-md">
                                <tr>
                                    <th class="whitespace-nowrap px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Short ID</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Created</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Size</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-500">File</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Downloads</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Expires</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/[0.04]">
                                @forelse($recentShares as $s)
                                    <tr>
                                        <td class="px-4 py-2.5 font-mono text-xs font-medium text-sky-300/90">{{ $s->short_id }}</td>
                                        <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs text-slate-500">{{ $s->created_at->format('Y-m-d H:i') }}</td>
                                        <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs text-slate-400">{{ $s->size_bytes ? $fmtBytes((int) $s->size_bytes) : '—' }}</td>
                                        <td class="px-4 py-2.5">
                                            @if($s->object_key)
                                                <span class="inline-flex rounded-full bg-emerald-500/15 px-2 py-0.5 text-[11px] font-semibold text-emerald-300 ring-1 ring-emerald-500/25">Yes</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-slate-500/10 px-2 py-0.5 text-[11px] font-semibold text-slate-500 ring-1 ring-slate-500/20">No</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <div class="flex items-center gap-2">
                                                <span class="font-mono text-xs tabular-nums text-slate-300">{{ $s->download_count }}/{{ $s->max_downloads }}</span>
                                                <div class="h-1.5 w-14 overflow-hidden rounded-full bg-white/10" role="presentation">
                                                    <div class="h-full rounded-full bg-gradient-to-r from-sky-600 to-sky-400" style="width: {{ $dlPct($s) }}%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs text-slate-500">{{ $s->expires_at->copy()->timezone($appTz)->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">No shares recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
                </div>

                <div role="tabpanel" aria-labelledby="admin-tab-danger" data-admin-panel="danger" class="hidden max-h-[min(75vh,56rem)] overflow-y-auto pb-2 pr-1" hidden>
        <section class="admin-panel border-rose-500/20 ring-1 ring-rose-500/20" aria-labelledby="purge-heading">
            <div class="border-b border-rose-500/15 bg-rose-950/20 px-5 py-4 sm:px-6">
                <h2 id="purge-heading" class="text-sm font-semibold text-rose-200">Danger zone</h2>
                <p class="mt-1 text-xs text-rose-200/70">
                    Permanently delete <strong class="text-rose-100">all shares</strong>, all download tokens, and remove stored files from
                    <span class="font-mono text-rose-100/90">{{ $otshareStorageDisk }}</span>.
                    CLI: <code class="rounded bg-black/30 px-1.5 py-0.5 font-mono text-[11px] text-rose-100/80">php artisan otshare:purge-all</code>
                    (<code class="font-mono text-[11px]">--force</code> for scripts).
                </p>
            </div>
            <div class="p-5 sm:p-6">
                @if (! request()->query('key'))
                    <p class="mb-4 text-sm text-amber-200/90">
                        Open this page with <code class="rounded bg-black/30 px-1 font-mono text-xs">?key=…</code> in the URL so the purge action can send your admin key (header-only auth is not supported for this form).
                    </p>
                @endif
                <form method="post" action="{{ route('admin.purge') }}" class="max-w-xl space-y-4" data-admin-validate="purge" data-confirm-phrase="{{ $purgeConfirmPhrase }}">
                    @csrf
                    <input type="hidden" name="key" value="{{ request('key', '') }}">
                    <div>
                        <label for="purge-confirm" class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            Type confirmation phrase
                        </label>
                        <input
                            id="purge-confirm"
                            name="confirm"
                            type="text"
                            autocomplete="off"
                            class="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-2.5 font-mono text-sm text-white placeholder:text-slate-600 focus:border-rose-500/40 focus:outline-none focus:ring-2 focus:ring-rose-500/20"
                            placeholder="{{ $purgeConfirmPhrase }}"
                        >
                        <p class="mt-1.5 text-xs text-slate-500">Exact phrase: <code class="font-mono text-slate-400">{{ $purgeConfirmPhrase }}</code></p>
                    </div>
                    <button
                        type="submit"
                        data-admin-submit
                        class="relative inline-flex min-h-[2.75rem] min-w-[12rem] items-center justify-center rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-rose-900/30 transition hover:bg-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-400/50 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <span class="admin-btn-label">Purge all shares &amp; files</span>
                        <span class="admin-btn-spinner absolute inset-0 hidden items-center justify-center" aria-hidden="true">
                            <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                </form>
            </div>
        </section>
                </div>
            </div>
        </div>

        <footer class="mt-auto flex flex-col gap-2 border-t border-white/10 pt-8 text-xs text-slate-600 sm:flex-row sm:items-center sm:justify-between">
            <p class="font-mono">{{ config('app.name') }} · snapshot {{ now()->timezone($appTz)->toIso8601String() }}</p>
            <p class="text-slate-600">Do not bookmark URLs that include <code class="text-slate-500">?key=</code> in shared browsers.</p>
        </footer>
    </div>
</body>
</html>
