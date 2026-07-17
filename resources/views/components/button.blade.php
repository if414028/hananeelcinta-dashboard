@props(['variant' => 'primary', 'type' => 'button'])
@php
    $classes = match ($variant) {
        'secondary' => 'border-[1.5px] border-ink bg-white text-ink hover:bg-canvas',
        'danger' => 'border-[1.5px] border-signal bg-signal text-white hover:opacity-90',
        default => 'border-[1.5px] border-ink bg-ink text-canvas hover:bg-ink/90',
    };
@endphp
<button type="{{ $type }}" {{ $attributes->class("inline-flex min-h-11 items-center justify-center rounded-[20px] px-6 py-2 font-medium tracking-[-0.02em] transition disabled:cursor-not-allowed disabled:opacity-50 $classes") }}>
    {{ $slot }}
</button>
