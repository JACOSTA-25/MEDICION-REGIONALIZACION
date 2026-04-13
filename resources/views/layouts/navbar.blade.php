@php
    $user = auth()->user();
    $initial = mb_substr((string) ($user->nombre ?? 'U'), 0, 1);
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
        <button class="ms-user-btn" @click="open = !open" type="button">
            <span class="ms-user-name">{{ $user->nombre }}</span>
            <span class="ms-user-avatar">{{ $initial }}</span>
        </button>

        <div class="ms-user-menu" x-show="open" @click.outside="open = false" x-transition>
            <div class="item" style="font-weight:600; border-bottom:1px solid #e5e7eb;">
                {{ $user->rol }}
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
