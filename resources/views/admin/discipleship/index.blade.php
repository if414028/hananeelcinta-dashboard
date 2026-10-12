<x-layouts.admin title="Pemuridan">
    <header class="event-toolbar">
        <div><p class="event-kicker">Pelayanan</p><h1 class="event-title">Pemuridan</h1><p class="event-subtitle">Kelola kelas dan dampingi pertumbuhan jemaat</p></div>
        @can('discipleship.create')<a href="{{ route('admin.discipleship.create') }}" class="button-primary mobile-full"><x-icon name="plus"/>Buat Kelas</a>@endcan
    </header>
    <form method="get" class="mb-6 flex flex-col gap-3 sm:flex-row" role="search">
        <div class="flex-1"><label for="class-search" class="sr-only">Cari kelas atau pengajar</label><input id="class-search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari nama kelas atau pengajar" maxlength="255"></div>
        <div><label for="class-status" class="sr-only">Status kelas</label><select id="class-status" name="status" class="form-control"><option value="">Semua status</option><option value="active" @selected(request('status') === 'active')>Belum selesai</option><option value="completed" @selected(request('status') === 'completed')>Selesai</option></select></div>
        <x-button type="submit" variant="secondary"><x-icon name="search"/>Cari</x-button>
    </form>
    <p class="mb-4 text-sm text-slate">{{ $classes->total() }} kelas ditemukan</p>
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse($classes as $class)
            <article class="event-panel min-w-0 overflow-hidden">
                <a href="{{ route('admin.discipleship.show', $class) }}" class="block"><img src="{{ $class->bannerUrl() }}" alt="Banner {{ $class->name }}" class="aspect-video w-full object-cover" loading="lazy"></a>
                <div class="p-5 sm:p-6">
                    <div class="mb-4 flex items-center gap-3"><img src="{{ $class->badgeUrl() }}" alt="" class="h-11 w-11 object-contain" loading="lazy"><span @class(['rounded-full px-3 py-1 text-xs font-semibold', 'bg-emerald-50 text-emerald-700' => $class->completed_at, 'bg-primary/8 text-primary' => !$class->completed_at])>{{ $class->statusLabel() }}</span></div>
                    <h2 class="break-words text-2xl"><a href="{{ route('admin.discipleship.show', $class) }}" class="hover:text-primary">{{ $class->name }}</a></h2>
                    <p class="mt-2 break-words text-sm text-slate">Pengajar: {{ $class->teacher_name }}</p>
                    <p class="mt-4 line-clamp-2 break-words text-sm leading-6 text-slate">{{ $class->description }}</p>
                    <div class="mt-5 border-t border-ink/10 pt-4 text-sm"><p class="flex items-center gap-2"><x-icon name="calendar" :size="16"/>{{ $class->starts_at->translatedFormat('d M Y') }} – {{ $class->ends_at->translatedFormat('d M Y') }}</p><div class="mt-4 flex items-center justify-between"><span class="text-slate">{{ $class->participants_count }} peserta</span><a href="{{ route('admin.discipleship.show', $class) }}" class="text-link !min-h-8">Detail kelas <x-icon name="arrow-right" :size="16"/></a></div></div>
                </div>
            </article>
        @empty
            <div class="event-panel md:col-span-2 xl:col-span-3"><x-empty-state :title="request()->filled('search') || request()->filled('status') ? 'Kelas tidak ditemukan' : 'Belum ada kelas pemuridan'" :description="request()->filled('search') || request()->filled('status') ? 'Coba kata kunci atau status lain.' : 'Buat kelas pertama dan pilih jemaat yang akan mengikuti kelas.'"/></div>
        @endforelse
    </div>
    @if($classes->hasPages())<div class="mt-6">{{ $classes->links() }}</div>@endif
</x-layouts.admin>
