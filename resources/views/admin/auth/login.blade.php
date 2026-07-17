<x-layouts.auth title="Login Admin">
    <x-card>
        <p class="mb-2 text-sm font-bold uppercase tracking-[0.04em] text-slate"><span class="text-signal-light">•</span> CMS Admin</p>
        <h1 class="text-4xl">Selamat datang kembali</h1>
        <p class="mt-3 text-slate">Masuk untuk mengelola konten JKI Hananeel Cinta.</p>
        @if ($errors->any())<x-alert type="error" class="mt-5">{{ $errors->first() }}</x-alert>@endif
        <form action="{{ route('admin.login.store') }}" method="post" class="mt-7 space-y-5">@csrf
            <x-input label="Email" name="email" type="email" :value="old('email')" autocomplete="email" required autofocus />
            <x-input label="Password" name="password" type="password" autocomplete="current-password" required />
            <div class="flex items-center justify-between gap-4 text-sm">
                <label class="flex items-center gap-2"><input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded"> Ingat saya</label>
                <a href="{{ route('admin.password.request') }}" class="underline underline-offset-4">Lupa password?</a>
            </div>
            <x-button type="submit" class="w-full">Masuk</x-button>
        </form>
    </x-card>
</x-layouts.auth>
