@php
    $canEditMinistry = auth()->user()->hasRole('Super Admin');
    $isServing = (bool) ($canEditMinistry ? old('is_serving', $item->is_serving) : $item->is_serving);
    $ministryUnit = $canEditMinistry ? old('ministry_unit', $item->ministry_unit?->value) : $item->ministry_unit?->value;
@endphp
<fieldset class="grid gap-6 md:col-span-2 md:grid-cols-2" x-data="{ serving: @js($isServing), canEdit: @js($canEditMinistry) }" @disabled(! $canEditMinistry)>
    <div>
        <label for="is_serving" class="flex min-h-12 items-center gap-3">
            <input type="hidden" name="is_serving" value="0">
            <input id="is_serving" type="checkbox" name="is_serving" value="1" class="h-5 w-5" x-model="serving" aria-controls="ministry-unit-field" @checked($isServing)>
            Pelayanan
        </label>
        @unless($canEditMinistry)<p class="mt-2 text-sm text-slate">Hanya Super Admin yang dapat mengubah data pelayanan.</p>@endunless
        @error('is_serving')<p class="mt-2 text-sm text-signal" role="alert">{{ $message }}</p>@enderror
    </div>
    <div id="ministry-unit-field" x-show="serving" @if(! $isServing) style="display: none;" @endif>
        <x-select name="ministry_unit" label="Unit Pelayanan" x-bind:required="serving && canEdit" x-bind:disabled="!serving || !canEdit" :disabled="! $isServing || ! $canEditMinistry" :required="$isServing && $canEditMinistry">
            <option value="">Pilih Unit Pelayanan…</option>
            @foreach($field['options'] as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected($ministryUnit === $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </x-select>
    </div>
</fieldset>
