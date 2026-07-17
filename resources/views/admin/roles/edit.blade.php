<x-layouts.admin title="Atur Permission">
    <div class="mb-8"><a href="{{ route('admin.roles.index') }}" class="text-sm underline">← Kembali</a><h1 class="mt-4 text-4xl">Permission {{ $role->name }}</h1></div>
    <form method="post" action="{{ route('admin.roles.update',$role) }}" class="space-y-6">@csrf @method('PUT')
        @foreach($permissions as $module=>$modulePermissions)<x-card :title="str($module)->replace('_',' ')->title()"><div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">@foreach($modulePermissions as $permission)<label class="flex items-center gap-3 rounded-[20px] border border-ink/10 bg-white p-4"><input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="h-5 w-5" @checked($role->hasPermissionTo($permission))><span>{{ str($permission->name)->after('.')->replace('_',' ')->title() }}</span></label>@endforeach</div></x-card>@endforeach
        <x-button type="submit">Simpan permission</x-button>
    </form>
</x-layouts.admin>
