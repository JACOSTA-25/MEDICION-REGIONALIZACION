@php
    $user = auth()->user();
    $sedeService = app(\App\Services\Sedes\ServicioSedes::class);
    $availableSedes = $sedeService->visibleTo($user);
    $selectedScopeSedeId = $sedeService->resolveForRequest($user, request());
    $initial = mb_substr((string) ($user->nombre ?? 'U'), 0, 1);
    $scopeLabel = $user->hasGlobalSedeAccess()
        ? 'Acceso global - '.$sedeService->selectionLabel($selectedScopeSedeId)
        : ($user->sede?->nombre ?? 'Sin sede asignada');
@endphp

<header class="ms-header">
    <div class="left">
        <div class="ms-menu-container" onclick="window.MSLayout.toggleSidebar()">
            <div class="ms-menu" id="ms-menu">
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>

        <a class="ms-brand" href="{{ route('dashboard') }}" title="Ir al inicio">
            <img src="{{ asset('assets/images/logo-uniguajira-blanco.webp') }}" alt="Logo Uniguajira">
        </a>
    </div>

    <div class="ms-right" x-data="{ open: false }">
        @if ($user->hasGlobalSedeAccess())
            <form method="POST" action="{{ route('sedes.scope.update') }}" class="ms-sede-switcher">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">

                <label for="navbar_id_sede">Vista</label>
                <select id="navbar_id_sede" name="id_sede" onchange="this.form.submit()">
                    <option value="">Todas las sedes</option>
                    @foreach ($availableSedes as $sede)
                        <option value="{{ $sede->id_sede }}" @selected((string) $selectedScopeSedeId === (string) $sede->id_sede)>
                            {{ $sede->nombre }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endif

        <button class="ms-user-btn" @click="open = !open" type="button">
            <span class="ms-user-name">{{ $user->nombre }}</span>
            <span class="ms-user-avatar">{{ $initial }}</span>
        </button>

        <div class="ms-user-menu" x-show="open" @click.outside="open = false" x-transition>
            <div class="item" style="font-weight:600; border-bottom:1px solid #e5e7eb;">
                {{ $user->rol }}
            </div>

            <div class="item" style="border-bottom:1px solid #e5e7eb;">
                {{ $scopeLabel }}
            </div>

            <a href="{{ route('profile.edit') }}#cambiar-contrasena" class="item">
                <img class="icon" src="{{ asset('assets/icons/password.svg') }}" alt="Cambiar contrasena">
                Cambiar contrasena
            </a>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit">
                    <img class="icon" src="{{ asset('assets/icons/cerrarSesion.svg') }}" alt="Cerrar sesion">
                    Cerrar sesion
                </button>
            </form>
        </div>
    </div>
</header>
