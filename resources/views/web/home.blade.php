<x-layouts.public :description="$settings['seo_description']??null" :image="asset('images/about/menara-hananeel-hero.webp')">
    <section class="relative isolate flex min-h-screen items-end overflow-hidden bg-primary bg-cover bg-[68%_center] pt-40 text-white sm:bg-center" style="background-image: url('{{ asset('images/about/menara-hananeel-hero.webp') }}')">
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(30,12,15,.90)_0%,rgba(30,12,15,.70)_44%,rgba(30,12,15,.18)_78%,rgba(30,12,15,.08)_100%)]" aria-hidden="true"></div>
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(0deg,rgba(20,10,10,.72)_0%,transparent_64%)]" aria-hidden="true"></div>
        <div class="page-container grid w-full items-end gap-10 pb-16 lg:grid-cols-[1.3fr_.7fr] lg:pb-20">
            <div>
                <p class="text-sm font-bold uppercase tracking-[.04em] text-white/75"><span class="mr-2 text-signal-light">•</span>{{ $settings['church_name']??'JKI Hananeel Cinta' }}</p>
                <h1 class="mt-6 max-w-4xl text-5xl leading-[.98] text-white drop-shadow-sm md:text-6xl lg:text-[5rem]">Menara penjaga kota yang berdiri dalam doa, kasih, dan pengharapan.</h1>
            </div>
            <div class="pb-2">
                <p class="text-lg leading-7 text-white/80">JKI Hananeel Cinta dipanggil untuk berjaga, membangun, dan membawa kasih Tuhan bagi keluarga, kota, serta bangsa-bangsa.</p>
                <div class="mt-8 flex flex-wrap gap-3"><a href="{{ route('about') }}" class="button-primary">Tentang Kami <x-icon name="arrow-right"/></a><a href="{{ route('prayer-request.create') }}" class="button-secondary border-white bg-white text-primary hover:bg-canvas">Prayer Request</a></div>
            </div>
        </div>
    </section>
    <section class="bg-lifted py-20 lg:py-28"><div class="page-container"><div class="mb-12 flex items-end justify-between gap-6"><div><p class="eyebrow">Terbaru</p><h2 class="mt-3 text-4xl lg:text-5xl">Pengumuman gereja</h2></div><a href="{{ route('announcements.index') }}" class="text-link hidden sm:inline-flex">Lihat semua <x-icon name="arrow-right"/></a></div><div class="grid gap-12 md:grid-cols-3">@forelse($announcements as $announcement)@include('web.partials.announcement-card')@empty<x-empty-state class="md:col-span-3" title="Belum ada pengumuman" />@endforelse</div><a href="{{ route('announcements.index') }}" class="text-link mt-8 sm:hidden">Lihat semua <x-icon name="arrow-right"/></a></div></section>
    <section class="relative isolate overflow-hidden bg-primary py-20 text-white lg:py-28" x-data="parallaxBackground">
        <div class="absolute -inset-y-16 inset-x-0 -z-20 scale-110 bg-cover bg-[70%_center] will-change-transform sm:bg-center" style="background-image: url('{{ asset('images/home/pastor-message-scripture.webp') }}')" :style="`transform: translate3d(0, ${offset}px, 0); background-image: url('{{ asset('images/home/pastor-message-scripture.webp') }}')`" aria-hidden="true"></div>
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(55,8,18,.94)_0%,rgba(74,10,24,.82)_46%,rgba(55,8,18,.52)_100%)]" aria-hidden="true"></div>
        <p class="pointer-events-none absolute -right-10 top-6 text-[7rem] font-medium leading-none tracking-[-.04em] text-white/[.06] md:text-[11rem]">Firman</p>
        <div class="page-container relative">
            <div class="mb-12 max-w-2xl"><p class="text-xs font-bold uppercase tracking-[.08em] text-signal-light">Renungan</p><h2 class="mt-3 text-4xl text-white lg:text-5xl">Pastor Message terbaru</h2><p class="mt-5 text-lg leading-7 text-white/70">Bertumbuh dalam hikmat dan kebenaran melalui perenungan Firman Tuhan.</p></div>
            <div class="grid gap-6 md:grid-cols-3">@forelse($pastorMessages as $message)@include('web.partials.pastor-card')@empty<x-empty-state class="md:col-span-3" title="Belum ada Pastor Message" />@endforelse</div>
        </div>
    </section>
    <section class="relative isolate overflow-hidden bg-primary py-20 text-canvas lg:py-28" x-data="parallaxBackground">
        <div class="absolute -inset-y-16 inset-x-0 -z-20 scale-110 bg-cover bg-center will-change-transform" style="background-image: url('{{ asset('images/home/worship-gathering-background.webp') }}')" :style="`transform: translate3d(0, ${offset}px, 0); background-image: url('{{ asset('images/home/worship-gathering-background.webp') }}')`" aria-hidden="true"></div>
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(91,10,29,.94),rgba(110,13,35,.82)_52%,rgba(78,8,23,.74))]" aria-hidden="true"></div>
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(0deg,rgba(46,5,15,.64),transparent_68%)]" aria-hidden="true"></div>
        <div class="page-container relative"><div class="mb-12 max-w-3xl"><p class="text-xs font-bold uppercase tracking-[.08em] text-canvas/60">Beribadah bersama</p><h2 class="mt-3 text-4xl lg:text-5xl">Jadwal ibadah dan persekutuan.</h2></div><div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><article class="service-card"><p class="service-day">Minggu · 07:00 & 10:00 WIB</p><h3>Ibadah Raya</h3><p>Perayaan iman bersama seluruh jemaat.</p></article><article class="service-card"><p class="service-day">Minggu · 07:00 & 10:00 WIB</p><h3>Sekolah Minggu</h3><p>Ibadah dan pembinaan iman bagi anak-anak.</p></article><article class="service-card"><p class="service-day">Selasa · 19:00 WIB</p><h3>Holy Spirit Night</h3><p>Malam doa, penyembahan, dan perjumpaan dengan Tuhan.</p></article><article class="service-card"><p class="service-day">Sabtu · 17:00 WIB</p><h3>Rock Jakarta</h3><p>Youth Service bagi generasi muda.</p></article></div></div>
    </section>
    <section class="bg-lifted py-20 lg:py-28"><div class="page-container grid gap-12 lg:grid-cols-[.8fr_1.2fr]"><div class="lg:sticky lg:top-32 lg:self-start"><p class="eyebrow">Komunitas</p><h2 class="mt-3 text-4xl lg:text-5xl">Bertumbuh melalui Mezbah Keluarga.</h2><p class="mt-5 max-w-md text-lg leading-7 text-slate">Temukan keluarga rohani terdekat untuk saling menguatkan, belajar Firman, dan bertumbuh bersama.</p><a href="{{ route('family-altars.index') }}" class="button-primary mt-7">Temukan lokasi <x-icon name="arrow-right"/></a></div><div class="grid gap-4 sm:grid-cols-2">@forelse($familyAltars as $altar)<article class="group rounded-[40px] border border-ink/6 bg-white p-6 transition duration-200 hover:-translate-y-1 hover:shadow-[0_24px_48px_rgba(0,0,0,.06)]"><div class="flex items-start justify-between gap-3"><x-badge>{{ $altar->day_of_week->label() }}</x-badge><span class="grid h-10 w-10 place-items-center rounded-full bg-canvas text-slate transition group-hover:bg-primary group-hover:text-white"><x-icon name="map" :size="18"/></span></div><h3 class="mt-5 text-2xl">{{ $altar->name }}</h3><p class="mt-2 text-slate">{{ $altar->start_time }} · {{ $altar->pic_name }}</p></article>@empty<x-empty-state class="sm:col-span-2" title="Jadwal segera hadir" />@endforelse</div></div></section>
    @php
        $ministries = [
            ['name' => 'Praise and Worship', 'image' => 'images/ministries/praise-worship.webp', 'description' => 'Membawa jemaat masuk dalam penyembahan yang tulus melalui musik, pujian, dan kesatuan hati.'],
            ['name' => 'Hananeel Cinta Dancers', 'image' => 'images/ministries/dancers.webp', 'description' => 'Melayani Tuhan melalui tarian, gerak, dan ekspresi penyembahan yang memuliakan nama-Nya.'],
            ['name' => 'Dapur Cinta', 'image' => 'images/ministries/dapur-cinta.webp', 'description' => 'Menyiapkan makanan dengan kasih untuk jemaat dan berbagi kehangatan kepada masyarakat.'],
            ['name' => 'Food Bank', 'image' => 'images/ministries/food-bank.webp', 'description' => 'Menghubungkan kemurahan hati dengan kebutuhan nyata melalui dukungan pangan bagi keluarga.'],
            ['name' => 'Pelayanan ke bangsa-bangsa', 'image' => 'images/ministries/outreach-nations.webp', 'description' => 'Membangun relasi, melayani dengan rendah hati, dan membawa kabar pengharapan melintasi budaya.'],
            ['name' => 'Pelayanan ke provinsi', 'image' => 'images/ministries/outreach-provinces.webp', 'description' => 'Hadir bersama komunitas di berbagai daerah untuk menguatkan, melayani, dan bertumbuh bersama.'],
        ];
    @endphp
    <section class="bg-lifted py-20 lg:py-28">
        <div class="page-container grid gap-6 lg:grid-cols-2 lg:items-end"><div><p class="eyebrow">Melayani dengan kasih</p><h2 class="mt-3 max-w-xl text-4xl lg:text-5xl">Pelayanan di JKI Hananeel Cinta.</h2></div><p class="max-w-xl text-lg leading-8 text-slate">Setiap pelayanan menjadi bagian dari panggilan kami untuk membangun jemaat, memberkati kota, dan menjangkau bangsa-bangsa.</p></div>
    </section>
    @foreach($ministries as $ministry)
        <section class="relative isolate flex min-h-[70vh] items-end overflow-hidden bg-primary py-16 text-white lg:min-h-[82vh] lg:py-24" x-data="parallaxBackground" aria-labelledby="ministry-{{ $loop->iteration }}">
            <img src="{{ asset($ministry['image']) }}" alt="Suasana {{ $ministry['name'] }} di JKI Hananeel Cinta" class="absolute -inset-y-16 inset-x-0 -z-20 h-[calc(100%+8rem)] w-full scale-110 object-cover will-change-transform" :style="`transform: translate3d(0, ${offset}px, 0)`" loading="lazy" decoding="async">
            <div @class(['absolute inset-0 -z-10', 'bg-[linear-gradient(90deg,rgba(25,7,11,.90)_0%,rgba(35,8,15,.68)_48%,rgba(25,7,11,.18)_100%)]' => $loop->odd, 'bg-[linear-gradient(270deg,rgba(25,7,11,.90)_0%,rgba(35,8,15,.68)_48%,rgba(25,7,11,.18)_100%)]' => $loop->even]) aria-hidden="true"></div>
            <div class="absolute inset-0 -z-10 bg-[linear-gradient(0deg,rgba(20,5,9,.72)_0%,transparent_64%)]" aria-hidden="true"></div>
            <div @class(['page-container relative flex', 'justify-start' => $loop->odd, 'justify-end' => $loop->even])>
                <article class="max-w-2xl rounded-[32px] border border-white/15 bg-black/20 p-7 shadow-[0_24px_64px_rgba(0,0,0,.16)] backdrop-blur-sm sm:p-9 lg:p-10">
                    <p class="text-xs font-bold uppercase tracking-[.12em] text-signal-light">Pelayanan · {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                    <h3 id="ministry-{{ $loop->iteration }}" class="mt-4 text-4xl leading-tight text-white md:text-5xl lg:text-6xl">{{ $ministry['name'] }}</h3>
                    <p class="mt-6 max-w-xl text-lg leading-8 text-white/78">{{ $ministry['description'] }}</p>
                </article>
            </div>
        </section>
    @endforeach
</x-layouts.public>
