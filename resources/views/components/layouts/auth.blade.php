<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>{{ $title ?? 'Admin' }} · JKI Hananeel Cinta</title><link rel="icon" type="image/webp" href="{{ route('brand.logo') }}">@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body>
<main class="auth-shell">
    <section class="auth-visual orbital-stage items-end p-12 xl:p-16" aria-label="JKI Hananeel Cinta"><p class="pointer-events-none absolute -bottom-5 -left-5 text-[8rem] font-medium leading-none tracking-[-.05em] text-white/[.04] xl:text-[11rem]">Hananeel</p><div class="relative z-10 max-w-xl"><p class="text-xs font-bold uppercase tracking-[.08em] text-white/50">Content management system</p><h1 class="mt-5 text-5xl leading-[1.02] xl:text-6xl">Melayani lebih rapi,<br>berkomunikasi lebih hangat.</h1><p class="mt-6 max-w-md text-lg leading-7 text-white/60">Kelola informasi jemaat dan konten gereja dalam satu ruang kerja yang aman.</p></div></section>
    <section class="grid place-items-center px-5 py-12 sm:px-8"><div class="w-full max-w-md"><a href="{{ route('home') }}" class="mb-8 inline-flex min-h-12 items-center gap-3 rounded-full pr-4" aria-label="Kembali ke beranda"><img src="{{ route('brand.logo') }}" alt="" class="h-14 w-14 rounded-full object-cover"><span><strong class="block text-base">JKI Hananeel Cinta</strong><span class="text-sm text-slate">Admin workspace</span></span></a>@if (session('success'))<x-alert class="mb-5">{{ session('success') }}</x-alert>@endif{{ $slot }}<p class="mt-8 text-center text-xs text-slate">Area terbatas untuk admin yang berwenang.</p></div></section>
</main>
</body>
</html>
