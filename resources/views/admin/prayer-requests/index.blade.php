<x-layouts.admin title="Prayer Request">
    <header class="admin-page-header">
        <div>
            <p class="eyebrow">Pelayanan doa</p>
            <h1 class="admin-page-title">Prayer Request</h1>
            <p class="admin-page-subtitle">Geser permohonan dari Open untuk mulai mendoakan. Isi hasil doa saat memindahkannya ke Selesai. Kartu yang sudah diambil hanya dapat dibuka oleh yang mendoakan.</p>
        </div>
    </header>

    <x-card class="mb-6">
        <form method="get" class="grid gap-4 md:grid-cols-3">
            <x-input name="search" label="Pencarian" :value="request('search')" placeholder="Cari nama atau referensi…" />
            @foreach($filters as $filter)
                <x-select :name="$filter['name']" :label="$filter['label']">
                    <option value="">Semua</option>
                    @foreach($filter['options'] as $value => $label)
                        <option value="{{ $value }}" @selected(request($filter['name']) === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
            @endforeach
            <div class="admin-filter-actions">
                <x-button type="submit"><x-icon name="search" :size="18"/>Terapkan</x-button>
                <a href="{{ route('admin.prayer-requests.index') }}" class="text-link justify-center !px-3">Reset</a>
            </div>
        </form>
    </x-card>

    <section x-data="prayerBoard('{{ csrf_token() }}')" data-open-prayer="{{ request('prayer') }}" data-detail-base="{{ route('admin.prayer-requests.index') }}" @pointermove.window="pointerMove($event)" @pointerup.window="pointerEnd($event)" @pointercancel.window="pointerCancel()" @resize.window="updateBoardScroll()" @scroll.window.passive="updateBoardScroll()" aria-label="Papan Prayer Request">
        <p x-cloak x-show="pending" role="status" class="mb-4 rounded-xl border border-primary/20 bg-white px-4 py-3 text-sm font-medium text-primary">Memindahkan kartu…</p>
        <div x-cloak x-show="error" role="alert" class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-signal/30 bg-white px-4 py-3 text-sm text-ink">
            <span x-text="error"></span>
            <button type="button" class="font-semibold text-primary underline underline-offset-4" @click="window.location.reload()">Muat ulang papan</button>
        </div>
        <p class="mb-4 text-sm text-slate">Klik kartu untuk melihat detail. Geser ke kolom berikutnya untuk mengubah status. Saat digeser ke Selesai, isi hasil doa di dialog yang terbuka. Pada layar sentuh gunakan pegangan, atau tekan panah kanan saat pegangan terfokus.</p>
        <div x-cloak x-show="boardOverflows" class="sticky top-20 z-20 mb-3 flex items-center justify-end gap-2 rounded-xl bg-canvas/90 py-2 backdrop-blur-sm" aria-label="Navigasi horizontal papan">
            <span class="mr-1 text-xs font-medium text-slate">Geser kolom</span>
            <button type="button" class="grid h-11 w-11 place-items-center rounded-xl border border-ink/15 bg-white text-ink hover:border-primary/30 hover:text-primary disabled:opacity-40" :disabled="!canScrollLeft" @click="scrollColumns(-1)" aria-label="Geser papan ke kiri" aria-controls="prayer-board-columns"><x-icon name="arrow-left" :size="19"/></button>
            <button type="button" class="grid h-11 w-11 place-items-center rounded-xl border border-ink/15 bg-white text-ink hover:border-primary/30 hover:text-primary disabled:opacity-40" :disabled="!canScrollRight" @click="scrollColumns(1)" aria-label="Geser papan ke kanan" aria-controls="prayer-board-columns"><x-icon name="arrow-right" :size="19"/></button>
        </div>
        <div id="prayer-board-columns" x-ref="columns" class="prayer-board-scroll flex max-w-full snap-x snap-mandatory gap-4 overflow-x-auto pb-5 xl:grid xl:grid-cols-3 xl:overflow-visible" @scroll.passive="updateBoardScroll()" role="region" tabindex="0" aria-label="Kolom status Prayer Request">
            @foreach($statuses as $status)
                @php($pageItems = $columns[$status->value])
                @php($columnTone = match($status) {
                    \App\Enums\PrayerRequestStatus::Open => 'border-t-signal-light',
                    \App\Enums\PrayerRequestStatus::InPrayer => 'border-t-primary',
                    \App\Enums\PrayerRequestStatus::Closed => 'border-t-emerald-600',
                })
                <section data-board-status="{{ $status->value }}" class="w-[min(20rem,calc(100vw-3rem))] shrink-0 snap-start rounded-2xl border border-ink/10 border-t-4 bg-white/55 p-3 sm:w-80 xl:w-auto {{ $columnTone }}" aria-labelledby="prayer-column-{{ $status->value }}" :class="dragTarget === '{{ $status->value }}' && 'ring-2 ring-primary/40 bg-primary/5'">
                    <div class="flex items-start justify-between gap-3 px-1 pb-3">
                        <h2 id="prayer-column-{{ $status->value }}" class="text-base font-semibold leading-snug text-ink">{{ $status->label() }}</h2>
                        <span class="inline-flex min-w-8 justify-center rounded-full bg-ink/8 px-2 py-1 text-xs font-semibold text-ink" aria-label="{{ $pageItems->count() }} permohonan">{{ $pageItems->count() }}</span>
                    </div>
                    <ol class="space-y-3">
                        @forelse($pageItems as $item)
                            @php($mine = $item->handled_by !== null && (int) $item->handled_by === auth()->id())
                            @php($available = $item->handled_by === null)
                            @php($canAccess = ! $item->is_confidential || auth()->user()->can('prayer_requests.view_confidential'))
                            @php($canView = $canAccess && ($available || $mine))
                            @php($canChange = auth()->user()->can('prayer_requests.update') && $canView)
                            @php($nextStatus = $status->nextStatuses()[0] ?? null)
                            <li>
                                <article class="rounded-xl border border-ink/10 bg-white p-4 shadow-sm transition-colors {{ $canView ? 'hover:border-primary/30 focus-visible:outline-2 focus-visible:outline-primary' : '' }} {{ $canChange && $nextStatus ? 'cursor-grab select-none active:cursor-grabbing' : ($canView ? 'cursor-pointer' : '') }}" data-url="{{ route('admin.prayer-requests.move', $item) }}" data-detail-url="{{ route('admin.prayer-requests.show', $item) }}" data-current="{{ $status->value }}" data-next="{{ $nextStatus?->value }}" data-reference="{{ $item->reference_number }}" @if($canView) role="button" tabindex="0" aria-label="Lihat detail {{ $item->reference_number }}" @click="openCardDetail($event, '{{ route('admin.prayer-requests.show', $item) }}')" @keydown.enter.prevent="openCardDetail($event, '{{ route('admin.prayer-requests.show', $item) }}')" @keydown.space.prevent="openCardDetail($event, '{{ route('admin.prayer-requests.show', $item) }}')" @endif @if($canChange && $nextStatus) @pointerdown="pointerStart($event)" @endif>
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="break-all text-xs font-semibold tracking-wide text-primary">{{ $item->reference_number }}</p>
                                        @if($canChange && $nextStatus)
                                            <span data-drag-handle role="button" tabindex="0" aria-label="Geser {{ $item->reference_number }} ke {{ $nextStatus->label() }}" class="grid h-11 w-11 shrink-0 place-items-center rounded-lg text-slate hover:bg-primary/5 hover:text-primary [touch-action:none]" @click.stop="void 0" @pointerdown="pointerStart($event)" @keydown.arrow-right.prevent.stop="keyboardMove($event)" @keydown.enter.prevent.stop="keyboardMove($event)" @keydown.space.prevent.stop="keyboardMove($event)"><x-icon name="grip" :size="20"/></span>
                                        @endif
                                    </div>
                                    <p class="mt-3 text-base font-semibold leading-snug text-ink">{{ $item->prayer_category->label() }}</p>
                                    @if(($available || $mine) && $canAccess)
                                        <p class="mt-1 break-words text-sm text-slate">{{ $item->is_anonymous ? 'Anonim' : $item->name }}</p>
                                    @else
                                        <p class="mt-1 text-sm text-slate">Yang mendoakan: {{ $item->legacy_handler_name ?: ($item->handler?->name ?? 'pengguna lain') }}</p>
                                    @endif
                                    <p class="mt-3 text-xs text-slate">{{ $item->created_at->format('d M Y · H:i') }}</p>
                                    @unless($canView)
                                        <p class="mt-4 border-t border-ink/8 pt-3 text-xs font-medium text-slate">{{ $available ? 'Akses detail tidak tersedia' : 'Kartu terkunci' }}</p>
                                    @endunless
                                </article>
                            </li>
                        @empty
                            <li class="rounded-xl border border-dashed border-ink/15 bg-white px-4 py-8 text-center text-sm text-slate">Belum ada permohonan di tahap ini.</li>
                        @endforelse
                    </ol>
                </section>
            @endforeach
        </div>

        <div x-cloak x-show="showScrollDock" class="prayer-scroll-dock flex items-center gap-3" :style="`left:${scrollDockLeft}px;width:${scrollDockWidth}px`">
            <span class="shrink-0 text-xs font-semibold text-slate">Geser kolom</span>
            <div x-ref="dockTrack" class="prayer-scroll-track min-w-0 flex-1" role="scrollbar" tabindex="0" aria-label="Geser kolom Prayer Request" aria-controls="prayer-board-columns" aria-orientation="horizontal" aria-valuemin="0" :aria-valuemax="Math.round(scrollMax)" :aria-valuenow="scrollPosition" :aria-valuetext="`Posisi ${Math.round(scrollMax ? scrollPosition / scrollMax * 100 : 0)} persen`" @pointerdown="dockPointerStart($event)" @pointermove="dockPointerMove($event)" @pointerup="dockPointerEnd()" @pointercancel="dockPointerEnd()" @keydown="dockKeydown($event)">
                <span data-scroll-thumb class="prayer-scroll-thumb" :class="dockDragging && 'is-dragging'" :style="`width:${scrollThumbWidth}px;left:${scrollThumbLeft}px`"></span>
            </div>
        </div>

        <dialog x-ref="detailDialog" @close="clearDetail()" @cancel="saving && $event.preventDefault()" @click.self="closeDetail()" aria-labelledby="prayer-dialog-task prayer-dialog-title" class="m-auto max-h-[calc(100vh-2rem)] w-[min(48rem,calc(100vw-2rem))] overflow-y-auto rounded-2xl border border-ink/10 bg-white p-0 text-ink shadow-2xl backdrop:bg-ink/55">
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-ink/10 bg-white px-5 py-4 sm:px-7">
                <div class="min-w-0">
                    <p id="prayer-dialog-task" class="text-xs font-semibold uppercase tracking-wide text-primary" x-text="completionCard ? 'Selesaikan permohonan doa' : 'Detail permohonan doa'"></p>
                    <h2 id="prayer-dialog-title" class="mt-1 break-all text-xl font-semibold" x-text="detail?.reference || 'Memuat…'"></h2>
                </div>
                <button type="button" :disabled="saving" class="grid h-11 w-11 shrink-0 place-items-center rounded-full border border-ink/15 hover:bg-canvas disabled:opacity-50" @click="closeDetail()" aria-label="Tutup detail"><x-icon name="close"/></button>
            </div>
            <p x-cloak x-show="detailLoading" role="status" class="px-5 py-12 text-center text-slate sm:px-7">Memuat detail permohonan…</p>
            <p x-cloak x-show="detailError" role="alert" class="px-5 py-6 text-sm text-signal sm:px-7" x-text="detailError"></p>
            <template x-if="detail && !detailLoading">
                <div class="space-y-6 px-5 py-6 sm:px-7">
                    <div class="flex flex-wrap items-center gap-2 text-sm"><span class="rounded-full bg-primary/8 px-3 py-1 font-semibold text-primary" x-text="detail.status"></span><span class="text-slate" x-text="detail.submitted_at"></span></div>
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate">Nama</dt><dd class="mt-1 break-words" x-text="detail.name"></dd></div>
                        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate">Kategori</dt><dd class="mt-1" x-text="detail.category"></dd></div>
                        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate">Jenis permohonan</dt><dd class="mt-1 break-words" x-text="detail.request_type"></dd></div>
                        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate">Yang Mendoakan</dt><dd class="mt-1 break-words" x-text="detail.handler || 'Belum ada'"></dd></div>
                        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate">Email</dt><dd class="mt-1 break-words" x-text="detail.email || '-'"></dd></div>
                        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate">Telepon</dt><dd class="mt-1" x-text="detail.phone || '-'"></dd></div>
                    </dl>
                    <div><h3 class="text-sm font-semibold text-ink">Isi permohonan doa</h3><p class="mt-2 whitespace-pre-line rounded-xl bg-canvas p-5 leading-7" x-text="detail.content"></p></div>
                    <form x-show="detail.can_update" @submit.prevent="saveDetail()" class="space-y-4 border-t border-ink/10 pt-5">
                        <div>
                            <label for="prayer-result" class="mb-2 block text-sm font-semibold">Hasil doa <span x-show="completionCard" class="text-signal">*</span></label>
                            <p x-show="completionCard" id="prayer-result-help" class="mb-3 text-sm leading-6 text-slate">Isi hasil doa untuk menyelesaikan permohonan ini. Kartu tetap di Sedang Didoakan sampai hasilnya disimpan.</p>
                            <textarea id="prayer-result" x-ref="prayerResult" x-model="detail.prayer_result" @input="saveError = ''" :readonly="!detail.can_update" :aria-required="!!completionCard" :aria-invalid="!!saveError" :aria-describedby="completionCard ? 'prayer-result-help' : null" maxlength="5000" class="form-control min-h-28 resize-y"></textarea>
                            <p x-cloak x-show="saveError" role="alert" class="mt-2 text-sm text-signal" x-text="saveError"></p>
                        </div>
                        <p x-cloak x-show="saveSuccess" role="status" class="text-sm font-semibold text-primary" x-text="saveSuccess"></p>
                        <div class="flex flex-wrap gap-3">
                            <button type="submit" :disabled="saving" class="button-primary" x-text="saving ? 'Menyimpan…' : (completionCard ? 'Simpan hasil doa dan selesaikan' : 'Simpan hasil doa')"></button>
                            <button x-show="completionCard" type="button" :disabled="saving" class="button-secondary" @click="closeDetail()">Batal</button>
                        </div>
                    </form>
                    <div x-show="!detail.can_update" class="border-t border-ink/10 pt-5">
                        <h3 class="text-sm font-semibold text-ink">Hasil doa</h3>
                        <p class="mt-2 whitespace-pre-line rounded-xl bg-canvas p-5 text-sm leading-7 text-slate" x-text="detail.prayer_result || 'Belum ada hasil doa. Geser kartu ke Sedang Didoakan untuk mulai menangani.'"></p>
                    </div>
                </div>
            </template>
        </dialog>
    </section>
</x-layouts.admin>
