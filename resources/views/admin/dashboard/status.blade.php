@php
    $colors = ['#7d1729', '#b7791f', '#416ca6', '#21806b', '#8b8b96'];
    $total = $data->sum();
    $offset = 0;
@endphp
<x-card>
    <h2 class="text-xl">Status prayer request</h2><p class="mt-1 text-sm text-slate">Seluruh permohonan doa berdasarkan status</p>
    <div class="mt-6 flex flex-col items-center gap-6 sm:flex-row">
        <div class="relative w-44 shrink-0">
            <svg viewBox="0 0 200 200" class="w-full" role="group" aria-label="Diagram donut status prayer request, total {{ $total }}. Rincian status tercantum di samping diagram.">
                <circle cx="100" cy="100" r="76" fill="none" stroke="#e5e5ea" stroke-width="20"/>
                @foreach(\App\Enums\PrayerRequestStatus::cases() as $index => $status)
                    @php($portion = $total > 0 ? (int) ($data[$status->value] ?? 0) / $total * 100 : 0)
                    @if($portion > 0)<circle cx="100" cy="100" r="76" fill="none" stroke="{{ $colors[$index] }}" stroke-width="20" pathLength="100" stroke-dasharray="{{ $portion }} {{ 100 - $portion }}" stroke-dashoffset="{{ -$offset }}" transform="rotate(-90 100 100)" class="chart-segment" pointer-events="stroke" tabindex="0" role="button" data-chart-label="{{ $status->label() }}" data-chart-value="{{ number_format($data[$status->value]) }} permohonan doa" aria-label="{{ $status->label() }}: {{ $data[$status->value] }} permohonan doa"><title>{{ $status->label() }}: {{ $data[$status->value] }}</title></circle>@endif
                    @php($offset += $portion)
                @endforeach
            </svg>
            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center" aria-hidden="true"><strong class="text-3xl tabular-nums">{{ number_format($total) }}</strong><span class="mt-1 text-xs text-slate">Permohonan doa</span></div>
        </div>
        <dl class="w-full space-y-3">
            @foreach(\App\Enums\PrayerRequestStatus::cases() as $index => $status)
                <div class="flex items-center justify-between gap-3 rounded-lg text-sm" tabindex="0" data-chart-label="{{ $status->label() }}" data-chart-value="{{ number_format($data[$status->value] ?? 0) }} permohonan doa"><dt class="flex items-center gap-2 text-slate"><span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $colors[$index] }}" aria-hidden="true"></span>{{ $status->label() }}</dt><dd class="font-semibold tabular-nums">{{ number_format($data[$status->value] ?? 0) }}</dd></div>
            @endforeach
        </dl>
    </div>
    @if($total === 0)<p class="mt-4 text-sm text-slate">Belum ada permohonan doa.</p>@endif
</x-card>
