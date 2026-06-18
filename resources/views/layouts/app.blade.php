<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Medicion de Servicios') }}</title>

        @php
            $navbarCssPath = public_path('assets/css/components/navbar.css');
            $sidebarCssPath = public_path('assets/css/components/sidebar.css');
            $navbarCssVersion = file_exists($navbarCssPath) ? filemtime($navbarCssPath) : time();
            $sidebarCssVersion = file_exists($sidebarCssPath) ? filemtime($sidebarCssPath) : time();
        @endphp

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600;700&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="{{ asset('assets/css/components/navbar.css').'?v='.$navbarCssVersion }}">
        <link rel="stylesheet" href="{{ asset('assets/css/components/sidebar.css').'?v='.$sidebarCssVersion }}">

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        @include('layouts.navbar')
        @include('layouts.sidebar')

        <main id="ms-main" class="ms-main menu-toggle">
            <div class="p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </div>
        </main>

        <form id="ms-auto-logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
            @csrf
        </form>

        <script>
            window.MSLayout = {
                toggleSidebar() {
                    const sidebar = document.getElementById('ms-sidebar');
                    const main = document.getElementById('ms-main');
                    const menu = document.getElementById('ms-menu');

                    if (!sidebar || !main || !menu) {
                        return;
                    }

                    sidebar.classList.toggle('menu-toggle');
                    main.classList.toggle('menu-toggle');
                    menu.classList.toggle('menu-toggle');
                },
            };

            // Cierre de sesion automatico tras 5 minutos de inactividad.
            (function () {
                const INACTIVITY_LIMIT_MS = 5 * 60 * 1000;
                let inactivityTimer = null;

                function logoutByInactivity() {
                    const form = document.getElementById('ms-auto-logout-form');

                    if (form) {
                        form.submit();
                    }
                }

                function resetInactivityTimer() {
                    if (inactivityTimer) {
                        clearTimeout(inactivityTimer);
                    }

                    inactivityTimer = setTimeout(logoutByInactivity, INACTIVITY_LIMIT_MS);
                }

                ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function (eventName) {
                    document.addEventListener(eventName, resetInactivityTimer, { passive: true });
                });

                resetInactivityTimer();
            })();
        </script>
    </body>
</html>
