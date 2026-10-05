<x-layouts.admin :title="$title">
    <header class="admin-page-header">
        <div><a href="{{ route('admin.admin-users.index') }}" class="text-link !min-h-8 !p-0"><x-icon name="arrow-left" :size="17"/>Kembali</a><h1 class="admin-page-title mt-4">{{ $title }}</h1></div>
    </header>
    @if($errors->any())
        <x-alert type="error" class="mb-6"><strong>Data belum dapat disimpan.</strong><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-alert>
    @endif
    <x-card>
        <form action="{{ $item->exists ? route('admin.admin-users.update', $item) : route('admin.admin-users.store') }}" method="post" class="grid gap-6 md:grid-cols-2" x-data="{}">
            @csrf
            @if($item->exists) @method('PUT') @endif
            <div class="md:col-span-2">
                <x-select name="congregation_id" label="Hubungkan ke jemaat" aria-describedby="congregation-help"
                    x-on:change="if ($el.value) { $refs.adminName.value = $el.selectedOptions[0].dataset.name; $refs.adminEmail.value = $el.selectedOptions[0].dataset.email; }">
                    <option value="">Tanpa hubungan jemaat</option>
                    @foreach($congregations as $congregation)
                        <option value="{{ $congregation->id }}" data-name="{{ $congregation->full_name }}" data-email="{{ $congregation->email }}" @selected((string) old('congregation_id', $item->congregation_id) === (string) $congregation->id)>{{ $congregation->full_name }} — {{ $congregation->member_number }}{{ $congregation->is_active ? '' : ' (Nonaktif)' }}</option>
                    @endforeach
                </x-select>
                <p id="congregation-help" class="mt-2 text-sm text-slate">Pilih jemaat yang sudah ada untuk mengisi nama dan email. Jemaat yang terhubung ke admin aktif akan mendapat role SuperUser di aplikasi mobile.</p>
            </div>
            <x-input name="name" label="Nama" :value="old('name', $item->name)" required x-ref="adminName" />
            <x-input name="email" label="Email login CMS" type="email" :value="old('email', $item->email)" required x-ref="adminEmail" />
            <x-select name="role" label="Role CMS" required>
                <option value="">Pilih…</option>
                @foreach($roles as $value => $label)<option value="{{ $value }}" @selected(old('role', $item->getRoleNames()->first()) === $value)>{{ $label }}</option>@endforeach
            </x-select>
            <label class="flex min-h-12 items-center gap-3 self-end"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="h-5 w-5" @checked((bool) old('is_active', $item->exists ? $item->is_active : true))> Admin aktif</label>
            @if($canChangePassword)
                <x-input name="password" label="Password CMS" type="password" :required="!$item->exists" autocomplete="new-password" />
                <x-input name="password_confirmation" label="Konfirmasi password CMS" type="password" :required="!$item->exists" autocomplete="new-password" />
                <p class="text-sm text-slate md:col-span-2">Password ini untuk login CMS. Login mobile tetap menggunakan akun Firebase.{{ $item->exists ? ' Kosongkan password jika tidak ingin mengubahnya.' : '' }}</p>
            @else
                <p class="text-sm text-slate md:col-span-2">Password Super Admin hanya dapat diubah oleh pemilik akun.</p>
            @endif
            <div class="admin-action-group border-t border-ink/10 pt-6 md:col-span-2"><x-button type="submit">Simpan data</x-button><a href="{{ route('admin.admin-users.index') }}" class="button-secondary">Batal</a></div>
        </form>
    </x-card>
</x-layouts.admin>
