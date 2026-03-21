@extends('errors.layout')

@section('title', 'Page not found')

@section('error_content')
    <p class="font-mono text-5xl font-semibold tabular-nums text-sky-400/90 sm:text-6xl">404</p>
    <h1 class="mt-4 text-xl font-bold tracking-tight text-white sm:text-2xl">This page doesn’t exist</h1>
    <p class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-slate-400">
        The link may be wrong or the page was removed. Check the URL or start over from the home page.
    </p>
    <a href="{{ url('/') }}" class="v2-btn-primary mt-8 inline-flex items-center justify-center px-6 py-3 text-sm font-semibold no-underline">
        Go home
    </a>
@endsection
