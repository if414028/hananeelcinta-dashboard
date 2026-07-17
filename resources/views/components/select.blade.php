@props(['label' => null, 'name'])
@php($hasError = $errors->has($name))
<div>
    @if ($label)<label for="{{ $name }}" class="mb-2 block text-sm font-bold">{{ $label }}@if($attributes->has('required')) <span class="text-signal" aria-hidden="true">*</span>@endif</label>@endif
    <select id="{{ $name }}" name="{{ $name }}" @if($hasError) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif {{ $attributes->class('form-control') }}>{{ $slot }}</select>
    @error($name)<p id="{{ $name }}-error" class="mt-2 text-sm text-signal" role="alert">{{ $message }}</p>@enderror
</div>
