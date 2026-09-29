<x-layouts.admin title="Dashboard">
    <header class="admin-page-header"><div><p class="eyebrow">Ringkasan</p><h1 class="admin-page-title">Dashboard admin</h1><p class="admin-page-subtitle">Pantau pelayanan dan aktivitas konten dari satu tempat.</p></div><span class="inline-flex w-fit items-center gap-2 rounded-full bg-white px-4 py-2 text-sm text-slate"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Sistem aktif</span></header>
    <div class="grid grid-cols-2 gap-3 sm:gap-5 xl:grid-cols-4">
        @foreach([['congregations','Total jemaat','users'],['active_announcements','Pengumuman aktif','bell'],['new_prayer_requests','Prayer request baru','heart'],['published_pastor_messages','Pastor Message terbit','book']] as [$key,$label,$icon])
            <x-card class="group relative overflow-hidden !bg-primary !p-4 text-white sm:!p-6"><div class="flex items-start justify-between gap-2 sm:gap-4"><div class="min-w-0"><p class="text-xs leading-5 text-white/70 sm:text-sm">{{ $label }}</p><p class="mt-3 text-3xl font-medium sm:mt-4 sm:text-5xl">{{ number_format($summary[$key]) }}</p></div><span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-white/10 text-white transition group-hover:bg-white group-hover:text-ink sm:h-12 sm:w-12"><x-icon :name="$icon"/></span></div><div class="absolute -bottom-16 -right-12 h-36 w-36 rounded-full border border-signal-light/40"></div></x-card>
        @endforeach
    </div>
    <div class="mt-6 grid grid-flow-dense gap-6 xl:grid-cols-2">
        @include('admin.dashboard.trend', ['title' => 'Pertumbuhan jemaat', 'subtitle' => 'Jemaat yang ditambahkan setiap bulan', 'unit' => 'jemaat baru', 'data' => $charts['pertumbuhan_jemaat'], 'type' => 'area', 'color' => '#7d1729', 'id' => 'congregation'])
        @include('admin.dashboard.trend', ['title' => 'Prayer request bulanan', 'subtitle' => 'Permohonan doa yang masuk setiap bulan', 'unit' => 'permohonan doa', 'data' => $charts['prayer_per_bulan'], 'type' => 'bar', 'color' => '#416ca6', 'id' => 'prayer'])
        @include('admin.dashboard.status', ['data' => $charts['prayer_status']])
        <x-card>
            <h2 class="text-xl">Publikasi konten</h2><p class="mt-1 text-sm text-slate">Perbandingan konten terbit dan draft</p>
            <div class="mt-7 space-y-7">
                @foreach([['Pengumuman', 'announcement_status'], ['Pastor Message', 'pastor_status']] as [$label, $key])
                    @php
                        $published = (int) ($charts[$key]['published'] ?? 0);
                        $draft = (int) ($charts[$key]['draft'] ?? 0);
                        $total = $published + $draft;
                    @endphp
                    <div>
                        <div class="mb-3 flex justify-between gap-3"><h3 class="text-base">{{ $label }}</h3><span class="text-sm text-slate">{{ number_format($total) }} konten</span></div>
                        <div class="flex h-4 overflow-hidden rounded-full bg-canvas" role="group" aria-label="{{ $label }}: {{ $published }} terbit dan {{ $draft }} draft">
                            <span tabindex="0" role="button" class="chart-segment bg-primary" data-chart-label="{{ $label }} · Terbit" data-chart-value="{{ number_format($published) }} konten" aria-label="{{ $label }}: {{ $published }} terbit" style="width: {{ $total ? $published / $total * 100 : 0 }}%"></span><span tabindex="0" role="button" class="chart-segment bg-[#b7791f]" data-chart-label="{{ $label }} · Draft" data-chart-value="{{ number_format($draft) }} konten" aria-label="{{ $label }}: {{ $draft }} draft" style="width: {{ $total ? $draft / $total * 100 : 0 }}%"></span>
                        </div>
                        <div class="mt-3 flex flex-wrap justify-between gap-2 text-sm text-slate"><span>Terbit <strong class="ml-1 text-ink">{{ $published }}</strong></span><span>Draft <strong class="ml-1 text-ink">{{ $draft }}</strong></span></div>
                    </div>
                @endforeach
            </div>
            <p class="mt-6 border-t border-ink/10 pt-4 text-xs text-slate">Konten dengan status lain tidak termasuk dalam diagram ini.</p>
        </x-card>
    </div>
    <div class="mt-10 grid gap-6 xl:grid-cols-[1.25fr_.75fr]">
        <div class="grid gap-6 sm:grid-cols-2">
            @foreach([['Prayer request terbaru',$recentPrayerRequests,'reference_number','heart'],['Jemaat terbaru',$recentCongregations,'full_name','users'],['Pengumuman terbaru',$recentAnnouncements,'title','bell'],['Pastor Message terbaru',$recentPastorMessages,'title','book']] as [$heading,$records,$field,$icon])
                <x-card><div class="mb-5 flex items-center justify-between gap-3"><h2 class="text-xl">{{ $heading }}</h2><span class="grid h-10 w-10 place-items-center rounded-full bg-canvas text-slate"><x-icon :name="$icon" :size="18"/></span></div><div class="space-y-1">@forelse($records as $record)<div class="flex items-center justify-between gap-4 border-b border-ink/8 py-3 last:border-0"><span class="line-clamp-1">{{ $record->{$field} }}</span><span class="shrink-0 rounded-full bg-canvas px-3 py-1 text-xs text-slate">{{ $record->created_at->format('d M') }}</span></div>@empty<p class="py-5 text-slate">Belum ada data.</p>@endforelse</div></x-card>
            @endforeach
        </div>
        <div class="space-y-6">
            <x-card title="Operasional hari ini"><dl class="grid gap-1">@foreach([['Jemaat aktif','active_congregations'],['Jemaat baru bulan ini','new_congregations'],['Prayer sedang didoakan','in_prayer_requests'],['Total Mezbah Keluarga','family_altars'],['Total Pastor Message','pastor_messages']] as [$label,$key])<div class="flex items-center justify-between gap-4 border-b border-ink/8 py-3 last:border-0"><dt class="text-slate">{{ $label }}</dt><dd class="text-lg font-bold">{{ number_format($summary[$key]) }}</dd></div>@endforeach</dl></x-card>

        </div>
    </div>
</x-layouts.admin>
