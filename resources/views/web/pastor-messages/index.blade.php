<x-layouts.public title="Pastor Message" description="Renungan dan pesan penggembalaan JKI Hananeel Cinta." :image="asset('images/home/pastor-message-scripture.webp')">
    <section class="relative isolate flex min-h-[38rem] items-end overflow-hidden bg-primary pb-16 pt-40 text-white lg:min-h-[42rem] lg:pb-20 lg:pt-48" x-data="parallaxBackground">
        <div class="absolute -inset-y-16 inset-x-0 -z-20 scale-110 bg-cover bg-[70%_center] will-change-transform sm:bg-center" style="background-image: url('{{ asset('images/home/pastor-message-scripture.webp') }}')" :style="`transform: translate3d(0, ${offset}px, 0); background-image: url('{{ asset('images/home/pastor-message-scripture.webp') }}')`" aria-hidden="true"></div>
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(44,8,16,.94)_0%,rgba(61,10,22,.78)_48%,rgba(44,8,16,.38)_100%)]" aria-hidden="true"></div>
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(0deg,rgba(24,8,12,.64)_0%,transparent_62%)]" aria-hidden="true"></div>
        <div class="page-container">
            <p class="text-sm font-bold uppercase tracking-[.04em] text-white/75"><span class="text-signal-light">•</span> Renungan</p>
            <div class="mt-5 flex flex-wrap items-end justify-between gap-8">
                <div><h1 class="text-5xl text-white drop-shadow-sm md:text-7xl">Pastor Message</h1><p class="mt-5 max-w-xl text-lg leading-7 text-white/70">Renungan dan pesan penggembalaan untuk bertumbuh dalam hikmat serta kebenaran Firman Tuhan.</p></div>
                <form method="get" class="flex w-full max-w-md gap-2 rounded-[28px] border border-white/20 bg-black/15 p-2 backdrop-blur-md sm:w-auto" role="search">
                    <input name="search" value="{{ request('search') }}" class="min-h-12 min-w-0 flex-1 rounded-full border border-white/25 bg-white px-5 text-ink placeholder:text-slate" placeholder="Cari pesan atau penulis" aria-label="Cari Pastor Message">
                    <x-button type="submit">Cari</x-button>
                </form>
            </div>
        </div>
    </section>
    <section class="bg-lifted py-20">
        <div class="page-container">
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">@forelse($items as $message)@include('web.partials.pastor-card')@empty<x-empty-state class="md:col-span-2 lg:col-span-3" title="Pastor Message tidak ditemukan" />@endforelse</div>
            <div class="mt-14">{{ $items->links() }}</div>
        </div>
    </section>
</x-layouts.public>
