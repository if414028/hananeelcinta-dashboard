<x-layouts.public title="Kontak" description="Alamat, jadwal ibadah, dan kanal kontak JKI Hananeel Cinta." :image="asset('images/contact/open-door-hero.webp')">
    <section class="relative isolate flex min-h-[38rem] items-end overflow-hidden bg-primary pb-16 pt-40 text-white lg:min-h-[42rem] lg:pb-20 lg:pt-48" x-data="parallaxBackground">
        <div class="absolute -inset-y-16 inset-x-0 -z-20 scale-110 bg-cover bg-[70%_center] will-change-transform sm:bg-center" style="background-image: url('{{ asset('images/contact/open-door-hero.webp') }}')" :style="`transform: translate3d(0, ${offset}px, 0); background-image: url('{{ asset('images/contact/open-door-hero.webp') }}')`" aria-hidden="true"></div>
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(43,8,15,.92)_0%,rgba(57,10,20,.70)_48%,rgba(42,8,14,.22)_100%)]" aria-hidden="true"></div>
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(0deg,rgba(24,8,11,.64)_0%,transparent_62%)]" aria-hidden="true"></div>
        <div class="page-container">
            <p class="text-sm font-bold uppercase tracking-[.04em] text-white/75"><span class="text-signal-light">•</span> Hubungi Kami</p>
            <h1 class="mt-6 max-w-4xl text-5xl leading-none text-white drop-shadow-sm md:text-7xl">Mari terhubung dan berjalan bersama.</h1>
            <p class="mt-7 max-w-2xl text-xl leading-8 text-white/75">Pintu kami terbuka. Temukan alamat, kanal komunikasi, dan waktu yang tepat untuk beribadah bersama.</p>
        </div>
    </section>
    <section class="bg-lifted py-20">
        <div class="page-container grid gap-6 md:grid-cols-2">
            <x-card title="Alamat">
                <p class="whitespace-pre-line text-slate">{{ $settings['church_address']?:"Gereja JKI Hananeel Cinta\nBlok Jl. Pangeran Tubagus Angke No.2 13, RT.13/RW.7, Jelambar Baru, Kec. Grogol petamburan, Kota Jakarta Barat, Daerah Khusus Ibukota Jakarta 11460" }}</p>
                @if($settings['church_maps_url']??null)<a href="{{ $settings['church_maps_url'] }}" target="_blank" rel="noopener" class="mt-6 inline-block underline">Buka Google Maps ↗</a>@endif
            </x-card>
            <x-card title="Media Sosial">
                <div class="grid gap-4 text-slate sm:grid-cols-[11rem_1fr] sm:items-center">
                    <img src="{{ asset('images/jkihananeelcinta-qr.png') }}" alt="QR Instagram JKI Hananeel Cinta" class="aspect-square w-full max-w-44 rounded-[24px] border border-ink/8 bg-white object-contain p-2">
                    <div class="grid gap-4"><a href="{{ $settings['church_instagram']?:'https://www.instagram.com/jkihananeelcinta?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==' }}" target="_blank" rel="noopener" class="font-medium text-ink underline">Instagram ↗</a><a href="{{ $settings['church_youtube']?:'https://youtube.com/@jkihananeelcinta?si=3LvFKnaoGAndKpFK' }}" target="_blank" rel="noopener" class="font-medium text-ink underline">Live Streaming YouTube ↗</a></div>
                </div>
            </x-card>
        </div>
    </section>
    <section class="relative isolate overflow-hidden bg-primary py-20 text-white lg:py-28" x-data="parallaxBackground">
        <div class="absolute -inset-y-16 inset-x-0 -z-20 scale-110 bg-cover bg-center will-change-transform" style="background-image: url('{{ asset('images/home/worship-gathering-background.webp') }}')" :style="`transform: translate3d(0, ${offset}px, 0); background-image: url('{{ asset('images/home/worship-gathering-background.webp') }}')`" aria-hidden="true"></div>
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(91,10,29,.94),rgba(110,13,35,.82)_52%,rgba(78,8,23,.74))]" aria-hidden="true"></div>
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(0deg,rgba(46,5,15,.64),transparent_68%)]" aria-hidden="true"></div>
        <div class="page-container relative"><div class="mb-10 flex flex-wrap items-end justify-between gap-6"><h2 class="text-4xl text-white">Jadwal ibadah</h2><a href="{{ route('family-altars.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-[20px] px-1 font-medium text-white underline decoration-white/30 underline-offset-4 hover:decoration-white">Jadwal Mezbah Keluarga <x-icon name="arrow-right"/></a></div><div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><article class="service-card"><p class="service-day">Minggu · 07:00 & 10:00 WIB</p><h3>Ibadah Raya</h3></article><article class="service-card"><p class="service-day">Minggu · 07:00 & 10:00 WIB</p><h3>Sekolah Minggu</h3></article><article class="service-card"><p class="service-day">Selasa · 19:00 WIB</p><h3>Holy Spirit Night</h3></article><article class="service-card"><p class="service-day">Sabtu · 17:00 WIB</p><h3>Rock Jakarta</h3><p>Youth Service</p></article></div></div>
    </section>
</x-layouts.public>
