@php
    $createProcessErrors = $errors->getBag('createProcess');
    $updateProcessErrors = $errors->getBag('updateProcess');
    $openEditProcess = session('open_edit_process');
@endphp

<x-app-layout>
    <div
        class="ms-content-shell"
        x-data="{
            createProcessOpen: {{ session('open_create_process') || $createProcessErrors->any() ? 'true' : 'false' }},
            editProcessId: {{ $openEditProcess ? (int) $openEditProcess : 'null' }},
        }"
    >
        <x-generals.top-bar
            title="Gestion de procesos y dependencias"
            description="Vista habilitada para ADMIN y ADMIN_2_0"
        />

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
                    <h2>Procesos</h2>
                    <p>Selecciona un proceso para ver y administrar sus dependencias.</p>
                </div>

                <div class="ms-form-actions" style="margin-top: 1rem;">
                    <button type="button" class="ms-btn ms-btn-primary" x-on:click="createProcessOpen = true">
                        Crear proceso
                    </button>
                </div>

                <div class="ms-table-shell">
                    <table class="ms-data-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Dependencias</th>
                                <th>Usuarios</th>
                                <th>Respuestas</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($processes as $process)
                                <tr class="ms-process-row" data-href="{{ route('process-dependency.processes.dependencies', $process) }}">
                                    <td>
                                        <a href="{{ route('process-dependency.processes.dependencies', $process) }}" class="font-semibold text-slate-700 hover:text-red-700">
                                            {{ $process->nombre }}
                                        </a>
                                    </td>
                                    <td>{{ $process->dependencias_totales }}</td>
                                    <td>{{ $process->usuarios_totales }}</td>
                                    <td>{{ $process->respuestas_totales }}</td>
                                    <td>{{ $process->activo ? 'Activo' : 'Inactivo' }}</td>
                                    <td>
                                        <div class="ms-inline-actions">
                                            <button
                                                type="button"
                                                class="ms-btn ms-btn-secondary"
                                                x-on:click="editProcessId = {{ $process->id_proceso }}"
                                            >
                                                Editar
                                            </button>

                                            @if ($process->activo)
                                                <form method="POST" action="{{ route('process-dependency.processes.deactivate', $process) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        class="ms-btn ms-btn-muted"
                                                        onclick="return confirm('Se inactivara este proceso y sus dependencias activas. ¿Deseas continuar?')"
                                                    >
                                                        Inactivar
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('process-dependency.processes.activate', $process) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="ms-btn ms-btn-primary">
                                                        Activar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">No hay procesos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div
            x-show="createProcessOpen"
            x-on:click.self="createProcessOpen = false"
            style="display: none;"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
        >
            <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Crear proceso</h2>
                        <p class="mt-1 text-sm text-slate-500">Registra un nuevo proceso en el catalogo.</p>
                    </div>

                    <button type="button" class="text-sm font-semibold text-slate-500" x-on:click="createProcessOpen = false">
                        Cerrar
                    </button>
                </div>

                @if ($createProcessErrors->any())
                    <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-disc space-y-1 pl-5">
                            @foreach ($createProcessErrors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('process-dependency.processes.store') }}" class="mt-6 space-y-5">
                    @csrf

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="create_process_name" class="block text-sm font-semibold text-slate-700">Nombre</label>
                            <input
                                id="create_process_name"
                                name="nombre"
                                type="text"
                                value="{{ old('nombre') }}"
                                class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                required
                            >
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="hidden" name="activo" value="0">
                            <input
                                id="create_process_active"
                                name="activo"
                                type="checkbox"
                                value="1"
                                class="h-4 w-4 rounded border-slate-300 text-red-700"
                                @checked(old('activo', true))
                            >
                            <label for="create_process_active" class="text-sm font-semibold text-slate-700">Proceso activo</label>
                        </div>
                    </div>

                    <div class="ms-form-actions">
                        <button type="submit" class="ms-btn ms-btn-primary">Guardar proceso</button>
                        <button type="button" class="ms-btn ms-btn-secondary" x-on:click="createProcessOpen = false">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>

        @foreach ($processes as $process)
            @php
                $isEditingProcess = (int) $openEditProcess === (int) $process->id_proceso;
                $editProcessName = $isEditingProcess ? old('nombre', $process->nombre) : $process->nombre;
                $editProcessActive = $isEditingProcess ? (bool) old('activo', $process->activo) : (bool) $process->activo;
            @endphp

            <div
                x-show="editProcessId === {{ $process->id_proceso }}"
                x-on:click.self="editProcessId = null"
                style="display: none;"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
            >
                <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">Editar proceso</h2>
                            <p class="mt-1 text-sm text-slate-500">Actualiza los datos de {{ $process->nombre }}.</p>
                        </div>

                        <button type="button" class="text-sm font-semibold text-slate-500" x-on:click="editProcessId = null">
                            Cerrar
                        </button>
                    </div>

                    @if ($isEditingProcess && $updateProcessErrors->any())
                        <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach ($updateProcessErrors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('process-dependency.processes.update', $process) }}" class="mt-6 space-y-5">
                        @csrf
                        @method('PUT')

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="edit_process_name_{{ $process->id_proceso }}" class="block text-sm font-semibold text-slate-700">Nombre</label>
                                <input
                                    id="edit_process_name_{{ $process->id_proceso }}"
                                    name="nombre"
                                    type="text"
                                    value="{{ $editProcessName }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                    required
                                >
                            </div>

                            <div class="flex items-center gap-3">
                                <input type="hidden" name="activo" value="0">
                                <input
                                    id="edit_process_active_{{ $process->id_proceso }}"
                                    name="activo"
                                    type="checkbox"
                                    value="1"
                                    class="h-4 w-4 rounded border-slate-300 text-red-700"
                                    @checked($editProcessActive)
                                >
                                <label for="edit_process_active_{{ $process->id_proceso }}" class="text-sm font-semibold text-slate-700">Proceso activo</label>
                            </div>
                        </div>

                        <div class="ms-form-actions">
                            <button type="submit" class="ms-btn ms-btn-primary">Guardar cambios</button>
                            <button type="button" class="ms-btn ms-btn-secondary" x-on:click="editProcessId = null">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
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
