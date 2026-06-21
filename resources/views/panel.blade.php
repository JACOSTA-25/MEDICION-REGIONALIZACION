@php
    $user = auth()->user();
    $sedeService = app(\App\Services\Sedes\ServicioSedes::class);
    $selectedScopeSedeId = $sedeService->resolveForRequest($user, request());
    $selectedScopeSede = $selectedScopeSedeId !== null
        ? $sedeService->active()->firstWhere('id_sede', $selectedScopeSedeId)
        : null;
    $surveyLink = $selectedScopeSede?->slug
        ? route('survey.create', ['sede' => $selectedScopeSede->slug])
        : ($user?->id_sede && $user->sede?->slug
            ? route('survey.create', ['sede' => $user->sede->slug])
            : route('survey.create'));
    $quarterErrors = $errors->getBag('updateQuarters');

    $qrSubject = 'Encuesta institucional de medicion de servicios';
    $qrBody = 'Comparto el acceso directo a la encuesta institucional:'."\n\n".$surveyLink;
    $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=480x480&margin=16&data='.urlencode($surveyLink);
    $gmailShareUrl = 'https://mail.google.com/mail/?view=cm&fs=1&su='.urlencode($qrSubject).'&body='.urlencode($qrBody);
    $whatsAppShareUrl = 'https://wa.me/?text='.urlencode('Comparto el acceso directo a la encuesta institucional: '.$surveyLink);

    $modules = [
        [
            'visible' => $user->puedeAccederModuloReportes(),
            'title' => 'Reportes',
            'description' => 'Modulo de reporteria: general, por proceso e individual segun tu rol.',
            'route' => route('reports.index'),
        ],
        [
            'visible' => $user->puedeAccederModuloUsuarios(),
            'title' => 'Gestion de usuarios',
            'description' => 'Modulo de administracion de usuarios.',
            'route' => route('users.index'),
        ],
        [
            'visible' => $user->puedeAccederModuloProgramas(),
            'title' => 'Gestion de programas',
            'description' => 'Modulo para administrar los programas por sede.',
            'route' => route('programs.index'),
        ],
        [
            'visible' => $user->puedeAccederModuloEstructuraOrganizacional(),
            'title' => 'Procesos y dependencias',
            'description' => 'Modulo para administrar el catalogo organizacional.',
            'route' => route('process-dependency.index'),
        ],
    ];
@endphp

<x-app-layout>
    <div class="ms-content-shell">
        <x-generals.top-bar
            title="Inicio"
            description="Accesos disponibles segun el rol activo: {{ $user->rol }}"
        />

        <div class="ms-panel-body">
            <div class="ms-dashboard-stack">
                <section class="ms-report-card">
                    <div class="ms-report-card-header">
                        <h2>Codigo QR de la encuesta</h2>
                        <p>El formulario publico siempre es el mismo. Escanea el codigo QR, comparte la encuesta o descarga el codigo en formato PNG.</p>
                    </div>

                    <input type="hidden" id="dashboard-survey-link" value="{{ $surveyLink }}">

                    <div class="ms-qr-share">
                        <div class="ms-qr-share-image">
                            <img
                                src="{{ $qrImageUrl }}"
                                alt="Codigo QR de la encuesta institucional"
                                class="block w-full max-w-[320px] rounded-2xl"
                                loading="eager"
                            >
                        </div>

                        <div class="ms-form-actions">
                            <a href="{{ $surveyLink }}" target="_blank" rel="noopener noreferrer" class="ms-btn ms-btn-primary">
                                Abrir encuesta
                            </a>
                            <button
                                type="button"
                                class="ms-btn ms-btn-primary"
                                data-qr-download-button
                                data-qr-image-url="{{ $qrImageUrl }}"
                                data-qr-filename="qr-encuesta-medicion.png"
                            >
                                Descargar QR
                            </button>
                            <a href="{{ $gmailShareUrl }}" target="_blank" rel="noopener noreferrer" class="ms-btn ms-btn-secondary">
                                Compartir por Correo
                            </a>
                            <a href="{{ $whatsAppShareUrl }}" target="_blank" rel="noopener noreferrer" class="ms-btn ms-btn-secondary">
                                Compartir por WhatsApp
                            </a>
                            <button type="button" class="ms-btn ms-btn-secondary" data-copy-trigger data-copy-target="#dashboard-survey-link">
                                Copiar link
                            </button>
                            <a href="{{ $qrImageUrl }}" target="_blank" rel="noopener noreferrer" class="ms-btn ms-btn-secondary">
                                Ver imagen
                            </a>
                        </div>
                    </div>

                    <p class="ms-form-note" data-qr-download-status>
                        Puedes abrir el enlace, copiarlo, descargar el QR o compartirlo directamente por Gmail y WhatsApp.
                    </p>
                </section>

                <section class="ms-report-card ms-report-card-accent">
                    <div class="ms-report-card-header">
                        <h2>Navegacion del sistema</h2>
                        <p>Los modulos se consultan desde el menu lateral para evitar accesos duplicados en esta pantalla.</p>
                    </div>

                    @php
                        $availableModules = collect($modules)->where('visible', true)->pluck('title')->all();
                    @endphp

                    @if ($availableModules !== [])
                        <div class="ms-inline-checklist">
                            <p>Modulos habilitados para este usuario:</p>
                            <ul>
                                @foreach ($availableModules as $moduleName)
                                    <li>{{ $moduleName }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="ms-inline-alert">
                            Tu usuario no tiene modulos habilitados. Contacta al administrador.
                        </div>
                    @endif
                </section>

                <section class="ms-report-card">
                    <div class="ms-report-card-header">
                        <h2>Trimestres {{ $quarterYear }} - {{ $quarterScopeLabel }}</h2>
                        <p>
                            El sistema genera los reportes usando el trimestre seleccionado para el alcance activo. Aqui se definen las fechas que usara cada uno.
                        </p>
                    </div>

                    @if (session('quarter_status'))
                        <div class="ms-inline-alert ms-inline-alert-soft">
                            {{ session('quarter_status') }}
                        </div>
                    @endif

                    @if ($quarterErrors->any())
                        <div class="ms-inline-alert">
                            {{ $quarterErrors->first() }}
                        </div>
                    @endif

                    @if ($puedeGestionarTrimestres)
                        <form
                            method="POST"
                            action="{{ route('dashboard.quarters.update') }}"
                            class="ms-report-form"
                            data-quarter-validation-form
                        >
                            @csrf
                            @method('PUT')

                            <div class="ms-table-shell">
                                <table class="ms-data-table ms-data-table-compact">
                                    <thead>
                                        <tr>
                                            <th>Trimestre</th>
                                            <th>Fecha inicial</th>
                                            <th>Fecha final</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($quarters as $quarter)
                                            @php
                                                $quarterLimit = $quarterLimits[$quarter->quarter_number] ?? null;
                                            @endphp
                                            <tr>
                                                <td class="ms-cell-name">{{ $quarter->label() }}</td>
                                                <td>
                                                    <input
                                                        class="ms-quarter-input"
                                                        type="date"
                                                        name="quarters[{{ $quarter->quarter_number }}][start_date]"
                                                        value="{{ old('quarters.'.$quarter->quarter_number.'.start_date', $quarter->start_date?->format('Y-m-d')) }}"
                                                        min="{{ $quarterLimit['start_date'] ?? '' }}"
                                                        max="{{ $quarterLimit['end_date'] ?? '' }}"
                                                        data-quarter-number="{{ $quarter->quarter_number }}"
                                                        data-quarter-label="{{ $quarter->label() }}"
                                                        data-allowed-start="{{ $quarterLimit['start_date'] ?? '' }}"
                                                        data-allowed-end="{{ $quarterLimit['end_date'] ?? '' }}"
                                                        data-quarter-role="start"
                                                    >
                                                </td>
                                                <td>
                                                    <input
                                                        class="ms-quarter-input"
                                                        type="date"
                                                        name="quarters[{{ $quarter->quarter_number }}][end_date]"
                                                        value="{{ old('quarters.'.$quarter->quarter_number.'.end_date', $quarter->end_date?->format('Y-m-d')) }}"
                                                        min="{{ $quarterLimit['start_date'] ?? '' }}"
                                                        max="{{ $quarterLimit['end_date'] ?? '' }}"
                                                        data-quarter-number="{{ $quarter->quarter_number }}"
                                                        data-quarter-label="{{ $quarter->label() }}"
                                                        data-allowed-start="{{ $quarterLimit['start_date'] ?? '' }}"
                                                        data-allowed-end="{{ $quarterLimit['end_date'] ?? '' }}"
                                                        data-quarter-role="end"
                                                    >
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="ms-form-actions">
                                <button type="submit" class="ms-btn ms-btn-primary">
                                    Guardar trimestres
                                </button>
                            </div>

                            <p class="ms-form-note">
                                El Super Administrador puede actualizar el alcance global o la sede seleccionada. El Administrador de sede solo puede actualizar su propia sede. Cada trimestre debe mantenerse dentro de su rango natural: enero-marzo, abril-junio, julio-septiembre y octubre-diciembre.
                            </p>
                        </form>
                    @else
                        <div class="ms-table-shell">
                            <table class="ms-data-table ms-data-table-compact">
                                <thead>
                                    <tr>
                                        <th>Trimestre</th>
                                        <th>Periodo configurado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($quarters as $quarter)
                                        <tr>
                                            <td class="ms-cell-name">{{ $quarter->label() }}</td>
                                            <td>{{ $quarter->periodLabel() }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <p class="ms-form-note">
                            Esta configuracion es administrada por el Super Administrador o por el Administrador de la sede correspondiente desde esta misma pantalla.
                        </p>
                    @endif
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
