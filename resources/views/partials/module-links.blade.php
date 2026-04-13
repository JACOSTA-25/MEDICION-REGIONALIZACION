@php
    $user = auth()->user();
@endphp

@if ($user->puedeAccederReportesGenerales())
    <x-nav-link :href="route('reports.general')" :active="request()->routeIs('reports.general')">
        {{ __('Reporte general') }}
    </x-nav-link>
@endif

@if ($user->puedeAccederReportesProceso())
    <x-nav-link :href="route('reports.process')" :active="request()->routeIs('reports.process')">
        {{ __('Reporte por proceso') }}
    </x-nav-link>
@endif

@if ($user->puedeAccederReportesIndividuales())
    <x-nav-link :href="route('reports.individual')" :active="request()->routeIs('reports.individual')">
        {{ __('Reporte individual') }}
    </x-nav-link>
@endif

@if ($user->puedeAccederModuloEstadisticas())
    <x-nav-link :href="route('statistics.index')" :active="request()->routeIs('statistics.*')">
        {{ __('Estadisticas') }}
    </x-nav-link>
@endif

<x-nav-link :href="route('survey.qr')" :active="request()->routeIs('survey.qr')">
    {{ __('QR de encuesta') }}
</x-nav-link>

@if ($user->puedeAccederModuloUsuarios())
    <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.index')">
        {{ __('Usuarios') }}
    </x-nav-link>
@endif

@if ($user->puedeAccederModuloEstructuraOrganizacional())
    <x-nav-link :href="route('process-dependency.index')" :active="request()->routeIs('process-dependency.*')">
        {{ __('Procesos y dependencias') }}
    </x-nav-link>
@endif
