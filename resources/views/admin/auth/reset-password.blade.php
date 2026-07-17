<x-layouts.auth title="Password Baru">
    <x-card><h1 class="text-4xl">Buat password baru</h1>
        @if ($errors->any())<x-alert type="error" class="mt-5">{{ $errors->first() }}</x-alert>@endif
        <form action="{{ route('admin.password.update') }}" method="post" class="mt-7 space-y-5">@csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-input label="Email" name="email" type="email" :value="old('email', $email)" required />
            <x-input label="Password baru" name="password" type="password" required />
            <x-input label="Konfirmasi password" name="password_confirmation" type="password" required />
            <x-button type="submit" class="w-full">Simpan password</x-button>
        </form>
    </x-card>
</x-layouts.auth>
