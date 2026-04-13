@php
    $toDataUri = static function (string $relativePath): ?string {
        $absolutePath = public_path($relativePath);

        if (! is_file($absolutePath)) {
            return null;
        }

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        $contents = file_get_contents($absolutePath);

        if ($contents === false) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    };

    $sidebarImage = $toDataUri('assets/images/sidebar.png');
    $encabezadoImage = $toDataUri('assets/images/Ecabezado2.png');
    $portadaImage = $toDataUri('assets/images/Portada2.png');

    $signature = $signature ?? null;
    $quarterLabel = null;
    $quarterRoman = null;
    $periodLabel = null;
    $processName = null;
    $dependencyName = null;

    foreach (($contextRows ?? []) as $contextRow) {
        if (($contextRow['label'] ?? '') === 'Trimestre') {
            $quarterLabel = $contextRow['value'] ?? null;
        }

        if (($contextRow['label'] ?? '') === 'Periodo') {
            $periodLabel = $contextRow['value'] ?? null;
        }

        if (($contextRow['label'] ?? '') === 'Proceso') {
            $processName = $contextRow['value'] ?? null;
        }

        if (($contextRow['label'] ?? '') === 'Dependencia') {
            $dependencyName = $contextRow['value'] ?? null;
        }
    }

    if (is_string($quarterLabel)) {
        $normalizedQuarterLabel = mb_strtoupper($quarterLabel, 'UTF-8');

        if (preg_match('/\b(IV|III|II|I)\b/u', $normalizedQuarterLabel, $romanMatch) === 1) {
            $quarterRoman = $romanMatch[1];
        } elseif (preg_match('/\b([1-4])\b/u', $normalizedQuarterLabel, $numericMatch) === 1) {
            $quarterRoman = match ((int) $numericMatch[1]) {
                1 => 'I',
                2 => 'II',
                3 => 'III',
                4 => 'IV',
                default => null,
            };
        }
    }

    $reportType = $reportType ?? 'general';
    $surveyCount = (int) ($report['totals']['survey_count'] ?? 0);
    $answerCount = (int) ($report['totals']['answer_count'] ?? 0);
    $scopeTable = $report['tables']['scope_population'] ?? ['rows' => [], 'total_general' => 0, 'first_column_title' => 'Encuestados', 'second_column_title' => 'Total'];
    $estamentoRows = $report['tables']['by_estamento'] ?? [];
    $consolidated = $report['tables']['measurement_consolidated'] ?? ['rows' => [], 'summary' => []];
    $globalIndicator = $report['indicators']['global'] ?? [];
    $coverYear = \Carbon\CarbonImmutable::parse($report['from'] ?? now()->toDateString(), config('app.timezone'))->format('Y');
    $scopeName = match ($reportType) {
        'process' => $processName ?: 'Proceso seleccionado',
        'individual' => $dependencyName ?: 'Dependencia seleccionada',
        default => 'procesos evaluados',
    };
    $scopeNameUpper = mb_strtoupper((string) $scopeName, 'UTF-8');
    $scopeSentence = match ($reportType) {
        'process' => 'el proceso '.$scopeName,
        'individual' => 'la dependencia '.$scopeName,
        default => 'los procesos evaluados',
    };
    $scopeInstitutional = match ($reportType) {
        'process' => 'del proceso '.$scopeName,
        'individual' => 'de la dependencia '.$scopeName,
        default => 'de los procesos evaluados',
    };
    $scopeIndicatorTitle = match ($reportType) {
        'process' => 'DEL PROCESO '.$scopeNameUpper,
        'individual' => 'DE LA DEPENDENCIA '.$scopeNameUpper,
        default => 'DE LOS PROCESOS EVALUADOS',
    };
    $scopeTocOfficeTitle = match ($reportType) {
        'process', 'individual' => 'DE LA OFICINA DE '.$scopeNameUpper,
        default => 'DE LOS PROCESOS EVALUADOS',
    };
    $estamentoTotal = (int) array_sum(array_column($estamentoRows, 'encuestas'));
    $formatValue = static function (float|int $value): string {
        $formatted = number_format((float) $value, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    };
    $questionActionParagraphs = [
        1 => 'En atencion a estos resultados, se continuara fortaleciendo las estrategias orientadas a mantener la calidad del servicio y la atencion oportuna al usuario.',
        2 => 'En atencion a estos resultados, se continuaran fortaleciendo las acciones orientadas a garantizar condiciones adecuadas de seguridad y comodidad para los usuarios.',
        3 => 'En atencion a estos resultados, se continuaran fortaleciendo las estrategias orientadas a mejorar la experiencia del usuario y el valor percibido del servicio.',
        4 => 'En atencion a estos resultados, se continuaran fortaleciendo las acciones orientadas a garantizar informacion clara, pertinente y oportuna para los usuarios.',
        5 => 'En atencion a estos resultados, se continuaran fortaleciendo las practicas institucionales orientadas a garantizar una comunicacion respetuosa, incluyente y libre de cualquier forma de discriminacion.',
    ];
    $resolvedGeneratedConclusion = filled($generatedConclusion ?? null)
        ? trim((string) $generatedConclusion)
        : null;
    $questionChartOffset = 4;
    $tocEntries = [
        ['label' => '1. INTRODUCCION', 'page' => 3],
        ['label' => '2. OBJETIVO', 'page' => 3],
        ['label' => '3. CONTEXTUALIZACION DE LA ENCUESTA', 'page' => 3],
        ['label' => '4. NUMERO DE USUARIOS ENCUESTADOS', 'page' => 4],
        ['label' => '5. CARACTERIZACION DE LOS USUARIOS ENCUESTADOS', 'page' => 5],
        ['label' => '6. RESULTADOS DE LA ENCUESTA APLICADA', 'page' => 7],
        ['label' => '7. ANALISIS DE LA ENCUESTA', 'page' => 12],
        ['label' => '8. INDICADOR DE MEDICION DE LA SATISFACCION GLOBAL DE LOS USUARIOS '.$scopeTocOfficeTitle, 'page' => 13],
        ['label' => '9. CONCLUSIONES DE LA MEDICION DE LA SATISFACCION DE LOS USUARIOS '.$scopeTocOfficeTitle, 'page' => 14],
    ];
@endphp
