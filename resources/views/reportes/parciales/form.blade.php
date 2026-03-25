<div class="ms-content-shell">
    <x-generals.top-bar
        :title="$title"
        :description="$description"
    />

    <div class="ms-panel-body">
        <div class="ms-report-grid" data-report-shell>
            <section class="ms-report-card">
                <div class="ms-report-card-header">
                    <h2>Filtros del reporte</h2>
                    <p>{{ $summary }}</p>
                </div>

                <form
                    method="GET"
                    action="{{ route(request()->route()->getName()) }}"
                    class="ms-report-form"
                    data-report-filter-form
                    @if ($showDependencySelect)
                        data-dependencias-endpoint="{{ route('survey.catalogs.dependencias') }}"
                    @endif
                >
                    <div class="ms-report-fields">
                        <div class="ms-field">
                            <label for="trimestre">Trimestre</label>
                            <select id="trimestre" name="trimestre">
                                <option value="">Seleccione un trimestre</option>
                                @foreach ($quarters as $quarter)
                                    <option value="{{ $quarter->quarter_number }}" @selected((string) $selectedQuarterNumber === (string) $quarter->quarter_number)>
                                        {{ $quarter->label() }}
                                    </option>
                                @endforeach
                            </select>

                            @if ($selectedQuarterPeriod !== '')
                                <small class="ms-field-help">Periodo {{ $quarterYear }}: {{ $selectedQuarterPeriod }}</small>
                            @else
                                <small class="ms-field-help">Selecciona uno de los cuatro trimestres configurados para {{ $quarterYear }}.</small>
                            @endif
                        </div>

                        @if ($showProcessSelect)
                            <div class="ms-field">
                                <label for="id_proceso">Proceso</label>
                                <select
                                    id="id_proceso"
                                    name="id_proceso"
                                    data-process-select
                                    {{ $selectedProcessLocked ? 'disabled' : '' }}
                                >
                                    <option value="">Seleccione un proceso</option>
                                    @foreach ($procesos as $proceso)
                                        <option value="{{ $proceso->id_proceso }}" @selected((string) $selectedProcesoId === (string) $proceso->id_proceso)>
                                            {{ $proceso->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($selectedProcessLocked)
                                    <input type="hidden" name="id_proceso" value="{{ $selectedProcesoId }}">
                                @endif
                            </div>
                        @endif

                        @if ($showDependencySelect)
                            <div class="ms-field">
                                <label for="id_dependencia">Dependencia</label>
                                <select
                                    id="id_dependencia"
                                    name="id_dependencia"
                                    data-dependency-select
                                    data-selected="{{ $selectedDependenciaId }}"
                                    {{ $dependencias->isEmpty() || $selectedDependencyLocked ? 'disabled' : '' }}
                                >
                                    <option value="">Seleccione una dependencia</option>
                                    @foreach ($dependencias as $dependencia)
                                        <option value="{{ $dependencia->id_dependencia }}" @selected((string) $selectedDependenciaId === (string) $dependencia->id_dependencia)>
                                            {{ $dependencia->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($selectedDependencyLocked)
                                    <input type="hidden" name="id_dependencia" value="{{ $selectedDependenciaId }}">
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="ms-form-actions">
                        <button type="submit" class="ms-btn ms-btn-primary">
                            Ver reporte
                        </button>

                        @if ($pdfUrl)
                            <a href="{{ $pdfUrl }}" class="ms-btn ms-btn-secondary">
                                Descargar PDF
                            </a>
                        @endif
                    </div>

                    @if ($filterError)
                        <div class="ms-inline-alert">
                            {{ $filterError }}
                        </div>
                    @else
                        <p class="ms-form-note">
                            Clasificacion de satisfaccion: Mala (1-2), Intermedia (3) y Buena (4-5).
                        </p>
                    @endif
                </form>
            </section>

            <aside class="ms-report-card ms-report-card-accent">
                <div class="ms-report-card-header">
                    <h2>Resumen del filtro</h2>
                    <p>{{ $selectionSummary }}</p>
                </div>

                @if ($report)
                    <div class="ms-stat-grid">
                        <div class="ms-stat-card ms-stat-card-compact">
                            <span class="ms-stat-label">Encuestas</span>
                            <strong>{{ $report['totals']['survey_count'] }}</strong>
                            <small>Usuarios encuestados en el periodo</small>
                        </div>

                        <div class="ms-stat-card ms-stat-card-compact">
                            <span class="ms-stat-label">Respuestas</span>
                            <strong>{{ $report['totals']['answer_count'] }}</strong>
                            <small>Total de respuestas (6 preguntas por encuesta)</small>
                        </div>

                        <div class="ms-stat-card ms-stat-card-compact">
                            <span class="ms-stat-label">Indicador global</span>
                            <strong>{{ $report['indicators']['global']['satisfaction_percentage'] }}%</strong>
                            <small>Promedio de usuarios satisfechos entre las 6 dimensiones</small>
                        </div>

                        <div class="ms-stat-card ms-stat-card-compact">
                            <span class="ms-stat-label">Satisfaccion total</span>
                            <strong>{{ $report['indicators']['global']['satisfaction_answer_percentage'] }}%</strong>
                            <small>Sobre el total de respuestas registradas</small>
                        </div>
                    </div>
                @else
                    <div class="ms-inline-alert ms-inline-alert-soft">
                        Genera el reporte para ver estadisticas consolidadas y exportar el PDF.
                    </div>
                @endif
            </aside>
        </div>

        @if ($report)
            <div class="ms-report-results ms-report-accordion">
                <details class="ms-report-card ms-report-card-collapsible">
                    <summary class="ms-report-card-summary">
                        <span class="ms-report-card-summary-copy">
                            <span class="ms-report-card-summary-title">Distribucion poblacional</span>
                            <span class="ms-report-card-summary-description">Poblacion atendida por programa y estamento.</span>
                        </span>
                        <span class="ms-report-card-summary-indicator" aria-hidden="true"></span>
                    </summary>

                    <div class="ms-report-card-body">
                        <div class="ms-table-shell">
                            <table class="ms-data-table">
                                <thead>
                                    <tr>
                                        <th>Programa</th>
                                        <th>Encuestas</th>
                                        <th>Porcentaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($report['tables']['by_program'] as $row)
                                        <tr>
                                            <td>{{ $row['programa'] }}</td>
                                            <td>{{ $row['encuestas'] }}</td>
                                            <td>{{ $row['porcentaje'] }}%</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3">Sin registros en el trimestre seleccionado.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="ms-table-shell">
                            <table class="ms-data-table">
                                <thead>
                                    <tr>
                                        <th>Estamento</th>
                                        <th>Encuestas</th>
                                        <th>Porcentaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($report['tables']['by_estamento'] as $row)
                                        <tr>
                                            <td>{{ $row['estamento'] }}</td>
                                            <td>{{ $row['encuestas'] }}</td>
                                            <td>{{ $row['porcentaje'] }}%</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3">Sin registros en el trimestre seleccionado.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </details>

                <details class="ms-report-card ms-report-card-collapsible">
                    <summary class="ms-report-card-summary">
                        <span class="ms-report-card-summary-copy">
                            <span class="ms-report-card-summary-title">Servicios prestados</span>
                            <span class="ms-report-card-summary-description">Conteo de encuestas asociadas a cada servicio.</span>
                        </span>
                        <span class="ms-report-card-summary-indicator" aria-hidden="true"></span>
                    </summary>

                    <div class="ms-report-card-body">
                        <div class="ms-table-shell">
                            <table class="ms-data-table">
                                <thead>
                                    <tr>
                                        <th>Servicio</th>
                                        <th>Encuestas</th>
                                        <th>Porcentaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($report['tables']['services'] as $row)
                                        <tr>
                                            <td>{{ $row['servicio'] }}</td>
                                            <td>{{ $row['encuestas'] }}</td>
                                            <td>{{ $row['porcentaje'] }}%</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3">Sin registros en el trimestre seleccionado.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </details>

                <details class="ms-report-card ms-report-card-collapsible">
                    <summary class="ms-report-card-summary">
                        <span class="ms-report-card-summary-copy">
                            <span class="ms-report-card-summary-title">Resultado por pregunta</span>
                            <span class="ms-report-card-summary-description">Frecuencia por valor (1 a 5) y porcentaje de satisfaccion por dimension.</span>
                        </span>
                        <span class="ms-report-card-summary-indicator" aria-hidden="true"></span>
                    </summary>

                    <div class="ms-report-card-body">
                        <div class="ms-table-shell">
                            <table class="ms-data-table">
                                <thead>
                                    <tr>
                                        <th>Pregunta</th>
                                        <th>Deficiente</th>
                                        <th>Malo</th>
                                        <th>Regular</th>
                                        <th>Bueno</th>
                                        <th>Excelente</th>
                                        <th>% Satisfechos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['questions'] as $question)
                                        <tr>
                                            <td>
                                                <strong>{{ $question['number'] }}.</strong>
                                                {{ $question['dimension'] }}
                                            </td>
                                            @foreach ($question['frequencies'] as $frequency)
                                                <td>
                                                    {{ $frequency['frequency'] }}
                                                    <span>{{ $frequency['percentage'] }}%</span>
                                                </td>
                                            @endforeach
                                            <td>{{ $question['satisfaction']['satisfied_percentage'] }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </details>

                <details class="ms-report-card ms-report-card-collapsible">
                    <summary class="ms-report-card-summary">
                        <span class="ms-report-card-summary-copy">
                            <span class="ms-report-card-summary-title">Consolidado de satisfaccion</span>
                            <span class="ms-report-card-summary-description">Usuarios satisfechos, neutros e insatisfechos por dimension.</span>
                        </span>
                        <span class="ms-report-card-summary-indicator" aria-hidden="true"></span>
                    </summary>

                    <div class="ms-report-card-body">
                        <div class="ms-table-shell">
                            <table class="ms-data-table">
                                <thead>
                                    <tr>
                                        <th>Dimension</th>
                                        <th>Satisfechos</th>
                                        <th>Neutros</th>
                                        <th>Insatisfechos</th>
                                        <th>% Satisfechos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['tables']['satisfaction_consolidated'] as $row)
                                        <tr>
                                            <td>{{ $row['dimension'] }}</td>
                                            <td>{{ $row['satisfechos'] }}</td>
                                            <td>{{ $row['neutros'] }}</td>
                                            <td>{{ $row['insatisfechos'] }}</td>
                                            <td>{{ $row['porcentaje_satisfechos'] }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </details>

                @if ($report['observations'] !== [])
                    <details class="ms-report-card ms-report-card-collapsible">
                        <summary class="ms-report-card-summary">
                            <span class="ms-report-card-summary-copy">
                                <span class="ms-report-card-summary-title">Observaciones recientes</span>
                                <span class="ms-report-card-summary-description">Se muestran hasta diez comentarios del periodo consultado.</span>
                            </span>
                            <span class="ms-report-card-summary-indicator" aria-hidden="true"></span>
                        </summary>

                        <div class="ms-report-card-body">
                            <div class="ms-observations-list">
                                @foreach ($report['observations'] as $observation)
                                    <p>{{ $observation }}</p>
                                @endforeach
                            </div>
                        </div>
                    </details>
                @endif
            </div>
        @endif
    </div>
</div>
