@php
    $items = $chart['items'] ?? [];
    $chartHeight = 220;
    $baseY = 250;
    $leftPadding = 40;
    $usableWidth = 620;
    $count = max(count($items), 1);
    $step = $usableWidth / $count;
    $barWidth = max(min($step * 0.55, 70), 24);
@endphp

<div class="chart-shell">
    <svg width="700" height="360" viewBox="0 0 700 360" class="chart-svg" role="img" aria-label="{{ $chart['title'] }}">
        <line x1="{{ $leftPadding }}" y1="20" x2="{{ $leftPadding }}" y2="{{ $baseY }}" stroke="#9ca3af" stroke-width="1" />
        <line x1="{{ $leftPadding }}" y1="{{ $baseY }}" x2="680" y2="{{ $baseY }}" stroke="#9ca3af" stroke-width="1" />

        @for ($tick = 0; $tick <= 5; $tick++)
            @php
                $value = 100 - ($tick * 20);
                $y = 20 + ($tick * ($chartHeight / 5));
            @endphp
            <line x1="{{ $leftPadding }}" y1="{{ $y }}" x2="680" y2="{{ $y }}" stroke="#f3f4f6" stroke-width="1" />
            <text x="4" y="{{ $y + 4 }}" font-size="10" fill="#6b7280">{{ $value }}%</text>
        @endfor

        @if ($items === [])
            <text x="50" y="80" font-size="13" fill="#6b7280">Sin datos para graficar</text>
        @else
            @foreach ($items as $index => $item)
                @php
                    $value = max(min((float) $item['value'], 100), 0);
                    $barHeight = ($value / 100) * $chartHeight;
                    $x = $leftPadding + ($index * $step) + (($step - $barWidth) / 2);
                    $y = $baseY - $barHeight;
                @endphp

                <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $barHeight }}" fill="{{ $item['color'] }}" />
                <text x="{{ $x + ($barWidth / 2) }}" y="{{ $y - 6 }}" text-anchor="middle" font-size="10" fill="#111827">
                    {{ round($value, 2) }}%
                </text>
                <text
                    transform="translate({{ $x + ($barWidth / 2) }},{{ $baseY + 10 }}) rotate(28)"
                    text-anchor="start"
                    font-size="9"
                    fill="#111827"
                >
                    {{ $item['label'] }}
                </text>
            @endforeach
        @endif
    </svg>
</div>
