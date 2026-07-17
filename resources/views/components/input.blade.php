@props(['label' => null, 'name', 'type' => 'text'])
<div>
    @if ($label)<label for="{{ $name }}" class="mb-2 block text-sm font-bold">{{ $label }}</label>@endif
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" {{ $attributes->class('min-h-12 w-full rounded-[20px] border border-ink/30 bg-white px-5 py-3 text-ink placeholder:text-slate/70') }}>
    @error($name)<p id="{{ $name }}-error" class="mt-2 text-sm text-signal" role="alert">{{ $message }}</p>@enderror
</div>
