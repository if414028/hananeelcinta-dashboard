<x-layouts.admin :title="$title">
    @php($permissionPrefix = match($routeBase) {'admin.admin-users'=>'admins','admin.congregations'=>'congregations','admin.announcements'=>'announcements','admin.family-altars'=>'family_altars','admin.pastor-messages'=>'pastor_messages',default=>null})
    @php($isCongregationList = $routeBase === 'admin.congregations')
    <header class="admin-page-header">
        <div><p class="eyebrow">CMS</p><h1 class="admin-page-title">{{ $title }}</h1><p class="admin-page-subtitle">Kelola, cari, dan perbarui data dengan cepat.</p></div>
        <div class="admin-action-group">
            @isset($exportPermission) @can($exportPermission)<a href="{{ route($routeBase.'.export', request()->query()) }}" class="button-secondary !px-5"><x-icon name="download" :size="18"/>Export CSV</a>@endcan @endisset
            @isset($createPermission) @can($createPermission)<a href="{{ route($routeBase.'.create') }}" class="button-primary !px-5"><x-icon name="plus" :size="18"/>Tambah data</a>@endcan @endisset
        </div>
    </header>
    <x-card class="mb-6">
        <form method="get" class="grid gap-4 md:grid-cols-4">
            <x-input name="search" label="Pencarian" :value="request('search')" placeholder="Cari data…" />
            @foreach($filters ?? [] as $filter)<x-select :name="$filter['name']" :label="$filter['label']"><option value="">Semua</option>@foreach($filter['options'] as $value=>$label)<option value="{{ $value }}" @selected(request($filter['name'])===$value)>{{ $label }}</option>@endforeach</x-select>@endforeach
            <div class="admin-filter-actions"><x-button type="submit"><x-icon name="search" :size="18"/>Terapkan</x-button><a href="{{ route($routeBase.'.index') }}" class="text-link justify-center !px-3">Reset</a></div>
        </form>
    </x-card>
    @isset($bulkRoute)<form method="post" action="{{ route($bulkRoute) }}">@csrf @method('PATCH')@endisset
    <x-card class="overflow-hidden !p-0">
        @isset($bulkRoute)<div class="grid items-end gap-3 border-b border-ink/10 p-5 sm:grid-cols-[minmax(12rem,20rem)_auto]"><x-select name="status" label="Ubah status terpilih">@foreach($bulkOptions as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</x-select><x-button type="submit">Terapkan bulk action</x-button></div>@endisset
        @if($items->isEmpty())<x-empty-state class="m-6" />@else
        <div class="hidden overflow-x-auto md:block"><table class="admin-table"><thead><tr>@isset($bulkRoute)<th><span class="sr-only">Pilih</span></th>@endisset @foreach($columns as $label)<th>{{ $label }}</th>@endforeach @unless($isCongregationList)<th>Aksi</th>@endunless</tr></thead><tbody>
            @foreach($rows as $row)<tr>@isset($bulkRoute)<td><input type="checkbox" name="ids[]" value="{{ $row['id'] }}" class="h-5 w-5" aria-label="Pilih data"></td>@endisset @foreach(array_keys($columns) as $key)<td>@if($isCongregationList)<a class="block min-h-11 py-2 font-medium hover:text-primary" href="{{ route($routeBase.'.show',$row['id']) }}">{{ $row[$key] ?? '-' }}</a>@else{{ $row[$key] ?? '-' }}@endif</td>@endforeach @unless($isCongregationList)<td><div class="flex gap-3 whitespace-nowrap"><a class="font-bold underline decoration-ink/25 underline-offset-4 hover:decoration-ink" href="{{ route($routeBase.'.show',$row['id']) }}">Detail</a>@if(Route::has($routeBase.'.edit') && (!$permissionPrefix || auth()->user()->can($permissionPrefix.'.update')))<a class="underline decoration-ink/25 underline-offset-4 hover:decoration-ink" href="{{ route($routeBase.'.edit',$row['id']) }}">Edit</a>@endif</div></td>@endunless</tr>@endforeach
        </tbody></table></div>
        @if($isCongregationList)
        <div class="grid gap-3 bg-canvas/70 p-3 md:hidden">
            @foreach($rows as $row)
                <a href="{{ route($routeBase.'.show',$row['id']) }}" class="group flex min-h-24 items-center gap-4 rounded-2xl border border-ink/10 bg-white p-5 shadow-sm transition hover:border-primary/25 hover:shadow-md active:scale-[.99]" aria-label="Buka detail {{ $row['name'] }}">
                    <span class="relative grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-full bg-primary/8 font-semibold text-primary" aria-hidden="true"><span>{{ str($row['name'])->substr(0, 1)->upper() }}</span>@if($row['profile_photo_url'])<img src="{{ $row['profile_photo_url'] }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy" referrerpolicy="no-referrer" onerror="this.remove()">@endif</span>
                    <span class="min-w-0 flex-1"><span class="block truncate text-lg font-semibold text-ink">{{ $row['name'] }}</span><span class="mt-1 block text-sm font-medium text-slate">NIJ {{ $row['member_number'] }}</span></span>
                    <x-icon name="arrow-right" class="shrink-0 text-slate transition group-hover:translate-x-0.5 group-hover:text-primary" :size="19"/>
                </a>
            @endforeach
        </div>
        @else
        <div class="divide-y divide-ink/8 md:hidden">
            @foreach($rows as $row)
                <article class="p-5">
                    <div class="flex items-start gap-3">
                        @isset($bulkRoute)<input type="checkbox" name="ids[]" value="{{ $row['id'] }}" class="mt-1 h-5 w-5 shrink-0" aria-label="Pilih data">@endisset
                        <dl class="min-w-0 flex-1 space-y-3">
                            @foreach($columns as $key => $label)<div><dt class="text-xs font-semibold uppercase tracking-[.05em] text-slate">{{ $label }}</dt><dd class="mt-1 break-words text-[.95rem] font-medium">{{ $row[$key] ?? '-' }}</dd></div>@endforeach
                        </dl>
                    </div>
                    <div class="mt-5 grid gap-2 border-t border-ink/8 pt-4 sm:grid-cols-2">
                        <a class="button-secondary !px-3" href="{{ route($routeBase.'.show',$row['id']) }}">Detail</a>
                        @if(Route::has($routeBase.'.edit') && (!$permissionPrefix || auth()->user()->can($permissionPrefix.'.update')))<a class="button-secondary !px-3" href="{{ route($routeBase.'.edit',$row['id']) }}">Edit</a>@endif
                    </div>
                </article>
            @endforeach
        </div>
        @endif
        <div class="border-t border-ink/8 p-5 sm:p-6">{{ $items->links() }}</div>@endif
    </x-card>
    @isset($bulkRoute)</form>@endisset
</x-layouts.admin>
