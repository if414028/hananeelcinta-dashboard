<x-layouts.public :title="$pastorMessage->title" :description="$pastorMessage->excerpt" :image="$pastorMessage->featured_image?\Illuminate\Support\Facades\Storage::disk('public')->url($pastorMessage->featured_image):asset('images/home/pastor-message-scripture.webp')" type="article">
    <article class="relative isolate overflow-hidden pb-24 pt-40 lg:pt-48">
        <div class="absolute inset-0 -z-20 bg-[#ead2a6] bg-[length:100%_auto] bg-repeat-y" style="background-image: url('{{ asset('images/pastor-messages/parchment-paper.webp') }}')" aria-hidden="true"></div>
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(255,249,232,.22),rgba(255,252,240,.56)_50%,rgba(255,249,232,.22))]" aria-hidden="true"></div>
        <div class="page-container">
            <a href="{{ route('pastor-messages.index') }}" class="inline-flex min-h-11 items-center rounded-full border border-ink/15 bg-white/35 px-5 text-sm underline decoration-ink/25 underline-offset-4 backdrop-blur-sm hover:bg-white/55">← Semua Pastor Message</a>
            <header class="mx-auto mt-10 max-w-4xl text-center">
                <p class="text-sm font-bold uppercase tracking-[.04em] text-ink/60"><span class="text-signal">•</span> {{ $pastorMessage->writer }} · {{ $pastorMessage->published_at->format('d M Y') }}</p>
                <h1 class="mt-6 text-5xl leading-none text-ink md:text-7xl">{{ $pastorMessage->title }}</h1>
                <div class="mx-auto mt-10 flex max-w-sm items-center gap-4 text-primary/45" aria-hidden="true"><span class="h-px flex-1 bg-current"></span><span class="text-xl">✦</span><span class="h-px flex-1 bg-current"></span></div>
            </header>
            @if($pastorMessage->featured_image)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($pastorMessage->featured_image) }}" alt="{{ $pastorMessage->title }}" class="mt-12 aspect-[16/8] w-full rounded-[40px] border border-ink/10 object-cover shadow-[0_24px_64px_rgba(80,45,15,.14)]">
            @endif
            <div class="prose-public mx-auto mt-12 max-w-3xl text-ink/80">{!! $pastorMessage->content !!}</div>
            <div class="mx-auto mt-12 flex max-w-3xl flex-wrap items-center justify-between gap-4 border-t border-ink/20 pt-7">
                <span class="text-sm text-ink/55">Bagikan pesan ini</span>
                <div class="flex gap-4"><a href="https://wa.me/?text={{ urlencode($pastorMessage->title.' '.url()->current()) }}" target="_blank" rel="noopener" class="underline decoration-ink/25 underline-offset-4">WhatsApp</a><button type="button" class="underline decoration-ink/25 underline-offset-4" x-data @click="navigator.clipboard.writeText(window.location.href); $el.textContent='Tersalin'">Salin link</button></div>
            </div>
        </div>
    </article>
    @if($related->isNotEmpty())
        <section class="bg-lifted py-20"><div class="page-container"><h2 class="mb-10 text-4xl">Pesan lainnya</h2><div class="grid gap-6 md:grid-cols-3">@foreach($related as $message)@include('web.partials.pastor-card')@endforeach</div></div></section>
    @endif
</x-layouts.public>
