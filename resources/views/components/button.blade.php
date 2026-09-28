@props(['variant' => 'primary', 'type' => 'button'])
@php
    $classes = match ($variant) {
        'secondary' => 'border border-ink/15 bg-white text-ink shadow-sm hover:bg-canvas',
        'danger' => 'border border-signal bg-signal text-white shadow-sm hover:brightness-105',
        default => 'border border-primary bg-primary text-white shadow-sm hover:brightness-110',
    };
@endphp
<button type="{{ $type }}" {{ $attributes->class("inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-5 py-2 font-semibold transition duration-150 active:scale-[.98] disabled:cursor-not-allowed disabled:opacity-50 $classes") }}>
    {{ $slot }}
</button>
