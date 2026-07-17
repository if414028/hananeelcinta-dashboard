@props(['title' => null])
<section {{ $attributes->class('rounded-[40px] bg-lifted p-6 shadow-[0_24px_48px_rgba(0,0,0,0.06)] lg:p-8') }}>
    @if ($title)<h2 class="mb-5 text-2xl">{{ $title }}</h2>@endif
    {{ $slot }}
</section>
