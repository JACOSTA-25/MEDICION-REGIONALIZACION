@php
    $user = auth()->user();
@endphp

@if ($user->canAccessGeneralReports())
    <x-nav-link :href="route('reports.general')" :active="request()->routeIs('reports.general')">
        {{ __('Reporte general') }}
    </x-nav-link>
@endif

@if ($user->canAccessProcessReports())
    <x-nav-link :href="route('reports.process')" :active="request()->routeIs('reports.process')">
        {{ __('Reporte por proceso') }}
    </x-nav-link>
@endif

@if ($user->canAccessIndividualReports())
    <x-nav-link :href="route('reports.individual')" :active="request()->routeIs('reports.individual')">
        {{ __('Reporte individual') }}
    </x-nav-link>
@endif

@if ($user->canAccessUsersModule())
    <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.index')">
        {{ __('Usuarios') }}
    </x-nav-link>
@endif
