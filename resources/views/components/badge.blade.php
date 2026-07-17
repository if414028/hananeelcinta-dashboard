@props(['tone' => 'neutral'])
@php($classes = $tone === 'success' ? 'bg-emerald-100 text-emerald-800' : ($tone === 'warning' ? 'bg-amber-100 text-amber-800' : 'bg-ink/10 text-ink'))
<span {{ $attributes->class("inline-flex rounded-full px-3 py-1 text-xs font-bold $classes") }}>{{ $slot }}</span>
