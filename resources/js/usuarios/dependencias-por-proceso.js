document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.ms-content-shell[data-dependencies-by-process]').forEach((shell) => {
        const rawDependencies = shell.getAttribute('data-dependencies-by-process') ?? '{}';

        let dependenciesByProcess = {};

        try {
            dependenciesByProcess = JSON.parse(rawDependencies);
        } catch (error) {
            console.error(error);
            return;
        }

        const createPlaceholder = () => {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Seleccione una dependencia';

            return option;
        };

        const renderDependencies = (processSelect, dependencySelect) => {
            const processId = processSelect.value;
            const dependencies = processId ? (dependenciesByProcess[processId] ?? []) : [];
            const selectedDependencyId = dependencySelect.dataset.selected ?? '';

            dependencySelect.innerHTML = '';
            dependencySelect.append(createPlaceholder());

            dependencies.forEach((dependency) => {
                const option = document.createElement('option');
                option.value = String(dependency.id);
                option.textContent = dependency.nombre;

                if (selectedDependencyId !== '' && selectedDependencyId === String(dependency.id)) {
                    option.selected = true;
                }

                dependencySelect.append(option);
            });

            dependencySelect.disabled = !processId || dependencies.length === 0;
            dependencySelect.dataset.selected = '';
        };

        shell.querySelectorAll('[data-user-form]').forEach((form) => {
            const processSelect = form.querySelector('[data-user-process-select]');
            const dependencySelect = form.querySelector('[data-user-dependency-select]');

            if (!(processSelect instanceof HTMLSelectElement) || !(dependencySelect instanceof HTMLSelectElement)) {
                return;
            }

            renderDependencies(processSelect, dependencySelect);

            processSelect.addEventListener('change', () => {
                dependencySelect.dataset.selected = '';
                renderDependencies(processSelect, dependencySelect);
            });
        });
    });
});
