<x-app-layout>
    <div class="ms-content-shell">
        <x-generals.top-bar
            title="QR de encuesta"
            description="Comparte el acceso directo a la encuesta institucional con un codigo QR listo para descargar."
        />

        <div class="ms-panel-body">
            <div class="ms-report-grid">
                <section class="ms-report-card">
                    <div class="ms-report-card-header">
                        <h2>Codigo QR listo para compartir</h2>
                        <p>Escanea el codigo con la camara del celular para abrir la encuesta institucional.</p>
                    </div>

                    <div class="mt-6 flex justify-center">
                        <div class="rounded-[2rem] border-2 border-slate-200 bg-white p-4 shadow-sm">
                            <img
                                src="{{ $qrImageUrl }}"
                                alt="Codigo QR de la encuesta institucional"
                                class="block w-full max-w-[320px] rounded-2xl"
                                loading="eager"
                            >
                        </div>
                    </div>

                    <p class="ms-form-note">
                        Usa este QR en piezas impresas, pantallas informativas o mensajes digitales para dirigir a los usuarios al formulario.
                    </p>
                </section>

                <aside class="ms-report-card ms-report-card-accent">
                    <div class="ms-report-card-header">
                        <h2>Acciones rapidas</h2>
                        <p>Consulta el enlace oficial, comparte la encuesta y descarga el QR en formato PNG.</p>
                    </div>

                    <div class="ms-link-panel">
                        <label for="survey-qr-link">URL publica de la encuesta</label>
                        <input
                            id="survey-qr-link"
                            type="text"
                            readonly
                            value="{{ $surveyUrl }}"
                        >
                    </div>

                    <div class="ms-form-actions">
                        <a href="{{ $surveyUrl }}" target="_blank" rel="noopener noreferrer" class="ms-btn ms-btn-primary">
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
                        <button type="button" class="ms-btn ms-btn-secondary" data-copy-trigger data-copy-target="#survey-qr-link">
                            Copiar enlace
                        </button>
                        <a href="{{ $qrImageUrl }}" target="_blank" rel="noopener noreferrer" class="ms-btn ms-btn-secondary">
                            Ver imagen
                        </a>
                    </div>

                    <p class="ms-form-note" data-qr-download-status>
                        Puedes abrir el enlace, copiarlo, descargar el QR o compartirlo directamente por Gmail y WhatsApp.
                    </p>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
