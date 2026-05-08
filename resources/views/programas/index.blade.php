@php
    $currentUser = auth()->user();
    $createProgramErrors = $errors->getBag('createProgram');
    $updateProgramErrors = $errors->getBag('updateProgram');
    $openEditProgram = session('open_edit_program');
@endphp

<x-app-layout>
    <div
        class="ms-content-shell"
        x-data="{
            createProgramOpen: {{ session('open_create_program') || $createProgramErrors->any() ? 'true' : 'false' }},
            editProgramId: {{ $openEditProgram ? (int) $openEditProgram : 'null' }},
        }"
    >
        <x-generals.top-bar
            title="Gestion de programas"
            description="Administra los programas por sede. La encuesta los utiliza para estudiantes, docentes y egresados."
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
                    <h2>Programas registrados</h2>
                    <p>
                        Cada sede mantiene su propio catalogo de programas. Los estamentos estudiante, docente y egresado
                        seleccionan uno de estos programas al diligenciar la encuesta.
                    </p>
                </div>

                <div class="ms-form-actions" style="margin-top: 1rem;">
                    @if ($currentUser?->hasGlobalSedeAccess())
                        <form method="GET" action="{{ route('programs.index') }}" class="ms-inline-actions">
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

                    @if ($canManagePrograms)
                        <button type="button" class="ms-btn ms-btn-primary" x-on:click="createProgramOpen = true">
                            Crear programa
                        </button>
                    @endif
                </div>

                <div class="ms-table-shell ms-table-shell-compact">
                    <table class="ms-data-table ms-data-table-compact">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Sede</th>
                                <th>Respuestas</th>
                                @if ($canManagePrograms)
                                    <th>Acciones</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($programs as $program)
                                <tr>
                                    <td class="ms-cell-name">{{ $program->nombre }}</td>
                                    <td>{{ $program->sede?->nombre ?? 'Sin sede' }}</td>
                                    <td>{{ $program->respuestas_totales }}</td>
                                    @if ($canManagePrograms)
                                        <td>
                                            <div class="ms-inline-actions">
                                                <button
                                                    type="button"
                                                    class="ms-btn ms-btn-secondary ms-btn-icon"
                                                    aria-label="Editar programa"
                                                    title="Editar programa"
                                                    x-on:click="editProgramId = {{ $program->id_programa }}"
                                                >
                                                    <svg viewBox="0 0 24 24" aria-hidden="true" class="ms-btn-icon-svg">
                                                        <path d="M4 17.25V20h2.75L17.81 8.94l-2.75-2.75L4 17.25Z" fill="currentColor"/>
                                                        <path d="M19.71 7.04a1.003 1.003 0 0 0 0-1.42l-1.34-1.34a1.003 1.003 0 0 0-1.42 0l-1.05 1.05 2.75 2.75 1.06-1.04Z" fill="currentColor"/>
                                                    </svg>
                                                </button>

                                                <form method="POST" action="{{ route('programs.destroy', $program) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="redirect_sede" value="{{ $selectedSedeId }}">
                                                    <button
                                                        type="submit"
                                                        class="ms-btn ms-btn-muted ms-btn-icon"
                                                        aria-label="Eliminar programa"
                                                        title="Eliminar programa"
                                                        onclick="return confirm('Se eliminara este programa. ¿Deseas continuar?')"
                                                    >
                                                        <svg viewBox="0 0 24 24" aria-hidden="true" class="ms-btn-icon-svg">
                                                            <path d="M7 21c-.55 0-1-.45-1-1V7h12v13c0 .55-.45 1-1 1H7Z" fill="currentColor"/>
                                                            <path d="M9 4h6l1 1h4v2H4V5h4l1-1Z" fill="currentColor"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $canManagePrograms ? '4' : '3' }}">No hay programas registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        @if ($canManagePrograms)
            <div
                x-show="createProgramOpen"
                x-on:click.self="createProgramOpen = false"
                style="display: none;"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
            >
                <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">Crear programa</h2>
                            <p class="mt-1 text-sm text-slate-500">Registra un nuevo programa dentro de la sede correspondiente.</p>
                        </div>

                        <button type="button" class="text-sm font-semibold text-slate-500" x-on:click="createProgramOpen = false">
                            Cerrar
                        </button>
                    </div>

                    @if ($createProgramErrors->any())
                        <div class="mt-4 rounded-2xl border border-[#8ddcdf] bg-[#e6f8f8] px-4 py-3 text-sm text-[#0b5d60]">
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach ($createProgramErrors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('programs.store') }}" class="mt-6 space-y-5">
                        @csrf
                        <input type="hidden" name="redirect_sede" value="{{ $selectedSedeId }}">

                        <div class="grid gap-4 md:grid-cols-2">
                            @if ($currentUser?->isAdminSede())
                                <input type="hidden" name="id_sede" value="{{ $currentUser->id_sede }}">
                            @else
                                <div class="md:col-span-2">
                                    <label for="create_program_sede" class="block text-sm font-semibold text-slate-700">Sede</label>
                                    <select
                                        id="create_program_sede"
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
                                <label for="create_program_name" class="block text-sm font-semibold text-slate-700">Nombre</label>
                                <input
                                    id="create_program_name"
                                    name="nombre"
                                    type="text"
                                    value="{{ old('nombre') }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                    required
                                >
                            </div>
                        </div>

                        <div class="ms-form-actions">
                            <button type="submit" class="ms-btn ms-btn-primary">
                                Guardar programa
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @foreach ($programs as $program)
                <div
                    x-show="editProgramId === {{ $program->id_programa }}"
                    x-on:click.self="editProgramId = null"
                    style="display: none;"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
                >
                    <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-bold text-slate-900">Editar programa</h2>
                                <p class="mt-1 text-sm text-slate-500">Actualiza la informacion principal del programa.</p>
                            </div>

                            <button type="button" class="text-sm font-semibold text-slate-500" x-on:click="editProgramId = null">
                                Cerrar
                            </button>
                        </div>

                        @if ($updateProgramErrors->any() && (int) $openEditProgram === (int) $program->id_programa)
                            <div class="mt-4 rounded-2xl border border-[#8ddcdf] bg-[#e6f8f8] px-4 py-3 text-sm text-[#0b5d60]">
                                <ul class="list-disc space-y-1 pl-5">
                                    @foreach ($updateProgramErrors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('programs.update', $program) }}" class="mt-6 space-y-5">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="redirect_sede" value="{{ $selectedSedeId }}">

                            <div class="grid gap-4 md:grid-cols-2">
                                @if ($currentUser?->isAdminSede())
                                    <input type="hidden" name="id_sede" value="{{ $currentUser->id_sede }}">
                                @else
                                    <div class="md:col-span-2">
                                        <label for="edit_program_sede_{{ $program->id_programa }}" class="block text-sm font-semibold text-slate-700">Sede</label>
                                        <select
                                            id="edit_program_sede_{{ $program->id_programa }}"
                                            name="id_sede"
                                            class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                            required
                                        >
                                            <option value="">Seleccione una sede</option>
                                            @foreach ($sedes as $sede)
                                                <option value="{{ $sede->id_sede }}" @selected((string) old('id_sede', $program->id_sede) === (string) $sede->id_sede)>
                                                    {{ $sede->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <div class="md:col-span-2">
                                    <label for="edit_program_name_{{ $program->id_programa }}" class="block text-sm font-semibold text-slate-700">Nombre</label>
                                    <input
                                        id="edit_program_name_{{ $program->id_programa }}"
                                        name="nombre"
                                        type="text"
                                        value="{{ old('nombre', $program->nombre) }}"
                                        class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="ms-form-actions">
                                <button type="submit" class="ms-btn ms-btn-primary">
                                    Guardar cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</x-app-layout>
