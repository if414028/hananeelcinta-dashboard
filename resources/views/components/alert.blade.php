@props(['type' => 'info'])
@php($classes = $type === 'error' ? 'border-signal/30 bg-red-50 text-red-900' : 'border-ink/15 bg-white text-ink')
<div {{ $attributes->class("rounded-[20px] border px-5 py-4 text-sm $classes") }} role="{{ $type === 'error' ? 'alert' : 'status' }}">{{ $slot }}</div>
