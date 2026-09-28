<x-layouts.admin title="Event">
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div><p class="eyebrow">Pelayanan</p><h1 class="mt-3 text-4xl">Event</h1><p class="mt-2 text-slate">Kelola acara, formulir pendaftaran, dan kehadiran jemaat.</p></div>
        @can('events.create')<a href="{{ route('admin.events.create') }}" class="button-primary"><x-icon name="plus"/>Buat Event</a>@endcan
    </div>
    <x-card class="mb-6"><form method="get" class="flex flex-col gap-3 sm:flex-row"><x-input name="search" aria-label="Cari event" placeholder="Cari event" :value="request('search')"/><x-button type="submit" variant="secondary"><x-icon name="search"/>Cari</x-button></form></x-card>
    <x-card class="overflow-hidden !p-0">
        <div class="overflow-x-auto"><table class="admin-table"><thead><tr><th>Event</th><th>Tanggal</th><th>Status</th><th>Pendaftar</th><th>Aksi</th></tr></thead><tbody>
        @forelse($events as $event)<tr><td><strong>{{ $event->title }}</strong></td><td>{{ $event->starts_at->format('d M Y, H:i') }}@if($event->ends_at)<br><span class="text-slate">s.d. {{ $event->ends_at->format('d M Y, H:i') }}</span>@endif</td><td><x-badge :tone="$event->is_published ? 'success' : 'neutral'">{{ $event->is_published ? 'Publik' : 'Draft' }}</x-badge></td><td>{{ $event->registrations_count }}</td><td><a href="{{ route('admin.events.show',$event) }}" class="font-semibold text-link hover:underline">Detail</a></td></tr>
        @empty<tr><td colspan="5"><x-empty-state title="Belum ada event" description="Buat event pertama untuk mulai menerima pendaftaran."/></td></tr>@endforelse
        </tbody></table></div><div class="p-6">{{ $events->links() }}</div>
    </x-card>
</x-layouts.admin>
