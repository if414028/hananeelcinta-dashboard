@props(['title' => null, 'description' => null, 'image' => null, 'canonical' => null, 'noindex' => false, 'type' => 'website'])
@php
    $churchName = $siteSettings['church_name'] ?? 'JKI Hananeel Cinta';
    $pageTitle = $title ? $title.' · '.$churchName : ($siteSettings['seo_title'] ?: $churchName);
    $pageDescription = $description ?: ($siteSettings['seo_description'] ?: ($siteSettings['church_tagline'] ?? 'Rumah untuk bertumbuh dalam iman, pengharapan, dan kasih.'));
    $canonicalUrl = $canonical ?: url()->current();
    $shareImage = $image ?: route('brand.logo');
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
<body x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false" :class="menuOpen && 'overflow-hidden xl:overflow-auto'">
<a href="#main-content" class="fixed left-5 top-4 z-[60] -translate-y-24 rounded-full bg-primary px-5 py-3 text-white focus:translate-y-0">Lewati ke konten utama</a>
<header class="page-container fixed inset-x-0 top-4 z-50 lg:top-6">
    <nav class="floating-nav flex items-center justify-between gap-5" aria-label="Navigasi utama">
        <a href="{{ route('home') }}" class="flex min-h-11 items-center gap-3 rounded-full pr-3 font-bold" aria-label="{{ $churchName }} — Beranda"><img src="{{ route('brand.logo') }}" alt="" class="h-11 w-11 rounded-full object-cover"><span class="hidden max-w-40 truncate sm:inline">{{ $churchName }}</span></a>
        <div class="hidden items-center gap-1 text-sm xl:flex">
            <a href="{{ route('about') }}" @class(['nav-link','nav-link-active'=>request()->routeIs('about')]) @if(request()->routeIs('about')) aria-current="page" @endif>Tentang</a>
            <a href="{{ route('announcements.index') }}" @class(['nav-link','nav-link-active'=>request()->routeIs('announcements.*')]) @if(request()->routeIs('announcements.*')) aria-current="page" @endif>Pengumuman</a>
            <a href="{{ route('pastor-messages.index') }}" @class(['nav-link','nav-link-active'=>request()->routeIs('pastor-messages.*')]) @if(request()->routeIs('pastor-messages.*')) aria-current="page" @endif>Pastor Message</a>
            <a href="{{ route('family-altars.index') }}" @class(['nav-link','nav-link-active'=>request()->routeIs('family-altars.*')]) @if(request()->routeIs('family-altars.*')) aria-current="page" @endif>Mezbah Keluarga</a>
            <a href="{{ route('contact') }}" @class(['nav-link','nav-link-active'=>request()->routeIs('contact')]) @if(request()->routeIs('contact')) aria-current="page" @endif>Kontak</a>
        </div>
        <div class="flex items-center gap-2"><a href="{{ route('prayer-request.create') }}" class="button-primary hidden !px-5 sm:inline-flex">Prayer Request</a><button type="button" class="grid h-11 w-11 place-items-center rounded-full border border-primary text-primary hover:bg-canvas xl:hidden" @click="menuOpen=!menuOpen" :aria-expanded="menuOpen" aria-controls="mobile-menu"><span class="sr-only" x-text="menuOpen ? 'Tutup menu' : 'Buka menu'"></span><x-icon name="menu" x-show="!menuOpen"/><x-icon name="close" x-cloak x-show="menuOpen"/></button></div>
    </nav>
    <div id="mobile-menu" x-cloak x-show="menuOpen" x-transition.origin.top @click.outside="menuOpen = false" class="mt-3 rounded-[40px] border border-white bg-white p-5 shadow-[0_24px_48px_rgba(0,0,0,.10)] xl:hidden"><div class="grid gap-1"><a class="nav-link" href="{{ route('about') }}">Tentang Kami</a><a class="nav-link" href="{{ route('announcements.index') }}">Pengumuman</a><a class="nav-link" href="{{ route('pastor-messages.index') }}">Pastor Message</a><a class="nav-link" href="{{ route('family-altars.index') }}">Mezbah Keluarga</a><a class="nav-link" href="{{ route('contact') }}">Kontak</a><a href="{{ route('prayer-request.create') }}" class="button-primary mt-3 sm:hidden">Prayer Request <x-icon name="arrow-right"/></a></div></div>
</header>
<main id="main-content">{{ $slot }}</main>
<footer class="bg-primary py-20 text-white lg:py-28"><div class="page-container"><div class="flex flex-col items-start justify-between gap-8 lg:flex-row lg:items-end"><h2 class="max-w-3xl text-4xl lg:text-5xl">Kami selalu ada saat Anda membutuhkan keluarga untuk berjalan bersama.</h2><a href="{{ route('prayer-request.create') }}" class="inline-flex min-h-12 items-center gap-3 rounded-full bg-white px-6 text-ink hover:-translate-y-0.5">Kirim prayer request <x-icon name="arrow-right"/></a></div><div class="mt-14 grid gap-10 border-t border-white/20 pt-10 sm:grid-cols-2 lg:grid-cols-4"><div><p class="text-xs font-bold uppercase tracking-[.04em] text-white/50">Gereja</p><p class="mt-4 text-white/75">{{ $siteSettings['footer_description'] ?: ($siteSettings['church_tagline'] ?? '') }}</p></div><div><p class="text-xs font-bold uppercase tracking-[.04em] text-white/50">Kunjungi</p><p class="mt-4 whitespace-pre-line text-white/75">{{ $siteSettings['church_address'] ?: 'Alamat gereja dapat dilihat pada halaman kontak.' }}</p></div><div><p class="text-xs font-bold uppercase tracking-[.04em] text-white/50">Hubungi</p><div class="mt-4 grid gap-2 text-white/75">@if($siteSettings['church_email']??null)<a class="hover:text-white hover:underline" href="mailto:{{ $siteSettings['church_email'] }}">{{ $siteSettings['church_email'] }}</a>@endif @if($siteSettings['church_whatsapp']??null)<a class="hover:text-white hover:underline" href="https://wa.me/{{ preg_replace('/\D+/','',$siteSettings['church_whatsapp']) }}">WhatsApp</a>@endif<a class="hover:text-white hover:underline" href="{{ route('contact') }}">Informasi kontak</a></div></div><div><p class="text-xs font-bold uppercase tracking-[.04em] text-white/50">Jelajahi</p><div class="mt-4 grid gap-2 text-white/75"><a class="hover:text-white hover:underline" href="{{ route('privacy') }}">Kebijakan Privasi</a><a class="hover:text-white hover:underline" href="{{ route('terms') }}">Ketentuan</a><a class="hover:text-white hover:underline" href="{{ route('admin.login') }}">Admin CMS</a></div></div></div><div class="mt-14 flex flex-wrap justify-between gap-4 border-t border-white/20 pt-8 text-sm text-white/60"><span>© {{ date('Y') }} {{ $churchName }}</span><span>Waktu Jakarta · Asia/Jakarta</span></div></div></footer>
</body>
</html>
