const form = document.querySelector('[data-quarter-validation-form]');

if (form) {
    const quarterInputs = Array.from(form.querySelectorAll('.ms-quarter-input[data-quarter-number]'));

    const formatDate = (value) => {
        if (!value || !value.includes('-')) {
            return value;
        }

        const [year, month, day] = value.split('-');

        return `${day}/${month}/${year}`;
    };

    const findQuarterInput = (quarterNumber, role) =>
        form.querySelector(`.ms-quarter-input[data-quarter-number="${quarterNumber}"][data-quarter-role="${role}"]`);

    const setQuarterMessage = (quarterNumber, message) => {
        const startInput = findQuarterInput(quarterNumber, 'start');
        const endInput = findQuarterInput(quarterNumber, 'end');

        if (startInput) {
            startInput.setCustomValidity(message);
        }

        if (endInput) {
            endInput.setCustomValidity(message);
        }
    };

    const clearQuarterMessage = (quarterNumber) => {
        setQuarterMessage(quarterNumber, '');
    };

    const validateQuarter = (quarterNumber) => {
        const startInput = findQuarterInput(quarterNumber, 'start');
        const endInput = findQuarterInput(quarterNumber, 'end');

        if (!startInput || !endInput) {
            return { valid: true };
        }

        clearQuarterMessage(quarterNumber);

        const label = startInput.dataset.quarterLabel || `Trimestre ${quarterNumber}`;
        const allowedStart = startInput.dataset.allowedStart || '';
        const allowedEnd = startInput.dataset.allowedEnd || '';
        const startValue = startInput.value;
        const endValue = endInput.value;

        if (!startValue || !endValue) {
            return { valid: true };
        }

        if (startValue > endValue) {
            const message = `${label} debe terminar despues de la fecha inicial.`;
            setQuarterMessage(quarterNumber, message);

            return { valid: false, message, focusTarget: endInput };
        }

        if (
            (allowedStart && startValue < allowedStart) ||
            (allowedEnd && startValue > allowedEnd) ||
            (allowedStart && endValue < allowedStart) ||
            (allowedEnd && endValue > allowedEnd)
        ) {
            const message = `${label} esta superando los 3 meses permitidos. Solo puedes seleccionar fechas entre ${formatDate(allowedStart)} y ${formatDate(allowedEnd)}.`;
            setQuarterMessage(quarterNumber, message);

            return { valid: false, message, focusTarget: startInput };
        }

        return { valid: true };
    };

    quarterInputs.forEach((input) => {
        input.addEventListener('input', () => {
            clearQuarterMessage(input.dataset.quarterNumber || '');
        });

        input.addEventListener('change', () => {
            const result = validateQuarter(input.dataset.quarterNumber || '');

            if (!result.valid && result.message) {
                window.alert(result.message);
                result.focusTarget?.focus();
            }
        });
    });

    form.addEventListener('submit', (event) => {
        const quarterNumbers = [...new Set(quarterInputs.map((input) => input.dataset.quarterNumber).filter(Boolean))];

        for (const quarterNumber of quarterNumbers) {
            const result = validateQuarter(quarterNumber);

            if (!result.valid) {
                event.preventDefault();

                if (result.message) {
                    window.alert(result.message);
                }

                result.focusTarget?.focus();
                break;
            }
        }
    });
}
