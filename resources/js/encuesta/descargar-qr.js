document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-qr-download-button]').forEach((button) => {
        button.addEventListener('click', async () => {
            const imageUrl = button.dataset.qrImageUrl;
            const fileName = button.dataset.qrFilename || 'codigo-qr.png';
            const status = button.closest('.ms-report-card')?.querySelector('[data-qr-download-status]');

            if (!imageUrl) {
                return;
            }

            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Descargando...';

            if (status) {
                status.textContent = 'Preparando la descarga del codigo QR...';
            }

            try {
                const response = await fetch(imageUrl, {
                    headers: {
                        Accept: 'image/png',
                    },
                    mode: 'cors',
                });

                if (!response.ok) {
                    throw new Error(`QR request failed with status ${response.status}`);
                }

                const blob = await response.blob();
                const objectUrl = window.URL.createObjectURL(blob);
                const link = document.createElement('a');

                link.href = objectUrl;
                link.download = fileName;
                document.body.append(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(objectUrl);

                if (status) {
                    status.textContent = 'El codigo QR se descargo correctamente.';
                }
            } catch (error) {
                console.error(error);

                if (status) {
                    status.textContent = 'No se pudo descargar automaticamente. Se abrira la imagen del QR en una nueva pestana.';
                }

                window.open(imageUrl, '_blank', 'noopener');
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
        });
    });
});
