<x-layouts.admin :title="$title">
    <div class="mb-8"><a href="{{ route($routeBase.'.index') }}" class="text-sm underline">← Kembali</a><h1 class="mt-4 text-4xl">{{ $title }}</h1></div>
    @if($errors->any())<x-alert type="error" class="mb-6"><strong>Data belum dapat disimpan.</strong><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-alert>@endif
    <x-card><form action="{{ $item->exists ? route($routeBase.'.update',$item) : route($routeBase.'.store') }}" method="post" enctype="multipart/form-data" class="grid gap-6 md:grid-cols-2">@csrf @if($item->exists)@method('PUT')@endif
        @foreach($fields as $field) @php($name=$field['name']) @php($type=$field['type']??'text') @php($raw=$field['value']??data_get($item,$name)) @php($value=old($name,$raw instanceof \BackedEnum?$raw->value:($raw instanceof \Carbon\CarbonInterface?($type==='datetime-local'?$raw->format('Y-m-d\TH:i'):$raw->format('Y-m-d')):$raw)))
            @if($type==='textarea')<div class="md:col-span-2"><x-textarea :name="$name" :label="$field['label']" :required="$field['required']??false">{{ $value }}</x-textarea></div>
            @elseif($type==='select')<x-select :name="$name" :label="$field['label']" :required="$field['required']??false"><option value="">Pilih…</option>@foreach($field['options'] as $optionValue=>$optionLabel)<option value="{{ $optionValue }}" @selected((string)$value===(string)$optionValue)>{{ $optionLabel }}</option>@endforeach</x-select>
            @elseif($type==='checkbox')<label class="flex min-h-12 items-center gap-3 self-end"><input type="hidden" name="{{ $name }}" value="0"><input type="checkbox" name="{{ $name }}" value="1" class="h-5 w-5" @checked((bool)$value)> {{ $field['label'] }}</label>
            @else<x-input :name="$name" :label="$field['label']" :type="$type" :value="$type==='file'?null:$value" :required="$field['required']??false" :step="$field['step']??null" />@endif
        @endforeach
        <div class="md:col-span-2 flex gap-3 border-t border-ink/10 pt-6"><x-button type="submit">Simpan data</x-button><a href="{{ route($routeBase.'.index') }}" class="inline-flex min-h-11 items-center px-5">Batal</a></div>
    </form></x-card>
</x-layouts.admin>
