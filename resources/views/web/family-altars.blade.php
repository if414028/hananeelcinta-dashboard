<x-layouts.public title="Mezbah Keluarga" description="Temukan jadwal dan lokasi Mezbah Keluarga JKI Hananeel Cinta." :image="asset('images/family-altars/family-prayer-hero.webp')">
    <section class="relative isolate flex min-h-[38rem] items-end overflow-hidden bg-primary pb-16 pt-40 text-white lg:min-h-[42rem] lg:pb-20 lg:pt-48" x-data="parallaxBackground">
        <div class="absolute -inset-y-16 inset-x-0 -z-20 scale-110 bg-cover bg-[68%_center] will-change-transform sm:bg-center" style="background-image: url('{{ asset('images/family-altars/family-prayer-hero.webp') }}')" :style="`transform: translate3d(0, ${offset}px, 0); background-image: url('{{ asset('images/family-altars/family-prayer-hero.webp') }}')`" aria-hidden="true"></div>
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(43,11,14,.91)_0%,rgba(58,14,18,.68)_48%,rgba(38,10,12,.24)_100%)]" aria-hidden="true"></div>
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(0deg,rgba(25,9,10,.68)_0%,transparent_62%)]" aria-hidden="true"></div>
        <div class="page-container">
            <p class="text-sm font-bold uppercase tracking-[.04em] text-white/75"><span class="text-signal-light">•</span> Komunitas</p>
            <h1 class="mt-6 max-w-3xl text-5xl text-white drop-shadow-sm md:text-7xl">Mezbah Keluarga</h1>
            <p class="mt-7 max-w-2xl text-xl leading-8 text-white/75">Tempat untuk bertumbuh, berbagi kehidupan, dan saling menguatkan dalam komunitas yang lebih dekat.</p>
        </div>
    </section>
    <section class="bg-lifted py-20">
        <div class="page-container">
            <form method="get" class="mb-12 grid gap-4 rounded-[40px] bg-white p-6 md:grid-cols-[1fr_1fr_auto]">
                <x-input name="search" label="Cari lokasi atau PIC" :value="request('search')"/>
                <x-select name="day" label="Hari"><option value="">Semua hari</option>@foreach($days as $value=>$label)<option value="{{ $value }}" @selected(request('day')===$value)>{{ $label }}</option>@endforeach</x-select>
                <div class="flex items-end"><x-button type="submit">Terapkan</x-button></div>
            </form>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">@forelse($items as $altar)<article class="rounded-[40px] border border-primary/20 bg-white p-7 transition duration-200 hover:-translate-y-0.5 hover:border-primary/45 hover:shadow-[0_20px_44px_rgba(133,18,38,.10)]"><x-badge>{{ $altar->day_of_week->label() }}</x-badge><h2 class="mt-5 text-2xl">{{ $altar->name }}</h2><p class="mt-2 text-slate">{{ $altar->start_time }}@if($altar->end_time)–{{ $altar->end_time }}@endif</p><div class="mt-5 space-y-2 text-sm"><p><strong>PIC:</strong> {{ $altar->pic_name?:'-' }}</p><p><strong>Lokasi:</strong> {{ $altar->location_name?:$altar->address }}</p>@if($altar->description)<p class="pt-2 text-slate">{{ $altar->description }}</p>@endif</div><div class="mt-7 flex flex-wrap gap-3">@if($altar->whatsapp_url)<a href="{{ $altar->whatsapp_url }}" target="_blank" rel="noopener" class="rounded-[20px] bg-primary px-5 py-2 text-canvas">WhatsApp</a>@endif @if($altar->map_url)<a href="{{ $altar->map_url }}" target="_blank" rel="noopener" class="rounded-[20px] border border-primary px-5 py-2 text-primary">Buka peta</a>@endif</div></article>@empty<x-empty-state class="md:col-span-2 lg:col-span-3" title="Lokasi tidak ditemukan" />@endforelse</div>
            <div class="mt-12">{{ $items->links() }}</div>
        </div>
    </section>
</x-layouts.public>
