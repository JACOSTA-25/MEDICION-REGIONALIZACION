document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    document.querySelectorAll('[data-report-shell]').forEach((shell) => {
        const form = shell.querySelector('[data-report-filter-form]');
        const processSelect = shell.querySelector('[data-process-select]');
        const dependencySelect = shell.querySelector('[data-dependency-select]');
        const pdfButton = shell.querySelector('[data-report-pdf-button]');
        const conclusionShell = document.querySelector('[data-report-conclusion-shell]');
        const conclusionTextarea = conclusionShell?.querySelector('[data-report-conclusion-textarea]');
        const concludeButton = conclusionShell?.querySelector('[data-report-conclude-button]');
        const conclusionStatus = conclusionShell?.querySelector('[data-report-conclusion-status]');

        if (!form) {
            return;
        }

        let confirmedConclusion = '';
        let lastGeneratedConclusion = '';
        let isGeneratingConclusion = false;

        const setConclusionStatus = (message, state = '') => {
            if (!conclusionStatus) {
                return;
            }

            conclusionStatus.textContent = message;

            if (state) {
                conclusionStatus.dataset.state = state;
                return;
            }

            delete conclusionStatus.dataset.state;
        };

        const setPdfButtonEnabled = (enabled) => {
            if (!pdfButton) {
                return;
            }

            pdfButton.disabled = !enabled;
            pdfButton.setAttribute('aria-disabled', enabled ? 'false' : 'true');
        };

        const createPlaceholder = (label) => {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = label;

            return option;
        };

        const resetDependencySelect = (disabled = true) => {
            dependencySelect.innerHTML = '';
            dependencySelect.append(createPlaceholder('Seleccione una dependencia'));
            dependencySelect.disabled = disabled;
        };

        const loadDependencies = async () => {
            if (!processSelect.value) {
                resetDependencySelect();
                return;
            }

            resetDependencySelect();

            try {
                const endpoint = form.dataset.dependenciasEndpoint;
                const response = await fetch(
                    `${endpoint}?id_proceso=${encodeURIComponent(processSelect.value)}&include_inactive=1`,
                    {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    },
                );

                if (!response.ok) {
                    throw new Error(`Dependency request failed with status ${response.status}`);
                }

                const dependencies = await response.json();
                const selectedDependencyId = dependencySelect.dataset.selected;

                resetDependencySelect(dependencies.length === 0);

                dependencies.forEach((dependency) => {
                    const option = document.createElement('option');
                    option.value = String(dependency.id);
                    option.textContent = dependency.nombre;

                    if (selectedDependencyId && selectedDependencyId === String(dependency.id)) {
                        option.selected = true;
                    }

                    dependencySelect.append(option);
                });

                dependencySelect.dataset.selected = '';
            } catch (error) {
                console.error(error);
                resetDependencySelect();
            }
        };

        const requestConclusion = async () => {
            if (!conclusionShell || !conclusionTextarea || !concludeButton) {
                return;
            }

            if (isGeneratingConclusion) {
                return;
            }

            const endpoint = conclusionShell.dataset.conclusionUrl;

            if (!endpoint) {
                setConclusionStatus('No se encontro la ruta para generar la conclusion.', 'error');
                return;
            }

            isGeneratingConclusion = true;
            concludeButton.disabled = true;
            setConclusionStatus('Generando conclusion con IA...', 'loading');

            try {
                const payload = Object.fromEntries(new FormData(form).entries());
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message ?? 'No fue posible generar la conclusion.');
                }

                const conclusion = String(data.conclusion ?? '').trim();

                if (!conclusion) {
                    throw new Error('La IA no devolvio una conclusion utilizable.');
                }

                lastGeneratedConclusion = conclusion;
                confirmedConclusion = conclusion;
                conclusionTextarea.value = conclusion;
                setPdfButtonEnabled(true);
                setConclusionStatus('Conclusion generada y confirmada. Ya puedes descargar el PDF o editar el texto y volver a oprimir Concluir.', 'success');
            } catch (error) {
                setPdfButtonEnabled(false);
                const message = error instanceof Error ? error.message : 'No fue posible generar la conclusion.';
                setConclusionStatus(`${message} Si lo necesitas, puedes escribir la conclusion manualmente en el cuadro y oprimir Concluir para habilitar el PDF.`, 'error');
            } finally {
                isGeneratingConclusion = false;
                concludeButton.disabled = false;
            }
        };

        if (processSelect && dependencySelect) {
            processSelect.addEventListener('change', async () => {
                dependencySelect.dataset.selected = '';
                await loadDependencies();
            });
        }

        if (conclusionTextarea && concludeButton) {
            conclusionTextarea.addEventListener('input', () => {
                const nextValue = conclusionTextarea.value.trim();

                if (nextValue === confirmedConclusion) {
                    if (confirmedConclusion !== '') {
                        setPdfButtonEnabled(true);
                        setConclusionStatus('La conclusion confirmada sigue lista para el PDF.', 'success');
                    }

                    return;
                }

                setPdfButtonEnabled(false);

                if (nextValue === '') {
                    setConclusionStatus('El textarea esta vacio. Oprime Concluir para volver a generar la conclusion con IA.', '');
                    return;
                }

                setConclusionStatus('Hay cambios pendientes. Oprime Concluir para confirmar esta version y habilitar el PDF.', '');
            });

            concludeButton.addEventListener('click', async () => {
                const currentValue = conclusionTextarea.value.trim();

                if (currentValue !== '' && currentValue !== confirmedConclusion) {
                    confirmedConclusion = currentValue;
                    setPdfButtonEnabled(true);
                    setConclusionStatus('Conclusion confirmada. El PDF se descargara con esta version editada.', 'success');
                    return;
                }

                if (currentValue !== '' && currentValue === confirmedConclusion) {
                    setPdfButtonEnabled(true);
                    setConclusionStatus('La conclusion ya esta confirmada para el PDF.', 'success');
                    return;
                }

                await requestConclusion();
            });
        }

        if (pdfButton) {
            pdfButton.addEventListener('click', () => {
                if (pdfButton.disabled || !confirmedConclusion) {
                    return;
                }

                const pdfUrl = pdfButton.dataset.pdfUrl;

                if (!pdfUrl) {
                    return;
                }

                const target = new URL(pdfUrl, window.location.origin);
                target.searchParams.set('generated_conclusion', confirmedConclusion);
                window.location.assign(target.toString());
            });
        }
    });
});
