<x-guest-layout>
    @if ($errors->has('login'))
        <div x-data="{ open: true }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 px-6" x-cloak>
            <div class="w-full max-w-sm rounded-[1.75rem] border border-white/55 bg-white/94 p-6 text-center shadow-[0_30px_90px_-35px_rgba(2,12,18,0.95)] backdrop-blur-xl">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#fff3f0] text-[#d9485f] shadow-sm">
                    <span class="text-2xl font-black">!</span>
                </div>

                <h2 class="mt-5 text-xl font-black tracking-tight text-slate-950">Acceso no valido</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $errors->first('login') }}</p>

                <button
                    type="button"
                    class="mt-6 inline-flex items-center justify-center rounded-2xl bg-[#0ca4b5] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#0b8c9b] focus:outline-none focus:ring-2 focus:ring-[#0ca4b5] focus:ring-offset-2 focus:ring-offset-white"
                    @click="open = false"
                >
                    Entendido
                </button>
            </div>
        </div>
    @endif

    <div class="mx-auto flex w-full max-w-sm flex-col gap-6">
        <div class="flex justify-center pb-2">
            <img
                src="{{ asset('images/logo-elisa.png') }}"
                alt="Logo del aplicativo ELISA"
                class="block h-auto"
                style="width: min(100%, 430px);"
            >
        </div>

        <div class="space-y-2">
            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.34em] text-[#0b7f87]">Ingreso seguro</p>
            <h2 class="text-2xl font-black text-slate-950 sm:text-3xl">Bienvenido de nuevo</h2>
            <p class="text-sm leading-6 text-slate-600">
                Usa tu usuario y contrasena institucional para entrar al sistema y continuar con tus reportes,
                mediciones y consultas.
            </p>
        </div>

        <x-auth-session-status class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <x-input-label for="username" :value="__('Usuario')" class="text-[0.78rem] font-semibold uppercase tracking-[0.24em] text-slate-500" />
                <x-text-input
                    id="username"
                    class="mt-2 block w-full rounded-[1.15rem] border border-slate-200 bg-white px-4 py-3.5 text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-[#0ca4b5] focus:ring-[#0ca4b5]"
                    type="text"
                    name="username"
                    :value="old('username')"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Ej: admisionesmaicao"
                />
                <x-input-error :messages="$errors->get('username')" class="mt-2 text-[#c2415d]" />
            </div>

            <div x-data="{ showPassword: false }">
                <x-input-label for="password" :value="__('Contrasena')" class="text-[0.78rem] font-semibold uppercase tracking-[0.24em] text-slate-500" />
                <div class="relative mt-2">
                    <x-text-input
                        id="password"
                        class="block w-full rounded-[1.15rem] border border-slate-200 bg-white px-4 py-3.5 pr-14 text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-[#0ca4b5] focus:ring-[#0ca4b5]"
                        x-bind:type="showPassword ? 'text' : 'password'"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Ingresa tu contrasena"
                    />

                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 inline-flex w-14 items-center justify-center text-slate-500 transition hover:text-slate-700 focus:outline-none"
                        @click="showPassword = !showPassword"
                        x-bind:aria-label="showPassword ? 'Ocultar contrasena' : 'Mostrar contrasena'"
                    >
                        <svg x-show="!showPassword" viewBox="0 0 24 24" fill="none" class="h-5 w-5" aria-hidden="true">
                            <path d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/>
                        </svg>

                        <svg x-show="showPassword" viewBox="0 0 24 24" fill="none" class="h-5 w-5" aria-hidden="true" style="display: none;">
                            <path d="M3 3l18 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M10.58 10.58A2 2 0 0 0 10 12a2 2 0 0 0 3.42 1.42" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9.88 5.08A10.94 10.94 0 0 1 12 4.88c6.25 0 9.75 7.12 9.75 7.12a15.71 15.71 0 0 1-4.04 4.88" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M6.69 6.69C4.27 8.07 2.75 12 2.75 12a15.83 15.83 0 0 0 6.75 6.24A10.3 10.3 0 0 0 12 18.88c1.04 0 2.04-.14 3-.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-[#c2415d]" />
            </div>

            <label for="remember_me" class="inline-flex items-center gap-3 text-sm font-medium text-slate-600">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 bg-white text-[#0ca4b5] focus:ring-[#0ca4b5]" name="remember">
                <span>{{ __('Recordarme') }}</span>
            </label>

            <x-primary-button
                class="w-full justify-center !rounded-2xl !px-5 !py-4 !text-sm !font-semibold !normal-case !tracking-[0.18em] !text-white transition hover:scale-[1.01] hover:!opacity-95 focus:!ring-[#0ca4b5]"
                style="background: linear-gradient(135deg, #0ca4b5 0%, #0e8f9e 40%, #ef6b4a 100%); box-shadow: 0 24px 50px -24px rgba(14, 143, 158, 0.95);"
            >
                {{ __('Iniciar sesion') }}
            </x-primary-button>

            <p class="text-center text-xs font-medium uppercase tracking-[0.22em] text-slate-400">
                Medicion institucional de servicios
            </p>
        </form>
    </div>
</x-guest-layout>
