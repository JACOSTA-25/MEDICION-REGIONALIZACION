<section id="cambiar-contrasena">
    @php
        $passwordUpdated = session('status') === 'password-updated';
    @endphp

    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Cambiar contrasena') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Si el Super Administrador te asigno una contrasena inicial, ingresala como contrasena actual y luego define tu clave personal.') }}
        </p>
        <p class="mt-2 text-sm text-slate-500">
            Despues de confirmar el cambio, el sistema cerrara tu sesion para que ingreses nuevamente con la nueva contrasena.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Contrasena actual')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('Nueva contrasena')" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <p class="mt-2 text-sm text-slate-500">
                La nueva contrasena debe tener minimo 8 caracteres.
            </p>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirmar nueva contrasena')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Guardar contrasena') }}</x-primary-button>
        </div>
    </form>

    @if ($passwordUpdated)
        <div
            x-data="{
                open: true,
                closeAndLogout() {
                    if (! this.open) {
                        return;
                    }

                    this.open = false;
                    this.$nextTick(() => this.$refs.logoutAfterPasswordUpdate.submit());
                }
            }"
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-6"
            x-on:keydown.escape.window="closeAndLogout()"
        >
            <div class="absolute inset-0" x-on:click="closeAndLogout()"></div>

            <div class="relative w-full max-w-md overflow-hidden rounded-[2rem] border border-emerald-100 bg-white p-7 text-center shadow-[0_35px_120px_-45px_rgba(15,23,42,0.9)]">
                <button
                    type="button"
                    class="absolute right-4 top-4 inline-flex h-10 w-10 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-300"
                    x-on:click="closeAndLogout()"
                    aria-label="Cerrar mensaje"
                >
                    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" aria-hidden="true">
                        <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </button>

                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-50 ring-8 ring-emerald-100/80">
                    <svg viewBox="0 0 24 24" fill="none" class="h-10 w-10 text-emerald-600" aria-hidden="true">
                        <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>

                <h3 class="mt-6 text-2xl font-black tracking-tight text-slate-950">
                    Contrasena actualizada correctamente
                </h3>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Tu nueva contrasena fue registrada con exito. Para proteger tu cuenta, al cerrar este mensaje se cerrara tu sesion y deberas ingresar nuevamente.
                </p>

                <div class="mt-7 flex justify-center">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-white"
                        x-on:click="closeAndLogout()"
                    >
                        Aceptar
                    </button>
                </div>

                <form method="POST" action="{{ route('logout') }}" x-ref="logoutAfterPasswordUpdate" class="hidden">
                    @csrf
                    <input type="hidden" name="password_updated_logout" value="1">
                </form>
            </div>
        </div>
    @endif
</section>
