<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600;700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen antialiased text-white">
        <div class="relative grid min-h-screen lg:grid-cols-2 overflow-hidden">
            <div class="absolute inset-0 -z-20 bg-cover bg-center bg-no-repeat" style="background-image: url('{{ asset('images/fondo-uniguajira.jpeg') }}')"></div>

            <div class="absolute -z-10 -top-24 -left-16 h-80 w-80 rounded-full bg-[#a3333d]/35 blur-3xl"></div>
            <div class="absolute -z-10 bottom-0 right-0 h-72 w-72 rounded-full bg-[#ad3728]/35 blur-3xl"></div>
            <div class="absolute -z-10 top-1/3 right-1/3 h-56 w-56 rounded-[35%_65%_55%_45%/45%_35%_65%_55%] bg-[#7f1f28]/40 blur-2xl"></div>

            <aside class="hidden lg:flex flex-col justify-between border-r border-white/10 bg-black/35 p-10">
                <div class="space-y-6">

                    <p class="max-w-xl text-sm text-white/80 leading-relaxed">
                        Gestiona el proceso de medicion de satisfaccion y consulta reportes consolidados de forma segura.
                    </p>

                    <div class="space-y-3 text-sm">
                        <div class="rounded-lg border border-white/15 bg-black/30 p-3">Registrar respuestas de encuesta por proceso, dependencia y servicio.</div>
                        <div class="rounded-lg border border-white/15 bg-black/30 p-3">Analizar resultados y generar informes por periodo.</div>
                        <div class="rounded-lg border border-white/15 bg-black/30 p-3">Controlar acceso por roles para administracion y consulta.</div>
                    </div>
                </div>

                <blockquote class="rounded-lg border border-white/10 bg-black/25 p-4 text-sm text-white/75">
                    "La calidad del servicio se mejora cuando se mide de forma consistente."
                </blockquote>
            </aside>

            <main class="relative flex items-center justify-center p-6 sm:p-10">
                <div class="absolute inset-0 -z-10 bg-black/35"></div>

                <div class="w-full max-w-md rounded-xl border border-white/15 bg-black/65 p-6 shadow-2xl backdrop-blur-sm sm:p-8">
                    <div class="mb-6 flex items-center justify-center gap-2 lg:hidden">
                        <x-app-logo-icon />
                        <span class="text-lg font-semibold">{{ config('app.name', 'Medicion de Servicios') }}</span>
                    </div>

                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>

