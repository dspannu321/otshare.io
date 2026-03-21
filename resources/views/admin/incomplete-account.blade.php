<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin account incomplete — {{ config('app.name') }}</title>
    @vite(['resources/css/admin.css'])
</head>
<body class="admin-ops-surface flex min-h-full items-center justify-center p-6 font-sans text-slate-300">
    <div class="admin-panel max-w-lg p-8">
        <h1 class="text-lg font-semibold text-white">Admin account needs a password</h1>
        <p class="mt-2 text-sm text-slate-500">
            This credential has no dashboard password set. Create a new admin with
            <code class="rounded bg-black/40 px-1.5 py-0.5 font-mono text-xs text-slate-400">php artisan otshare:admin-create "name" "password"</code>
            or set one with
            <code class="rounded bg-black/40 px-1.5 py-0.5 font-mono text-xs text-slate-400">php artisan otshare:admin-set-password "name" "password"</code>.
        </p>
    </div>
</body>
</html>
