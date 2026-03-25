@php
    $cards = [
        [
            'level' => 'processes',
            'title' => 'Procesos',
            'description' => 'Comparativas de cantidad, satisfaccion y ranking de procesos.',
            'route' => route('statistics.processes'),
        ],
        [
            'level' => 'dependencies',
            'title' => 'Dependencias',
            'description' => 'Comparativas y consolidado de dependencias dentro del alcance visible.',
            'route' => route('statistics.dependencies'),
        ],
        [
            'level' => 'services',
            'title' => 'Servicios',
            'description' => 'Comparativas, satisfaccion y servicios mas evaluados.',
            'route' => route('statistics.services'),
        ],
    ];
@endphp

<x-app-layout>
    <div class="ms-content-shell ms-statistics-shell">
        <x-generals.top-bar
            title="Estadisticas"
            description="Modulo independiente para analizar encuestas por procesos, dependencias y servicios."
        />

        <div class="ms-panel-body">
            <section class="ms-report-card ms-report-card-accent">
                <div class="ms-report-card-header">
                    <h2>Modulos disponibles</h2>
                    <p>Las vistas habilitadas dependen del rol y del alcance organizacional del usuario autenticado.</p>
                </div>

                <div class="ms-statistics-entry-grid">
                    @foreach ($cards as $card)
                        @continue(! in_array($card['level'], $nivelesPermitidos, true))

                        <a href="{{ $card['route'] }}" class="ms-statistics-entry-card">
                            <span class="ms-statistics-entry-kicker">Estadisticas</span>
                            <strong>{{ $card['title'] }}</strong>
                            <p>{{ $card['description'] }}</p>
                            <span class="ms-statistics-entry-link">Abrir modulo</span>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
