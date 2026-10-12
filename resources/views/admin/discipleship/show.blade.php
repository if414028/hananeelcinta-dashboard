<x-layouts.admin :title="$class->name">
    <div x-data="{ submitting: false }">
    <header class="event-toolbar">
        <div class="min-w-0"><a href="{{ route('admin.discipleship.index') }}" class="text-link !min-h-8"><x-icon name="arrow-left" :size="17"/>Kembali ke Pemuridan</a><p class="event-kicker mt-5">Detail kelas</p><h1 class="event-title break-words">{{ $class->name }}</h1><p class="event-subtitle break-words">Pengajar: {{ $class->teacher_name }}</p></div>
        @if(!$class->completed_at)<div class="flex shrink-0 flex-col gap-3 sm:flex-row sm:flex-wrap">
            @can('discipleship.update')<a href="{{ route('admin.discipleship.edit', $class) }}" class="button-secondary mobile-full">Edit Kelas</a>@endcan
            @can('discipleship.complete')<x-button class="mobile-full" aria-haspopup="dialog" aria-controls="complete-class-dialog" @click="$refs.completeDialog.showModal()"><x-icon name="check"/>Selesaikan Kelas</x-button>@endcan
        </div>@endif
    </header>
    @if($errors->any())<x-alert type="error" class="mb-6">{{ $errors->first() }}</x-alert>@endif
    @if($class->completed_at)<x-alert class="mb-6">Kelas diselesaikan pada {{ $class->completed_at->translatedFormat('d M Y · H:i') }} WIB. Badge kelulusan sudah diberikan kepada seluruh peserta. Riwayat kelas telah dikunci.</x-alert>@endif
    <div class="mb-6 grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(16rem,1fr)]">
        <section class="event-panel min-w-0 overflow-hidden">
            <img src="{{ $class->bannerUrl() }}" alt="Banner {{ $class->name }}" class="aspect-video max-h-96 w-full object-cover">
            <div class="event-panel-body"><h2 class="text-xl">Tentang kelas</h2><p class="mt-4 break-words whitespace-pre-line text-slate leading-7">{{ $class->description }}</p></div>
        </section>
        <section class="event-panel min-w-0">
            <div class="event-panel-header"><h2 class="text-xl">Informasi kelas</h2><span class="rounded-full bg-primary/8 px-3 py-1 text-xs font-semibold text-primary">{{ $class->statusLabel() }}</span></div>
            <div class="event-panel-body">
                <dl class="space-y-4"><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate">Mulai</dt><dd class="mt-1 font-medium">{{ $class->starts_at->translatedFormat('d M Y · H:i') }} WIB</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate">Selesai</dt><dd class="mt-1 font-medium">{{ $class->ends_at->translatedFormat('d M Y · H:i') }} WIB</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate">Peserta</dt><dd class="mt-1 font-medium">{{ $class->participants_count }} jemaat</dd></div></dl>
                <div class="mt-6 border-t border-ink/10 pt-5"><h3 class="text-sm font-semibold">Badge kelulusan</h3><img src="{{ $class->badgeUrl() }}" alt="Badge {{ $class->name }}" class="mx-auto my-5 h-32 w-32 object-contain"><p class="text-sm leading-6 text-slate">{{ $class->completed_at ? 'Badge ini sudah tampil pada profil setiap peserta.' : 'Badge diberikan saat admin menekan Selesaikan Kelas. Berakhirnya jadwal tidak otomatis menyelesaikan kelas.' }}</p></div>
            </div>
        </section>
    </div>
    <section class="event-panel">
        <div class="event-panel-header"><div><h2 class="text-xl">Peserta kelas</h2><p class="mt-1 text-sm text-slate">{{ $class->participants_count }} jemaat mengikuti kelas ini.</p></div>@if(!$class->completed_at)@can('discipleship.update')<a href="{{ route('admin.discipleship.edit', $class) }}#peserta" class="button-secondary mobile-full"><x-icon name="plus"/>Tambah Peserta</a>@endcan @endif</div>
        <div class="px-5 py-4 sm:px-6"><form method="get" class="flex max-w-md gap-2" role="search"><label for="enrolled-search" class="sr-only">Cari peserta kelas</label><input id="enrolled-search" class="form-control min-w-0" name="search" value="{{ request('search') }}" placeholder="Cari nama atau NIJ peserta" maxlength="255"><x-button variant="secondary" type="submit" aria-label="Cari peserta"><x-icon name="search"/></x-button></form></div>
        <ul class="divide-y divide-ink/8">
            @forelse($participants as $participant)
                <li class="flex items-center gap-4 px-5 py-4 sm:px-6">
                    @php
                        $photoUrl = $participant->profilePhotoUrl();
                        $initials = collect(preg_split('/\s+/u', trim($participant->full_name), -1, PREG_SPLIT_NO_EMPTY))
                            ->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->implode('');
                    @endphp
                    <div class="relative grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-full bg-primary/8 text-xs font-semibold text-primary" aria-hidden="true">
                        <span>{{ mb_strtoupper($initials) ?: '?' }}</span>
                        @if($photoUrl)<img src="{{ $photoUrl }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy" referrerpolicy="no-referrer" x-on:error="$el.remove()">@endif
                    </div>
                    <div class="min-w-0 flex-1">@can('congregations.view')<a href="{{ route('admin.congregations.show', $participant) }}" class="break-words font-semibold hover:text-primary">{{ $participant->full_name }}</a>@else<p class="break-words font-semibold">{{ $participant->full_name }}</p>@endcan<p class="mt-1 text-xs text-slate">{{ $participant->member_number }}</p></div>
                    <span class="text-xs font-semibold text-slate">{{ $participant->pivot->completed_at ? 'Lulus' : 'Terdaftar' }}</span>
                </li>
            @empty
                <li><x-empty-state title="Peserta tidak ditemukan" description="Coba kata kunci lain atau tambahkan jemaat ke kelas ini."/></li>
            @endforelse
        </ul>
        @if($participants->hasPages())<div class="border-t border-ink/8 p-5">{{ $participants->links() }}</div>@endif
    </section>
    @if(!$class->completed_at)
        @can('discipleship.complete')
            <dialog id="complete-class-dialog" x-ref="completeDialog" aria-labelledby="complete-class-title" aria-describedby="complete-class-description" @click.self="!submitting && $refs.completeDialog.close()" @cancel="submitting && $event.preventDefault()" class="m-auto max-h-[calc(100dvh-2rem)] w-[min(28rem,calc(100vw-2rem))] overflow-y-auto rounded-3xl border border-ink/10 bg-white p-0 text-ink shadow-2xl backdrop:bg-ink/55">
                <div class="p-6 sm:p-8">
                    <div class="mb-5 flex items-center justify-between gap-4">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-primary/8 text-primary"><x-icon name="check" :size="24"/></span>
                        <button type="button" class="grid h-11 w-11 place-items-center rounded-full text-slate hover:bg-canvas hover:text-ink" aria-label="Tutup konfirmasi" @click="$refs.completeDialog.close()" :disabled="submitting"><x-icon name="close"/></button>
                    </div>
                    <h2 id="complete-class-title" class="text-2xl">Selesaikan Kelas?</h2>
                    <p class="mt-2 break-words font-semibold">{{ $class->name }}</p>
                    <p id="complete-class-description" class="mt-4 text-sm leading-6 text-slate">Seluruh peserta akan mendapatkan badge kelulusan. Setelah selesai, informasi dan peserta kelas tidak dapat diubah.</p>
                    <form method="post" action="{{ route('admin.discipleship.complete', $class) }}" class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end" :aria-busy="submitting" @submit="if (submitting) { $event.preventDefault(); return; } submitting = true">
                        @csrf
                        <x-button variant="secondary" autofocus @click="$refs.completeDialog.close()" x-bind:disabled="submitting">Batal</x-button>
                        <x-button type="submit" x-bind:disabled="submitting"><span x-text="submitting ? 'Menyelesaikan…' : 'Selesaikan Kelas'">Selesaikan Kelas</span></x-button>
                    </form>
                </div>
            </dialog>
        @endcan
    @endif
    </div>
</x-layouts.admin>
