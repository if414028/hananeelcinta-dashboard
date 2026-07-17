@props(['title' => 'Belum ada data', 'description' => 'Data akan tampil di sini setelah ditambahkan.'])
<div {{ $attributes->class('rounded-[40px] border border-dashed border-ink/25 px-6 py-12 text-center') }}>
    <h3 class="text-xl">{{ $title }}</h3><p class="mt-2 text-slate">{{ $description }}</p>{{ $slot }}
</div>
