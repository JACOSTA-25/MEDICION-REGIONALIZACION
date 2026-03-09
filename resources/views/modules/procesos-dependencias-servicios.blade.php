@php
    $createServiceErrors = $errors->getBag('createService');
    $updateServiceErrors = $errors->getBag('updateService');
    $openEditService = session('open_edit_service');
@endphp

<x-app-layout>
    <div
        class="ms-content-shell"
        x-data="{
            createServiceOpen: {{ session('open_create_service') || $createServiceErrors->any() ? 'true' : 'false' }},
            editServiceId: {{ $openEditService ? (int) $openEditService : 'null' }},
        }"
    >
        <x-generals.top-bar
            :title="'Servicios de '.$selectedDependency->nombre"
            description="Gestiona los servicios asociados a la dependencia seleccionada."
        >
            <x-slot:actions>
                <a
                    href="{{ route('process-dependency.processes.dependencies', ['proceso' => $selectedProcess->id_proceso]) }}"
                    class="ms-btn ms-btn-secondary"
                >
                    Volver a dependencias
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
                    <h2>Servicios de {{ $selectedDependency->nombre }}</h2>
                    <p>Mantiene las operaciones de creacion, edicion y control de estado para cada servicio.</p>
                </div>

                <div class="ms-form-actions" style="margin-top: 1rem;">
                    <button type="button" class="ms-btn ms-btn-primary" x-on:click="createServiceOpen = true">
                        Crear servicio
                    </button>
                </div>

                <div class="ms-table-shell">
                    <table class="ms-data-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Respuestas</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($services as $service)
                                <tr>
                                    <td>{{ $service->nombre }}</td>
                                    <td>{{ $service->respuestas_totales }}</td>
                                    <td>{{ $service->activo ? 'Activo' : 'Inactivo' }}</td>
                                    <td>
                                        <div class="ms-inline-actions">
                                            <button
                                                type="button"
                                                class="ms-btn ms-btn-secondary"
                                                x-on:click="editServiceId = {{ $service->id_servicio }}"
                                            >
                                                Editar
                                            </button>

                                            @if ($service->activo)
                                                <form method="POST" action="{{ route('process-dependency.services.deactivate', $service) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="redirect_proceso" value="{{ $selectedProcess->id_proceso }}">
                                                    <input type="hidden" name="redirect_dependencia" value="{{ $selectedDependency->id_dependencia }}">
                                                    <button type="submit" class="ms-btn ms-btn-muted">
                                                        Inactivar
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('process-dependency.services.activate', $service) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="redirect_proceso" value="{{ $selectedProcess->id_proceso }}">
                                                    <input type="hidden" name="redirect_dependencia" value="{{ $selectedDependency->id_dependencia }}">
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
                                    <td colspan="4">No hay servicios registrados para esta dependencia.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div
            x-show="createServiceOpen"
            x-on:click.self="createServiceOpen = false"
            style="display: none;"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
        >
            <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Crear servicio</h2>
                        <p class="mt-1 text-sm text-slate-500">Asocia el servicio a una dependencia activa.</p>
                    </div>

                    <button type="button" class="text-sm font-semibold text-slate-500" x-on:click="createServiceOpen = false">
                        Cerrar
                    </button>
                </div>

                @if ($createServiceErrors->any())
                    <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-disc space-y-1 pl-5">
                            @foreach ($createServiceErrors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('process-dependency.services.store') }}" class="mt-6 space-y-5">
                    @csrf
                    <input type="hidden" name="redirect_proceso" value="{{ $selectedProcess->id_proceso }}">
                    <input type="hidden" name="redirect_dependencia" value="{{ $selectedDependency->id_dependencia }}">

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="create_service_name" class="block text-sm font-semibold text-slate-700">Nombre</label>
                            <input
                                id="create_service_name"
                                name="nombre"
                                type="text"
                                value="{{ old('nombre') }}"
                                class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                required
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label for="create_service_dependency" class="block text-sm font-semibold text-slate-700">Dependencia</label>
                            <select
                                id="create_service_dependency"
                                name="id_dependencia"
                                class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                required
                            >
                                <option value="">Seleccione una dependencia</option>
                                @foreach ($activeDependencies as $dependency)
                                    <option
                                        value="{{ $dependency->id_dependencia }}"
                                        @selected((string) old('id_dependencia', $selectedDependency->id_dependencia) === (string) $dependency->id_dependencia)
                                    >
                                        {{ $dependency->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="hidden" name="activo" value="0">
                            <input
                                id="create_service_active"
                                name="activo"
                                type="checkbox"
                                value="1"
                                class="h-4 w-4 rounded border-slate-300 text-red-700"
                                @checked(old('activo', true))
                            >
                            <label for="create_service_active" class="text-sm font-semibold text-slate-700">Servicio activo</label>
                        </div>
                    </div>

                    <div class="ms-form-actions">
                        <button type="submit" class="ms-btn ms-btn-primary">Guardar servicio</button>
                        <button type="button" class="ms-btn ms-btn-secondary" x-on:click="createServiceOpen = false">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>

        @foreach ($services as $service)
            @php
                $isEditingService = (int) $openEditService === (int) $service->id_servicio;
                $editServiceName = $isEditingService ? old('nombre', $service->nombre) : $service->nombre;
                $editServiceDependency = $isEditingService ? old('id_dependencia', $service->id_dependencia) : $service->id_dependencia;
                $editServiceActive = $isEditingService ? (bool) old('activo', $service->activo) : (bool) $service->activo;
            @endphp

            <div
                x-show="editServiceId === {{ $service->id_servicio }}"
                x-on:click.self="editServiceId = null"
                style="display: none;"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
            >
                <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">Editar servicio</h2>
                            <p class="mt-1 text-sm text-slate-500">Actualiza los datos de {{ $service->nombre }}.</p>
                        </div>

                        <button type="button" class="text-sm font-semibold text-slate-500" x-on:click="editServiceId = null">
                            Cerrar
                        </button>
                    </div>

                    @if ($isEditingService && $updateServiceErrors->any())
                        <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach ($updateServiceErrors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('process-dependency.services.update', $service) }}" class="mt-6 space-y-5">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="redirect_proceso" value="{{ $selectedProcess->id_proceso }}">
                        <input type="hidden" name="redirect_dependencia" value="{{ $selectedDependency->id_dependencia }}">

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="edit_service_name_{{ $service->id_servicio }}" class="block text-sm font-semibold text-slate-700">Nombre</label>
                                <input
                                    id="edit_service_name_{{ $service->id_servicio }}"
                                    name="nombre"
                                    type="text"
                                    value="{{ $editServiceName }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                    required
                                >
                            </div>

                            <div class="md:col-span-2">
                                <label for="edit_service_dependency_{{ $service->id_servicio }}" class="block text-sm font-semibold text-slate-700">Dependencia</label>
                                <select
                                    id="edit_service_dependency_{{ $service->id_servicio }}"
                                    name="id_dependencia"
                                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                    required
                                >
                                    <option value="">Seleccione una dependencia</option>
                                    @foreach ($dependencies as $dependency)
                                        <option
                                            value="{{ $dependency->id_dependencia }}"
                                            @selected((string) $editServiceDependency === (string) $dependency->id_dependencia)
                                            @disabled(! $dependency->activo && (int) $editServiceDependency !== (int) $dependency->id_dependencia)
                                        >
                                            {{ $dependency->nombre }}{{ $dependency->activo ? '' : ' (Inactiva)' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-center gap-3">
                                <input type="hidden" name="activo" value="0">
                                <input
                                    id="edit_service_active_{{ $service->id_servicio }}"
                                    name="activo"
                                    type="checkbox"
                                    value="1"
                                    class="h-4 w-4 rounded border-slate-300 text-red-700"
                                    @checked($editServiceActive)
                                >
                                <label for="edit_service_active_{{ $service->id_servicio }}" class="text-sm font-semibold text-slate-700">Servicio activo</label>
                            </div>
                        </div>

                        <div class="ms-form-actions">
                            <button type="submit" class="ms-btn ms-btn-primary">Guardar cambios</button>
                            <button type="button" class="ms-btn ms-btn-secondary" x-on:click="editServiceId = null">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>
