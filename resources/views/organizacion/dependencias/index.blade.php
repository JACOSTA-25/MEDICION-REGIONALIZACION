@php
    $currentUser = auth()->user();
    $createDependencyErrors = $errors->getBag('createDependency');
    $updateDependencyErrors = $errors->getBag('updateDependency');
    $openEditDependency = session('open_edit_dependency');
@endphp

<x-app-layout>
    <div
        class="ms-content-shell"
        x-data="{
            createDependencyOpen: {{ session('open_create_dependency') || $createDependencyErrors->any() ? 'true' : 'false' }},
            editDependencyId: {{ $openEditDependency ? (int) $openEditDependency : 'null' }},
        }"
    >
        <x-generals.top-bar
            :title="'Dependencias de '.$selectedProcess->nombre"
            description="Gestiona las dependencias asociadas al proceso seleccionado."
        >
            <x-slot:actions>
                <a href="{{ route('process-dependency.index') }}" class="ms-btn ms-btn-secondary">
                    Volver a procesos
                </a>
            </x-slot:actions>
        </x-generals.top-bar>

        <div class="ms-panel-body">
            @if (session('catalog_status'))
                <div class="ms-inline-alert ms-inline-alert-soft">
                    {{ session('catalog_status') }}
                </div>
            @endif

            @if (session('catalog_error'))
                <div class="ms-inline-alert">
                    {{ session('catalog_error') }}
                </div>
            @endif

            <section class="ms-report-card" style="margin-top: 1rem;">
                <div class="ms-report-card-header">
                    <h2>Dependencias de {{ $selectedProcess->nombre }}</h2>
                    <p>Mantiene las mismas operaciones de creacion, edicion y control de estado.</p>
                </div>

                @if ($canManageCatalogs)
                    <div class="ms-form-actions" style="margin-top: 1rem;">
                        <button type="button" class="ms-btn ms-btn-primary" x-on:click="createDependencyOpen = true">
                            Crear dependencia
                        </button>
                    </div>
                @endif

                <div class="ms-table-shell ms-table-shell-compact">
                    <table class="ms-data-table ms-data-table-compact">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Servicios</th>
                                <th>Usuarios</th>
                                <th>Respuestas</th>
                                <th>Estado</th>
                                @if ($canManageCatalogs)
                                    <th>Acciones</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dependencies as $dependency)
                                <tr class="ms-process-row" data-href="{{ route('process-dependency.dependencies.services', $dependency) }}">
                                    <td>
                                        <a href="{{ route('process-dependency.dependencies.services', $dependency) }}" class="font-semibold text-slate-700 hover:text-[#00a9ad]">
                                            {{ $dependency->nombre }}
                                        </a>
                                    </td>
                                    <td>{{ $dependency->servicios_totales }}</td>
                                    <td>{{ $dependency->usuarios_totales }}</td>
                                    <td>{{ $dependency->respuestas_totales }}</td>
                                    <td>{{ $dependency->activo ? 'Activo' : 'Inactivo' }}</td>
                                    @if ($canManageCatalogs)
                                        <td>
                                            <div class="ms-inline-actions">
                                                <button
                                                    type="button"
                                                    class="ms-btn ms-btn-secondary ms-btn-icon"
                                                    aria-label="Editar dependencia"
                                                    title="Editar dependencia"
                                                    x-on:click="editDependencyId = {{ $dependency->id_dependencia }}"
                                                >
                                                    <svg viewBox="0 0 24 24" aria-hidden="true" class="ms-btn-icon-svg">
                                                        <path d="M4 17.25V20h2.75L17.81 8.94l-2.75-2.75L4 17.25Z" fill="currentColor"/>
                                                        <path d="M19.71 7.04a1.003 1.003 0 0 0 0-1.42l-1.34-1.34a1.003 1.003 0 0 0-1.42 0l-1.05 1.05 2.75 2.75 1.06-1.04Z" fill="currentColor"/>
                                                    </svg>
                                                </button>

                                                @if ($dependency->activo)
                                                    <form method="POST" action="{{ route('process-dependency.dependencies.deactivate', $dependency) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="redirect_proceso" value="{{ $selectedProcess->id_proceso }}">
                                                        <button type="submit" class="ms-btn ms-btn-muted ms-btn-icon" aria-label="Inactivar dependencia" title="Inactivar dependencia">
                                                            <svg viewBox="0 0 24 24" aria-hidden="true" class="ms-btn-icon-svg">
                                                                <path d="M7 21c-.55 0-1-.45-1-1V7h12v13c0 .55-.45 1-1 1H7Z" fill="currentColor"/>
                                                                <path d="M9 4h6l1 1h4v2H4V5h4l1-1Z" fill="currentColor"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('process-dependency.dependencies.activate', $dependency) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="redirect_proceso" value="{{ $selectedProcess->id_proceso }}">
                                                        <button type="submit" class="ms-btn ms-btn-primary ms-btn-icon" aria-label="Activar dependencia" title="Activar dependencia">
                                                            <svg viewBox="0 0 24 24" aria-hidden="true" class="ms-btn-icon-svg">
                                                                <path d="M12 2 3 6v6c0 5 3.84 9.74 9 11 5.16-1.26 9-6 9-11V6l-9-4Z" fill="currentColor"/>
                                                                <path d="m10.5 14.5-2.5-2.5-1.5 1.5 4 4 7-7-1.5-1.5-5.5 5.5Z" fill="#fff"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $canManageCatalogs ? '6' : '5' }}">No hay dependencias registradas para este proceso.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        @if ($canManageCatalogs)
        <div
            x-show="createDependencyOpen"
            x-on:click.self="createDependencyOpen = false"
            style="display: none;"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
        >
            <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Crear dependencia</h2>
                        <p class="mt-1 text-sm text-slate-500">Asocia la dependencia al proceso correspondiente.</p>
                    </div>

                    <button type="button" class="text-sm font-semibold text-slate-500" x-on:click="createDependencyOpen = false">
                        Cerrar
                    </button>
                </div>

                @if ($createDependencyErrors->any())
                    <div class="mt-4 rounded-2xl border border-[#8ddcdf] bg-[#e6f8f8] px-4 py-3 text-sm text-[#0b5d60]">
                        <ul class="list-disc space-y-1 pl-5">
                            @foreach ($createDependencyErrors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('process-dependency.dependencies.store') }}" class="mt-6 space-y-5">
                    @csrf
                    <input type="hidden" name="redirect_proceso" value="{{ $selectedProcess->id_proceso }}">

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="create_dependency_name" class="block text-sm font-semibold text-slate-700">Nombre</label>
                            <input
                                id="create_dependency_name"
                                name="nombre"
                                type="text"
                                value="{{ old('nombre') }}"
                                class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                required
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label for="create_dependency_process" class="block text-sm font-semibold text-slate-700">Proceso</label>
                            <select
                                id="create_dependency_process"
                                name="id_proceso"
                                class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                required
                            >
                                <option value="">Seleccione un proceso</option>
                                @foreach ($activeProcesses as $process)
                                    <option
                                        value="{{ $process->id_proceso }}"
                                        @selected((string) old('id_proceso', $selectedProcess->id_proceso) === (string) $process->id_proceso)
                                    >
                                        {{ $process->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="hidden" name="activo" value="0">
                            <input
                                id="create_dependency_active"
                                name="activo"
                                type="checkbox"
                                value="1"
                                class="h-4 w-4 rounded border-slate-300 text-[#00a9ad]"
                                @checked(old('activo', true))
                            >
                            <label for="create_dependency_active" class="text-sm font-semibold text-slate-700">Dependencia activa</label>
                        </div>
                    </div>

                    <div class="ms-form-actions">
                        <button type="submit" class="ms-btn ms-btn-primary">Guardar dependencia</button>
                        <button type="button" class="ms-btn ms-btn-secondary" x-on:click="createDependencyOpen = false">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        @if ($canManageCatalogs)
        @foreach ($dependencies as $dependency)
            @php
                $isEditingDependency = (int) $openEditDependency === (int) $dependency->id_dependencia;
                $editDependencyName = $isEditingDependency ? old('nombre', $dependency->nombre) : $dependency->nombre;
                $editDependencyProcess = $isEditingDependency ? old('id_proceso', $dependency->id_proceso) : $dependency->id_proceso;
                $editDependencyActive = $isEditingDependency ? (bool) old('activo', $dependency->activo) : (bool) $dependency->activo;
            @endphp

            <div
                x-show="editDependencyId === {{ $dependency->id_dependencia }}"
                x-on:click.self="editDependencyId = null"
                style="display: none;"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
            >
                <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">Editar dependencia</h2>
                            <p class="mt-1 text-sm text-slate-500">Actualiza los datos de {{ $dependency->nombre }}.</p>
                        </div>

                        <button type="button" class="text-sm font-semibold text-slate-500" x-on:click="editDependencyId = null">
                            Cerrar
                        </button>
                    </div>

                    @if ($isEditingDependency && $updateDependencyErrors->any())
                        <div class="mt-4 rounded-2xl border border-[#8ddcdf] bg-[#e6f8f8] px-4 py-3 text-sm text-[#0b5d60]">
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach ($updateDependencyErrors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('process-dependency.dependencies.update', $dependency) }}" class="mt-6 space-y-5">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="redirect_proceso" value="{{ $selectedProcess->id_proceso }}">

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="edit_dependency_name_{{ $dependency->id_dependencia }}" class="block text-sm font-semibold text-slate-700">Nombre</label>
                                <input
                                    id="edit_dependency_name_{{ $dependency->id_dependencia }}"
                                    name="nombre"
                                    type="text"
                                    value="{{ $editDependencyName }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                    required
                                >
                            </div>

                            <div class="md:col-span-2">
                                <label for="edit_dependency_process_{{ $dependency->id_dependencia }}" class="block text-sm font-semibold text-slate-700">Proceso</label>
                                <select
                                    id="edit_dependency_process_{{ $dependency->id_dependencia }}"
                                    name="id_proceso"
                                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                    required
                                >
                                    <option value="">Seleccione un proceso</option>
                                    @foreach ($processes as $process)
                                        <option
                                            value="{{ $process->id_proceso }}"
                                            @selected((string) $editDependencyProcess === (string) $process->id_proceso)
                                            @disabled(! $process->activo && (int) $editDependencyProcess !== (int) $process->id_proceso)
                                        >
                                            {{ $process->nombre }}{{ $process->activo ? '' : ' (Inactivo)' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-center gap-3">
                                <input type="hidden" name="activo" value="0">
                                <input
                                    id="edit_dependency_active_{{ $dependency->id_dependencia }}"
                                    name="activo"
                                    type="checkbox"
                                    value="1"
                                    class="h-4 w-4 rounded border-slate-300 text-[#00a9ad]"
                                    @checked($editDependencyActive)
                                >
                                <label for="edit_dependency_active_{{ $dependency->id_dependencia }}" class="text-sm font-semibold text-slate-700">Dependencia activa</label>
                            </div>
                        </div>

                        <div class="ms-form-actions">
                            <button type="submit" class="ms-btn ms-btn-primary">Guardar cambios</button>
                            <button type="button" class="ms-btn ms-btn-secondary" x-on:click="editDependencyId = null">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.ms-process-row[data-href]').forEach((row) => {
                row.addEventListener('click', (event) => {
                    if (event.target.closest('a, button, form, input, select, label')) {
                        return;
                    }

                    const href = row.getAttribute('data-href');

                    if (href) {
                        window.location.assign(href);
                    }
                });
            });
        });
    </script>
</x-app-layout>
