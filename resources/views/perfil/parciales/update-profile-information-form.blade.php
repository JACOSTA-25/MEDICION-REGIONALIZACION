<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Informacion del perfil') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Estos datos son informativos y son administrados por el Super Administrador.') }}
        </p>
    </header>

    <div class="mt-6 space-y-6">
        <div>
            <x-input-label for="nombre" :value="__('Nombre')" />
            <x-text-input
                id="nombre"
                type="text"
                class="mt-1 block w-full bg-gray-100 text-gray-600"
                :value="$user->nombre"
                disabled
            />
        </div>

        <div>
            <x-input-label for="username" :value="__('Usuario')" />
            <x-text-input
                id="username"
                type="text"
                class="mt-1 block w-full bg-gray-100 text-gray-600"
                :value="$user->username"
                disabled
            />
        </div>

        <p class="text-sm text-gray-500">
            Si necesitas actualizar esta informacion, solicita el ajuste al Super Administrador.
        </p>
    </div>
</section>
