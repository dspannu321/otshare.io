<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>MFA — {{ config('app.name') }}</title>
    @vite(['resources/css/admin.css'])
</head>
<body class="admin-ops-surface flex min-h-full items-center justify-center p-6 font-sans text-slate-300">
    <div class="admin-panel max-w-md p-8 text-center">
        <h1 class="text-lg font-semibold text-white">Admin key required</h1>
        <p class="mt-2 text-sm text-slate-500">Open <code class="rounded bg-black/40 px-1.5 py-0.5 font-mono text-xs text-slate-400">/admin/login?key=…</code> (or any admin URL with <code class="font-mono text-xs text-slate-400">?key=</code>) using the admin key from your environment / credential.</p>
    </div>
</body>
</html>
