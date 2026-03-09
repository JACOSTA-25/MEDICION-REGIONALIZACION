document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-report-shell]').forEach((shell) => {
        const form = shell.querySelector('[data-report-filter-form]');
        const processSelect = shell.querySelector('[data-process-select]');
        const dependencySelect = shell.querySelector('[data-dependency-select]');

        if (!form) {
            return;
        }

        if (!processSelect || !dependencySelect) {
            return;
        }

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

        processSelect.addEventListener('change', async () => {
            dependencySelect.dataset.selected = '';
            await loadDependencies();
        });
    });
});
