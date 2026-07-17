@props(['title' => null])
<section {{ $attributes->class('admin-surface p-6 lg:p-8') }}>
    @if ($title)<h2 class="mb-5 text-2xl">{{ $title }}</h2>@endif
    {{ $slot }}
</section>
