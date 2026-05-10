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
                    position: fixed;
                    left: clamp(1.5rem, 3vw, 3rem);
                    bottom: clamp(1.5rem, 4vh, 3rem);
                    width: min(48rem, calc(100vw - 31rem));
                    max-width: 48rem;
                    z-index: 10;
                    flex: 0 0 auto;
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
                    left: clamp(2rem, 4vw, 4rem);
                    bottom: clamp(2rem, 5vh, 3.5rem);
                    width: min(52rem, calc(100vw - 33rem));
                    max-width: 52rem;
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

            .ms-login-info-lead {
                background: linear-gradient(180deg, rgba(255, 255, 255, 0.12) 0%, rgba(8, 29, 40, 0.24) 100%);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
            }

            .ms-login-info-feature {
                border: 1px solid rgba(255, 255, 255, 0.09);
                background: linear-gradient(180deg, rgba(255, 255, 255, 0.1) 0%, rgba(11, 34, 48, 0.28) 100%);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
            }

            .ms-login-info-icon {
                color: rgba(255, 255, 255, 0.92);
                background: rgba(255, 255, 255, 0.08);
                border: 1px solid rgba(255, 255, 255, 0.08);
            }

            .ms-login-info-track {
                grid-template-columns: repeat(3, minmax(0, 1fr));
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
