<x-layouts.admin title="Website Settings">
    <header class="admin-page-header"><div><p class="eyebrow">Konfigurasi</p><h1 class="admin-page-title">Website Settings</h1><p class="admin-page-subtitle">Kelola identitas, kontak, dan informasi publik website.</p></div></header>
    @if($errors->any())<x-alert type="error" class="mb-6">{{ $errors->first() }}</x-alert>@endif
    <form method="post" action="{{ route('admin.settings.update') }}" class="space-y-6">@csrf @method('PUT')
        @foreach($groups as $group=>$settings)<x-card :title="str($group)->replace('_',' ')->title()"><div class="grid gap-6 md:grid-cols-2">@foreach($settings as $setting)<div @class(['md:col-span-2'=>in_array($setting->type,['textarea','richtext'])])>@if(in_array($setting->type,['textarea','richtext']))<x-textarea :name="'settings['.$setting->key.']'" :label="str($setting->key)->replace('_',' ')->title()">{{ old('settings.'.$setting->key,$setting->value) }}</x-textarea>@else<x-input :name="'settings['.$setting->key.']'" :label="str($setting->key)->replace('_',' ')->title()" :type="in_array($setting->type,['email','url'])?$setting->type:'text'" :value="old('settings.'.$setting->key,$setting->value)" />@endif</div>@endforeach</div></x-card>@endforeach
        @can('settings.update')<div class="mobile-action-bar sticky flex rounded-2xl border border-white/70 bg-white/85 p-3 shadow-lg backdrop-blur-xl sm:justify-end"><x-button type="submit" class="mobile-full">Simpan pengaturan</x-button></div>@endcan
    </form>
</x-layouts.admin>
