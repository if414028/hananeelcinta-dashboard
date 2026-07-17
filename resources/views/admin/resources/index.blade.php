<x-layouts.admin :title="$title">
    @php($permissionPrefix = match($routeBase) {'admin.admin-users'=>'admins','admin.congregations'=>'congregations','admin.announcements'=>'announcements','admin.family-altars'=>'family_altars','admin.pastor-messages'=>'pastor_messages',default=>null})
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-sm font-bold uppercase tracking-[.04em] text-slate"><span class="text-signal-light">•</span> CMS</p><h1 class="mt-2 text-4xl">{{ $title }}</h1></div>
        <div class="flex gap-3">
            @isset($exportPermission) @can($exportPermission)<a href="{{ route($routeBase.'.export', request()->query()) }}" class="inline-flex min-h-11 items-center rounded-[20px] border border-ink bg-white px-5">Export CSV</a>@endcan @endisset
            @isset($createPermission) @can($createPermission)<a href="{{ route($routeBase.'.create') }}" class="inline-flex min-h-11 items-center rounded-[20px] bg-ink px-5 text-canvas">Tambah data</a>@endcan @endisset
        </div>
    </div>
    <x-card class="mb-6">
        <form method="get" class="grid gap-4 md:grid-cols-4">
            <x-input name="search" label="Pencarian" :value="request('search')" placeholder="Cari data…" />
            @foreach($filters ?? [] as $filter)<x-select :name="$filter['name']" :label="$filter['label']"><option value="">Semua</option>@foreach($filter['options'] as $value=>$label)<option value="{{ $value }}" @selected(request($filter['name'])===$value)>{{ $label }}</option>@endforeach</x-select>@endforeach
            <div class="flex items-end gap-2"><x-button type="submit">Terapkan</x-button><a href="{{ route($routeBase.'.index') }}" class="inline-flex min-h-11 items-center px-3 underline">Reset</a></div>
        </form>
    </x-card>
    @isset($bulkRoute)<form method="post" action="{{ route($bulkRoute) }}">@csrf @method('PATCH')@endisset
    <x-card class="overflow-hidden !p-0">
        @isset($bulkRoute)<div class="flex flex-wrap items-end gap-3 border-b border-ink/10 p-5"><x-select name="status" label="Ubah status terpilih">@foreach($bulkOptions as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</x-select><x-button type="submit">Terapkan bulk action</x-button></div>@endisset
        @if($items->isEmpty())<x-empty-state class="m-6" />@else
        <div class="overflow-x-auto"><table class="admin-table"><thead><tr>@isset($bulkRoute)<th><span class="sr-only">Pilih</span></th>@endisset @foreach($columns as $label)<th>{{ $label }}</th>@endforeach<th>Aksi</th></tr></thead><tbody>
            @foreach($rows as $row)<tr>@isset($bulkRoute)<td><input type="checkbox" name="ids[]" value="{{ $row['id'] }}" class="h-5 w-5" aria-label="Pilih data"></td>@endisset @foreach(array_keys($columns) as $key)<td>{{ $row[$key] ?? '-' }}</td>@endforeach<td><div class="flex gap-3"><a class="font-bold underline" href="{{ route($routeBase.'.show',$row['id']) }}">Detail</a>@if(Route::has($routeBase.'.edit') && (!$permissionPrefix || auth()->user()->can($permissionPrefix.'.update')))<a class="underline" href="{{ route($routeBase.'.edit',$row['id']) }}">Edit</a>@endif</div></td></tr>@endforeach
        </tbody></table></div><div class="p-6">{{ $items->links() }}</div>@endif
    </x-card>
    @isset($bulkRoute)</form>@endisset
</x-layouts.admin>
