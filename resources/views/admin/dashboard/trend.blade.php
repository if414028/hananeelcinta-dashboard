@php
    $months = collect(range(11, 0))->map(fn ($offset) => now()->startOfMonth()->subMonths($offset));
    $values = $months->map(fn ($month) => (int) ($data[$month->format('Y-m')] ?? 0));
    $maximum = max(4, (int) (ceil($values->max() / 4) * 4));
    $points = $values->map(fn ($value, $index) => (48 + $index * 44).','.round(190 - $value / $maximum * 150, 2))->implode(' ');
@endphp
<x-card>
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div><h2 class="text-xl">{{ $title }}</h2><p class="mt-1 text-sm text-slate">{{ $subtitle }}</p></div>
        <span class="rounded-lg bg-canvas px-3 py-2 text-xs font-medium text-slate">12 bulan terakhir</span>
    </div>
    <div class="mb-2 flex items-baseline gap-2"><strong class="text-3xl tabular-nums">{{ number_format($values->sum()) }}</strong><span class="text-sm text-slate">{{ $unit }}</span></div>
    <svg viewBox="0 0 560 230" class="block w-full" role="group" aria-label="{{ $title }}: {{ $values->sum() }} {{ $unit }} dalam 12 bulan terakhir. Rincian tersedia pada tabel data.">
        <defs><linearGradient id="{{ $id }}-fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="{{ $color }}" stop-opacity=".24"/><stop offset="100%" stop-color="{{ $color }}" stop-opacity=".02"/></linearGradient></defs>
        @foreach(range(0, 4) as $tick)
            <line x1="48" x2="532" y1="{{ 190 - $tick * 37.5 }}" y2="{{ 190 - $tick * 37.5 }}" stroke="#e5e5ea" stroke-dasharray="3 5"/>
            <text x="36" y="{{ 194 - $tick * 37.5 }}" text-anchor="end" fill="#636366" font-size="12">{{ $maximum * $tick / 4 }}</text>
        @endforeach
        @if($type === 'area')
            <polygon points="48,190 {{ $points }} 532,190" fill="url(#{{ $id }}-fill)"/>
            <polyline points="{{ $points }}" fill="none" stroke="{{ $color }}" stroke-width="3" stroke-linejoin="round" stroke-linecap="round"/>
        @endif
        @foreach($months as $index => $month)
            @php($y = 190 - $values[$index] / $maximum * 150)
            @if($type === 'area')
                <circle cx="{{ 48 + $index * 44 }}" cy="{{ $y }}" r="4" fill="white" stroke="{{ $color }}" stroke-width="2"><title>{{ $month->translatedFormat('F Y') }}: {{ $values[$index] }}</title></circle>
            @else
                <rect x="{{ 48 + $index * 44 - 11 }}" y="{{ $y }}" width="22" height="{{ 190 - $y }}" rx="4" fill="{{ $color }}"><title>{{ $month->translatedFormat('F Y') }}: {{ $values[$index] }}</title></rect>
            @endif
            <text x="{{ 48 + $index * 44 }}" y="215" text-anchor="middle" fill="#636366" font-size="12">{{ $month->translatedFormat('M') }}</text>
        @endforeach
        @foreach($months as $index => $month)
            <rect x="{{ 26 + $index * 44 }}" y="28" width="44" height="170" rx="4" fill="transparent" class="chart-hit"
                tabindex="0" role="button" data-chart-label="{{ $month->translatedFormat('F Y') }}" data-chart-value="{{ number_format($values[$index]) }} {{ $unit }}"
                aria-label="{{ $month->translatedFormat('F Y') }}: {{ $values[$index] }} {{ $unit }}"/>
        @endforeach
    </svg>
    @if($values->sum() === 0)<p class="mt-2 text-sm text-slate">Belum ada data dalam 12 bulan terakhir.</p>@endif
    <details class="mt-3 border-t border-ink/10 pt-3 text-sm"><summary class="w-fit rounded py-1 font-medium text-primary">Lihat data per bulan</summary><table class="mt-3 w-full text-left"><caption class="sr-only">{{ $title }}</caption><thead><tr><th class="py-2">Bulan</th><th class="text-right">Jumlah</th></tr></thead><tbody>@foreach($months as $index => $month)<tr class="border-t border-ink/8"><th scope="row" class="py-2 font-normal text-slate">{{ $month->translatedFormat('F Y') }}</th><td class="text-right tabular-nums">{{ $values[$index] }}</td></tr>@endforeach</tbody></table></details>
</x-card>
