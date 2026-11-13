@props([
    'count' => 0,
    'variant' => 'light',
])

@php
    $isClickable = (int) $count > 0;
    $isNavy = $variant === 'navy';
@endphp

@if ($isClickable)
    <button
        type="button"
        title="Dokumente anzeigen"
        {{ $attributes->class([
            'inline-flex h-7 min-w-8 cursor-pointer select-none items-center justify-center rounded-md px-2 text-sm font-semibold tabular-nums transition duration-150 ease-out active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2',
            'bg-white text-blue-600 shadow-sm ring-1 ring-[#073070]/15 hover:bg-[#073070] hover:text-white hover:shadow-md focus-visible:outline-blue-600 dark:bg-white/10 dark:text-white dark:shadow-none dark:ring-white/25 dark:hover:bg-white/20 dark:hover:text-white dark:hover:shadow-none dark:focus-visible:outline-white' => ! $isNavy,
            'bg-white/15 text-white! shadow-none ring-1 ring-white/30 hover:bg-white/25 focus-visible:outline-white' => $isNavy,
        ]) }}
    >{{ $count }}</button>
@else
    <span @class([
        'inline-flex h-7 min-w-8 select-none items-center justify-center px-2 text-sm tabular-nums',
        'text-slate-400 dark:text-white/35' => ! $isNavy,
        'text-white/40!' => $isNavy,
    ])>{{ $count }}</span>
@endif
