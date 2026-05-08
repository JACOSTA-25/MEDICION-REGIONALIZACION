<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Seleccion de sede</title>

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-950 text-white">
        <div class="relative min-h-screen overflow-hidden">
            <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" style="background-image: url('{{ asset('images/fondo-uniguajira.jpeg') }}')"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-slate-950/90 via-slate-900/82 to-[#003c40]/76"></div>
            <div class="absolute -left-20 top-16 h-72 w-72 rounded-full bg-[#7de7ea]/15 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-80 w-80 rounded-full bg-[#00a9ad]/20 blur-3xl"></div>

            <main class="relative mx-auto flex min-h-screen max-w-6xl items-center px-4 py-10 sm:px-6 lg:px-8">
                <div class="w-full rounded-[2rem] border border-white/10 bg-white/10 p-8 shadow-2xl backdrop-blur">
                    <div class="mx-auto max-w-2xl text-center">
                        <p class="text-sm font-semibold uppercase tracking-[0.3em] text-[#c8f6f7]">Universidad de La Guajira</p>
                        <h1 class="mt-4 text-4xl font-black tracking-tight sm:text-5xl">Selecciona tu sede</h1>
                        <p class="mt-4 text-base leading-7 text-slate-200">
                            La encuesta mantiene la misma estructura institucional, pero cada sede registra sus propios procesos,
                            dependencias, servicios y programas.
                        </p>
                    </div>

                    <div class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ($sedes as $sede)
                            <a
                                href="{{ route('survey.create', ['sede' => $sede->slug]) }}"
                                class="group rounded-[2rem] border border-white/15 bg-white/10 p-6 transition hover:-translate-y-1 hover:border-[#9fecee] hover:bg-white/15"
                            >
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#c8f6f7]">Encuesta institucional</p>
                                <h2 class="mt-4 text-2xl font-black">{{ $sede->nombre }}</h2>
                                <p class="mt-3 text-sm leading-6 text-slate-200">
                                    Ingresa al mismo formulario institucional con los datos exclusivos de esta sede.
                                </p>
                                <span class="mt-6 inline-flex rounded-full border border-white/20 px-4 py-2 text-sm font-semibold text-white transition group-hover:border-[#9fecee] group-hover:text-[#c8f6f7]">
                                    Continuar
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
