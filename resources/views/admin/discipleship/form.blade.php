<x-layouts.admin :title="$title">
    <header class="event-toolbar"><div><a href="{{ $class->exists ? route('admin.discipleship.show', $class) : route('admin.discipleship.index') }}" class="text-link !min-h-8"><x-icon name="arrow-left" :size="17"/>Kembali</a><p class="event-kicker mt-5">Pemuridan</p><h1 class="event-title">{{ $title }}</h1><p class="event-subtitle">Siapkan informasi kelas, badge kelulusan, dan jemaat yang akan mengikuti kelas.</p></div></header>
    @if($errors->any())<x-alert type="error" class="mb-6">{{ $errors->first() }}</x-alert>@endif
    <form method="post" action="{{ $class->exists ? route('admin.discipleship.update', $class) : route('admin.discipleship.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf @if($class->exists)@method('PUT')@endif
        <section class="event-panel">
            <div class="event-panel-header"><div><h2 class="text-xl">Informasi kelas</h2><p class="mt-1 text-sm text-slate">Data kelas dapat diubah selama kelas belum diselesaikan.</p></div></div>
            <div class="event-panel-body grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2"><x-input name="name" label="Nama Kelas" :value="old('name', $class->name)" maxlength="255" required/></div>
                <div class="md:col-span-2"><x-textarea name="description" label="Deskripsi Kelas" maxlength="20000" rows="5" required>{{ old('description', $class->description) }}</x-textarea></div>
                <div class="md:col-span-2"><x-input name="teacher_name" label="Nama Pengajar" :value="old('teacher_name', $class->teacher_name)" maxlength="255" required/></div>
                <x-input name="starts_at" label="Mulai Kelas (WIB)" type="datetime-local" :value="old('starts_at', $class->starts_at?->format('Y-m-d\TH:i'))" required/>
                <x-input name="ends_at" label="Selesai Kelas (WIB)" type="datetime-local" :value="old('ends_at', $class->ends_at?->format('Y-m-d\TH:i'))" required/>
            </div>
        </section>
        <section class="event-panel">
            <div class="event-panel-header"><div><h2 class="text-xl">Banner & badge</h2><p class="mt-1 text-sm text-slate">JPG, PNG, atau WebP, maksimal 5 MB per gambar. Badge diberikan saat admin menyelesaikan kelas.</p></div></div>
            <div class="event-panel-body grid gap-6 md:grid-cols-2">
                <div><x-input name="banner" label="Upload Banner Kelas" type="file" accept="image/jpeg,image/png,image/webp" :required="!$class->exists"/><p class="mt-2 text-xs text-slate">Gunakan gambar horizontal untuk tampilan banner.</p>@if($class->exists)<img src="{{ $class->bannerUrl() }}" alt="Banner kelas saat ini" class="mt-4 aspect-video w-full rounded-2xl object-cover">@endif</div>
                <div><x-input name="badge" label="Upload Badge Kelas" type="file" accept="image/jpeg,image/png,image/webp" :required="!$class->exists"/><p class="mt-2 text-xs text-slate">Gunakan gambar persegi. PNG transparan juga didukung.</p>@if($class->exists)<img src="{{ $class->badgeUrl() }}" alt="Badge kelas saat ini" class="mt-4 h-32 w-32 object-contain">@endif</div>
            </div>
        </section>
        <section id="peserta" class="event-panel scroll-mt-24" x-data="discipleshipParticipants(@js(route('admin.discipleship.members')), @js($selectedParticipants))">
            <div class="event-panel-header"><div><h2 class="text-xl">Peserta kelas</h2><p class="mt-1 text-sm text-slate">Pilih minimal satu jemaat. Peserta lain dapat ditambahkan melalui Edit Kelas.</p></div><span class="text-sm font-semibold text-primary" aria-live="polite"><span x-text="selected.length">{{ $selectedParticipants->count() }}</span> dipilih</span></div>
            <div class="event-panel-body grid gap-6 lg:grid-cols-2">
                <div>
                    <label for="participant-search" class="mb-2 block text-sm font-bold">Cari jemaat</label><input id="participant-search" type="search" class="form-control" placeholder="Nama atau NIJ" maxlength="255" x-model="search" @input.debounce.300ms="load()" @keydown.enter.prevent="load()" aria-controls="participant-results">
                    <p x-show="loading" class="mt-3 text-sm text-slate" role="status">Memuat jemaat…</p><p x-cloak x-show="error" x-text="error" class="mt-3 text-sm text-signal" role="alert"></p><button x-cloak x-show="error" type="button" class="text-link" @click="load()">Coba lagi</button>
                    <div id="participant-results" class="mt-3 max-h-80 overflow-y-auto rounded-xl border border-ink/10" :aria-busy="loading">
                        <template x-for="member in members" :key="member.id">
                            <label class="flex min-h-16 cursor-pointer items-center gap-3 border-b border-ink/8 p-3 last:border-0 hover:bg-canvas">
                                <input type="checkbox" class="h-5 w-5 shrink-0 accent-primary" :checked="isSelected(member.id)" @change="toggle(member)">
                                <span class="relative grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-full bg-primary/8 text-xs font-semibold text-primary" aria-hidden="true">
                                    <span x-text="initials(member.full_name)"></span>
                                    <template x-if="member.profile_photo_url"><img :src="member.profile_photo_url" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy" referrerpolicy="no-referrer" x-on:error="$el.remove()"></template>
                                </span>
                                <span class="min-w-0"><span class="block break-words text-sm font-semibold" x-text="member.full_name"></span><span class="text-xs text-slate" x-text="member.member_number"></span></span>
                            </label>
                        </template>
                        <p x-cloak x-show="!loading && !error && members.length === 0" class="p-4 text-sm text-slate">Jemaat tidak ditemukan.</p>
                    </div>
                    <button x-cloak x-show="nextPage" type="button" class="text-link mt-2" @click="load(nextPage)" :disabled="loading">Muat lebih banyak</button>
                </div>
                <div><h3 class="mb-3 text-sm font-bold">Jemaat terpilih</h3><div class="max-h-96 space-y-2 overflow-y-auto">
                    <template x-for="member in selected" :key="member.id">
                        <div class="flex items-center gap-3 rounded-xl border border-ink/10 bg-canvas/50 p-3">
                            <input type="hidden" name="participant_ids[]" :value="member.id">
                            <span class="relative grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-full bg-primary/8 text-xs font-semibold text-primary" aria-hidden="true">
                                <span x-text="initials(member.full_name)"></span>
                                <template x-if="member.profile_photo_url"><img :src="member.profile_photo_url" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy" referrerpolicy="no-referrer" x-on:error="$el.remove()"></template>
                            </span>
                            <div class="min-w-0 flex-1"><p class="break-words text-sm font-semibold" x-text="member.full_name"></p><p class="text-xs text-slate" x-text="member.member_number"></p></div>
                            <button type="button" class="grid h-11 w-11 shrink-0 place-items-center rounded-full hover:bg-white" @click="toggle(member)" :aria-label="`Hapus ${member.full_name} dari peserta`"><x-icon name="close" :size="17"/></button>
                        </div>
                    </template>
                    <p x-show="selected.length === 0" class="rounded-xl border border-dashed border-ink/20 p-5 text-sm text-slate">Belum ada peserta dipilih.</p>
                </div>@error('participant_ids')<p class="mt-3 text-sm text-signal" role="alert">{{ $message }}</p>@enderror @error('participant_ids.*')<p class="mt-3 text-sm text-signal" role="alert">{{ $message }}</p>@enderror</div>
            </div>
            <noscript><p class="px-6 pb-6 text-sm text-signal">Aktifkan JavaScript untuk memilih peserta kelas.</p></noscript>
        </section>
        <div class="mobile-action-bar sticky flex justify-end rounded-2xl border border-white/70 bg-white/85 p-3 shadow-lg backdrop-blur-xl"><x-button type="submit" class="mobile-full">{{ $class->exists ? 'Simpan Perubahan' : 'Buat Kelas' }}</x-button></div>
    </form>
</x-layouts.admin>
