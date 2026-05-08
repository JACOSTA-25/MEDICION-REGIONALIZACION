@php
    $currentUser = auth()->user();
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
            description="Consulta o administra la estructura organizacional por sede."
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
                    @if ($currentUser?->hasGlobalSedeAccess())
                        <form method="GET" action="{{ route('process-dependency.index') }}" class="ms-inline-actions">
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

                    @if ($canManageCatalogs)
                        <button type="button" class="ms-btn ms-btn-primary" x-on:click="createProcessOpen = true">
                            Crear proceso
                        </button>
                    @endif
                </div>

                <div class="ms-table-shell ms-table-shell-compact">
                    <table class="ms-data-table ms-data-table-compact">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Sede</th>
                                <th>Dependencias</th>
                                <th>Usuarios</th>
                                <th>Respuestas</th>
                                <th>Estado</th>
                                @if ($canManageCatalogs)
                                    <th>Acciones</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($processes as $process)
                                <tr class="ms-process-row" data-href="{{ route('process-dependency.processes.dependencies', $process) }}">
                                    <td>
                                        <a href="{{ route('process-dependency.processes.dependencies', $process) }}" class="font-semibold text-slate-700 hover:text-[#00a9ad]">
                                            {{ $process->nombre }}
                                        </a>
                                    </td>
                                    <td>{{ $process->sede?->nombre ?? 'Sin sede' }}</td>
                                    <td>{{ $process->dependencias_totales }}</td>
                                    <td>{{ $process->usuarios_totales }}</td>
                                    <td>{{ $process->respuestas_totales }}</td>
                                    <td>{{ $process->activo ? 'Activo' : 'Inactivo' }}</td>
                                    @if ($canManageCatalogs)
                                        <td>
                                            <div class="ms-inline-actions">
                                                <button
                                                    type="button"
                                                    class="ms-btn ms-btn-secondary ms-btn-icon"
                                                    aria-label="Editar proceso"
                                                    title="Editar proceso"
                                                    x-on:click="editProcessId = {{ $process->id_proceso }}"
                                                >
                                                    <svg viewBox="0 0 24 24" aria-hidden="true" class="ms-btn-icon-svg">
                                                        <path d="M4 17.25V20h2.75L17.81 8.94l-2.75-2.75L4 17.25Z" fill="currentColor"/>
                                                        <path d="M19.71 7.04a1.003 1.003 0 0 0 0-1.42l-1.34-1.34a1.003 1.003 0 0 0-1.42 0l-1.05 1.05 2.75 2.75 1.06-1.04Z" fill="currentColor"/>
                                                    </svg>
                                                </button>

                                                @if ($process->activo)
                                                    <form method="POST" action="{{ route('process-dependency.processes.deactivate', $process) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button
                                                            type="submit"
                                                            class="ms-btn ms-btn-muted ms-btn-icon"
                                                            aria-label="Inactivar proceso"
                                                            title="Inactivar proceso"
                                                            onclick="return confirm('Se inactivara este proceso y sus dependencias activas. ¿Deseas continuar?')"
                                                        >
                                                            <svg viewBox="0 0 24 24" aria-hidden="true" class="ms-btn-icon-svg">
                                                                <path d="M7 21c-.55 0-1-.45-1-1V7h12v13c0 .55-.45 1-1 1H7Z" fill="currentColor"/>
                                                                <path d="M9 4h6l1 1h4v2H4V5h4l1-1Z" fill="currentColor"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('process-dependency.processes.activate', $process) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="ms-btn ms-btn-primary ms-btn-icon" aria-label="Activar proceso" title="Activar proceso">
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
                                    <td colspan="{{ $canManageCatalogs ? '7' : '6' }}">No hay procesos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        @if ($canManageCatalogs)
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
                        <div class="mt-4 rounded-2xl border border-[#8ddcdf] bg-[#e6f8f8] px-4 py-3 text-sm text-[#0b5d60]">
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
                            @if ($currentUser?->isAdminSede())
                                <input type="hidden" name="id_sede" value="{{ $currentUser->id_sede }}">
                            @else
                                <div class="md:col-span-2">
                                    <label for="create_process_sede" class="block text-sm font-semibold text-slate-700">Sede</label>
                                    <select
                                        id="create_process_sede"
                                        name="id_sede"
                                        class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                        required
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
                                    class="h-4 w-4 rounded border-slate-300 text-[#00a9ad]"
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
        @endif

        @if ($canManageCatalogs)
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
                        <div class="mt-4 rounded-2xl border border-[#8ddcdf] bg-[#e6f8f8] px-4 py-3 text-sm text-[#0b5d60]">
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
                            @if ($currentUser?->isAdminSede())
                                <input type="hidden" name="id_sede" value="{{ $currentUser->id_sede }}">
                            @else
                                <div class="md:col-span-2">
                                    <label for="edit_process_sede_{{ $process->id_proceso }}" class="block text-sm font-semibold text-slate-700">Sede</label>
                                    <select
                                        id="edit_process_sede_{{ $process->id_proceso }}"
                                        name="id_sede"
                                        class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                        required
                                    >
                                        <option value="">Seleccione una sede</option>
                                        @foreach ($sedes as $sede)
                                            <option value="{{ $sede->id_sede }}" @selected((string) old('id_sede', $process->id_sede) === (string) $sede->id_sede)>
                                                {{ $sede->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

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
                                    class="h-4 w-4 rounded border-slate-300 text-[#00a9ad]"
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
