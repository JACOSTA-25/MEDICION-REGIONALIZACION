document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-survey-form]');

    if (!form) {
        return;
    }

    const estamentoSelect = document.getElementById('id_estamento');
    const programaContainer = document.getElementById('programa-container');
    const programaSelect = document.getElementById('id_programa');
    const procesoSelect = document.getElementById('id_proceso');
    const dependenciaSelect = document.getElementById('id_dependencia');
    const servicioSelect = document.getElementById('id_servicio');

    const createPlaceholder = (label) => {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = label;

        return option;
    };

    const resetSelect = (select, label, disabled = true) => {
        select.innerHTML = '';
        select.append(createPlaceholder(label));
        select.disabled = disabled;
    };

    const populateSelect = (select, items, label) => {
        resetSelect(select, label, items.length === 0);

        items.forEach((item) => {
            const option = document.createElement('option');
            option.value = String(item.id);
            option.textContent = item.nombre;
            select.append(option);
        });
    };

    const togglePrograma = () => {
        const selectedOption = estamentoSelect.options[estamentoSelect.selectedIndex];
        const requiresProgram = selectedOption?.dataset.requiresProgram === '1';

        programaContainer.classList.toggle('hidden', !requiresProgram);

        if (requiresProgram) {
            programaSelect.disabled = false;
            programaSelect.required = true;
            return;
        }

        programaSelect.value = '';
        programaSelect.disabled = true;
        programaSelect.required = false;
    };

    const loadCatalog = async (url, select, label) => {
        resetSelect(select, label);

        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`Catalog request failed with status ${response.status}`);
            }

            const items = await response.json();
            populateSelect(select, items, label);
        } catch (error) {
            console.error(error);
            resetSelect(select, label);
        }
    };

    togglePrograma();

    if (!procesoSelect.value) {
        resetSelect(dependenciaSelect, 'Seleccione una dependencia');
        resetSelect(servicioSelect, 'Seleccione un servicio');
    } else {
        dependenciaSelect.disabled = dependenciaSelect.options.length <= 1;

        if (!dependenciaSelect.value) {
            resetSelect(servicioSelect, 'Seleccione un servicio');
        } else {
            servicioSelect.disabled = servicioSelect.options.length <= 1;
        }
    }

    estamentoSelect.addEventListener('change', () => {
        togglePrograma();
    });

    procesoSelect.addEventListener('change', async () => {
        resetSelect(servicioSelect, 'Seleccione un servicio');

        if (!procesoSelect.value) {
            resetSelect(dependenciaSelect, 'Seleccione una dependencia');
            return;
        }

        await loadCatalog(
            `/encuesta/catalogos/dependencias?id_proceso=${encodeURIComponent(procesoSelect.value)}`,
            dependenciaSelect,
            'Seleccione una dependencia',
        );
    });

    dependenciaSelect.addEventListener('change', async () => {
        if (!dependenciaSelect.value) {
            resetSelect(servicioSelect, 'Seleccione un servicio');
            return;
        }

        await loadCatalog(
            `/encuesta/catalogos/servicios?id_dependencia=${encodeURIComponent(dependenciaSelect.value)}`,
            servicioSelect,
            'Seleccione un servicio',
        );
    });
});
