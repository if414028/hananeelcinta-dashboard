@props(['label' => null, 'name'])
@php
    $errorKey = str($name)->replace(['][', '[', ']'], ['.', '.', ''])->trim('.')->toString();
    $fieldId = str($name)->replaceMatches('/[^A-Za-z0-9_-]+/', '-')->trim('-')->toString();
    $hasError = $errors->has($errorKey);
@endphp
<div>
    @if ($label)<label for="{{ $fieldId }}" class="mb-2 block text-sm font-bold">{{ $label }}@if($attributes->has('required')) <span class="text-signal" aria-hidden="true">*</span>@endif</label>@endif
    <textarea id="{{ $fieldId }}" name="{{ $name }}" @if($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif {{ $attributes->class('form-control min-h-36 resize-y') }}>{{ $slot }}</textarea>
    @error($errorKey)<p id="{{ $fieldId }}-error" class="mt-2 text-sm text-signal" role="alert">{{ $message }}</p>@enderror
</div>
