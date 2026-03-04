document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-copy-trigger]').forEach((button) => {
        button.addEventListener('click', async () => {
            const selector = button.dataset.copyTarget;
            const target = selector ? document.querySelector(selector) : null;
            const value = target instanceof HTMLInputElement
                ? target.value
                : target?.textContent?.trim();

            if (!value) {
                return;
            }

            try {
                await navigator.clipboard.writeText(value);
                const originalText = button.textContent;
                button.textContent = 'Copiado';

                window.setTimeout(() => {
                    button.textContent = originalText;
                }, 1600);
            } catch (error) {
                console.error(error);

                if (target instanceof HTMLInputElement) {
                    target.select();
                }
            }
        });
    });
});
