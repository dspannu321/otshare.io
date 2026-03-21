@extends('errors.layout')

@section('title', 'Slow down')

@section('error_content')
    <p class="font-mono text-5xl font-semibold tabular-nums text-violet-400/90 sm:text-6xl">429</p>
    <h1 class="mt-4 text-xl font-bold tracking-tight text-white sm:text-2xl">Too many requests</h1>
    <p class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-slate-400">
        You’ve sent requests a little too quickly. Wait a bit, then try again.
    </p>
    <a href="{{ url('/') }}" class="v2-btn-primary mt-8 inline-flex items-center justify-center px-6 py-3 text-sm font-semibold no-underline">
        Go home
    </a>
@endsection
