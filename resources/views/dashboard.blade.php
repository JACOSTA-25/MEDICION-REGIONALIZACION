@php
    $user = auth()->user();

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
    ];
@endphp

<x-app-layout>
    <div class="ms-content-shell">
        <x-generals.top-bar
            title="Dashboard"
            description="Accesos disponibles segun el rol activo: {{ $user->rol }}"
        />

        <div class="ms-panel-body">
            <div class="ms-module-grid">
                @forelse (collect($modules)->where('visible', true) as $module)
                    <a href="{{ $module['route'] }}" class="ms-module-card">
                        <h3>{{ $module['title'] }}</h3>
                        <p>{{ $module['description'] }}</p>
                    </a>
                @empty
                    <div class="ms-module-card" style="border-color:#fbbf24; background:#fffbeb;">
                        <h3 style="color:#92400e;">Sin modulos habilitados</h3>
                        <p style="color:#78350f;">Tu usuario no tiene modulos habilitados. Contacta al administrador.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
