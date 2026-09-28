<x-layouts.admin title="Atur Permission">
    <header class="admin-page-header"><div><a href="{{ route('admin.roles.index') }}" class="text-link !min-h-8 !p-0"><x-icon name="arrow-left" :size="17"/>Kembali</a><h1 class="admin-page-title mt-4">Permission {{ $role->name }}</h1><p class="admin-page-subtitle">Pilih akses yang tersedia untuk admin dengan role ini.</p></div></header>
    <form method="post" action="{{ route('admin.roles.update',$role) }}" class="space-y-6">@csrf @method('PUT')
        @foreach($permissions as $module=>$modulePermissions)<x-card :title="str($module)->replace('_',' ')->title()"><div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">@foreach($modulePermissions as $permission)<label class="flex items-center gap-3 rounded-[20px] border border-ink/10 bg-white p-4"><input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="h-5 w-5" @checked($role->hasPermissionTo($permission))><span>{{ str($permission->name)->after('.')->replace('_',' ')->title() }}</span></label>@endforeach</div></x-card>@endforeach
        <div class="mobile-action-bar sticky flex rounded-2xl border border-white/70 bg-white/85 p-3 shadow-lg backdrop-blur-xl sm:justify-end"><x-button type="submit" class="mobile-full">Simpan permission</x-button></div>
    </form>
</x-layouts.admin>
