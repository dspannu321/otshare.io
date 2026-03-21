@extends('errors.layout')

@section('title', 'Session expired')

@section('error_content')
    <p class="font-mono text-5xl font-semibold tabular-nums text-slate-400 sm:text-6xl">419</p>
    <h1 class="mt-4 text-xl font-bold tracking-tight text-white sm:text-2xl">Page expired</h1>
    <p class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-slate-400">
        Your session timed out or the security token is no longer valid. Go back, refresh the page, and submit the form again.
    </p>
    <a href="javascript:history.back()" class="v2-btn-primary mt-8 inline-flex items-center justify-center px-6 py-3 text-sm font-semibold no-underline">
        Go back
    </a>
@endsection
