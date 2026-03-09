@php
    $user = auth()->user();
@endphp

<aside class="ms-sidebar" id="ms-sidebar">
    <nav>
        <ul class="list-unstyled">
            <li>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'selected' : '' }}">
                    <span class="icon"><img src="{{ asset('assets/icons/home.svg') }}" alt="Inicio"></span>
                    <span>Inicio</span>
                </a>
            </li>

            @if ($user->canAccessGeneralReports())
                <li>
                    <a href="{{ route('reports.general') }}" class="{{ request()->routeIs('reports.general') ? 'selected' : '' }}">
                        <span class="icon"><img src="{{ asset('assets/icons/reportes.svg') }}" alt="Reporte general"></span>
                        <span>Reporte general</span>
                    </a>
                </li>
            @endif

            @if ($user->canAccessProcessReports())
                <li>
                    <a href="{{ route('reports.process') }}" class="{{ request()->routeIs('reports.process') ? 'selected' : '' }}">
                        <span class="icon"><img src="{{ asset('assets/icons/inventario.svg') }}" alt="Reporte por proceso"></span>
                        <span>Reporte por proceso</span>
                    </a>
                </li>
            @endif

            @if ($user->canAccessIndividualReports())
                <li>
                    <a href="{{ route('reports.individual') }}" class="{{ request()->routeIs('reports.individual') ? 'selected' : '' }}">
                        <span class="icon"><img src="{{ asset('assets/icons/file.svg') }}" alt="Reporte individual"></span>
                        <span>Reporte individual</span>
                    </a>
                </li>
            @endif

            @if ($user->canAccessUsersModule())
                <li>
                    <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.index') ? 'selected' : '' }}">
                        <span class="icon"><img src="{{ asset('assets/icons/usuarios.svg') }}" alt="Usuarios"></span>
                        <span>Usuarios</span>
                    </a>
                </li>
            @endif

            @if ($user->canAccessProcessDependencyModule())
                <li>
                    <a href="{{ route('process-dependency.index') }}" class="{{ request()->routeIs('process-dependency.*') ? 'selected' : '' }}">
                        <span class="icon"><img src="{{ asset('assets/icons/inventario.svg') }}" alt="Procesos y dependencias"></span>
                        <span>Procesos y dependencias</span>
                    </a>
                </li>
            @endif
        </ul>
    </nav>
</aside>
