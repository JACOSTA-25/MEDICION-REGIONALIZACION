const FILE_SAFE_REGEX = /[^a-z0-9\-]+/gi;

export const CHART_COLORS = [
    '#9C3A3A',
    '#B45353',
    '#D97706',
    '#2563EB',
    '#16A34A',
    '#7C3AED',
    '#0F766E',
    '#E11D48',
];

export function formatNumber(value) {
    return Number(value ?? 0).toLocaleString('es-CO');
}

export function formatPercentage(value) {
    return `${Number(value ?? 0).toFixed(2)}%`;
}

function fileName(title) {
    return String(title || 'estadisticas')
        .toLowerCase()
        .replace(FILE_SAFE_REGEX, '-')
        .replace(/^-+|-+$/g, '');
}

export function downloadRowsAsExcel(columns, rows, title) {
    const escape = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    const header = columns.map((column) => `<th><b>${escape(column.label)}</b></th>`).join('');
    const body = rows.map((row) => (
        `<tr>${columns.map((column) => `<td>${escape(row[column.key])}</td>`).join('')}</tr>`
    )).join('');

    const html = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office"
              xmlns:x="urn:schemas-microsoft-com:office:excel"
              xmlns="http://www.w3.org/TR/REC-html40">
            <head><meta charset="utf-8"></head>
            <body>
                <table>
                    <tr>${header}</tr>
                    ${body}
                </table>
            </body>
        </html>
    `;

    const blob = new Blob(['\uFEFF' + html], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const anchor = document.createElement('a');
    anchor.href = URL.createObjectURL(blob);
    anchor.download = `${fileName(title)}.xls`;
    anchor.click();
    URL.revokeObjectURL(anchor.href);
}

export function downloadChartAsPng(container, title) {
    const svg = container?.querySelector('svg');

    if (!svg) {
        return;
    }

    const width = Math.max(svg.clientWidth, 720);
    const height = Math.max(svg.clientHeight, 320);
    const serializer = new XMLSerializer();
    const clone = svg.cloneNode(true);

    clone.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
    clone.setAttribute('width', width);
    clone.setAttribute('height', height);

    const svgMarkup = serializer.serializeToString(clone);
    const blob = new Blob([svgMarkup], { type: 'image/svg+xml;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const image = new Image();

    image.onload = () => {
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');

        canvas.width = width * 2;
        canvas.height = height * 2;
        context.scale(2, 2);
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, width, height);
        context.drawImage(image, 0, 0, width, height);

        canvas.toBlob((pngBlob) => {
            if (!pngBlob) {
                URL.revokeObjectURL(url);
                return;
            }

            const anchor = document.createElement('a');
            anchor.href = URL.createObjectURL(pngBlob);
            anchor.download = `${fileName(title)}.png`;
            anchor.click();
            URL.revokeObjectURL(anchor.href);
            URL.revokeObjectURL(url);
        }, 'image/png');
    };

    image.onerror = () => URL.revokeObjectURL(url);
    image.src = url;
}
