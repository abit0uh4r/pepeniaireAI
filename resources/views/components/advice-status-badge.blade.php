@props(['status'])

@php
    $classes = match ($status->value) {
        'PENDING' => 'bg-amber-100 text-amber-800 ring-amber-700/10',
        'PROCESSING' => 'bg-sky-100 text-sky-800 ring-sky-700/10',
        'COMPLETED' => 'bg-emerald-100 text-emerald-800 ring-emerald-700/10',
        'FAILED' => 'bg-rose-100 text-rose-800 ring-rose-700/10',
    };
@endphp

<span {{ $attributes->class(["inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold ring-1 ring-inset {$classes}"]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
    {{ $status->label() }}
</span>
