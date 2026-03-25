<x-app-layout>
    <div class="ms-content-shell ms-statistics-shell">
        <x-generals.top-bar
            :title="$title"
            :description="$description"
        >
            <x-slot name="actions">
                <div class="ms-statistics-level-nav">
                    @if (in_array('processes', $nivelesPermitidos, true))
                        <a href="{{ route('statistics.processes') }}" class="{{ $level === 'processes' ? 'is-active' : '' }}">Procesos</a>
                    @endif
                    @if (in_array('dependencies', $nivelesPermitidos, true))
                        <a href="{{ route('statistics.dependencies') }}" class="{{ $level === 'dependencies' ? 'is-active' : '' }}">Dependencias</a>
                    @endif
                    @if (in_array('services', $nivelesPermitidos, true))
                        <a href="{{ route('statistics.services') }}" class="{{ $level === 'services' ? 'is-active' : '' }}">Servicios</a>
                    @endif
                </div>
            </x-slot>
        </x-generals.top-bar>

        <div class="ms-panel-body">
            <div
                id="statistics-react-root"
                data-level="{{ $level }}"
                data-endpoint="{{ route('statistics.data.show', ['level' => $level]) }}"
            ></div>
        </div>
    </div>

    @vite(['resources/js/estadisticas/index.jsx'])
</x-app-layout>
