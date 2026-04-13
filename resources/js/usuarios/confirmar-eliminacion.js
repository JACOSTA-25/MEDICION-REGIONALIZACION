document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.matches('[data-confirm-user-delete]')) {
        return;
    }

    const userName = form.dataset.userName?.trim() || 'este usuario';
    const confirmed = window.confirm(
        `Deseas eliminar al usuario "${userName}"? Esta accion no se puede deshacer.`
    );

    if (!confirmed) {
        event.preventDefault();
    }
});
