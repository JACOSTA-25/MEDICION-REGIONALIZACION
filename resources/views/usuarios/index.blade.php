@php
    $currentUser = auth()->user();
    $createErrors = $errors->getBag('createUser');
    $updateErrors = $errors->getBag('updateUser');
    $openEditUser = session('open_edit_user');
    $defaultRole = old('rol', array_key_first($roles));
@endphp

<x-app-layout>
    <div
        class="ms-content-shell"
        data-dependencies-by-process='@json($dependenciesByProcess)'
        x-data="{
            createOpen: {{ session('open_create_user') || $createErrors->any() ? 'true' : 'false' }},
            editUserId: {{ $openEditUser ? (int) $openEditUser : 'null' }},
        }"
    >
        <x-generals.top-bar
            title="Gestion de usuarios"
            description="Administra usuarios globales y por sede sin mezclar alcances."
        />

        <div class="ms-panel-body">
            <div class="ms-report-card">
                <div class="ms-report-card-header">
                    <h2>Usuarios registrados</h2>
                    <p>Aqui puedes revisar el rol asignado a cada usuario y administrar sus datos.</p>
                </div>

                <div class="ms-form-actions" style="margin-top: 1rem;">
                    @if ($currentUser?->hasGlobalSedeAccess())
                        <form method="GET" action="{{ route('users.index') }}" class="ms-inline-actions">
                            <select
                                name="id_sede"
                                class="rounded-2xl border border-slate-300 px-4 py-2 text-sm"
                                onchange="this.form.submit()"
                            >
                                <option value="">Todas las sedes</option>
                                @foreach ($sedes as $sede)
                                    <option value="{{ $sede->id_sede }}" @selected((string) $selectedSedeId === (string) $sede->id_sede)>
                                        {{ $sede->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif

                    <button type="button" class="ms-btn ms-btn-primary" x-on:click="createOpen = true">
                        Crear usuario
                    </button>
                </div>

                @if (session('user_status'))
                    <div class="ms-inline-alert ms-inline-alert-soft">
                        {{ session('user_status') }}
                    </div>
                @endif

                @if (session('user_error'))
                    <div class="ms-inline-alert">
                        {{ session('user_error') }}
                    </div>
                @endif

                <div class="ms-table-shell ms-table-shell-compact">
                    <table class="ms-data-table ms-data-table-compact">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Sede</th>
                                <th>Proceso</th>
                                <th>Dependencia</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $managedUser)
                                <tr class="ms-interactive-row" x-on:click="editUserId = {{ $managedUser->id }}">
                                    <td class="ms-cell-name">{{ $managedUser->nombre }}</td>
                                    <td>{{ $managedUser->username }}</td>
                                    <td>{{ $roles[$managedUser->rol] ?? $managedUser->rol }}</td>
                                    <td>{{ $managedUser->sede?->nombre ?? 'Global' }}</td>
                                    <td>{{ $managedUser->proceso?->nombre ?? 'No aplica' }}</td>
                                    <td>{{ $managedUser->dependencia?->nombre ?? 'No aplica' }}</td>
                                    <td>{{ $managedUser->activo ? 'Activo' : 'Inactivo' }}</td>
                                    <td x-on:click.stop>
                                        <div class="ms-inline-actions">
                                            @if (auth()->id() !== $managedUser->id)
                                                <form
                                                    method="POST"
                                                    action="{{ route('users.destroy', $managedUser) }}"
                                                    data-confirm-user-delete
                                                    data-user-name="{{ $managedUser->nombre }}"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        class="ms-btn ms-btn-muted ms-btn-icon"
                                                        aria-label="Eliminar usuario"
                                                        title="Eliminar usuario"
                                                    >
                                                        <svg viewBox="0 0 24 24" aria-hidden="true" class="ms-btn-icon-svg">
                                                            <path d="M7 21c-.55 0-1-.45-1-1V7h12v13c0 .55-.45 1-1 1H7Z" fill="currentColor"/>
                                                            <path d="M9 4h6l1 1h4v2H4V5h4l1-1Z" fill="currentColor"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @else
                                                <button
                                                    type="button"
                                                    class="ms-btn ms-btn-muted ms-btn-icon"
                                                    aria-label="No puedes eliminar tu propio usuario"
                                                    title="No puedes eliminar tu propio usuario"
                                                    disabled
                                                >
                                                    <svg viewBox="0 0 24 24" aria-hidden="true" class="ms-btn-icon-svg">
                                                        <path d="M7 21c-.55 0-1-.45-1-1V7h12v13c0 .55-.45 1-1 1H7Z" fill="currentColor"/>
                                                        <path d="M9 4h6l1 1h4v2H4V5h4l1-1Z" fill="currentColor"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">No hay usuarios registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div
            x-show="createOpen"
            x-on:click.self="createOpen = false"
            style="display: none;"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
        >
            <div class="w-full max-w-3xl rounded-3xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Crear usuario</h2>
                        <p class="mt-1 text-sm text-slate-500">Completa los datos del nuevo usuario.</p>
                    </div>

                    <button type="button" class="text-sm font-semibold text-slate-500" x-on:click="createOpen = false">
                        Cerrar
                    </button>
                </div>

                @if ($createErrors->any())
                    <div class="mt-4 rounded-2xl border border-[#8ddcdf] bg-[#e6f8f8] px-4 py-3 text-sm text-[#0b5d60]">
                        <ul class="list-disc space-y-1 pl-5">
                            @foreach ($createErrors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('users.store') }}"
                    class="mt-6 space-y-5"
                    data-user-form
                    x-data="{ role: @js($defaultRole) }"
                >
                    @csrf

                    @php
                        $createDependencies = collect($dependenciesByProcess[(int) old('id_proceso')] ?? []);
                    @endphp

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="create_nombre" class="block text-sm font-semibold text-slate-700">Nombre</label>
                            <input
                                id="create_nombre"
                                name="nombre"
                                type="text"
                                value="{{ old('nombre') }}"
                                class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                required
                            >
                        </div>

                        <div>
                            <label for="create_username" class="block text-sm font-semibold text-slate-700">Usuario</label>
                            <input
                                id="create_username"
                                name="username"
                                type="text"
                                value="{{ old('username') }}"
                                class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                required
                            >
                        </div>

                        <div>
                            <label for="create_password" class="block text-sm font-semibold text-slate-700">Contrasena</label>
                            <input
                                id="create_password"
                                name="password"
                                type="password"
                                class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                required
                            >
                        </div>

                        <div>
                            <label for="create_rol" class="block text-sm font-semibold text-slate-700">Rol</label>
                            <select
                                id="create_rol"
                                name="rol"
                                x-model="role"
                                class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                            >
                                @foreach ($roles as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if ($currentUser?->isAdminSede())
                            <input type="hidden" name="id_sede" value="{{ $currentUser->id_sede }}">
                        @else
                            <div x-show="role !== '{{ \App\Models\User::ROLE_ADMIN }}' && role !== '{{ \App\Models\User::ROLE_ADMIN_2_0 }}'">
                                <label for="create_sede" class="block text-sm font-semibold text-slate-700">Sede</label>
                                <select
                                    id="create_sede"
                                    name="id_sede"
                                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                >
                                    <option value="">Seleccione una sede</option>
                                    @foreach ($sedes as $sede)
                                        <option value="{{ $sede->id_sede }}" @selected((string) old('id_sede', $selectedSedeId) === (string) $sede->id_sede)>
                                            {{ $sede->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div x-show="role === '{{ \App\Models\User::ROLE_LIDER_PROCESO }}' || role === '{{ \App\Models\User::ROLE_LIDER_DEPENDENCIA }}'">
                            <label for="create_proceso" class="block text-sm font-semibold text-slate-700">Proceso</label>
                            <select
                                id="create_proceso"
                                name="id_proceso"
                                data-user-process-select
                                class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                            >
                                <option value="">Seleccione un proceso</option>
                                @foreach ($processes as $process)
                                    <option value="{{ $process->id_proceso }}" @selected((string) old('id_proceso') === (string) $process->id_proceso)>
                                        {{ $process->nombre }}{{ $currentUser?->hasGlobalSedeAccess() ? ' - '.$process->sede?->nombre : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="role === '{{ \App\Models\User::ROLE_LIDER_DEPENDENCIA }}'">
                            <label for="create_dependencia" class="block text-sm font-semibold text-slate-700">Dependencia</label>
                            <select
                                id="create_dependencia"
                                name="id_dependencia"
                                data-user-dependency-select
                                data-selected="{{ old('id_dependencia') }}"
                                class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                {{ $createDependencies->isEmpty() ? 'disabled' : '' }}
                            >
                                <option value="">Seleccione una dependencia</option>
                                @foreach ($createDependencies as $dependency)
                                    <option value="{{ $dependency['id'] }}" @selected((string) old('id_dependencia') === (string) $dependency['id'])>
                                        {{ $dependency['nombre'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center gap-3">
                            <input
                                id="create_activo"
                                name="activo"
                                type="checkbox"
                                value="1"
                                class="h-4 w-4 rounded border-slate-300 text-[#00a9ad]"
                                @checked(old('activo', true))
                            >
                            <label for="create_activo" class="text-sm font-semibold text-slate-700">Usuario activo</label>
                        </div>
                    </div>

                    <div class="ms-form-actions">
                        <button type="submit" class="ms-btn ms-btn-primary">Guardar usuario</button>
                        <button type="button" class="ms-btn ms-btn-secondary" x-on:click="createOpen = false">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>

        @foreach ($users as $managedUser)
            @php
                $isEditingUser = (int) $openEditUser === (int) $managedUser->id;
                $editRole = $isEditingUser ? old('rol', $managedUser->rol) : $managedUser->rol;
                $editSedeId = $isEditingUser ? old('id_sede', $managedUser->id_sede) : $managedUser->id_sede;
                $editProcessId = $isEditingUser ? old('id_proceso', $managedUser->id_proceso) : $managedUser->id_proceso;
                $editDependencyId = $isEditingUser ? old('id_dependencia', $managedUser->id_dependencia) : $managedUser->id_dependencia;
                $editName = $isEditingUser ? old('nombre', $managedUser->nombre) : $managedUser->nombre;
                $editUsername = $isEditingUser ? old('username', $managedUser->username) : $managedUser->username;
                $editActive = $isEditingUser ? old('activo', $managedUser->activo) : $managedUser->activo;
            @endphp

            <div
                x-show="editUserId === {{ $managedUser->id }}"
                x-on:click.self="editUserId = null"
                style="display: none;"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
            >
                <div class="w-full max-w-3xl rounded-3xl bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">Editar usuario</h2>
                            <p class="mt-1 text-sm text-slate-500">Actualiza los datos de {{ $managedUser->nombre }}.</p>
                        </div>

                        <button type="button" class="text-sm font-semibold text-slate-500" x-on:click="editUserId = null">
                            Cerrar
                        </button>
                    </div>

                    @if ($isEditingUser && $updateErrors->any())
                        <div class="mt-4 rounded-2xl border border-[#8ddcdf] bg-[#e6f8f8] px-4 py-3 text-sm text-[#0b5d60]">
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach ($updateErrors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form
                        method="POST"
                        action="{{ route('users.update', $managedUser) }}"
                        class="mt-6 space-y-5"
                        data-user-form
                        x-data="{ role: @js($editRole) }"
                    >
                        @csrf
                        @method('PUT')

                        @php
                            $editDependencies = collect($dependenciesByProcess[(int) $editProcessId] ?? []);
                        @endphp

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="edit_nombre_{{ $managedUser->id }}" class="block text-sm font-semibold text-slate-700">Nombre</label>
                                <input
                                    id="edit_nombre_{{ $managedUser->id }}"
                                    name="nombre"
                                    type="text"
                                    value="{{ $editName }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                    required
                                >
                            </div>

                            <div>
                                <label for="edit_username_{{ $managedUser->id }}" class="block text-sm font-semibold text-slate-700">Usuario</label>
                                <input
                                    id="edit_username_{{ $managedUser->id }}"
                                    name="username"
                                    type="text"
                                    value="{{ $editUsername }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                    required
                                >
                            </div>

                            <div>
                                <label for="edit_password_{{ $managedUser->id }}" class="block text-sm font-semibold text-slate-700">Nueva contrasena</label>
                                <input
                                    id="edit_password_{{ $managedUser->id }}"
                                    name="password"
                                    type="password"
                                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                    placeholder="Deja vacio para conservar la actual"
                                >
                            </div>

                            <div>
                                <label for="edit_rol_{{ $managedUser->id }}" class="block text-sm font-semibold text-slate-700">Rol</label>
                                <select
                                    id="edit_rol_{{ $managedUser->id }}"
                                    name="rol"
                                    x-model="role"
                                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                >
                                    @foreach ($roles as $value => $label)
                                        <option value="{{ $value }}" @selected($editRole === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if ($currentUser?->isAdminSede())
                                <input type="hidden" name="id_sede" value="{{ $currentUser->id_sede }}">
                            @else
                                <div x-show="role !== '{{ \App\Models\User::ROLE_ADMIN }}' && role !== '{{ \App\Models\User::ROLE_ADMIN_2_0 }}'">
                                    <label for="edit_sede_{{ $managedUser->id }}" class="block text-sm font-semibold text-slate-700">Sede</label>
                                    <select
                                        id="edit_sede_{{ $managedUser->id }}"
                                        name="id_sede"
                                        class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                    >
                                        <option value="">Seleccione una sede</option>
                                        @foreach ($sedes as $sede)
                                            <option value="{{ $sede->id_sede }}" @selected((string) $editSedeId === (string) $sede->id_sede)>
                                                {{ $sede->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div x-show="role === '{{ \App\Models\User::ROLE_LIDER_PROCESO }}' || role === '{{ \App\Models\User::ROLE_LIDER_DEPENDENCIA }}'">
                                <label for="edit_proceso_{{ $managedUser->id }}" class="block text-sm font-semibold text-slate-700">Proceso</label>
                                <select
                                    id="edit_proceso_{{ $managedUser->id }}"
                                    name="id_proceso"
                                    data-user-process-select
                                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                >
                                    <option value="">Seleccione un proceso</option>
                                    @foreach ($processes as $process)
                                        <option value="{{ $process->id_proceso }}" @selected((string) $editProcessId === (string) $process->id_proceso)>
                                            {{ $process->nombre }}{{ $currentUser?->hasGlobalSedeAccess() ? ' - '.$process->sede?->nombre : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div x-show="role === '{{ \App\Models\User::ROLE_LIDER_DEPENDENCIA }}'">
                                <label for="edit_dependencia_{{ $managedUser->id }}" class="block text-sm font-semibold text-slate-700">Dependencia</label>
                                <select
                                    id="edit_dependencia_{{ $managedUser->id }}"
                                    name="id_dependencia"
                                    data-user-dependency-select
                                    data-selected="{{ $editDependencyId }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                    {{ $editDependencies->isEmpty() ? 'disabled' : '' }}
                                >
                                    <option value="">Seleccione una dependencia</option>
                                    @foreach ($editDependencies as $dependency)
                                        <option value="{{ $dependency['id'] }}" @selected((string) $editDependencyId === (string) $dependency['id'])>
                                            {{ $dependency['nombre'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-center gap-3">
                                <input
                                    id="edit_activo_{{ $managedUser->id }}"
                                    name="activo"
                                    type="checkbox"
                                    value="1"
                                    class="h-4 w-4 rounded border-slate-300 text-[#00a9ad]"
                                    @checked($editActive)
                                >
                                <label for="edit_activo_{{ $managedUser->id }}" class="text-sm font-semibold text-slate-700">Usuario activo</label>
                            </div>
                        </div>

                        <div class="ms-form-actions">
                            <button type="submit" class="ms-btn ms-btn-primary">Guardar cambios</button>
                            <button type="button" class="ms-btn ms-btn-secondary" x-on:click="editUserId = null">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>
