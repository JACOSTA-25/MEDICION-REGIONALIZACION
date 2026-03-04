<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }}</title>
        <style>
            body {
                font-family: DejaVu Sans, Arial, sans-serif;
                margin: 24px;
                color: #111827;
            }

            h1,
            h2 {
                margin: 0;
            }

            h1 {
                font-size: 24px;
            }

            h2 {
                font-size: 18px;
                margin-bottom: 10px;
            }

            p {
                margin: 0;
                line-height: 1.5;
            }

            .sheet {
                display: grid;
                gap: 18px;
            }

            .panel {
                border: 1px solid #d1d5db;
                border-radius: 12px;
                padding: 16px;
            }

            .context {
                display: grid;
                gap: 8px;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }

            .context strong,
            .metrics strong {
                display: block;
                margin-bottom: 4px;
            }

            .metrics {
                display: grid;
                gap: 12px;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }

            .metric {
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                padding: 12px;
                background: #f9fafb;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                font-size: 12px;
            }

            th,
            td {
                border: 1px solid #d1d5db;
                padding: 8px;
                vertical-align: top;
            }

            th {
                background: #f3f4f6;
                text-align: left;
            }

            .observations p + p {
                margin-top: 8px;
            }

            .fallback {
                padding: 12px 14px;
                border-radius: 10px;
                background: #eff6ff;
                border: 1px solid #bfdbfe;
                color: #1d4ed8;
            }

            @media print {
                body {
                    margin: 12px;
                }

                .fallback {
                    display: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="sheet">
            @if ($printFallback)
                <div class="fallback">
                    Esta vista queda lista para imprimir o guardar como PDF desde el navegador.
                </div>
            @endif

            <section class="panel">
                <h1>{{ $title }}</h1>
                <p>{{ $description }}</p>
            </section>

            <section class="panel">
                <h2>Contexto del reporte</h2>
                <div class="context">
                    @foreach ($contextRows as $row)
                        <div>
                            <strong>{{ $row['label'] }}</strong>
                            <span>{{ $row['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="panel">
                <h2>Resumen general</h2>
                <div class="metrics">
                    <div class="metric">
                        <strong>Encuestas</strong>
                        <span>{{ $report['total_responses'] }}</span>
                    </div>
                    <div class="metric">
                        <strong>Satisfaccion buena</strong>
                        <span>{{ $report['overall']['counts']['buena'] }} ({{ $report['overall']['percentages']['buena'] }}%)</span>
                    </div>
                    <div class="metric">
                        <strong>Satisfaccion intermedia</strong>
                        <span>{{ $report['overall']['counts']['intermedia'] }} ({{ $report['overall']['percentages']['intermedia'] }}%)</span>
                    </div>
                    <div class="metric">
                        <strong>Satisfaccion mala</strong>
                        <span>{{ $report['overall']['counts']['mala'] }} ({{ $report['overall']['percentages']['mala'] }}%)</span>
                    </div>
                </div>
            </section>

            <section class="panel">
                <h2>Resultados por pregunta</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Pregunta</th>
                            <th>Mala</th>
                            <th>Intermedia</th>
                            <th>Buena</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['questions'] as $question)
                            <tr>
                                <td>{{ $question['number'] }}. {{ $question['label'] }}</td>
                                <td>{{ $question['counts']['mala'] }} ({{ $question['percentages']['mala'] }}%)</td>
                                <td>{{ $question['counts']['intermedia'] }} ({{ $question['percentages']['intermedia'] }}%)</td>
                                <td>{{ $question['counts']['buena'] }} ({{ $question['percentages']['buena'] }}%)</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            <section class="panel">
                <h2>{{ $report['breakdown_title'] }}</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Elemento</th>
                            <th>Encuestas</th>
                            <th>Mala</th>
                            <th>Intermedia</th>
                            <th>Buena</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['breakdown'] as $item)
                            <tr>
                                <td>{{ $item['name'] }}</td>
                                <td>{{ $item['responses'] }}</td>
                                <td>{{ $item['counts']['mala'] }} ({{ $item['percentages']['mala'] }}%)</td>
                                <td>{{ $item['counts']['intermedia'] }} ({{ $item['percentages']['intermedia'] }}%)</td>
                                <td>{{ $item['counts']['buena'] }} ({{ $item['percentages']['buena'] }}%)</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">No hay registros disponibles para este rango.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

            @if ($report['observations'] !== [])
                <section class="panel observations">
                    <h2>Observaciones recientes</h2>
                    @foreach ($report['observations'] as $observation)
                        <p>{{ $observation }}</p>
                    @endforeach
                </section>
            @endif
        </div>

        @if ($printFallback)
            <script>
                window.addEventListener('load', () => {
                    window.print();
                });
            </script>
        @endif
    </body>
</html>
