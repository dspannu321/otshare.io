@extends('errors.layout')

@section('title', 'Something went wrong')

@section('error_content')
    <p class="font-mono text-5xl font-semibold tabular-nums text-rose-400/90 sm:text-6xl">500</p>
    <h1 class="mt-4 text-xl font-bold tracking-tight text-white sm:text-2xl">We hit a snag</h1>
    <p class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-slate-400">
        Something failed on our side. Try again in a moment. If it keeps happening, come back later.
    </p>
    <a href="{{ url('/') }}" class="v2-btn-primary mt-8 inline-flex items-center justify-center px-6 py-3 text-sm font-semibold no-underline">
        Go home
    </a>
@endsection
