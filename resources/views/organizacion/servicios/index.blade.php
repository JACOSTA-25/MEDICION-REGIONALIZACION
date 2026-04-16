@php
    $createServiceErrors = $errors->getBag('createService');
    $updateServiceErrors = $errors->getBag('updateService');
    $openEditService = session('open_edit_service');
    $allEstamentoIds = $estamentos->pluck('id_estamento')->map(fn ($id) => (int) $id)->all();
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

                <div class="ms-table-shell ms-table-shell-compact">
                    <table class="ms-data-table ms-data-table-compact">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Estamentos habilitados</th>
                                <th>Respuestas</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($services as $service)
                                <tr>
                                    <td class="ms-cell-name">{{ $service->nombre }}</td>
                                    <td>
                                        @if ($serviceEstamentosEnabled)
                                            {{ $service->estamentos->pluck('nombre')->sort()->join(', ') ?: 'Sin estamentos habilitados' }}
                                        @else
                                            Configuracion no disponible en esta base de datos
                                        @endif
                                    </td>
                                    <td>{{ $service->respuestas_totales }}</td>
                                    <td>{{ $service->activo ? 'Activo' : 'Inactivo' }}</td>
                                    <td>
                                        <div class="ms-inline-actions">
                                            <button
                                                type="button"
                                                class="ms-btn ms-btn-secondary ms-btn-icon"
                                                aria-label="Editar servicio"
                                                title="Editar servicio"
                                                x-on:click="editServiceId = {{ $service->id_servicio }}"
                                            >
                                                <svg viewBox="0 0 24 24" aria-hidden="true" class="ms-btn-icon-svg">
                                                    <path d="M4 17.25V20h2.75L17.81 8.94l-2.75-2.75L4 17.25Z" fill="currentColor"/>
                                                    <path d="M19.71 7.04a1.003 1.003 0 0 0 0-1.42l-1.34-1.34a1.003 1.003 0 0 0-1.42 0l-1.05 1.05 2.75 2.75 1.06-1.04Z" fill="currentColor"/>
                                                </svg>
                                            </button>

                                            @if ($service->activo)
                                                <form method="POST" action="{{ route('process-dependency.services.deactivate', $service) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="redirect_proceso" value="{{ $selectedProcess->id_proceso }}">
                                                    <input type="hidden" name="redirect_dependencia" value="{{ $selectedDependency->id_dependencia }}">
                                                    <button type="submit" class="ms-btn ms-btn-muted ms-btn-icon" aria-label="Inactivar servicio" title="Inactivar servicio">
                                                        <svg viewBox="0 0 24 24" aria-hidden="true" class="ms-btn-icon-svg">
                                                            <path d="M7 21c-.55 0-1-.45-1-1V7h12v13c0 .55-.45 1-1 1H7Z" fill="currentColor"/>
                                                            <path d="M9 4h6l1 1h4v2H4V5h4l1-1Z" fill="currentColor"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('process-dependency.services.activate', $service) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="redirect_proceso" value="{{ $selectedProcess->id_proceso }}">
                                                    <input type="hidden" name="redirect_dependencia" value="{{ $selectedDependency->id_dependencia }}">
                                                    <button type="submit" class="ms-btn ms-btn-primary ms-btn-icon" aria-label="Activar servicio" title="Activar servicio">
                                                        <svg viewBox="0 0 24 24" aria-hidden="true" class="ms-btn-icon-svg">
                                                            <path d="M12 2 3 6v6c0 5 3.84 9.74 9 11 5.16-1.26 9-6 9-11V6l-9-4Z" fill="currentColor"/>
                                                            <path d="m10.5 14.5-2.5-2.5-1.5 1.5 4 4 7-7-1.5-1.5-5.5 5.5Z" fill="#fff"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">No hay servicios registrados para esta dependencia.</td>
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
                    <div class="mt-4 rounded-2xl border border-[#8ddcdf] bg-[#e6f8f8] px-4 py-3 text-sm text-[#0b5d60]">
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
                    @if ($serviceEstamentosEnabled)
                        <input type="hidden" name="sync_estamentos" value="1">
                    @endif

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
                                class="h-4 w-4 rounded border-slate-300 text-[#00a9ad]"
                                @checked(old('activo', true))
                            >
                            <label for="create_service_active" class="text-sm font-semibold text-slate-700">Servicio activo</label>
                        </div>

                        @if ($serviceEstamentosEnabled)
                            <div class="md:col-span-2">
                                <span class="block text-sm font-semibold text-slate-700">Estamentos autorizados</span>
                                <p class="mt-1 text-sm text-slate-500">Por defecto un servicio nuevo queda habilitado para todos y aqui puedes ajustarlo.</p>

                                @php
                                    $createSelectedEstamentos = collect(old('id_estamentos', $allEstamentoIds))
                                        ->map(fn ($id) => (int) $id)
                                        ->all();
                                @endphp

                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    @foreach ($estamentos as $estamento)
                                        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700">
                                            <input
                                                type="checkbox"
                                                name="id_estamentos[]"
                                                value="{{ $estamento->id_estamento }}"
                                                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#00a9ad]"
                                                @checked(in_array((int) $estamento->id_estamento, $createSelectedEstamentos, true))
                                            >
                                            <span>{{ $estamento->nombre }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="md:col-span-2">
                                <div class="ms-inline-alert ms-inline-alert-soft">
                                    La configuracion de estamentos no esta disponible en esta base de datos. Ejecuta la migracion pendiente para habilitarla.
                                </div>
                            </div>
                        @endif
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
                $editServiceEstamentos = $serviceEstamentosEnabled
                    ? ($isEditingService
                        ? collect(old('id_estamentos', $service->estamentos->pluck('id_estamento')->all()))->map(fn ($id) => (int) $id)->all()
                        : $service->estamentos->pluck('id_estamento')->map(fn ($id) => (int) $id)->all())
                    : [];
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
                        <div class="mt-4 rounded-2xl border border-[#8ddcdf] bg-[#e6f8f8] px-4 py-3 text-sm text-[#0b5d60]">
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
                        @if ($serviceEstamentosEnabled)
                            <input type="hidden" name="sync_estamentos" value="1">
                        @endif

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
                                    class="h-4 w-4 rounded border-slate-300 text-[#00a9ad]"
                                    @checked($editServiceActive)
                                >
                                <label for="edit_service_active_{{ $service->id_servicio }}" class="text-sm font-semibold text-slate-700">Servicio activo</label>
                            </div>

                            @if ($serviceEstamentosEnabled)
                                <div class="md:col-span-2">
                                    <span class="block text-sm font-semibold text-slate-700">Estamentos autorizados</span>
                                    <p class="mt-1 text-sm text-slate-500">Selecciona los estamentos que pueden recibir este servicio.</p>

                                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                        @foreach ($estamentos as $estamento)
                                            <label class="flex items-start gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700">
                                                <input
                                                    type="checkbox"
                                                    name="id_estamentos[]"
                                                    value="{{ $estamento->id_estamento }}"
                                                    class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#00a9ad]"
                                                    @checked(in_array((int) $estamento->id_estamento, $editServiceEstamentos, true))
                                                >
                                                <span>{{ $estamento->nombre }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="md:col-span-2">
                                    <div class="ms-inline-alert ms-inline-alert-soft">
                                        La configuracion de estamentos no esta disponible en esta base de datos. Ejecuta la migracion pendiente para habilitarla.
                                    </div>
                                </div>
                            @endif
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
