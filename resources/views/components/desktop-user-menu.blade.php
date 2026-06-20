@php($user = auth()->user())

<flux:dropdown position="bottom" align="start">
    <flux:sidebar.profile
        :name="$user->display_name"
        :initials="$user->initials()"
        icon:trailing="chevrons-up-down"
        data-test="sidebar-menu-button"
    />

    <flux:menu>
        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
            <flux:avatar
                :name="$user->display_name"
                :initials="$user->initials()"
            />
            <div class="grid flex-1 text-start text-sm leading-tight">
                <flux:heading class="truncate">{{ $user->display_name }}</flux:heading>
                <flux:text class="truncate">{{ '@'.$user->username }}</flux:text>
            </div>
        </div>
        <flux:menu.separator />
        <flux:menu.radio.group>
            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                {{ __('Configuracion') }}
            </flux:menu.item>
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item
                    as="button"
                    type="submit"
                    icon="arrow-right-start-on-rectangle"
                    class="w-full cursor-pointer"
                    data-test="logout-button"
                >
                    {{ __('Cerrar sesion') }}
                </flux:menu.item>
            </form>
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
