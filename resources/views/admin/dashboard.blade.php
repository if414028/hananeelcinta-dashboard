<x-layouts.admin title="Dashboard">
    <div class="mb-10 flex flex-col justify-between gap-5 sm:flex-row sm:items-end"><div><p class="eyebrow">Ringkasan</p><h1 class="mt-2 text-4xl lg:text-5xl">Dashboard admin</h1><p class="mt-3 text-slate">Pantau pelayanan dan aktivitas konten dari satu tempat.</p></div><span class="inline-flex w-fit items-center gap-2 rounded-full bg-white px-4 py-2 text-sm text-slate"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Sistem aktif</span></div>
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        @foreach([['congregations','Total jemaat','users'],['active_announcements','Pengumuman aktif','bell'],['new_prayer_requests','Prayer request baru','heart'],['published_pastor_messages','Pastor Message terbit','book']] as [$key,$label,$icon])
            <x-card class="group relative overflow-hidden !bg-primary text-white"><div class="flex items-start justify-between gap-4"><div><p class="text-sm text-white/55">{{ $label }}</p><p class="mt-4 text-5xl font-medium">{{ number_format($summary[$key]) }}</p></div><span class="grid h-12 w-12 place-items-center rounded-full bg-white/10 text-white transition group-hover:bg-white group-hover:text-ink"><x-icon :name="$icon"/></span></div><div class="absolute -bottom-16 -right-12 h-36 w-36 rounded-full border border-signal-light/40"></div></x-card>
        @endforeach
    </div>
    <div class="mt-10 grid gap-6 xl:grid-cols-[1.25fr_.75fr]">
        <div class="grid gap-6 sm:grid-cols-2">
            @foreach([['Prayer request terbaru',$recentPrayerRequests,'reference_number','heart'],['Jemaat terbaru',$recentCongregations,'full_name','users'],['Pengumuman terbaru',$recentAnnouncements,'title','bell'],['Pastor Message terbaru',$recentPastorMessages,'title','book']] as [$heading,$records,$field,$icon])
                <x-card><div class="mb-5 flex items-center justify-between gap-3"><h2 class="text-xl">{{ $heading }}</h2><span class="grid h-10 w-10 place-items-center rounded-full bg-canvas text-slate"><x-icon :name="$icon" :size="18"/></span></div><div class="space-y-1">@forelse($records as $record)<div class="flex items-center justify-between gap-4 border-b border-ink/8 py-3 last:border-0"><span class="line-clamp-1">{{ $record->{$field} }}</span><span class="shrink-0 rounded-full bg-canvas px-3 py-1 text-xs text-slate">{{ $record->created_at->format('d M') }}</span></div>@empty<p class="py-5 text-slate">Belum ada data.</p>@endforelse</div></x-card>
            @endforeach
        </div>
        <div class="space-y-6">
            <x-card title="Operasional hari ini"><dl class="grid gap-1">@foreach([['Jemaat aktif','active_congregations'],['Jemaat baru bulan ini','new_congregations'],['Prayer sedang didoakan','in_prayer_requests'],['Total Mezbah Keluarga','family_altars'],['Total Pastor Message','pastor_messages']] as [$label,$key])<div class="flex items-center justify-between gap-4 border-b border-ink/8 py-3 last:border-0"><dt class="text-slate">{{ $label }}</dt><dd class="text-lg font-bold">{{ number_format($summary[$key]) }}</dd></div>@endforeach</dl></x-card>
            @foreach($charts as $title=>$data)<x-card :title="str($title)->replace('_',' ')->title()"><div class="space-y-3">@forelse($data as $label=>$total)<div class="flex justify-between gap-4"><span class="text-slate">{{ str($label)->replace('_',' ')->title() }}</span><strong>{{ $total }}</strong></div>@empty<p class="text-slate">Belum ada data.</p>@endforelse</div></x-card>@endforeach
        </div>
    </div>
</x-layouts.admin>
