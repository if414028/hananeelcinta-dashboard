<x-layouts.auth title="Lupa Password">
    <x-card><h1 class="text-4xl">Reset password</h1><p class="mt-3 text-slate">Kami akan mengirim tautan reset ke email admin yang terdaftar.</p>
        @if ($errors->any())<x-alert type="error" class="mt-5">{{ $errors->first() }}</x-alert>@endif
        <form action="{{ route('admin.password.email') }}" method="post" class="mt-7 space-y-5">@csrf
            <x-input label="Email" name="email" type="email" :value="old('email')" required autofocus />
            <x-button type="submit" class="w-full">Kirim tautan reset</x-button>
        </form><a href="{{ route('admin.login') }}" class="mt-5 block text-center text-sm underline">Kembali ke login</a>
    </x-card>
</x-layouts.auth>
