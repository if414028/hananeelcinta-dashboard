<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow"><title>{{ $title ?? 'CMS' }} · JKI Hananeel Cinta</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{ navOpen: false }">
<header class="sticky top-0 z-40 border-b border-ink/10 bg-canvas/95 backdrop-blur">
    <div class="page-container flex min-h-20 items-center justify-between gap-4">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 font-bold"><img src="{{ Vite::asset('resources/project-assets/logo.webp') }}" alt="" class="h-11 w-11 rounded-full object-cover">Hananeel CMS</a>
        <button class="min-h-11 rounded-full border border-ink px-4 lg:hidden" @click="navOpen = !navOpen" :aria-expanded="navOpen">Menu</button>
        <nav class="hidden items-center gap-5 text-sm lg:flex" aria-label="Navigasi admin">
            <a href="{{ route('admin.dashboard') }}" class="font-medium">Dashboard</a>
            @can('congregations.view')<a href="{{ route('admin.congregations.index') }}">Jemaat</a>@endcan
            @can('announcements.view')<a href="{{ route('admin.announcements.index') }}">Pengumuman</a>@endcan
            @can('prayer_requests.view')<a href="{{ route('admin.prayer-requests.index') }}">Prayer</a>@endcan
            @can('family_altars.view')<a href="{{ route('admin.family-altars.index') }}">Mezbah</a>@endcan
            @can('pastor_messages.view')<a href="{{ route('admin.pastor-messages.index') }}">Pastor</a>@endcan
            @can('admins.view')<a href="{{ route('admin.admin-users.index') }}">Admin</a>@endcan
            @role('Super Admin')<a href="{{ route('admin.roles.index') }}">Role</a>@endrole
            @can('settings.view')<a href="{{ route('admin.settings.index') }}">Settings</a>@endcan
            <span class="text-sm text-slate">{{ auth()->user()->name }}</span>
            <form action="{{ route('admin.logout') }}" method="post">@csrf<x-button variant="secondary" type="submit">Keluar</x-button></form>
        </nav>
    </div>
    <nav x-show="navOpen" x-cloak class="page-container space-y-4 border-t border-ink/10 py-5 lg:hidden">
        <a href="{{ route('admin.dashboard') }}" class="block">Dashboard</a>
        @can('congregations.view')<a href="{{ route('admin.congregations.index') }}" class="block">Jemaat</a>@endcan
        @can('announcements.view')<a href="{{ route('admin.announcements.index') }}" class="block">Pengumuman</a>@endcan
        @can('prayer_requests.view')<a href="{{ route('admin.prayer-requests.index') }}" class="block">Prayer Request</a>@endcan
        @can('family_altars.view')<a href="{{ route('admin.family-altars.index') }}" class="block">Mezbah Keluarga</a>@endcan
        @can('pastor_messages.view')<a href="{{ route('admin.pastor-messages.index') }}" class="block">Pastor Message</a>@endcan
        @role('Super Admin')<a href="{{ route('admin.roles.index') }}" class="block">Role & Permission</a>@endrole
        <form action="{{ route('admin.logout') }}" method="post">@csrf<x-button variant="secondary" type="submit">Keluar</x-button></form>
    </nav>
</header>
<main class="page-container py-10 lg:py-14">
    @if (session('success'))<x-alert class="mb-6">{{ session('success') }}</x-alert>@endif
    {{ $slot }}
</main>
</body>
</html>
