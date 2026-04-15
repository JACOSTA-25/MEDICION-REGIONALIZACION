<x-guest-layout>
    @if ($errors->has('login'))
        <div x-data="{ open: true }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/55 px-6" x-cloak>
            <div class="w-full max-w-sm rounded-2xl border border-white/15 bg-[#101010]/88 p-6 text-center shadow-2xl backdrop-blur-md">
                <h2 class="text-xl font-semibold text-white">Acceso no valido</h2>
                <p class="mt-3 text-sm leading-6 text-white/80">{{ $errors->first('login') }}</p>

                <button
                    type="button"
                    class="mt-5 inline-flex items-center justify-center rounded-xl bg-[#00a9ad] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#008d90] focus:outline-none focus:ring-2 focus:ring-[#00a9ad] focus:ring-offset-2 focus:ring-offset-transparent"
                    @click="open = false"
                >
                    Entendido
                </button>
            </div>
        </div>
    @endif

    <div class="flex flex-col gap-6">
        <div class="space-y-1 text-center">
            <h1 class="text-2xl font-semibold tracking-tight text-white">Iniciar sesion</h1>
            <p class="text-sm text-white/75">Ingresa tu usuario y contrasena para acceder al sistema</p>
        </div>

        <x-auth-session-status class="mb-1 text-sm text-green-300" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <x-input-label for="username" :value="__('Usuario')" class="text-white/90" />
                <x-text-input
                    id="username"
                    class="mt-1 block w-full border-white/20 bg-white/10 text-white placeholder:text-white/50 focus:border-[#f16d58] focus:ring-[#f16d58]"
                    type="text"
                    name="username"
                    :value="old('username')"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Ej: admisionesmaicao"
                />
                <x-input-error :messages="$errors->get('username')" class="mt-2 text-red-300" />
            </div>

            <div>
                <x-input-label for="password" :value="__('Contrasena')" class="text-white/90" />
                <x-text-input
                    id="password"
                    class="mt-1 block w-full border-white/20 bg-white/10 text-white placeholder:text-white/50 focus:border-[#f16d58] focus:ring-[#f16d58]"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Ingresa tu contrasena"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-300" />
            </div>

            <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-white/80">
                <input id="remember_me" type="checkbox" class="rounded border-white/30 bg-white/10 text-[#f16d58] focus:ring-[#f16d58]" name="remember">
                <span>{{ __('Recordarme') }}</span>
            </label>

            <x-primary-button class="w-full justify-center !bg-[#00a9ad] !text-white hover:!bg-[#008d90] focus:!bg-[#008d90] active:!bg-[#00777a] focus:!ring-[#00a9ad]">
                {{ __('Iniciar sesion') }}
            </x-primary-button>
        </form>
    </div>
</x-guest-layout>
