@php
    $user = auth()->user();
    $surveyLink = route('survey.create');

    $modules = [
        [
            'visible' => $user->canAccessGeneralReports(),
            'title' => 'Reporte general',
            'description' => 'Modulo de reporteria general consolidada.',
            'route' => route('reports.general'),
        ],
        [
            'visible' => $user->canAccessProcessReports(),
            'title' => 'Reporte por proceso',
            'description' => 'Modulo para informes por proceso.',
            'route' => route('reports.process'),
        ],
        [
            'visible' => $user->canAccessIndividualReports(),
            'title' => 'Reporte individual',
            'description' => 'Modulo para informe individual por dependencia.',
            'route' => route('reports.individual'),
        ],
        [
            'visible' => $user->canAccessUsersModule(),
            'title' => 'Gestion de usuarios',
            'description' => 'Modulo de administracion de usuarios.',
            'route' => route('users.index'),
        ],
        [
            'visible' => $user->canAccessProcessDependencyModule(),
            'title' => 'Procesos y dependencias',
            'description' => 'Modulo para administrar el catalogo organizacional.',
            'route' => route('process-dependency.index'),
        ],
    ];
@endphp

<x-app-layout>
    <div class="ms-content-shell">
        <x-generals.top-bar
            title="Inicio"
            description="Accesos disponibles segun el rol activo: {{ $user->rol }}"
        />

        <div class="ms-panel-body">
            <div class="ms-dashboard-stack">
                <section class="ms-report-card">
                    <div class="ms-report-card-header">
                        <h2>Enlace fijo de la encuesta</h2>
                        <p>El formulario publico siempre es el mismo. Copia el enlace o abre la encuesta directamente.</p>
                    </div>

                    <div class="ms-link-panel">
                        <label for="dashboard-survey-link">URL publica</label>
                        <input
                            id="dashboard-survey-link"
                            type="text"
                            readonly
                            value="{{ $surveyLink }}"
                        >

                        <div class="ms-inline-actions">
                            <button type="button" class="ms-btn ms-btn-secondary" data-copy-trigger data-copy-target="#dashboard-survey-link">
                                Copiar enlace
                            </button>
                            <a href="{{ $surveyLink }}" target="_blank" rel="noopener noreferrer" class="ms-btn ms-btn-primary">
                                Abrir encuesta
                            </a>
                        </div>
                    </div>
                </section>

                <section class="ms-report-card ms-report-card-accent">
                    <div class="ms-report-card-header">
                        <h2>Navegacion del sistema</h2>
                        <p>Los modulos se consultan desde el menu lateral para evitar accesos duplicados en esta pantalla.</p>
                    </div>

                    @php
                        $availableModules = collect($modules)->where('visible', true)->pluck('title')->all();
                    @endphp

                    @if ($availableModules !== [])
                        <div class="ms-inline-checklist">
                            <p>Modulos habilitados para este usuario:</p>
                            <ul>
                                @foreach ($availableModules as $moduleName)
                                    <li>{{ $moduleName }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="ms-inline-alert">
                            Tu usuario no tiene modulos habilitados. Contacta al administrador.
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
