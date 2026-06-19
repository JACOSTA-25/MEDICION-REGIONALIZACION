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

    <x-modal name="password-updated-dialog" :show="$passwordUpdated" focusable>
        <div class="space-y-5 p-6">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Contrasena actualizada</h3>
                <p class="mt-2 text-sm text-slate-600">
                    La contrasena ha sido cambiada correctamente.
                </p>
            </div>

            <div class="flex justify-end">
                <button
                    type="button"
                    x-on:click="$dispatch('close-modal', 'password-updated-dialog')"
                    class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2"
                >
                    Entendido
                </button>
            </div>
        </div>
    </x-modal>
</section>
