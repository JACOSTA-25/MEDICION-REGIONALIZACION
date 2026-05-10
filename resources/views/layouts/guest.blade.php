<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600;700&display=swap" rel="stylesheet" />

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            @media (min-width: 1024px) {
                .ms-login-stage {
                    max-width: 920px;
                    margin-left: auto;
                    margin-right: 8vw;
                    padding-left: 1rem;
                    padding-right: 1rem;
                }

                .ms-login-stage-inner {
                    justify-content: flex-end;
                    gap: 1.75rem;
                }

                .ms-login-info {
                    width: 22rem;
                    flex: 0 0 auto;
                    transform: translate(-8.5rem, 6.4rem);
                }

                .ms-login-auth {
                    width: 25rem;
                    max-width: 25rem;
                    flex: 0 0 auto;
                }
            }

            @media (min-width: 1280px) {
                .ms-login-stage {
                    margin-right: 10vw;
                }

                .ms-login-info {
                    transform: translate(-9.25rem, 6.9rem);
                }
            }

            .ms-login-info-shell {
                position: relative;
                overflow: hidden;
                background: linear-gradient(180deg, rgba(5, 24, 34, 0.6) 0%, rgba(10, 30, 42, 0.34) 100%);
                box-shadow: 0 34px 92px -58px rgba(4, 20, 27, 0.98);
                backdrop-filter: blur(18px);
            }

            .ms-login-info-shell::before {
                content: "";
                position: absolute;
                inset: 0;
                border-radius: inherit;
                background: linear-gradient(180deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0.02) 18%, rgba(255, 255, 255, 0) 48%);
                pointer-events: none;
            }

            .ms-login-info-shell::after {
                content: "";
                position: absolute;
                right: -2.25rem;
                top: -2.5rem;
                height: 8rem;
                width: 8rem;
                border-radius: 9999px;
                background: rgba(125, 231, 234, 0.18);
                filter: blur(28px);
                pointer-events: none;
            }

            .ms-login-info-content {
                position: relative;
                z-index: 1;
            }

            .ms-login-info-card {
                border: 1px solid rgba(255, 255, 255, 0.09);
                background: linear-gradient(180deg, rgba(255, 255, 255, 0.12) 0%, rgba(11, 34, 48, 0.36) 100%);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
            }

            .ms-login-info-feature {
                align-items: flex-start;
            }

            .ms-login-info-icon {
                color: rgba(255, 255, 255, 0.92);
            }
        </style>
    </head>
    <body class="min-h-screen bg-[#04141b] antialiased">
        <div class="relative isolate min-h-screen overflow-hidden">
            <div class="absolute inset-0 -z-30 bg-cover bg-center bg-no-repeat" style="background-image: url('{{ asset('images/fondo-uniguajira.png') }}')"></div>
            <div class="absolute inset-0 -z-20 bg-[linear-gradient(125deg,rgba(3,11,17,0.94)_6%,rgba(6,31,41,0.76)_40%,rgba(168,74,23,0.48)_100%)]"></div>
            <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgba(28,198,214,0.30),transparent_26%),radial-gradient(circle_at_86%_20%,rgba(253,186,24,0.24),transparent_22%),radial-gradient(circle_at_70%_78%,rgba(249,115,22,0.20),transparent_20%)]"></div>
            <div class="absolute inset-y-0 left-0 -z-10 hidden w-1/2 bg-[linear-gradient(90deg,rgba(4,20,27,0.88)_0%,rgba(4,20,27,0.62)_55%,rgba(4,20,27,0)_100%)] lg:block"></div>
            <div class="absolute -left-20 top-12 -z-10 h-72 w-72 rounded-full bg-[#15b8c8]/26 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 -z-10 h-96 w-96 rounded-full bg-[#f59e0b]/20 blur-3xl"></div>

            <main class="ms-login-stage relative mx-auto flex min-h-screen w-full items-center px-4 py-8 sm:px-6 lg:px-4">
                <div class="ms-login-stage-inner flex w-full flex-col gap-8 lg:flex-row lg:items-center xl:gap-8">
                    <section class="ms-login-info hidden lg:block">
                        <div
                            class="ms-login-info-shell w-full rounded-[2.1rem] p-3.5 xl:p-4"
                        >
                            <div class="ms-login-info-content mx-auto max-w-[19rem]">
                                <div class="rounded-[1.5rem] bg-black/18 px-4 py-2.5 text-center">
                                    <p class="text-[0.82rem] leading-6 text-white">
                                        Gestiona la medicion institucional y mantén el seguimiento del servicio por sede.
                                    </p>
                                </div>

                                <div class="mt-3 space-y-3">
                                    <article class="ms-login-info-feature flex gap-3 px-1 py-1">
                                        <div class="ms-login-info-icon flex h-10 w-10 shrink-0 items-center justify-center">
                                            <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" aria-hidden="true">
                                                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                                <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="2"/>
                                            </svg>
                                        </div>

                                        <div class="min-w-0 pt-0.5 text-left">
                                            <h3 class="text-[1rem] font-semibold leading-5 text-white">Operacion por sede</h3>
                                            <p class="mt-1 text-[0.74rem] leading-5 text-white">Gestiona procesos, servicios y mediciones de cada sede.</p>
                                        </div>
                                    </article>

                                    <article class="ms-login-info-feature flex gap-3 px-1 py-1">
                                        <div class="ms-login-info-icon flex h-10 w-10 shrink-0 items-center justify-center">
                                            <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" aria-hidden="true">
                                                <rect x="6" y="4.5" width="12" height="15" rx="2.2" stroke="currentColor" stroke-width="2"/>
                                                <path d="M9 9.5h6M9 13h6M9 16.5h3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            </svg>
                                        </div>

                                        <div class="min-w-0 pt-0.5 text-left">
                                            <h3 class="text-[1rem] font-semibold leading-5 text-white">Control trimestral</h3>
                                            <p class="mt-1 text-[0.74rem] leading-5 text-white">Supervisa periodos de corte y avances por dependencia.</p>
                                        </div>
                                    </article>

                                    <article class="ms-login-info-feature flex gap-3 px-1 py-1">
                                        <div class="ms-login-info-icon flex h-10 w-10 shrink-0 items-center justify-center">
                                            <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" aria-hidden="true">
                                                <path d="M6 18V10M12 18V6M18 18V13" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                                <rect x="4.5" y="10" width="3" height="8" rx="1.2" stroke="currentColor" stroke-width="2"/>
                                                <rect x="10.5" y="6" width="3" height="12" rx="1.2" stroke="currentColor" stroke-width="2"/>
                                                <rect x="16.5" y="13" width="3" height="5" rx="1.2" stroke="currentColor" stroke-width="2"/>
                                            </svg>
                                        </div>

                                        <div class="min-w-0 pt-0.5 text-left">
                                            <h3 class="text-[1rem] font-semibold leading-5 text-white">Reporteria consolidada</h3>
                                            <p class="mt-1 text-[0.74rem] leading-5 text-white">Consulta indicadores y reportes generales de la universidad.</p>
                                        </div>
                                    </article>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="ms-login-auth relative w-full">
                        <div class="absolute inset-6 rounded-[2rem] bg-[#16b8c6]/16 blur-3xl"></div>

                        <div
                            class="relative overflow-hidden p-6 sm:p-8"
                            style="border-radius: 32px; border: 1px solid rgba(255, 255, 255, 0.72); background: rgba(255, 255, 255, 0.92); box-shadow: 0 45px 120px -45px rgba(4, 20, 27, 0.95); backdrop-filter: blur(22px);"
                        >
                            <div class="absolute inset-x-0 top-0 h-1 bg-[linear-gradient(90deg,#12b9c8_0%,#fb7185_55%,#fbbf24_100%)]"></div>
                            <div class="absolute -right-12 top-10 h-28 w-28 rounded-full bg-[#12b9c8]/10 blur-2xl"></div>
                            <div class="absolute -bottom-14 left-6 h-32 w-32 rounded-full bg-[#fb923c]/12 blur-3xl"></div>

                            <div class="relative">
                                {{ $slot }}
                            </div>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </body>
</html>
