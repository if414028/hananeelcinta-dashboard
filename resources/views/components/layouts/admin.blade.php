<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow"><title>{{ $title ?? 'CMS' }} · JKI Hananeel Cinta</title><link rel="icon" type="image/webp" href="{{ route('brand.logo') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{ navOpen: false }" @keydown.escape.window="navOpen = false" :class="navOpen && 'overflow-hidden lg:overflow-auto'">
<a href="#admin-content" class="fixed left-5 top-4 z-[60] -translate-y-24 rounded-full bg-white px-5 py-3 text-ink focus:translate-y-0">Lewati ke konten</a>
<div class="admin-shell">
    <div x-cloak x-show="navOpen" x-transition.opacity class="fixed inset-0 z-30 bg-primary/45 backdrop-blur-sm lg:hidden" @click="navOpen = false" aria-hidden="true"></div>
    <aside class="admin-sidebar" :class="navOpen && '!translate-x-0'" aria-label="Navigasi CMS">
        <div class="flex items-center justify-between gap-3 px-2 pb-6">
            <a href="{{ route('admin.dashboard') }}" class="flex min-h-12 items-center gap-3 rounded-full" aria-label="Hananeel CMS — Dashboard"><img src="{{ route('brand.logo') }}" alt="" class="h-12 w-12 rounded-full object-cover"><span><strong class="block text-sm text-white">Hananeel CMS</strong><span class="text-xs text-white/50">Content workspace</span></span></a>
            <button type="button" class="grid h-11 w-11 place-items-center rounded-full border border-white/25 lg:hidden" @click="navOpen = false"><span class="sr-only">Tutup navigasi</span><x-icon name="close"/></button>
        </div>
        <nav class="space-y-1">
            <a href="{{ route('admin.dashboard') }}" @class(['admin-nav-link','admin-nav-link-active'=>request()->routeIs('admin.dashboard')]) @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif><x-icon name="home" class="admin-nav-icon"/>Dashboard</a>
            <p class="px-4 pb-2 pt-6 text-[.68rem] font-bold uppercase tracking-[.12em] text-white/35">Pelayanan</p>
            @can('congregations.view')<a href="{{ route('admin.congregations.index') }}" @class(['admin-nav-link','admin-nav-link-active'=>request()->routeIs('admin.congregations.*')])><x-icon name="users" class="admin-nav-icon"/>Jemaat</a>@endcan
            @can('prayer_requests.view')<a href="{{ route('admin.prayer-requests.index') }}" @class(['admin-nav-link','admin-nav-link-active'=>request()->routeIs('admin.prayer-requests.*')])><x-icon name="heart" class="admin-nav-icon"/>Prayer Request</a>@endcan
            @can('family_altars.view')<a href="{{ route('admin.family-altars.index') }}" @class(['admin-nav-link','admin-nav-link-active'=>request()->routeIs('admin.family-altars.*')])><x-icon name="map" class="admin-nav-icon"/>Mezbah Keluarga</a>@endcan
            <p class="px-4 pb-2 pt-6 text-[.68rem] font-bold uppercase tracking-[.12em] text-white/35">Konten</p>
            @can('announcements.view')<a href="{{ route('admin.announcements.index') }}" @class(['admin-nav-link','admin-nav-link-active'=>request()->routeIs('admin.announcements.*')])><x-icon name="bell" class="admin-nav-icon"/>Pengumuman</a>@endcan
            @can('pastor_messages.view')<a href="{{ route('admin.pastor-messages.index') }}" @class(['admin-nav-link','admin-nav-link-active'=>request()->routeIs('admin.pastor-messages.*')])><x-icon name="book" class="admin-nav-icon"/>Pastor Message</a>@endcan
            <p class="px-4 pb-2 pt-6 text-[.68rem] font-bold uppercase tracking-[.12em] text-white/35">Sistem</p>
            @can('admins.view')<a href="{{ route('admin.admin-users.index') }}" @class(['admin-nav-link','admin-nav-link-active'=>request()->routeIs('admin.admin-users.*')])><x-icon name="users" class="admin-nav-icon"/>Admin</a>@endcan
            @role('Super Admin')<a href="{{ route('admin.roles.index') }}" @class(['admin-nav-link','admin-nav-link-active'=>request()->routeIs('admin.roles.*')])><x-icon name="shield" class="admin-nav-icon"/>Role & Permission</a>@endrole
            @can('settings.view')<a href="{{ route('admin.settings.index') }}" @class(['admin-nav-link','admin-nav-link-active'=>request()->routeIs('admin.settings.*')])><x-icon name="settings" class="admin-nav-icon"/>Website Settings</a>@endcan
            @can('audit_logs.view')<a href="{{ route('admin.audit-logs.index') }}" @class(['admin-nav-link','admin-nav-link-active'=>request()->routeIs('admin.audit-logs.*')])><x-icon name="activity" class="admin-nav-icon"/>Audit Logs</a>@endcan
        </nav>
        <div class="mt-8 border-t border-white/12 px-2 pt-5">
            <p class="truncate text-sm font-bold text-white">{{ auth()->user()->name }}</p><p class="truncate text-xs text-white/45">{{ auth()->user()->email }}</p>
            <form action="{{ route('admin.logout') }}" method="post" class="mt-4">@csrf<button type="submit" class="admin-nav-link w-full"><x-icon name="logout" class="admin-nav-icon"/>Keluar</button></form>
        </div>
    </aside>
    <div class="min-w-0">
        <header class="sticky top-0 z-20 flex min-h-20 items-center justify-between border-b border-ink/8 bg-canvas/90 px-5 backdrop-blur-xl sm:px-8 lg:px-10 xl:px-14">
            <button type="button" class="grid h-11 w-11 place-items-center rounded-full border border-primary/35 bg-white text-primary lg:hidden" @click="navOpen = true" :aria-expanded="navOpen"><span class="sr-only">Buka navigasi</span><x-icon name="menu"/></button>
            <div class="ml-auto flex items-center gap-3"><a href="{{ route('home') }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center gap-2 rounded-full px-4 text-sm font-medium text-slate hover:bg-white hover:text-ink">Lihat website <x-icon name="arrow-right" :size="17"/></a><div class="grid h-10 w-10 place-items-center rounded-full bg-primary text-sm font-bold text-white" aria-hidden="true">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</div></div>
        </header>
        <main id="admin-content" class="admin-content">
            @if (session('success'))<x-alert class="mb-6">{{ session('success') }}</x-alert>@endif
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
