@extends('errors.layout')

@section('title', 'Be right back')

@section('error_content')
    <p class="font-mono text-5xl font-semibold tabular-nums text-sky-400/90 sm:text-6xl">503</p>
    <h1 class="mt-4 text-xl font-bold tracking-tight text-white sm:text-2xl">Temporarily unavailable</h1>
    <p class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-slate-400">
        We’re doing a quick update or the service is overloaded. Please try again shortly.
    </p>
    <a href="{{ url('/') }}" class="v2-btn-primary mt-8 inline-flex items-center justify-center px-6 py-3 text-sm font-semibold no-underline">
        Try again
    </a>
@endsection
