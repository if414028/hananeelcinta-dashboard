<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>JKI Hananeel Cinta · Rumah untuk Bertumbuh dalam Kasih</title>
    <meta name="description" content="Website resmi JKI Hananeel Cinta. Bertumbuh bersama dalam iman, pengharapan, dan kasih.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{ menuOpen: false }">
<header class="page-container fixed inset-x-0 top-6 z-50">
    <nav class="floating-nav flex items-center justify-between" aria-label="Navigasi utama">
        <a href="{{ route('home') }}" class="flex items-center gap-3 font-bold"><img src="{{ Vite::asset('resources/project-assets/logo.webp') }}" alt="Logo JKI Hananeel Cinta" class="h-12 w-12 rounded-full object-cover"><span>Hananeel Cinta</span></a>
        <div class="hidden items-center gap-8 lg:flex"><a href="#tentang">Tentang</a><a href="#pelayanan">Pelayanan</a><a href="#kontak">Kontak</a><a href="{{ route('admin.login') }}">Admin</a></div>
        <button class="grid h-11 w-11 place-items-center rounded-full border border-primary text-primary lg:hidden" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen" aria-label="Buka menu">☰</button>
    </nav>
    <div x-show="menuOpen" x-cloak class="mt-3 rounded-[40px] bg-white p-6 shadow-[0_24px_48px_rgba(0,0,0,0.08)] lg:hidden"><a class="block py-2" href="#tentang">Tentang</a><a class="block py-2" href="#pelayanan">Pelayanan</a><a class="block py-2" href="#kontak">Kontak</a></div>
</header>
<main>
    <section class="page-container flex min-h-screen items-center pb-16 pt-36">
        <div class="grid w-full items-end gap-10 lg:grid-cols-[1.3fr_.7fr]">
            <div><p class="text-sm font-bold uppercase tracking-[0.04em] text-slate"><span class="text-signal-light">•</span> JKI Hananeel Cinta</p><h1 class="mt-6 max-w-4xl text-5xl leading-[1] md:text-6xl lg:text-[5rem]">Rumah untuk bertumbuh dalam iman, pengharapan, dan kasih.</h1></div>
            <div class="pb-2"><p class="text-lg leading-7 text-slate">Selamat datang. Kami percaya setiap pribadi berharga, dipanggil, dan memiliki tempat dalam keluarga Allah.</p><div class="mt-8 flex flex-wrap gap-3"><x-button>Kenal lebih dekat</x-button><x-button variant="secondary">Kirim permohonan doa</x-button></div></div>
            <div class="col-span-full min-h-72 rounded-[40px] bg-primary p-8 text-canvas shadow-[0_24px_48px_rgba(0,0,0,0.08)] md:min-h-96 md:p-12"><div class="flex h-full max-w-xl flex-col justify-end"><p class="text-sm font-bold uppercase tracking-[0.04em] text-canvas/60">Kasih yang mengubahkan</p><h2 class="mt-4 text-4xl md:text-5xl">Berakar dalam Firman. Bertumbuh dalam komunitas.</h2></div></div>
        </div>
    </section>
    <section id="tentang" class="bg-lifted py-20 lg:py-28"><div class="page-container grid gap-10 lg:grid-cols-2"><p class="text-sm font-bold uppercase tracking-[0.04em] text-slate"><span class="text-signal-light">•</span> Tentang kami</p><div><h2 class="text-4xl lg:text-5xl">Gereja yang hadir, melayani, dan berjalan bersama.</h2><p class="mt-6 max-w-2xl text-lg leading-7 text-slate">Konten lengkap profil, visi, misi, dan sejarah gereja akan dikelola langsung melalui CMS.</p></div></div></section>
</main>
<footer id="kontak" class="bg-primary py-20 text-white"><div class="page-container"><h2 class="max-w-2xl text-4xl">Kami selalu ada saat Anda membutuhkan keluarga untuk berjalan bersama.</h2><div class="mt-14 border-t border-white/20 pt-8 text-sm text-white/70">© {{ date('Y') }} JKI Hananeel Cinta</div></div></footer>
</body>
</html>
