@props(['title' => null, 'description' => null, 'image' => null, 'canonical' => null, 'noindex' => false, 'type' => 'website'])
@php
    $churchName = $siteSettings['church_name'] ?? 'JKI Hananeel Cinta';
    $pageTitle = $title ? $title.' · '.$churchName : ($siteSettings['seo_title'] ?: $churchName);
    $pageDescription = $description ?: ($siteSettings['seo_description'] ?: ($siteSettings['church_tagline'] ?? 'Rumah untuk bertumbuh dalam iman, pengharapan, dan kasih.'));
    $canonicalUrl = $canonical ?: url()->current();
    $shareImage = $image ?: Vite::asset('resources/project-assets/logo.webp');
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title><meta name="description" content="{{ $pageDescription }}"><link rel="canonical" href="{{ $canonicalUrl }}">
    @if($noindex)<meta name="robots" content="noindex,nofollow">@else<meta name="robots" content="index,follow">@endif
    <meta property="og:type" content="{{ $type }}"><meta property="og:title" content="{{ $pageTitle }}"><meta property="og:description" content="{{ $pageDescription }}"><meta property="og:url" content="{{ $canonicalUrl }}"><meta property="og:image" content="{{ $shareImage }}">
    <meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="{{ $pageTitle }}"><meta name="twitter:description" content="{{ $pageDescription }}"><meta name="twitter:image" content="{{ $shareImage }}">
    <script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org','@type'=>'Church','name'=>$churchName,'url'=>route('home'),'email'=>$siteSettings['church_email']??null,'telephone'=>$siteSettings['church_phone']??null,'address'=>$siteSettings['church_address']??null], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{ menuOpen: false }">
<header class="page-container fixed inset-x-0 top-4 z-50 lg:top-6">
    <nav class="floating-nav flex items-center justify-between gap-5" aria-label="Navigasi utama">
        <a href="{{ route('home') }}" class="flex items-center gap-3 font-bold"><img src="{{ Vite::asset('resources/project-assets/logo.webp') }}" alt="Logo {{ $churchName }}" class="h-11 w-11 rounded-full object-cover"><span class="hidden sm:inline">{{ $churchName }}</span></a>
        <div class="hidden items-center gap-7 text-sm xl:flex"><a href="{{ route('about') }}">Tentang</a><a href="{{ route('announcements.index') }}">Pengumuman</a><a href="{{ route('pastor-messages.index') }}">Pastor Message</a><a href="{{ route('family-altars.index') }}">Mezbah Keluarga</a><a href="{{ route('contact') }}">Kontak</a></div>
        <div class="flex items-center gap-2"><a href="{{ route('prayer-request.create') }}" class="hidden min-h-11 items-center rounded-[20px] bg-ink px-5 text-canvas sm:inline-flex">Prayer Request</a><button class="grid h-11 w-11 place-items-center rounded-full border border-ink xl:hidden" @click="menuOpen=!menuOpen" :aria-expanded="menuOpen" aria-label="Buka menu">☰</button></div>
    </nav>
    <div x-cloak x-show="menuOpen" x-transition class="mt-3 rounded-[40px] bg-white p-7 shadow-[0_24px_48px_rgba(0,0,0,.08)] xl:hidden"><div class="grid gap-4"><a href="{{ route('about') }}">Tentang Kami</a><a href="{{ route('announcements.index') }}">Pengumuman</a><a href="{{ route('pastor-messages.index') }}">Pastor Message</a><a href="{{ route('family-altars.index') }}">Mezbah Keluarga</a><a href="{{ route('prayer-request.create') }}">Prayer Request</a><a href="{{ route('contact') }}">Kontak</a></div></div>
</header>
<main>{{ $slot }}</main>
<footer class="bg-ink py-20 text-white lg:py-28"><div class="page-container"><h2 class="max-w-3xl text-4xl lg:text-5xl">Kami selalu ada saat Anda membutuhkan keluarga untuk berjalan bersama.</h2><div class="mt-14 grid gap-10 border-t border-white/20 pt-10 sm:grid-cols-2 lg:grid-cols-4"><div><p class="text-xs font-bold uppercase tracking-[.04em] text-white/50">Gereja</p><p class="mt-4 text-white/75">{{ $siteSettings['footer_description'] ?: ($siteSettings['church_tagline'] ?? '') }}</p></div><div><p class="text-xs font-bold uppercase tracking-[.04em] text-white/50">Kunjungi</p><p class="mt-4 whitespace-pre-line text-white/75">{{ $siteSettings['church_address'] ?: 'Alamat gereja dapat dilihat pada halaman kontak.' }}</p></div><div><p class="text-xs font-bold uppercase tracking-[.04em] text-white/50">Hubungi</p><div class="mt-4 grid gap-2 text-white/75">@if($siteSettings['church_email']??null)<a href="mailto:{{ $siteSettings['church_email'] }}">{{ $siteSettings['church_email'] }}</a>@endif @if($siteSettings['church_whatsapp']??null)<a href="https://wa.me/{{ preg_replace('/\D+/','',$siteSettings['church_whatsapp']) }}">WhatsApp</a>@endif<a href="{{ route('contact') }}">Informasi kontak</a></div></div><div><p class="text-xs font-bold uppercase tracking-[.04em] text-white/50">Jelajahi</p><div class="mt-4 grid gap-2 text-white/75"><a href="{{ route('privacy') }}">Kebijakan Privasi</a><a href="{{ route('terms') }}">Ketentuan</a><a href="{{ route('admin.login') }}">Admin CMS</a></div></div></div><div class="mt-14 flex flex-wrap justify-between gap-4 border-t border-white/20 pt-8 text-sm text-white/60"><span>© {{ date('Y') }} {{ $churchName }}</span><span>Jakarta time · Asia/Jakarta</span></div></div></footer>
</body>
</html>
