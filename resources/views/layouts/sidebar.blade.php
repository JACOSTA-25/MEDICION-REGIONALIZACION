@php
    $user = auth()->user();
@endphp

<aside class="ms-sidebar menu-toggle" id="ms-sidebar">
    <nav>
        <ul class="list-unstyled">
            <li>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'selected' : '' }}">
                    <span class="icon"><img src="{{ asset('assets/icons/home.svg') }}" alt="Inicio"></span>
                    <span>Inicio</span>
                </a>
            </li>

            @if ($user->puedeAccederModuloReportes())
                <li>
                    <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'selected' : '' }}">
                        <span class="icon"><img src="{{ asset('assets/icons/reportes.svg') }}" alt="Reportes"></span>
                        <span>Reportes</span>
                    </a>
                </li>
            @endif

            @if ($user->puedeAccederModuloEstadisticas())
                <li>
                    <a href="{{ route('statistics.index') }}" class="{{ request()->routeIs('statistics.*') ? 'selected' : '' }}">
                        <span class="icon"><img src="{{ asset('assets/icons/estadisticas.svg') }}" alt="Estadisticas"></span>
                        <span>Estadisticas</span>
                    </a>
                </li>
            @endif

            @if ($user->puedeAccederModuloUsuarios())
                <li>
                    <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.index') ? 'selected' : '' }}">
                        <span class="icon"><img src="{{ asset('assets/icons/usuarios.svg') }}" alt="Usuarios"></span>
                        <span>Usuarios</span>
                    </a>
                </li>
            @endif

            @if ($user->puedeAccederModuloProgramas())
                <li>
                    <a href="{{ route('programs.index') }}" class="{{ request()->routeIs('programs.*') ? 'selected' : '' }}">
                        <span class="icon"><img src="{{ asset('assets/icons/programas.svg') }}" alt="Programas"></span>
                        <span>Programas</span>
                    </a>
                </li>
            @endif

            @if ($user->puedeAccederModuloEstructuraOrganizacional())
                <li>
                    <a href="{{ route('process-dependency.index') }}" class="{{ request()->routeIs('process-dependency.*') ? 'selected' : '' }}">
                        <span class="icon"><img src="{{ asset('assets/icons/estructura.svg') }}" alt="Procesos y dependencias"></span>
                        <span>Procesos y dependencias</span>
                    </a>
                </li>
            @endif
        </ul>
    </nav>
</aside>
