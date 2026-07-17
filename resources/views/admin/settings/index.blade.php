<x-layouts.admin title="Website Settings">
    <div class="mb-8"><p class="text-sm font-bold uppercase tracking-[.04em] text-slate"><span class="text-signal-light">•</span> Konfigurasi</p><h1 class="mt-2 text-4xl">Website Settings</h1></div>
    @if($errors->any())<x-alert type="error" class="mb-6">{{ $errors->first() }}</x-alert>@endif
    <form method="post" action="{{ route('admin.settings.update') }}" class="space-y-6">@csrf @method('PUT')
        @foreach($groups as $group=>$settings)<x-card :title="str($group)->replace('_',' ')->title()"><div class="grid gap-6 md:grid-cols-2">@foreach($settings as $setting)<div @class(['md:col-span-2'=>in_array($setting->type,['textarea','richtext'])])>@if(in_array($setting->type,['textarea','richtext']))<x-textarea :name="'settings['.$setting->key.']'" :label="str($setting->key)->replace('_',' ')->title()">{{ old('settings.'.$setting->key,$setting->value) }}</x-textarea>@else<x-input :name="'settings['.$setting->key.']'" :label="str($setting->key)->replace('_',' ')->title()" :type="in_array($setting->type,['email','url'])?$setting->type:'text'" :value="old('settings.'.$setting->key,$setting->value)" />@endif</div>@endforeach</div></x-card>@endforeach
        @can('settings.update')<div class="sticky bottom-5 flex justify-end"><x-button type="submit" class="shadow-xl">Simpan pengaturan</x-button></div>@endcan
    </form>
</x-layouts.admin>
