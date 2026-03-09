@php
    $items = $chart['items'] ?? [];
    $maxValue = max(array_column($items, 'value') ?: [1]);
    $labelWidth = 250;
    $chartWidth = 360;
    $barHeight = 16;
    $rowGap = 10;
    $topPadding = 24;
    $bottomPadding = 14;
    $rowHeight = $barHeight + $rowGap;
    $height = $topPadding + $bottomPadding + (count($items) * $rowHeight);
@endphp

<div class="chart-shell">
    <svg width="700" height="{{ max($height, 100) }}" viewBox="0 0 700 {{ max($height, 100) }}" class="chart-svg" role="img" aria-label="{{ $chart['title'] }}">
        @if ($items === [])
            <text x="16" y="40" font-size="13" fill="#6b7280">Sin datos para graficar</text>
        @else
            @foreach ($items as $index => $item)
                @php
                    $y = $topPadding + ($index * $rowHeight);
                    $barWidth = $maxValue > 0 ? round(($item['value'] / $maxValue) * $chartWidth, 2) : 0;
                    $fill = $item['color'] ?? '#1D4ED8';
                @endphp

                <text x="10" y="{{ $y + 12 }}" font-size="11" fill="#111827">
                    {{ $item['label'] }}
                </text>

                <rect
                    x="{{ $labelWidth }}"
                    y="{{ $y }}"
                    width="{{ $barWidth }}"
                    height="{{ $barHeight }}"
                    rx="3"
                    fill="{{ $fill }}"
                />

                <text x="{{ $labelWidth + $barWidth + 8 }}" y="{{ $y + 12 }}" font-size="11" fill="#111827">
                    {{ $item['value'] }} ({{ $item['percentage'] }}%)
                </text>
            @endforeach
        @endif
    </svg>
</div>
