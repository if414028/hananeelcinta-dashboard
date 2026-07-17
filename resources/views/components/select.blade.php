@props(['label' => null, 'name'])
<div>
    @if ($label)<label for="{{ $name }}" class="mb-2 block text-sm font-bold">{{ $label }}</label>@endif
    <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->class('min-h-12 w-full rounded-[20px] border border-ink/30 bg-white px-5 py-3') }}>{{ $slot }}</select>
    @error($name)<p class="mt-2 text-sm text-signal" role="alert">{{ $message }}</p>@enderror
</div>
