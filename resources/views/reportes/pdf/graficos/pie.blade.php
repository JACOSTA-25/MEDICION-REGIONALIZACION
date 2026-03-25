@php
    $items = $chart['items'] ?? [];
    $total = array_sum(array_column($items, 'value'));
    $centerX = 160;
    $centerY = 150;
    $radius = 110;
    $startAngle = -90.0;
    $nonZeroItems = array_values(array_filter($items, static fn (array $item): bool => $item['value'] > 0));
@endphp

<div class="chart-shell">
    <svg width="700" height="320" viewBox="0 0 700 320" class="chart-svg" role="img" aria-label="{{ $chart['title'] }}">
        @if ($total === 0)
            <circle cx="{{ $centerX }}" cy="{{ $centerY }}" r="{{ $radius }}" fill="#e5e7eb" />
            <text x="{{ $centerX }}" y="{{ $centerY }}" text-anchor="middle" font-size="13" fill="#6b7280">
                Sin datos para graficar
            </text>
        @elseif (count($nonZeroItems) === 1)
            <circle cx="{{ $centerX }}" cy="{{ $centerY }}" r="{{ $radius }}" fill="{{ $nonZeroItems[0]['color'] }}" />
        @else
            @foreach ($items as $item)
                @php
                    $value = $item['value'];
                    $angle = $total > 0 ? ($value / $total) * 360 : 0;
                    $endAngle = $startAngle + $angle;

                    $startX = $centerX + ($radius * cos(deg2rad($startAngle)));
                    $startY = $centerY + ($radius * sin(deg2rad($startAngle)));
                    $endX = $centerX + ($radius * cos(deg2rad($endAngle)));
                    $endY = $centerY + ($radius * sin(deg2rad($endAngle)));
                    $largeArc = $angle > 180 ? 1 : 0;

                    $path = sprintf(
                        'M %.3f %.3f L %.3f %.3f A %d %d 0 %d 1 %.3f %.3f Z',
                        $centerX,
                        $centerY,
                        $startX,
                        $startY,
                        $radius,
                        $radius,
                        $largeArc,
                        $endX,
                        $endY
                    );

                    $startAngle = $endAngle;
                @endphp

                @if ($value > 0)
                    <path d="{{ $path }}" fill="{{ $item['color'] }}" stroke="#ffffff" stroke-width="1" />
                @endif
            @endforeach
        @endif

        <g transform="translate(340,40)">
            @foreach ($items as $index => $item)
                @php
                    $legendY = $index * 24;
                @endphp
                <rect x="0" y="{{ $legendY }}" width="14" height="14" fill="{{ $item['color'] }}" />
                <text x="22" y="{{ $legendY + 11 }}" font-size="12" fill="#111827">
                    {{ $item['label'] }} ({{ $item['value'] }} - {{ $item['percentage'] }}%)
                </text>
            @endforeach
        </g>
    </svg>
</div>
