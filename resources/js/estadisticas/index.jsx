import React from 'react';
import { createRoot } from 'react-dom/client';

import AppEstadisticas from './AppEstadisticas.jsx';

const container = document.getElementById('statistics-react-root');

if (container) {
    const root = createRoot(container);

    root.render(
        <AppEstadisticas
            endpoint={container.dataset.endpoint}
            level={container.dataset.level}
        />,
    );
}
