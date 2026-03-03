<x-guest-layout>
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
                    placeholder="Ej: JACOSTA"
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

            <x-primary-button class="w-full justify-center !bg-[#ad3728] !text-white hover:!bg-[#8f291f] focus:!bg-[#8f291f] active:!bg-[#7d2119]">
                {{ __('Iniciar sesion') }}
            </x-primary-button>
        </form>
    </div>
</x-guest-layout>
