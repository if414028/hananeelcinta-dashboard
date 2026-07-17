@props(['variant' => 'primary', 'type' => 'button'])
@php
    $classes = match ($variant) {
        'secondary' => 'border-[1.5px] border-primary bg-white text-primary hover:-translate-y-0.5 hover:bg-canvas',
        'danger' => 'border-[1.5px] border-signal bg-signal text-white hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgba(207,69,0,.18)]',
        default => 'border-[1.5px] border-primary bg-primary text-canvas hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgba(133,18,38,.22)]',
    };
@endphp
<button type="{{ $type }}" {{ $attributes->class("inline-flex min-h-11 items-center justify-center gap-2 rounded-[20px] px-6 py-2 font-medium tracking-[-0.02em] transition duration-200 active:translate-y-0 active:scale-[.98] disabled:cursor-not-allowed disabled:opacity-50 $classes") }}>
    {{ $slot }}
</button>
