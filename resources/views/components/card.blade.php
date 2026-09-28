@props(['title' => null])
<section {{ $attributes->class('admin-surface min-w-0 max-w-full p-5 sm:p-6 lg:p-8') }}>
    @if ($title)<h2 class="mb-5 text-xl sm:text-2xl">{{ $title }}</h2>@endif
    {{ $slot }}
</section>
