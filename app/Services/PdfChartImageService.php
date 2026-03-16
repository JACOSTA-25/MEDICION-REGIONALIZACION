<?php

namespace App\Services;

class PdfChartImageService
{
    /**
     * Final PNG resolution multiplier against logical chart size.
     * 2x keeps good quality for PDF while avoiding oversized files.
     */
    private const OUTPUT_SCALE = 2;

    /**
     * Draw at a higher resolution and downsample for smoother edges/text.
     */
    private const SUPERSAMPLE_SCALE = 2;

    private ?string $fontPath = null;

    /**
     * @param  array{
     *      charts: array{
     *          population_by_program: array{items: array<int, array{label: string, value: int, percentage: float, color: string}>},
     *          population_by_estamento: array{items: array<int, array{label: string, value: int, percentage: float, color: string}>},
     *          question_results: array<int, array{items: array<int, array{label: string, value: int, percentage: float, color: string}>}>,
     *          satisfied_users_percentage: array{items: array<int, array{label: string, value: float, color: string}>}
     *      }
     * }  $report
     * @return array{
     *      population_by_program: string,
     *      population_by_estamento: string,
     *      question_results: array<int, string>,
     *      satisfied_users_percentage: string
     * }
     */
    public function build(array $report): array
    {
        $charts = $report['charts'] ?? [];

        return [
            'population_by_program' => $this->renderPieImage(
                $charts['population_by_program']['items'] ?? [],
                700,
                330,
                true,
                false
            ),
            'population_by_estamento' => $this->renderPieImage(
                $charts['population_by_estamento']['items'] ?? [],
                700,
                330,
                true,
                false
            ),
            'question_results' => array_map(
                fn (array $questionChart): string => $this->renderPieImage(
                    $questionChart['items'] ?? [],
                    760,
                    360,
                    true,
                    false
                ),
                $charts['question_results'] ?? []
            ),
            'satisfied_users_percentage' => $this->renderVerticalBarImage(
                $charts['satisfied_users_percentage']['items'] ?? [],
                760,
                360
            ),
        ];
    }

    /**
     * @param  array<int, array{label: string, value: int|float, percentage?: float, color: string}>  $items
     */
    private function renderPieImage(
        array $items,
        int $width,
        int $height,
        bool $drawOutsidePercentages = false,
        bool $showPercentageInLegend = true
    ): string {
        $outputScale = self::OUTPUT_SCALE;
        $drawScale = $outputScale * self::SUPERSAMPLE_SCALE;
        $scale = $drawScale;
        $canvasWidth = $width * $drawScale;
        $canvasHeight = $height * $drawScale;

        $image = imagecreatetruecolor($canvasWidth, $canvasHeight);
        imageantialias($image, true);
        imagealphablending($image, true);

        if (function_exists('imagesetinterpolation')) {
            imagesetinterpolation($image, IMG_BICUBIC_FIXED);
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $text = imagecolorallocate($image, 17, 24, 39);
        $gray = imagecolorallocate($image, 107, 114, 128);

        imagefilledrectangle($image, 0, 0, $canvasWidth, $canvasHeight, $white);

        $total = (float) array_sum(array_map(
            static fn (array $item): float => (float) ($item['value'] ?? 0),
            $items
        ));

        $centerX = (int) round(165 * $scale);
        $centerY = (int) floor($canvasHeight / 2);
        $diameter = (int) round(210 * $scale);

        if ($total <= 0) {
            imagefilledellipse($image, $centerX, $centerY, $diameter, $diameter, $this->allocateHexColor($image, '#E5E7EB'));
            $this->drawText($image, (int) round(11 * $scale), $centerX - (int) round(42 * $scale), $centerY, $gray, 'Sin datos');
        } else {
            $start = 270.0;
            $radius = (float) ($diameter / 2);

            foreach ($items as $item) {
                $value = (float) ($item['value'] ?? 0);

                if ($value <= 0) {
                    continue;
                }

                $angle = ($value / $total) * 360.0;
                $end = $start + $angle;

                imagefilledarc(
                    $image,
                    $centerX,
                    $centerY,
                    $diameter,
                    $diameter,
                    (int) round($start),
                    (int) round($end),
                    $this->allocateHexColor($image, (string) ($item['color'] ?? '#2563EB')),
                    IMG_ARC_PIE
                );

                if ($drawOutsidePercentages) {
                    $percentage = isset($item['percentage']) ? (float) $item['percentage'] : (($value / $total) * 100);
                    $this->drawPiePercentageCallout(
                        $image,
                        $centerX,
                        $centerY,
                        $radius,
                        $start,
                        $end,
                        $percentage,
                        $gray,
                        $text,
                        (int) round(7 * $scale),
                        $scale
                    );
                }

                $start = $end;
            }
        }

        imageellipse($image, $centerX, $centerY, $diameter, $diameter, $this->allocateHexColor($image, '#D1D5DB'));

        $legendX = (int) round(350 * $scale);
        $legendY = (int) round(26 * $scale);
        $lineHeight = (int) round(21 * $scale);

        foreach ($items as $index => $item) {
            $y = $legendY + ($index * $lineHeight);

            if ($y > $canvasHeight - (int) round(20 * $scale)) {
                break;
            }

            $color = $this->allocateHexColor($image, (string) ($item['color'] ?? '#2563EB'));
            imagefilledrectangle(
                $image,
                $legendX,
                $y,
                $legendX + (int) round(11 * $scale),
                $y + (int) round(11 * $scale),
                $color
            );

            $legendText = $showPercentageInLegend
                ? sprintf(
                    '%s (%s - %s%%)',
                    $this->truncate((string) ($item['label'] ?? ''), 34),
                    (string) ($item['value'] ?? 0),
                    $this->formatPercentage(isset($item['percentage']) ? (float) $item['percentage'] : 0.0)
                )
                : sprintf(
                    '%s (%s)',
                    $this->truncate((string) ($item['label'] ?? ''), 34),
                    (string) ($item['value'] ?? 0)
                );

            $this->drawText(
                $image,
                (int) round(8 * $scale),
                $legendX + (int) round(18 * $scale),
                $y + (int) round(10 * $scale),
                $text,
                $legendText
            );
        }

        return $this->toPngDataUri($image, $width * $outputScale, $height * $outputScale);
    }

    private function drawPiePercentageCallout(
        \GdImage $image,
        int $centerX,
        int $centerY,
        float $radius,
        float $startAngle,
        float $endAngle,
        float $percentage,
        int $lineColor,
        int $textColor,
        int $fontSize,
        int $scale
    ): void {
        if ($percentage <= 0) {
            return;
        }

        $midAngle = deg2rad(($startAngle + $endAngle) / 2);
        $edgeX = (int) round($centerX + (cos($midAngle) * $radius));
        $edgeY = (int) round($centerY + (sin($midAngle) * $radius));

        $lineOuterRadius = $radius + (16 * $scale);
        $labelRadius = $radius + (30 * $scale);
        $lineX = (int) round($centerX + (cos($midAngle) * $lineOuterRadius));
        $lineY = (int) round($centerY + (sin($midAngle) * $lineOuterRadius));
        $labelX = (int) round($centerX + (cos($midAngle) * $labelRadius));
        $labelY = (int) round($centerY + (sin($midAngle) * $labelRadius));
        $isRightSide = cos($midAngle) >= 0;
        $lineEndX = $isRightSide ? $labelX + (8 * $scale) : $labelX - (8 * $scale);

        imageline($image, $edgeX, $edgeY, $lineX, $lineY, $lineColor);
        imageline($image, $lineX, $lineY, $lineEndX, $lineY, $lineColor);

        $direction = atan2($edgeY - $lineY, $edgeX - $lineX);
        $headLength = (float) (4 * $scale);
        $leftHeadX = (int) round($edgeX - (cos($direction - 0.45) * $headLength));
        $leftHeadY = (int) round($edgeY - (sin($direction - 0.45) * $headLength));
        $rightHeadX = (int) round($edgeX - (cos($direction + 0.45) * $headLength));
        $rightHeadY = (int) round($edgeY - (sin($direction + 0.45) * $headLength));
        imageline($image, $edgeX, $edgeY, $leftHeadX, $leftHeadY, $lineColor);
        imageline($image, $edgeX, $edgeY, $rightHeadX, $rightHeadY, $lineColor);

        $labelText = $this->formatPercentage($percentage).'%';
        $estimatedTextWidth = (int) round(mb_strlen($labelText) * $fontSize * 0.55);
        $textX = $isRightSide
            ? $lineEndX + (2 * $scale)
            : $lineEndX - $estimatedTextWidth - (2 * $scale);
        $textY = $lineY + (int) round(3 * $scale);

        $this->drawText($image, $fontSize, $textX, $textY, $textColor, $labelText);
    }

    /**
     * @param  array<int, array{label: string, value: int|float, color: string}>  $items
     */
    private function renderVerticalBarImage(array $items, int $width, int $height): string
    {
        $outputScale = self::OUTPUT_SCALE;
        $drawScale = $outputScale * self::SUPERSAMPLE_SCALE;
        $scale = $drawScale;
        $canvasWidth = $width * $drawScale;
        $canvasHeight = $height * $drawScale;

        $image = imagecreatetruecolor($canvasWidth, $canvasHeight);
        imageantialias($image, true);
        imagealphablending($image, true);

        if (function_exists('imagesetinterpolation')) {
            imagesetinterpolation($image, IMG_BICUBIC_FIXED);
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $text = imagecolorallocate($image, 17, 24, 39);
        $grid = imagecolorallocate($image, 229, 231, 235);
        $axis = imagecolorallocate($image, 156, 163, 175);

        imagefilledrectangle($image, 0, 0, $canvasWidth, $canvasHeight, $white);

        $left = (int) round(42 * $scale);
        $right = $canvasWidth - (int) round(24 * $scale);
        $top = (int) round(18 * $scale);
        $baseY = $canvasHeight - (int) round(72 * $scale);
        $chartHeight = $baseY - $top;

        imageline($image, $left, $top, $left, $baseY, $axis);
        imageline($image, $left, $baseY, $right, $baseY, $axis);

        for ($tick = 0; $tick <= 5; $tick++) {
            $y = (int) round($top + ($tick * ($chartHeight / 5)));
            $value = 100 - ($tick * 20);

            imageline($image, $left, $y, $right, $y, $grid);
            $this->drawText($image, (int) round(12 * $scale), (int) round(4 * $scale), $y + (int) round(5 * $scale), $text, $value.'%');
        }

        if ($items !== []) {
            $count = max(count($items), 1);
            $step = ($right - $left) / $count;
            $barWidth = max(min((int) floor($step * 1.10), 152), 60);

            foreach ($items as $index => $item) {
                $value = max(min((float) ($item['value'] ?? 0), 100), 0);
                $barHeight = ($value / 100) * $chartHeight;

                $x = (int) round($left + ($index * $step) + (($step - $barWidth) / 2));
                $y = (int) round($baseY - $barHeight);

                $fill = $this->allocateHexColor($image, (string) ($item['color'] ?? '#2563EB'));
                imagefilledrectangle($image, $x, $y, $x + $barWidth, $baseY - 1, $fill);

                $valueText = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.').'%';
                $this->drawText(
                    $image,
                    (int) round(12 * $scale),
                    $x,
                    max($y - (int) round(12 * $scale), (int) round(6 * $scale)),
                    $text,
                    $valueText
                );

                $label = $this->truncate((string) ($item['label'] ?? ''), 18);
                $this->drawText(
                    $image,
                    (int) round(10 * $scale),
                    $x,
                    $baseY + (int) round(20 * $scale),
                    $text,
                    $label
                );
            }
        } else {
            $this->drawText(
                $image,
                (int) round(11 * $scale),
                (int) round(60 * $scale),
                (int) round(80 * $scale),
                $text,
                'Sin datos para graficar'
            );
        }

        return $this->toPngDataUri($image, $width * $outputScale, $height * $outputScale);
    }

    private function drawText(
        \GdImage $image,
        int $fontSize,
        int $x,
        int $y,
        int $color,
        string $text,
        float $angle = 0.0
    ): void {
        $fontPath = $this->resolveFontPath();

        if ($fontPath !== null && function_exists('imagettftext')) {
            $result = @imagettftext($image, $fontSize, $angle, $x, $y, $color, $fontPath, $text);

            if ($result !== false) {
                return;
            }
        }

        $font = match (true) {
            $fontSize >= 26 => 5,
            $fontSize >= 20 => 4,
            $fontSize >= 14 => 3,
            $fontSize >= 10 => 2,
            default => 1,
        };

        imagestring($image, $font, $x, max($y - 12, 0), $text, $color);
    }

    private function resolveFontPath(): ?string
    {
        if ($this->fontPath !== null) {
            return $this->fontPath !== '' ? $this->fontPath : null;
        }

        $candidates = [
            'C:\Windows\Fonts\arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf'),
            base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSansCondensed.ttf'),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $this->fontPath = $candidate;

                return $this->fontPath;
            }
        }

        $this->fontPath = '';

        return null;
    }

    private function allocateHexColor(\GdImage $image, string $hex): int
    {
        $normalized = ltrim(trim($hex), '#');

        if (strlen($normalized) === 3) {
            $normalized = $normalized[0].$normalized[0]
                .$normalized[1].$normalized[1]
                .$normalized[2].$normalized[2];
        }

        if (strlen($normalized) !== 6 || ! ctype_xdigit($normalized)) {
            $normalized = '2563EB';
        }

        $red = hexdec(substr($normalized, 0, 2));
        $green = hexdec(substr($normalized, 2, 2));
        $blue = hexdec(substr($normalized, 4, 2));

        return imagecolorallocate($image, $red, $green, $blue);
    }

    private function truncate(string $value, int $limit): string
    {
        $value = trim($value);

        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, max($limit - 3, 1))).'...';
    }

    private function formatPercentage(float $percentage): string
    {
        return rtrim(rtrim(number_format($percentage, 2, '.', ''), '0'), '.');
    }

    private function toPngDataUri(\GdImage $image, ?int $targetWidth = null, ?int $targetHeight = null): string
    {
        if (
            $targetWidth !== null &&
            $targetHeight !== null &&
            (imagesx($image) !== $targetWidth || imagesy($image) !== $targetHeight)
        ) {
            $resized = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($resized, true);

            if (function_exists('imagesetinterpolation')) {
                imagesetinterpolation($resized, IMG_BICUBIC_FIXED);
            }

            $white = imagecolorallocate($resized, 255, 255, 255);
            imagefilledrectangle($resized, 0, 0, $targetWidth, $targetHeight, $white);
            imagecopyresampled(
                $resized,
                $image,
                0,
                0,
                0,
                0,
                $targetWidth,
                $targetHeight,
                imagesx($image),
                imagesy($image)
            );

            imagedestroy($image);
            $image = $resized;
        }

        ob_start();
        imagepng($image, null, 3);
        $binary = (string) ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($binary);
    }
}
