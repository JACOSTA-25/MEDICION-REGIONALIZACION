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
        @endphp
        <style>
            @page {
                size: A4 portrait;
                margin: 0;
            }

            body {
                font-family: DejaVu Sans, Arial, sans-serif;
                margin: 0;
                color: #111827;
                font-size: 12px;
            }

            .page {
                page-break-after: always;
                position: relative;
                box-sizing: border-box;
                padding: 16mm 12mm 12mm;
            }

            .page:last-child {
                page-break-after: auto;
            }

            .page-with-decor {
                padding-top: 78px;
                padding-right: 12mm;
                padding-bottom: 58px;
                padding-left: 58px;
            }

            .decor-header {
                position: absolute;
                top: -3px;
                left: 50%;
                transform: translateX(-50%);
                width: 82%;
                max-height: 72px;
            }

            .decor-sidebar {
                position: absolute;
                top: 0;
                left: 0;
                width: 36px;
                height: 297mm;
            }

            .decor-footer {
                position: absolute;
                left: 0;
                bottom: 0;
                width: 240px;
                max-height: 52px;
            }

            .page-header {
                border-bottom: 1px solid #e5e7eb;
                margin-bottom: 10px;
                padding-bottom: 6px;
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
                background: #f3f4f6;
                text-align: left;
            }

            .chart-shell {
                border: 1px solid #d1d5db;
                border-radius: 10px;
                padding: 6px;
                margin-bottom: 10px;
            }

            .chart-image {
                width: 100%;
                height: auto;
                display: block;
            }

            .split-two {
                display: grid;
                gap: 10px;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .muted {
                color: #6b7280;
                font-size: 11px;
            }

            .conclusion-box {
                border: 1px dashed #9ca3af;
                border-radius: 8px;
                padding: 12px;
                min-height: 240px;
                background: #f9fafb;
            }
        </style>
    </head>
    <body>
        <section class="page">
            <div class="cover-card">
                <h1>{{ $title }}</h1>
                <p>{{ $description }}</p>
                <p class="muted" style="margin-top: 10px;">
                    Medicion de la satisfaccion de usuarios de los servicios institucionales.
                </p>
            </div>

            <h2 style="margin-bottom: 8px;">Contexto del reporte</h2>
            <div class="context">
                @foreach ($contextRows as $row)
                    <div class="context-item">
                        <strong>{{ $row['label'] }}</strong>
                        <span>{{ $row['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="page page-with-decor">
            @include('modules.pdf.partials.page-decor')
            <div class="page-header">
                <h2>Numero de usuarios encuestados</h2>
            </div>

            @php
                $scopeTable = $report['tables']['scope_population'];
            @endphp

            <table>
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
                            <td colspan="2">Sin datos para el rango de fechas seleccionado.</td>
                        </tr>
                    @endforelse
                    <tr>
                        <td><strong>Total general</strong></td>
                        <td><strong>{{ $scopeTable['total_general'] }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="page page-with-decor">
            @include('modules.pdf.partials.page-decor')
            <div class="page-header">
                <h2>Analisis de poblacion encuestada</h2>
                <p class="muted">Distribucion de usuarios por programa y por estamento.</p>
            </div>

            <div class="split-two">
                <div>
                    <h3>Programas atendidos</h3>
                    <div class="chart-shell">
                        <img src="{{ $chartImages['population_by_program'] }}" alt="Programas atendidos" class="chart-image">
                    </div>
                </div>
                <div>
                    <h3>Estamentos atendidos</h3>
                    <div class="chart-shell">
                        <img src="{{ $chartImages['population_by_estamento'] }}" alt="Estamentos atendidos" class="chart-image">
                    </div>
                </div>
            </div>
        </section>

        @foreach ($report['charts']['question_results'] as $index => $chart)
            <section class="page page-with-decor">
                @include('modules.pdf.partials.page-decor')
                <div class="page-header">
                    <h2>{{ $chart['title'] }}</h2>
                    <p class="muted">{{ $chart['subtitle'] }}</p>
                </div>

                <div class="chart-shell">
                    <img src="{{ $chartImages['question_results'][$index] ?? '' }}" alt="{{ $chart['title'] }}" class="chart-image">
                </div>
            </section>
        @endforeach

        <section class="page page-with-decor">
            @include('modules.pdf.partials.page-decor')
            <div class="page-header">
                <h2>Consolidado de la medicion de la satisfaccion de los usuarios</h2>
            </div>

            @php
                $consolidated = $report['tables']['measurement_consolidated'];
                $averages = $consolidated['averages'];
            @endphp

            <table>
                <thead>
                    <tr>
                        <th>Pregunta</th>
                        <th>Categoria</th>
                        <th>Numero de usuarios satisfecho</th>
                        <th>Numero de usuarios insatisfechos</th>
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
                            <td>{{ number_format($row['mejora'], 5, '.', '') }}</td>
                            <td>{{ number_format($row['indicador'], 5, '.', '') }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="2"><strong>Total</strong></td>
                        <td><strong>{{ number_format($averages['usuarios_satisfechos'], 5, '.', '') }}</strong></td>
                        <td><strong>{{ number_format($averages['usuarios_insatisfechos'], 5, '.', '') }}</strong></td>
                        <td><strong>{{ number_format($averages['usuarios_neutros'], 5, '.', '') }}</strong></td>
                        <td><strong>{{ number_format($averages['total'], 5, '.', '') }}</strong></td>
                        <td><strong>{{ number_format($averages['mejora'], 5, '.', '') }}</strong></td>
                        <td><strong>{{ number_format($averages['indicador'], 5, '.', '') }}</strong></td>
                    </tr>
                </tbody>
            </table>

            <h3 style="margin-top: 10px;">% Usuarios satisfechos</h3>
            <div class="chart-shell">
                <img src="{{ $chartImages['satisfied_users_percentage'] }}" alt="% Usuarios satisfechos" class="chart-image">
            </div>
        </section>

        <section class="page page-with-decor">
            @include('modules.pdf.partials.page-decor')
            <div class="page-header">
                <h2>Conclusion de la satisfaccion</h2>
            </div>

            <div class="conclusion-box">
                <p class="muted">
                    Seccion reservada para la construccion de conclusiones en la siguiente iteracion.
                </p>
            </div>
        </section>
    </body>
</html>
