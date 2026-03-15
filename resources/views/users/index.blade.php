@php
    $createErrors = $errors->getBag('createUser');
    $updateErrors = $errors->getBag('updateUser');
    $openEditUser = session('open_edit_user');
@endphp

<x-app-layout>
    <div
        class="ms-content-shell"
        x-data="{
            createOpen: {{ session('open_create_user') || $createErrors->any() ? 'true' : 'false' }},
            editUserId: {{ $openEditUser ? (int) $openEditUser : 'null' }},
        }"
    >
        <x-generals.top-bar
            title="Gestion de usuarios"
            description="Vista habilitada solo para ADMIN"
        />

        <div class="ms-panel-body">
            <div class="ms-report-card">
                <div class="ms-report-card-header">
                    <h2>Usuarios registrados</h2>
                    <p>Aqui puedes revisar el rol asignado a cada usuario y administrar sus datos.</p>
                </div>

                <div class="ms-form-actions" style="margin-top: 1rem;">
                    <button type="button" class="ms-btn ms-btn-primary" x-on:click="createOpen = true">
                        Crear usuario
                    </button>
                </div>

                @if (session('user_status'))
                    <div class="ms-inline-alert ms-inline-alert-soft">
                        {{ session('user_status') }}
                    </div>
                @endif

                <div class="ms-table-shell ms-table-shell-compact">
                    <table class="ms-data-table ms-data-table-compact">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Proceso</th>
                                <th>Dependencia</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $managedUser)
                                <tr class="ms-interactive-row" x-on:click="editUserId = {{ $managedUser->id }}">
                                    <td class="ms-cell-name">{{ $managedUser->nombre }}</td>
                                    <td>{{ $managedUser->username }}</td>
                                    <td>{{ $roles[$managedUser->rol] ?? $managedUser->rol }}</td>
                                    <td>{{ $managedUser->proceso?->nombre ?? 'No aplica' }}</td>
                                    <td>{{ $managedUser->dependencia?->nombre ?? 'No aplica' }}</td>
                                    <td>{{ $managedUser->activo ? 'Activo' : 'Inactivo' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">No hay usuarios registrados.</td>
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
                    <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
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
                    x-data="{ role: @js(old('rol', \App\Models\User::ROLE_ADMIN)) }"
                >
                    @csrf

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

                        <div x-show="role === '{{ \App\Models\User::ROLE_LIDER_PROCESO }}' || role === '{{ \App\Models\User::ROLE_LIDER_DEPENDENCIA }}'">
                            <label for="create_proceso" class="block text-sm font-semibold text-slate-700">Proceso</label>
                            <select
                                id="create_proceso"
                                name="id_proceso"
                                class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                            >
                                <option value="">Seleccione un proceso</option>
                                @foreach ($processes as $process)
                                    <option value="{{ $process->id_proceso }}" @selected((string) old('id_proceso') === (string) $process->id_proceso)>
                                        {{ $process->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="role === '{{ \App\Models\User::ROLE_LIDER_DEPENDENCIA }}'">
                            <label for="create_dependencia" class="block text-sm font-semibold text-slate-700">Dependencia</label>
                            <select
                                id="create_dependencia"
                                name="id_dependencia"
                                class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                            >
                                <option value="">Seleccione una dependencia</option>
                                @foreach ($dependenciesByProcess as $items)
                                    @foreach ($items as $dependency)
                                        <option value="{{ $dependency['id'] }}" @selected((string) old('id_dependencia') === (string) $dependency['id'])>
                                            {{ $dependency['nombre'] }}
                                        </option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center gap-3">
                            <input
                                id="create_activo"
                                name="activo"
                                type="checkbox"
                                value="1"
                                class="h-4 w-4 rounded border-slate-300 text-red-700"
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
                        <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
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
                        x-data="{ role: @js($editRole) }"
                    >
                        @csrf
                        @method('PUT')

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

                            <div x-show="role === '{{ \App\Models\User::ROLE_LIDER_PROCESO }}' || role === '{{ \App\Models\User::ROLE_LIDER_DEPENDENCIA }}'">
                                <label for="edit_proceso_{{ $managedUser->id }}" class="block text-sm font-semibold text-slate-700">Proceso</label>
                                <select
                                    id="edit_proceso_{{ $managedUser->id }}"
                                    name="id_proceso"
                                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                >
                                    <option value="">Seleccione un proceso</option>
                                    @foreach ($processes as $process)
                                        <option value="{{ $process->id_proceso }}" @selected((string) $editProcessId === (string) $process->id_proceso)>
                                            {{ $process->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div x-show="role === '{{ \App\Models\User::ROLE_LIDER_DEPENDENCIA }}'">
                                <label for="edit_dependencia_{{ $managedUser->id }}" class="block text-sm font-semibold text-slate-700">Dependencia</label>
                                <select
                                    id="edit_dependencia_{{ $managedUser->id }}"
                                    name="id_dependencia"
                                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                >
                                    <option value="">Seleccione una dependencia</option>
                                    @foreach ($dependenciesByProcess as $items)
                                        @foreach ($items as $dependency)
                                            <option value="{{ $dependency['id'] }}" @selected((string) $editDependencyId === (string) $dependency['id'])>
                                                {{ $dependency['nombre'] }}
                                            </option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-center gap-3">
                                <input
                                    id="edit_activo_{{ $managedUser->id }}"
                                    name="activo"
                                    type="checkbox"
                                    value="1"
                                    class="h-4 w-4 rounded border-slate-300 text-red-700"
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
