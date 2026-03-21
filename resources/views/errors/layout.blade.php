<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') — {{ config('app.name', 'otshare.io') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,500&display=swap" rel="stylesheet">
    @vite(['resources/css/v2.css'])
</head>
<body class="v2-shell v2-noise min-h-screen font-sans text-slate-200 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12 sm:px-6">
        <div class="v2-card w-full max-w-lg p-8 text-center sm:p-10">
            @yield('error_content')
        </div>
    </div>
</body>
</html>
