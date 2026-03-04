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

                        <div class="ms-field">
                            <label for="desde">Desde</label>
                            <input id="desde" name="desde" type="date" value="{{ $selectedFrom }}">
                        </div>

                        <div class="ms-field">
                            <label for="hasta">Hasta</label>
                            <input id="hasta" name="hasta" type="date" value="{{ $selectedTo }}">
                        </div>
                    </div>

                    <div class="ms-form-actions">
                        <button type="submit" class="ms-btn ms-btn-primary">
                            Ver reporte
                        </button>

                        @if ($pdfUrl)
                            <a href="{{ $pdfUrl }}" target="_blank" rel="noopener noreferrer" class="ms-btn ms-btn-secondary">
                                Abrir version PDF
                            </a>
                        @endif
                    </div>

                    @if ($filterError)
                        <div class="ms-inline-alert">
                            {{ $filterError }}
                        </div>
                    @else
                        <p class="ms-form-note">
                            Cada respuesta se clasifica asi: 1-2 = satisfaccion mala, 3 = intermedia y 4-5 = buena.
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
                        <div class="ms-stat-card">
                            <span class="ms-stat-label">Encuestas</span>
                            <strong>{{ $report['total_responses'] }}</strong>
                            <small>Total de formularios registrados</small>
                        </div>

                        <div class="ms-stat-card">
                            <span class="ms-stat-label">Satisfaccion buena</span>
                            <strong>{{ $report['overall']['percentages']['buena'] }}%</strong>
                            <small>{{ $report['overall']['counts']['buena'] }} respuestas de {{ $report['total_answers'] }}</small>
                        </div>

                        <div class="ms-stat-card">
                            <span class="ms-stat-label">Satisfaccion intermedia</span>
                            <strong>{{ $report['overall']['percentages']['intermedia'] }}%</strong>
                            <small>{{ $report['overall']['counts']['intermedia'] }} respuestas de {{ $report['total_answers'] }}</small>
                        </div>

                        <div class="ms-stat-card">
                            <span class="ms-stat-label">Satisfaccion mala</span>
                            <strong>{{ $report['overall']['percentages']['mala'] }}%</strong>
                            <small>{{ $report['overall']['counts']['mala'] }} respuestas de {{ $report['total_answers'] }}</small>
                        </div>
                    </div>
                @else
                    <div class="ms-inline-alert ms-inline-alert-soft">
                        Genera el reporte para ver los porcentajes consolidados y la version exportable.
                    </div>
                @endif
            </aside>
        </div>

        @if ($report)
            <div class="ms-report-results">
                <section class="ms-report-card">
                    <div class="ms-report-card-header">
                        <h2>Resultados por pregunta</h2>
                        <p>Las estadisticas se calculan sobre cada una de las seis preguntas del formulario.</p>
                    </div>

                    <div class="ms-table-shell">
                        <table class="ms-data-table">
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
                                        <td>
                                            <strong>{{ $question['number'] }}.</strong>
                                            {{ $question['label'] }}
                                        </td>
                                        <td>
                                            {{ $question['counts']['mala'] }}
                                            <span>{{ $question['percentages']['mala'] }}%</span>
                                        </td>
                                        <td>
                                            {{ $question['counts']['intermedia'] }}
                                            <span>{{ $question['percentages']['intermedia'] }}%</span>
                                        </td>
                                        <td>
                                            {{ $question['counts']['buena'] }}
                                            <span>{{ $question['percentages']['buena'] }}%</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="ms-report-card">
                    <div class="ms-report-card-header">
                        <h2>{{ $report['breakdown_title'] }}</h2>
                        <p>
                            @if ($report['breakdown'] !== [])
                                Distribucion de resultados segun el alcance del reporte.
                            @else
                                No hay registros dentro de ese rango para construir el consolidado secundario.
                            @endif
                        </p>
                    </div>

                    @if ($report['breakdown'] !== [])
                        <div class="ms-table-shell">
                            <table class="ms-data-table">
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
                                    @foreach ($report['breakdown'] as $item)
                                        <tr>
                                            <td>{{ $item['name'] }}</td>
                                            <td>{{ $item['responses'] }}</td>
                                            <td>{{ $item['counts']['mala'] }} <span>{{ $item['percentages']['mala'] }}%</span></td>
                                            <td>{{ $item['counts']['intermedia'] }} <span>{{ $item['percentages']['intermedia'] }}%</span></td>
                                            <td>{{ $item['counts']['buena'] }} <span>{{ $item['percentages']['buena'] }}%</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="ms-inline-alert ms-inline-alert-soft">
                            No se encontraron datos asociados para este filtro.
                        </div>
                    @endif
                </section>

                @if ($report['observations'] !== [])
                    <section class="ms-report-card">
                        <div class="ms-report-card-header">
                            <h2>Observaciones recientes</h2>
                            <p>Se muestran hasta diez comentarios registrados dentro del periodo consultado.</p>
                        </div>

                        <div class="ms-observations-list">
                            @foreach ($report['observations'] as $observation)
                                <p>{{ $observation }}</p>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
        @endif
    </div>
</div>
