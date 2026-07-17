<x-layouts.admin :title="$title">
    @php($permissionPrefix = match($routeBase) {'admin.admin-users'=>'admins','admin.congregations'=>'congregations','admin.announcements'=>'announcements','admin.family-altars'=>'family_altars','admin.pastor-messages'=>'pastor_messages',default=>null})
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4"><div><a href="{{ route($routeBase.'.index') }}" class="text-sm underline">← Kembali</a><h1 class="mt-4 text-4xl">{{ $title }}</h1></div><div class="flex gap-3">@isset($publishRoute)@can($publishPermission)<form method="post" action="{{ route($publishRoute,$item) }}">@csrf<x-button variant="secondary" type="submit">{{ $publishLabel }}</x-button></form>@endcan @endisset @if(Route::has($routeBase.'.edit') && (!$permissionPrefix || auth()->user()->can($permissionPrefix.'.update')))<a href="{{ route($routeBase.'.edit',$item) }}" class="inline-flex min-h-11 items-center rounded-[20px] bg-primary px-5 text-canvas">Edit</a>@endif @if(!$permissionPrefix || auth()->user()->can($permissionPrefix.'.delete'))<form method="post" action="{{ route($routeBase.'.destroy',$item) }}" onsubmit="return confirm('Hapus data ini?')">@csrf @method('DELETE')<x-button variant="danger" type="submit">Hapus</x-button></form>@endif</div></div>
    @isset($profile)
        <x-card class="mb-6">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                <div class="relative h-24 w-24 shrink-0 overflow-hidden rounded-[32px] bg-primary text-canvas shadow-[0_16px_32px_rgba(133,18,38,.18)]">
                    <span class="absolute inset-0 grid place-items-center text-2xl font-bold" aria-hidden="true">{{ $profile['initials'] }}</span>
                    @if($profile['photo_url'])
                        <img src="{{ $profile['photo_url'] }}" alt="Foto profil {{ $profile['name'] }}" class="absolute inset-0 h-full w-full object-cover" loading="lazy" referrerpolicy="no-referrer" onerror="this.remove()">
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold uppercase tracking-[.04em] text-primary">Profil jemaat</p>
                    <h2 class="mt-2 break-words text-3xl">{{ $profile['name'] }}</h2>
                    <p class="mt-2 text-slate">{{ $profile['meta'] }}</p>
                </div>
                <span class="sm:ml-auto inline-flex min-h-9 items-center rounded-full bg-primary/8 px-4 text-sm font-bold text-primary">{{ $profile['status'] }}</span>
            </div>
        </x-card>
    @endisset
    @isset($detailSections)
        <div class="grid gap-6 xl:grid-cols-2">
            @foreach($detailSections as $sectionTitle => $sectionDetails)
                <x-card :title="$sectionTitle" @class(['xl:col-span-2' => $loop->last])>
                    <dl class="grid gap-x-8 gap-y-6 md:grid-cols-2">
                        @foreach($sectionDetails as $detail)
                            <div @class(['border-b border-ink/10 pb-4', 'md:col-span-2' => $detail['wide'] ?? false])>
                                <dt class="text-sm font-bold text-slate">{{ $detail['label'] }}</dt>
                                <dd class="mt-2 break-words whitespace-pre-line">{{ filled($detail['value']) ? $detail['value'] : '-' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-card>
            @endforeach
        </div>
    @else
        <x-card><dl class="grid gap-6 md:grid-cols-2">@foreach($details as $label=>$value)<div class="border-b border-ink/10 pb-4"><dt class="text-sm font-bold text-slate">{{ $label }}</dt><dd class="mt-2 whitespace-pre-line">{{ $value ?: '-' }}</dd></div>@endforeach</dl></x-card>
    @endisset
</x-layouts.admin>
