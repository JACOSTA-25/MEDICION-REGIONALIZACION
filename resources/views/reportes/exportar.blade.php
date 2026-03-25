<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title></title>
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
            $encabezadoImage = $toDataUri('assets/images/encabezado.png');
            $piePaginaImage = $toDataUri('assets/images/pie-de-pagina.png');
            $portadaImage = $toDataUri('assets/images/portada.png');
            $coverLogoImage = $toDataUri('assets/images/logo-portada.png');
        @endphp
        <style>
            :root {
                --paper-width: 215.9mm;
                --paper-height: 279.4mm;
                --design-width: 210mm;
                --design-height: 297mm;
                --cover-image-bleed: 2mm;
                --cover-copy-top: 217mm;
                --cover-year-margin-top: 7.35mm;
                --header-horizontal-shift: -6mm;
                --page-content-center-offset: -7px;
                --consolidated-title-offset: -40px; 
                --satisfied-chart-offset: -35px;
                --compact-table-offset: -34px;
                --compact-table-title-offset: -28px;
                --question-chart-caption-offset: -40px;
                --content-shift-left: 88px;
                --content-trim-right: 180px;
                --question-offset-left: 80px;
                --question-offset-right: 176px;
                --intro-offset-left: 126px;
                --intro-offset-right: 46.8mm;
            }

            @page {
                size: letter portrait;
                margin: 0;
            }

            body {
                font-family: Calibri, Candara, Segoe, "Segoe UI", Optima, Arial, sans-serif;
                margin: 0;
                color: #111827;
                font-size: 12px;
            }

            .page {
                position: relative;
                box-sizing: border-box;
                width: var(--paper-width);
                min-height: var(--paper-height);
                padding: 16mm 12mm 12mm;
                overflow: hidden;
            }

            .page + .page {
                page-break-before: always;
            }

            .cover-page {
                padding: 0;
                width: var(--paper-width);
                height: var(--paper-height);
                overflow: hidden;
            }

            .cover-image {
                position: absolute;
                top: 0;
                left: 50%;
                transform: translateX(-50%);
                display: block;
                width: calc(var(--paper-width) + var(--cover-image-bleed));
                height: auto;
                z-index: 0;
            }

            .cover-copy {
                position: absolute;
                left: 7mm;
                top: var(--cover-copy-top);
                width: 150mm;
                color: #1AA6A6;
                font-family: 'Montserrat', 'DejaVu Sans', sans-serif;
                z-index: 2;
            }

            .cover-logo {
                position: absolute;
                right: 7mm;
                bottom: 5mm;
                display: block;
                width: 58mm;
                height: auto;
                z-index: 1;
            }

            .cover-subtitle {
                margin: 0;
                font-size: 4.9mm;
                font-weight: 700;
                line-height: 1.12;
                letter-spacing: 0;
                text-transform: uppercase;
            }

            .cover-year {
                margin: var(--cover-year-margin-top) 0 0;
                font-size: 14.5mm;
                font-weight: 700;
                line-height: 1;
                letter-spacing: 0.02em;
            }

            .page-with-decor {
                padding-top: 84px;
                padding-right: 10mm;
                padding-bottom: 46px;
                padding-left: 52px;
            }

            .decor-header {
                position: absolute;
                top: 5px;
                left: calc(50% + var(--header-horizontal-shift));
                transform: translateX(-50%);
                width: 160mm;
                height: auto;
                max-width: none;
                z-index: 1;
            }

            .decor-sidebar {
                position: absolute;
                top: 0;
                left: 0;
                width: 36px;
                height: var(--design-height);
            }

            .decor-footer {
                position: absolute;
                left: 40px;
                bottom: 10px;
                height: 52px;
                width: auto;
                max-width: 100%;
            }

            .page-header {
                margin-bottom: 6px;
                padding-bottom: 4px;
            }

            h1,
            h2,
            h3,
            p {
                margin: 0;
            }

            h1 {
                font-size: 26px;
                margin-bottom: 12px;
            }

            h2 {
                font-size: 18px;
            }

            h3 {
                font-size: 14px;
                margin: 8px 0 6px;
            }

            .cover-card {
                border: 1px solid #d1d5db;
                border-radius: 10px;
                padding: 14px;
                background: #f9fafb;
                margin-bottom: 12px;
            }

            .context {
                display: grid;
                gap: 8px;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .context-item {
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 8px;
            }

            .context-item strong {
                display: block;
                margin-bottom: 3px;
                font-size: 11px;
                color: #374151;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th,
            td {
                border: 1px solid #d1d5db;
                padding: 6px 7px;
                vertical-align: top;
                font-size: 11px;
            }

            th {
                background:rgb(69, 131, 255);
                text-align: left;
            }

            .chart-shell {
                border: none;
                border-radius: 10px;
                padding: 2px 2px 0;
                margin-bottom: 0;
            }

            .chart-image {
                width: 100%;
                height: auto;
                display: block;
            }

            .split-two {
                display: grid;
                gap: 6px;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .muted {
                color: #6b7280;
                font-size: 11px;
            }

            .chart-caption {
                margin-bottom: 10px;
                text-align: center;
                font-size: 10.5px;
                color: #374151;
            }

            .chart-caption--question {
                position: relative;
                left: var(--question-chart-caption-offset);
            }

            .conclusion-box {
                padding: 0;
                min-height: 240px;
                background: transparent;
            }

            .conclusion-copy {
                margin-left: var(--content-shift-left);
                margin-right: var(--content-trim-right);
            }

            .conclusion-copy p + p {
                margin-top: 8px;
            }

            .signature-block {
                margin-top: 20px;
                margin-left: var(--content-shift-left);
                margin-right: var(--content-trim-right);
            }

            .signature-name,
            .signature-title,
            .signature-scope {
                margin: 0;
                font-size: 10.5px;
                line-height: 1.35;
            }

            .signature-name {
                font-weight: 700;
            }

            .report-section-title {
                font-size: 12px;
                font-weight: 700;
                margin: 0 0 8px;
                text-align: left;
                margin-top: 28px;
            }

            .report-section-title--shift-right {
                margin-left: var(--content-shift-left);
            }

            .report-section-title--compact {
                margin-bottom: 2px;
            }

            .report-section-text {
                margin: 0 0 14px;
                font-size: 10.5px;
                line-height: 1.35;
                text-align: justify;
            }

            .report-section-text--shift-right {
                margin-left: var(--content-shift-left);
            }

            .report-section-block--aligned {
                margin-right: var(--content-trim-right);
            }

            .question-title {
                margin: 6px 0 6px;
                font-size: 11.5px;
                font-weight: 700;
            }

            .question-text {
                margin: 0 0 8px;
                font-size: 10.5px;
                line-height: 1.35;
                text-align: justify;
            }

            .question-block {
                padding-top: 14px;
                padding-left: var(--question-offset-left);
                padding-right: var(--question-offset-right);
            }

            .question-title--aligned {
                margin-top: 12px;
            }

            .question-chart {
                width: 92%;
                margin-top: 8px;
                margin-left: auto;
                margin-right: auto;
            }

            .table-title {
                margin: 4px 0 6px;
                margin-top: 34px;
                font-size: 10.5px;
                font-weight: 700;
                text-align: center;
            }

            .table-title--compact {
                margin-top: 12px;
            }

            .table-title--spacious {
                margin-top: 24px;
            }

            .compact-table th,
            .compact-table td {
                font-size: 10px;
                padding: 4px 5px;
            }

            .page-intro {
                padding-top: 126px;
                padding-right: 10mm;
                padding-left: 52px;
            }

            .page-intro .report-section-title,
            .page-intro .report-section-text {
                margin-left: var(--content-shift-left);
                margin-right: var(--content-trim-right);
            }

            .page-chart-overview .page-header {
                margin-bottom: 2px;
            }

            .page-chart-overview .table-title {
                margin-top: 8px;
            }

            .page-chart-overview .chart-shell {
                padding: 0;
            }

            .page-chart-overview .chart-image {
                width: 92%;
                margin-left: auto;
                margin-right: auto;
            }

            .page-chart-overview .chart-caption {
                margin-bottom: 0;
                font-size: 10px;
            }

            .compact-table th:first-child {
                text-align: center;
            }

            .compact-table {
                width: 72%;
                margin-left: auto;
                margin-right: auto;
                position: relative;
                left: var(--compact-table-offset);
            }

            .compact-table th:nth-child(2),
            .compact-table td:nth-child(2) {
                text-align: center;
            }

            .consolidated-table {
                width: 76%;
                margin-left: 8%;
                /* margin-right: 20%; */
                position: relative;
                left: var(--page-content-center-offset);
                table-layout: fixed;
            }

            .consolidated-table th,
            .consolidated-table td {
                font-size: 9.2px;
                padding: 4px 4px;
                word-wrap: break-word;
            }

            .consolidated-table th:nth-child(1),
            .consolidated-table td:nth-child(1) {
                width: 10%;
                text-align: center;
                white-space: nowrap;
            }

            .consolidated-table th:nth-child(2),
            .consolidated-table td:nth-child(2) {
                text-align: center;
                width: 21%;
            }

            .consolidated-table th:nth-child(3),
            .consolidated-table td:nth-child(3),
            .consolidated-table th:nth-child(4),
            .consolidated-table td:nth-child(4) {
                text-align: center;
                width: 12%;
            }

            .consolidated-table th:nth-child(5),
            .consolidated-table td:nth-child(5),
            .consolidated-table th:nth-child(6),
            .consolidated-table td:nth-child(6) {
                width: 8%;
                text-align: center;
            }

            .consolidated-table th:nth-child(7),
            .consolidated-table td:nth-child(7),
            .consolidated-table th:nth-child(8),
            .consolidated-table td:nth-child(8) {
                width: 11%;
                text-align: center;
            }

            .satisfied-users-chart {
                width: 53%;
                margin-top: 18px;
                margin-left: auto;
                margin-right: auto;
                position: relative;
                left: var(--satisfied-chart-offset);
                text-align: center;
            }

            .satisfied-users-chart + .chart-caption {
                position: relative;
                left: var(--satisfied-chart-offset);
            }

            .report-subheading {
                margin-top: 24px;
                font-size: 12px;
                font-weight: 700;
            }

            .report-subheading--shift-right {
                margin-left: var(--content-shift-left);
            }

            .table-title--consolidated {
                width: 76%;
                margin-left: auto;
                margin-right: auto;
                position: relative;
                left: var(--consolidated-title-offset);
            }

            .table-title--compact-table {
                width: 72%;
                margin-left: auto;
                margin-right: auto;
                position: relative;
                left: var(--compact-table-title-offset);
            }
        </style>
    </head>
    <body>
        @php
            $signature = $signature ?? null;
            $quarterLabel = null;
            $processName = null;
            $dependencyName = null;

            if (! empty($contextRows) && is_array($contextRows)) {
                foreach ($contextRows as $contextRow) {
                    if (($contextRow['label'] ?? '') === 'Trimestre') {
                        $quarterLabel = $contextRow['value'] ?? null;
                    }

                    if (($contextRow['label'] ?? '') === 'Proceso') {
                        $processName = $contextRow['value'] ?? null;
                    }

                    if (($contextRow['label'] ?? '') === 'Dependencia') {
                        $dependencyName = $contextRow['value'] ?? null;
                    }
                }
            }

            $coverQuarter = mb_strtoupper((string) $quarterLabel, 'UTF-8');
            $coverSubtitle = match ($reportType ?? null) {
                'process' => trim('PROCESO DE '.mb_strtoupper((string) ($processName ?? 'PROCESO SELECCIONADO'), 'UTF-8').' '.$coverQuarter),
                'individual' => trim('DEPENDENCIA '.mb_strtoupper((string) ($dependencyName ?? 'DEPENDENCIA SELECCIONADA'), 'UTF-8').' '.$coverQuarter),
                default => trim('SEDE MAICAO '.$coverQuarter),
            };
            $coverSubtitle = preg_replace('/\s+/', ' ', $coverSubtitle) ?? $coverSubtitle;
            $coverYear = \Carbon\CarbonImmutable::parse($report['from'] ?? now()->toDateString(), config('app.timezone'))
                ->format('Y');
        @endphp

        <section class="page cover-page">
            @if ($portadaImage)
                <img src="{{ $portadaImage }}" alt="Portada" class="cover-image">
            @endif

            @if ($coverLogoImage)
                <img src="{{ $coverLogoImage }}" alt="Logo de portada" class="cover-logo">
            @endif

            <div class="cover-copy">
                <p class="cover-subtitle">{{ $coverSubtitle }}</p>
                <p class="cover-year">{{ $coverYear }}</p>
            </div>
        </section>

        <section class="page page-with-decor page-intro">
            @include('reportes.pdf.parciales.page-decor')
            @php
                $scopeTable = $report['tables']['scope_population'];
                $objectiveText = match ($reportType ?? null) {
                    'process' => 'Medir el grado de satisfaccion por parte de los usuarios, partes interesadas y grupos de valor con relacion a los servicios brindados por el proceso durante el periodo.',
                    'individual' => 'Medir el grado de satisfaccion por parte de los usuarios, partes interesadas y grupos de valor con relacion a los servicios brindados por la dependencia durante el periodo.',
                    default => 'Medir el grado de satisfaccion por parte de los usuarios, partes interesadas y grupos de valor con relacion a los servicios brindados por todos los procesos durante el periodo.',
                };
            @endphp

            <h3 class="report-section-title">I. OBJETIVO</h3>
            <p class="report-section-text">{{ $objectiveText }}</p>

            <h3 class="report-section-title">II. ANALISIS DE LA ENCUESTA</h3>
            <p class="report-section-text">
                Respecto a las preguntas contenidas en la encuesta, a continuacion, se presenta el respectivo analisis de la totalidad de resultados dada la percepcion ciudadana. El compromiso institucional es el mejoramiento continuo en la calidad de la atencion prestada a la comunidad universitaria, con el fin de evaluar y mejorar los mecanismos de atencion, y los niveles de satisfaccion de los mismos y de esta manera tomar las acciones necesarias para la mejora continua.
            </p>

            <h3 class="report-section-title">III. ENCUESTADOS</h3>
            <p class="report-section-text">
                Se aplico la encuesta de satisfaccion durante el periodo a usuarios ecuestados, A los cuales se les presto el servicio, como se describe en la tabla 1.
            </p>

            <p class="table-title table-title--compact-table">Tabla 1. Numero de usuarios encuestados</p>
            <table class="compact-table">
                <thead>
                    <tr>
                        <th>{{ $scopeTable['first_column_title'] }}</th>
                        <th>{{ $scopeTable['second_column_title'] }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($scopeTable['rows'] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td>{{ $row['total'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">Sin datos para el trimestre seleccionado.</td>
                        </tr>
                    @endforelse
                    <tr>
                        <td><strong>Total general</strong></td>
                        <td><strong>{{ $scopeTable['total_general'] }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="page page-with-decor page-chart-overview">
            @include('reportes.pdf.parciales.page-decor')
            <div class="page-header">
                <h2 class="report-section-title report-section-title--shift-right">IV. ANALISIS DE POBLACI&Oacute;N ENCUESTADA</h2>
            </div>

            <div class="split-two">
                <div>
                    <h3 class="table-title table-title--compact">PROGRAMAS ATENDIDOS</h3>
                    <div class="chart-shell">
                        <img src="{{ $chartImages['population_by_program'] }}" alt="Programas atendidos" class="chart-image">
                    </div>
                    <p class="chart-caption">Gr&aacute;fico 1. Poblaci&oacute;n atendida por programa</p>
                </div>
                <div>
                    <h3 class="table-title table-title--compact">ESTAMENTOS ATENDIDOS</h3>
                    <div class="chart-shell">
                        <img src="{{ $chartImages['population_by_estamento'] }}" alt="Estamentos atendidos" class="chart-image">
                    </div>
                    <p class="chart-caption">Gr&aacute;fico 2. Poblaci&oacute;n atendida por Estamento</p>
                </div>
            </div>
        </section>

        @php
            $scopeName = match ($reportType ?? null) {
                'individual' => $dependencyName ? 'la dependencia '.$dependencyName : 'la dependencia seleccionada',
                'process' => $processName ? 'el proceso '.$processName : 'el proceso seleccionado',
                default => 'los procesos de forma general',
            };

            $questionTitles = [
                1 => 'PRESTACI&Oacute;N DEL SERVICIO',
                2 => 'ATENCI&Oacute;N DEL FUNCIONARIO',
                3 => 'EXPECTATIVAS DEL SERVICIO',
                4 => 'EFICACIA Y OPORTUNIDAD',
                5 => 'CONDICIONES LOCATIVAS',
                6 => 'LENGUAJE CLARO',
            ];
        @endphp

        @foreach ($report['charts']['question_results'] as $index => $chart)
            <section class="page page-with-decor">
                @include('reportes.pdf.parciales.page-decor')
                @php
                    $question = $report['questions'][$index] ?? null;
                    $questionNumber = $question['number'] ?? ($index + 1);
                    $questionLabel = $question['label'] ?? ('Pregunta '.$questionNumber);
                    $surveyCount = $report['totals']['survey_count'] ?? 0;
                    $frequencies = $question['frequencies'] ?? ($chart['items'] ?? []);
                    $percentageByValue = [];

                    foreach ($frequencies as $frequency) {
                        $valueKey = (int) ($frequency['value'] ?? 0);
                        $percentageByValue[$valueKey] = $frequency['percentage'] ?? 0;
                    }

                    $percentageExcelente = $percentageByValue[5] ?? 0;
                    $percentageBueno = $percentageByValue[4] ?? 0;
                    $percentageRegular = $percentageByValue[3] ?? 0;
                    $percentageMalo = $percentageByValue[2] ?? 0;
                    $percentageDeficiente = $percentageByValue[1] ?? 0;
                    $satisfactionPercentage = $question['satisfaction']['satisfied_percentage'] ?? 0;
                    $dissatisfactionPercentage = $percentageMalo + $percentageDeficiente;
                    $neutralPercentage = $percentageRegular;
                    $chartNumber = 4 + $index;
                @endphp

                @if ($index === 0)
                    <div class="page-header">
                        <h2 class="report-section-title report-section-title--shift-right report-section-title--compact">V. RESULTADOS DE LA ENCUESTA APLICADA</h2>
                    </div>
                @endif

                <div class="question-block">
                    <h3 class="question-title question-title--aligned">
                        PREGUNTA {{ $questionNumber }}. {!! $questionTitles[$questionNumber] ?? strtoupper((string) ($chart['title'] ?? '')) !!}
                    </h3>
                    <p class="question-text">
                        A los {{ $surveyCount }} usuarios encuestados se les formul&oacute; la pregunta No. {{ $questionNumber }}: {{ $questionLabel }}. Los resultados obtenidos fueron los siguientes:
                        <br>
                        &bull; El {{ number_format($percentageExcelente, 2, '.', '') }}% de los usuarios calific&oacute; el servicio como excelente.
                        <br>
                        &bull; El {{ number_format($percentageBueno, 2, '.', '') }}% lo calific&oacute; como bueno.
                        <br>
                        &bull; El {{ number_format($percentageRegular, 2, '.', '') }}% consider&oacute; que fue regular.
                        <br>
                        &bull; El {{ number_format($percentageMalo, 2, '.', '') }}% manifest&oacute; que fue malo.
                        <br>
                        &bull; El {{ number_format($percentageDeficiente, 2, '.', '') }}% lo calific&oacute; como deficiente.
                        <br>
                        En t&eacute;rminos generales, se observa que el {{ number_format($satisfactionPercentage, 2, '.', '') }}% de los usuarios manifiesta satisfacci&oacute;n con el servicio prestado, considerando las valoraciones positivas (excelente y bueno). Por otra parte, el {{ number_format($dissatisfactionPercentage, 2, '.', '') }}% presenta niveles de insatisfacci&oacute;n, al calificar el servicio como malo o deficiente, mientras que el <strong>{{ number_format($neutralPercentage, 2, '.', '') }}% mantiene una percepci&oacute;n neutral al catalogarlo como regular.</strong>
                    </p>
                </div>

                <div class="chart-shell question-chart">
                    <img src="{{ $chartImages['question_results'][$index] ?? '' }}" alt="{{ $chart['title'] }}" class="chart-image">
                </div>
                <p class="chart-caption chart-caption--question">
                    Gr&aacute;fica {{ $chartNumber }}. Resultados de la medici&oacute;n del servicio prestado en {{ $scopeName }}.
                </p>
            </section>
        @endforeach

        <section class="page page-with-decor">
            @include('reportes.pdf.parciales.page-decor')
            @php
                $consolidated = $report['tables']['measurement_consolidated'];
                $averages = $consolidated['averages'];
                $globalIndicator = $report['indicators']['global'] ?? [];
                $scopeIndicatorName = mb_strtoupper((string) ($dependencyName ?? $processName ?? 'GESTION DOCUMENTAL'), 'UTF-8');
                $formatReportValue = static function (float|int $value): string {
                    $formatted = number_format((float) $value, 2, '.', '');

                    return rtrim(rtrim($formatted, '0'), '.');
                };
                $questionPercentages = [];

                foreach (($report['questions'] ?? []) as $questionData) {
                    $questionPercentages[(int) ($questionData['number'] ?? 0)] = $questionData['satisfaction']['satisfied_percentage'] ?? 0;
                }
            @endphp

            <div class="page-header">
                <h2 class="report-section-title report-section-title--shift-right report-section-title--compact report-section-block--aligned">
                    VI. INDICADOR DE MEDICI&Oacute;N DE LA SATISFACCI&Oacute;N GLOBAL DE LOS USUARIOS DE {{ $scopeIndicatorName }}
                </h2>
            </div>

            <p class="report-section-text report-section-text--shift-right report-section-block--aligned">
                Seg&uacute;n los resultados obtenidos en las gr&aacute;ficas, podemos decir que, en promedio de los {{ $report['totals']['survey_count'] ?? 0 }} usuarios encuestados, {{ $formatReportValue($globalIndicator['satisfied_users'] ?? 0) }} se sintieron satisfechos, lo que da un porcentaje de satisfacci&oacute;n global del {{ $formatReportValue($globalIndicator['satisfaction_percentage'] ?? 0) }}%.
                Respecto a los aspectos evaluados el porcentaje de satisfacci&oacute;n para el Servicio fue de {{ $formatReportValue($questionPercentages[1] ?? 0) }}%, para la Atencion fue de {{ $formatReportValue($questionPercentages[2] ?? 0) }}%, para la expectativa del servicio fue de {{ $formatReportValue($questionPercentages[3] ?? 0) }}%, para el Servicio oportuno y eficaz fue de {{ $formatReportValue($questionPercentages[4] ?? 0) }}%, para el aspecto de Condiciones locativas fue de {{ $formatReportValue($questionPercentages[5] ?? 0) }}% y para el aspecto del lenguaje usado por el funcionario fue de {{ $formatReportValue($questionPercentages[6] ?? 0) }}%.
            </p>

            <p class="table-title table-title--compact table-title--spacious table-title--consolidated">Tabla 2. CONSOLIDADO GLOBAL MEDICI&Oacute;N DE LA SATISFACCI&Oacute;N DE LOS USUARIOS</p>

            <table class="consolidated-table">
                <thead>
                    <tr>
                        <th>Pregunta</th>
                        <th>Categoria</th>
                        <th>Numero de<br>usuarios<br>satisfechos</th>
                        <th>Numero de<br>usuarios insatisfechos</th>
                        <th>Neutro</th>
                        <th>Total</th>
                        <th>Mejora</th>
                        <th>Indicador</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($consolidated['rows'] as $row)
                        <tr>
                            <td>{{ $row['question_number'] }}</td>
                            <td>{{ $row['categoria'] }}</td>
                            <td>{{ $row['usuarios_satisfechos'] }}</td>
                            <td>{{ $row['usuarios_insatisfechos'] }}</td>
                            <td>{{ $row['usuarios_neutros'] }}</td>
                            <td>{{ $row['total'] }}</td>
                            <td>{{ number_format($row['mejora'], 2, '.', '') }}</td>
                            <td>{{ number_format($row['indicador'], 2, '.', '') }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="2"><strong>Total</strong></td>
                        <td><strong>{{ number_format($averages['usuarios_satisfechos'], 2, '.', '') }}</strong></td>
                        <td><strong>{{ number_format($averages['usuarios_insatisfechos'], 2, '.', '') }}</strong></td>
                        <td><strong>{{ number_format($averages['usuarios_neutros'], 2, '.', '') }}</strong></td>
                        <td><strong>{{ number_format($averages['total'], 2, '.', '') }}</strong></td>
                        <td><strong>{{ number_format($averages['mejora'], 2, '.', '') }}</strong></td>
                        <td><strong>{{ number_format($averages['indicador'], 2, '.', '') }}</strong></td>
                    </tr>
                </tbody>
            </table>

            <h3 class="report-subheading report-subheading--shift-right">Porcentaje de usuarios satisfechos</h3>
            <div class="chart-shell satisfied-users-chart">
                <img src="{{ $chartImages['satisfied_users_percentage'] }}" alt="% Usuarios satisfechos" class="chart-image">
            </div>
            <p class="chart-caption">Gr&aacute;fico 10. Porcentaje de Usuarios Satisfechos</p>
        </section>

        <section class="page page-with-decor">
            @include('reportes.pdf.parciales.page-decor')
            @php
                $questionPercentages = [];

                foreach (($report['questions'] ?? []) as $questionData) {
                    $questionPercentages[(int) ($questionData['number'] ?? 0)] = $questionData['satisfaction']['satisfied_percentage'] ?? 0;
                }

                $formatConclusionPercentage = static function (float|int $value): string {
                    $formatted = number_format((float) $value, 2, '.', '');

                    return rtrim(rtrim($formatted, '0'), '.');
                };

                $scopeEntityName = match ($reportType ?? null) {
                    'individual' => mb_strtoupper((string) ($dependencyName ?? 'DEPENDENCIA SELECCIONADA'), 'UTF-8'),
                    'process' => mb_strtoupper((string) ($processName ?? 'PROCESO SELECCIONADO'), 'UTF-8'),
                    default => 'FORMA GENERAL',
                };

                $conclusionTitle = match ($reportType ?? null) {
                    'individual' => 'CONCLUSIONES DE LA MEDICION DE LA SATISFACCION DE LOS USUARIOS DE '.$scopeEntityName.' ('.$coverYear.')',
                    'process' => 'CONCLUSIONES DE LA MEDICION DE LA SATISFACCION DE LOS USUARIOS DE '.$scopeEntityName.' ('.$coverYear.')',
                    default => 'CONCLUSIONES DE LA MEDICION DE LA SATISFACCION DE LOS USUARIOS DE FORMA GENERAL ('.$coverYear.')',
                };

                $conclusionProvider = match ($reportType ?? null) {
                    'individual' => 'por parte de la dependencia '.$scopeEntityName,
                    'process' => 'por parte de la oficina '.$scopeEntityName,
                    default => 'en todos los procesos',
                };

                $conclusionLocation = match ($reportType ?? null) {
                    'individual' => 'en la dependencia '.$scopeEntityName,
                    'process' => 'en la oficina '.$scopeEntityName,
                    default => 'en todos los procesos',
                };

                $surveyCount = (int) ($report['totals']['survey_count'] ?? 0);
                $surveyLabel = $surveyCount === 1 ? 'usuario' : 'usuarios';
                $neutralAnswersPercentage = $formatConclusionPercentage(
                    $report['indicators']['global']['neutral_answer_percentage'] ?? 0
                );
            @endphp

            <div class="page-header">
                <h2 class="report-section-title report-section-title--shift-right report-section-title--compact report-section-block--aligned">
                    {{ $conclusionTitle }}
                </h2>
            </div>

            <div class="conclusion-box">
                <div class="conclusion-copy">
                    <p class="report-section-text">
                        El numero de usuarios encuestados durante el {{ $quarterLabel }} de {{ $coverYear }} fue de {{ $surveyCount }} {{ $surveyLabel }} a los cuales se les presto el servicio {{ $conclusionProvider }}.
                    </p>
                    <p class="report-section-text">
                        El {{ $formatConclusionPercentage($questionPercentages[2] ?? 0) }}% se siente satisfecho con la atencion del funcionario {{ $conclusionLocation }}, durante el {{ $quarterLabel }} de {{ $coverYear }}.
                    </p>
                    <p class="report-section-text">
                        El {{ $formatConclusionPercentage($questionPercentages[1] ?? 0) }}% se siente satisfecho con el servicio prestado {{ $conclusionLocation }}, durante el {{ $quarterLabel }} de {{ $coverYear }}.
                    </p>
                    <p class="report-section-text">
                        El {{ $formatConclusionPercentage($questionPercentages[3] ?? 0) }}% sintio que el servicio lleno sus expectativas {{ $conclusionLocation }}, durante el {{ $quarterLabel }} de {{ $coverYear }}.
                    </p>
                    <p class="report-section-text">
                        El {{ $formatConclusionPercentage($questionPercentages[4] ?? 0) }}% se siente satisfecho con la eficacia y la oportunidad del servicio prestado {{ $conclusionLocation }}, durante el {{ $quarterLabel }} de {{ $coverYear }}.
                    </p>
                    <p class="report-section-text">
                        El {{ $formatConclusionPercentage($questionPercentages[5] ?? 0) }}% se siente satisfecho con las condiciones locativas {{ $conclusionLocation }}, durante el {{ $quarterLabel }} de {{ $coverYear }}.
                    </p>
                    <p class="report-section-text">
                        El {{ $formatConclusionPercentage($questionPercentages[6] ?? 0) }}% se siente satisfecho con el lenguaje usado por los funcionarios {{ $conclusionLocation }}, durante el {{ $quarterLabel }} de {{ $coverYear }}.
                    </p>
                    <p class="report-section-text">
                        En torno a los resultados obtenidos se presento un {{ $neutralAnswersPercentage }}% donde los usuarios perciben un servicio ni satisfactorio ni insatisfactorio, teniendo en cuenta esta informacion se adelantaran acciones para mejorar el nivel de satisfaccion de estos usuarios.
                    </p>
                </div>

                @if ($signature)
                    <div class="signature-block">
                        <p class="signature-name">{{ $signature['name'] }}</p>
                        <p class="signature-title">{{ $signature['title'] }}</p>
                        <p class="signature-scope">{{ $signature['scope'] }}</p>
                    </div>
                @endif
            </div>
        </section>
    </body>
</html>
