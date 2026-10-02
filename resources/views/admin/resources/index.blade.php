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
    @if($isCongregationList)
        @if($items->isEmpty())
            <x-card><x-empty-state /></x-card>
        @else
            <ul class="grid min-w-0 gap-3 sm:grid-cols-2 2xl:grid-cols-3" aria-label="Daftar jemaat">
                @foreach($rows as $row)
                    <li class="min-w-0"><a href="{{ route($routeBase.'.show', $row['id']) }}" class="group flex h-full min-h-28 min-w-0 items-center gap-4 rounded-2xl border border-ink/10 bg-white p-4 shadow-sm transition-colors hover:border-primary/35 hover:bg-primary/[.02] sm:p-5" aria-label="Lihat detail jemaat {{ $row['name'] }}, NIJ {{ $row['member_number'] }}">
                        <span class="relative grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-full bg-primary/8 text-lg font-semibold text-primary sm:h-16 sm:w-16" aria-hidden="true">
                            <span>{{ str($row['name'])->substr(0, 1)->upper() }}</span>
                            @if($row['profile_photo_url'])<img src="{{ $row['profile_photo_url'] }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy" referrerpolicy="no-referrer" onerror="this.remove()">@endif
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block break-words text-lg font-semibold leading-snug text-ink [overflow-wrap:anywhere]">{{ $row['name'] }}</span>
                            <span class="mt-1 block text-sm font-medium text-slate">NIJ {{ $row['member_number'] }}</span>
                        </span>
                        <x-icon name="arrow-right" class="shrink-0 text-slate group-hover:text-primary" :size="19"/>
                    </a></li>
                @endforeach
            </ul>
            <div class="mt-5 rounded-2xl border border-ink/10 bg-white p-5 sm:p-6">{{ $items->links() }}</div>
        @endif
    @else
    @isset($bulkRoute)<form method="post" action="{{ route($bulkRoute) }}">@csrf @method('PATCH')@endisset
    <x-card class="overflow-hidden !p-0">
        @isset($bulkRoute)<div class="grid items-end gap-3 border-b border-ink/10 p-5 sm:grid-cols-[minmax(12rem,20rem)_auto]"><x-select name="status" label="Ubah status terpilih">@foreach($bulkOptions as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</x-select><x-button type="submit">Terapkan bulk action</x-button></div>@endisset
        @if($items->isEmpty())<x-empty-state class="m-6" />@else
        <div class="hidden overflow-x-auto md:block"><table class="admin-table"><thead><tr>@isset($bulkRoute)<th><span class="sr-only">Pilih</span></th>@endisset @foreach($columns as $label)<th>{{ $label }}</th>@endforeach <th>Aksi</th></tr></thead><tbody>
            @foreach($rows as $row)<tr>@isset($bulkRoute)<td><input type="checkbox" name="ids[]" value="{{ $row['id'] }}" class="h-5 w-5" aria-label="Pilih data"></td>@endisset @foreach(array_keys($columns) as $key)<td>{{ $row[$key] ?? '-' }}</td>@endforeach <td><div class="flex gap-3 whitespace-nowrap"><a class="font-bold underline decoration-ink/25 underline-offset-4 hover:decoration-ink" href="{{ route($routeBase.'.show',$row['id']) }}">Detail</a>@if(Route::has($routeBase.'.edit') && (!$permissionPrefix || auth()->user()->can($permissionPrefix.'.update')))<a class="underline decoration-ink/25 underline-offset-4 hover:decoration-ink" href="{{ route($routeBase.'.edit',$row['id']) }}">Edit</a>@endif</div></td></tr>@endforeach
        </tbody></table></div>
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
        <div class="border-t border-ink/8 p-5 sm:p-6">{{ $items->links() }}</div>@endif
    </x-card>
    @isset($bulkRoute)</form>@endisset
    @endif
</x-layouts.admin>
