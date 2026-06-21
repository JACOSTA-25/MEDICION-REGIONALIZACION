@php
    $user = auth()->user();
@endphp

@if ($user->puedeAccederModuloReportes())
    <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
        {{ __('Reportes') }}
    </x-nav-link>
@endif

@if ($user->puedeAccederModuloEstadisticas())
    <x-nav-link :href="route('statistics.index')" :active="request()->routeIs('statistics.*')">
        {{ __('Estadisticas') }}
    </x-nav-link>
@endif

@if ($user->puedeAccederModuloUsuarios())
    <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.index')">
        {{ __('Usuarios') }}
    </x-nav-link>
@endif

@if ($user->puedeAccederModuloProgramas())
    <x-nav-link :href="route('programs.index')" :active="request()->routeIs('programs.*')">
        {{ __('Programas') }}
    </x-nav-link>
@endif

@if ($user->puedeAccederModuloEstructuraOrganizacional())
    <x-nav-link :href="route('process-dependency.index')" :active="request()->routeIs('process-dependency.*')">
        {{ __('Procesos y dependencias') }}
    </x-nav-link>
@endif
