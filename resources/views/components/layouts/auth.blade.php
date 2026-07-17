<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow"><title>{{ $title ?? 'Admin' }} · JKI Hananeel Cinta</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="page-container grid min-h-screen place-items-center py-12">
    <div class="w-full max-w-md">
        <a href="{{ route('home') }}" class="mb-8 flex items-center justify-center gap-3" aria-label="Kembali ke beranda">
            <img src="{{ Vite::asset('resources/project-assets/logo.webp') }}" alt="Logo JKI Hananeel Cinta" class="h-14 w-14 rounded-full object-cover">
            <span class="text-lg font-bold">JKI Hananeel Cinta</span>
        </a>
        @if (session('success'))<x-alert class="mb-5">{{ session('success') }}</x-alert>@endif
        {{ $slot }}
    </div>
</main>
</body>
</html>
