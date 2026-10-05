<x-layouts.admin :title="'Konfirmasi Manual · '.$event->title">
    <div x-data="{ selected: null, submitting: false }">
        <header class="event-toolbar">
            <div>
                <a href="{{ route('admin.events.show', $event) }}" class="text-link !min-h-8"><x-icon name="arrow-left" :size="17"/>{{ $event->title }}</a>
                <p class="event-kicker mt-5">Kehadiran</p>
                <h1 class="event-title">Konfirmasi Manual</h1>
                <p class="event-subtitle">Cari nama peserta yang sudah mendaftar, periksa detailnya, lalu konfirmasi kehadiran tanpa tiket atau handphone.</p>
            </div>
            <a href="{{ route('admin.events.scanner', $event) }}" class="button-primary"><x-icon name="qr"/>Scan tiket</a>
        </header>

        <section class="event-panel">
            <div class="event-panel-header">
                <div><h2 class="text-xl">Peserta terdaftar</h2><p class="mt-1 text-sm text-slate">{{ $totalRegistrations }} peserta terdaftar · {{ $registrations->total() }} peserta ditampilkan</p></div>
                <form method="get" class="flex w-full max-w-md gap-2">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <label class="sr-only" for="manual-participant-search">Cari nama peserta</label>
                    <input id="manual-participant-search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari nama peserta" maxlength="255">
                    <button type="submit" class="grid h-12 w-12 shrink-0 place-items-center rounded-xl border border-ink/15 bg-white" aria-label="Cari peserta"><x-icon name="search"/></button>
                </form>
            </div>
            <nav aria-label="Filter kehadiran peserta" class="flex flex-wrap gap-2 border-b border-ink/8 px-5 py-4 sm:px-7">
                @foreach(['all' => 'Semua', 'present' => 'Sudah Hadir', 'pending' => 'Belum Konfirmasi'] as $value => $label)
                    <a href="{{ route('admin.events.manual', [$event, 'status' => $value, 'search' => request('search')]) }}"
                       @class(['inline-flex min-h-11 items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition-colors', 'border-primary bg-primary text-white' => $status === $value, 'border-ink/15 bg-white text-slate hover:border-primary/40 hover:text-primary' => $status !== $value])
                       @if($status === $value) aria-current="page" @endif>
                        {{ $label }}<span class="rounded-full bg-current/10 px-2 py-0.5 text-xs">{{ $statusCounts[$value] }}</span>
                    </a>
                @endforeach
            </nav>
            @error('search')<p class="px-5 py-3 text-sm text-signal" role="alert">{{ $message }}</p>@enderror
            @if(request()->filled('search'))<div class="border-b border-ink/8 px-5 py-3"><a href="{{ route('admin.events.manual', [$event, 'status' => $status]) }}" class="text-link !min-h-8">Hapus pencarian</a></div>@endif
            <div class="divide-y divide-ink/8">
                @forelse($registrations as $registration)
                    @php
                        $detail = [
                            'name' => $registration->attendee_name,
                            'ticket' => $registration->ticket_code,
                            'registeredAt' => $registration->created_at->translatedFormat('d F Y, H:i'),
                            'checkedInAt' => $registration->checked_in_at?->translatedFormat('d F Y, H:i'),
                            'checkedInBy' => $registration->checkedInBy?->name ?? 'admin',
                            'fields' => collect($event->registration_fields)->where('key', '!=', 'name')->map(fn ($field) => ['label' => $field['label'], 'value' => $registration->answers[$field['key']] ?? '–'])->values()->all(),
                            'url' => route('admin.events.manual-check-in', [$event, $registration]),
                        ];
                    @endphp
                    <article class="flex flex-wrap items-center justify-between gap-4 p-5 sm:px-7">
                        <div class="min-w-0 flex-1">
                            <h3 class="break-words text-base font-semibold">{{ $registration->attendee_name }}</h3>
                            <p class="mt-1 break-all font-mono text-xs text-slate">{{ $registration->ticket_code }}</p>
                            <p class="mt-2 flex items-center gap-2 text-sm {{ $registration->checked_in_at ? 'text-emerald-700' : 'text-slate' }}"><span class="event-status-dot {{ $registration->checked_in_at ? 'bg-emerald-500' : 'bg-slate' }}"></span>{{ $registration->checked_in_at ? 'Hadir · '.$registration->checked_in_at->format('H:i') : 'Belum hadir' }}</p>
                        </div>
                        <button type="button" class="button-secondary !px-5" aria-haspopup="dialog" @click="selected = {{ Illuminate\Support\Js::from($detail) }}; submitting = false; $refs.participantDialog.showModal()">Detail<span class="sr-only"> {{ $registration->attendee_name }}</span><x-icon name="arrow-right" :size="17"/></button>
                    </article>
                @empty
                    <div class="p-5"><x-empty-state :title="request()->filled('search') ? 'Peserta tidak ditemukan' : ($status === 'present' ? 'Belum ada peserta yang hadir' : ($status === 'pending' ? 'Tidak ada peserta yang belum konfirmasi' : 'Belum ada peserta'))" :description="request()->filled('search') ? 'Coba cari dengan sebagian nama atau hapus pencarian.' : 'Pilih tab lain untuk melihat peserta dengan status berbeda.'"/></div>
                @endforelse
            </div>
            @if($registrations->hasPages())<div class="border-t border-ink/8 p-5">{{ $registrations->links() }}</div>@endif
        </section>

        <dialog x-ref="participantDialog" @close="selected = null; submitting = false" @click.self="!submitting && $refs.participantDialog.close()" @cancel="submitting && $event.preventDefault()" aria-labelledby="manual-dialog-title" aria-describedby="manual-dialog-description" class="m-auto max-h-[calc(100dvh-2rem)] w-[min(36rem,calc(100vw-2rem))] overflow-y-auto rounded-2xl border border-ink/10 bg-white p-0 text-ink shadow-2xl backdrop:bg-ink/55">
            <div class="flex items-start justify-between gap-4 border-b border-ink/10 p-5 sm:p-7">
                <div><p class="event-kicker">Konfirmasi kehadiran</p><h2 id="manual-dialog-title" class="mt-2 break-words text-2xl" x-text="selected?.name"></h2><p class="mt-2 text-sm text-slate">{{ $event->title }}</p></div>
                <button type="button" class="grid h-11 w-11 shrink-0 place-items-center rounded-full border border-ink/15" @click="$refs.participantDialog.close()" :disabled="submitting" aria-label="Tutup detail peserta" autofocus><x-icon name="close"/></button>
            </div>
            <div class="p-5 sm:p-7">
                <p id="manual-dialog-description" class="text-sm leading-6 text-slate">Pastikan nama dan data pendaftaran sesuai dengan jemaat yang hadir sebelum mengonfirmasi.</p>
                <dl class="mt-4">
                    <div class="event-data-row"><dt class="text-sm text-slate">Kode tiket</dt><dd class="break-all font-mono text-sm" x-text="selected?.ticket"></dd></div>
                    <template x-for="(field, index) in selected?.fields || []" :key="index"><div class="event-data-row"><dt class="text-sm text-slate" x-text="field.label"></dt><dd class="break-words font-medium" x-text="field.value"></dd></div></template>
                    <div class="event-data-row"><dt class="text-sm text-slate">Terdaftar</dt><dd class="text-sm" x-text="selected?.registeredAt"></dd></div>
                </dl>
                <template x-if="selected?.checkedInAt"><div class="mt-5"><x-alert><span x-text="'Kehadiran sudah dicatat pada ' + selected.checkedInAt + ' oleh ' + selected.checkedInBy + '.'"></span></x-alert><button type="button" class="button-secondary mt-5 w-full" @click="$refs.participantDialog.close()">Tutup</button></div></template>
                <template x-if="selected && !selected.checkedInAt">
                    <form method="post" :action="selected.url" class="mt-6" @submit="submitting = true">
                        @csrf
                        <button type="submit" class="button-primary w-full" :disabled="submitting"><x-icon name="check"/><span x-text="submitting ? 'Menyimpan…' : 'Konfirmasi kehadiran'"></span></button>
                    </form>
                </template>
            </div>
        </dialog>
    </div>
</x-layouts.admin>
