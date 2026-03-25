import React from 'react';

import { formatNumber, formatPercentage } from '../utils/exportar.js';

export default function ContadoresEstadisticos({ counters, etiquetaEntidad }) {
    const items = [
        {
            label: 'Encuestas completas',
            value: formatNumber(counters.surveys),
            help: 'Total de encuestas respondidas para el periodo seleccionado.',
        },
        {
            label: `${etiquetaEntidad} comparados`,
            value: formatNumber(counters.entities),
            help: 'Cantidad de elementos visibles en la comparativa del nivel actual.',
        },
        {
            label: 'Satisfaccion global',
            value: formatPercentage(counters.satisfaction_percentage),
            help: 'Porcentaje de respuestas positivas frente al total valido.',
        },
        {
            label: 'Promedio general',
            value: Number(counters.average_score ?? 0).toFixed(2),
            help: 'Promedio consolidado sobre la escala institucional de 1 a 5.',
        },
    ];

    return (
        <section className="ms-report-card">
            <div className="ms-report-card-header">
                <h2>Resumen ejecutivo</h2>
                <p>Indicadores consolidados del analisis para el nivel activo.</p>
            </div>

            <div className="ms-stat-grid">
                {items.map((item) => (
                    <div key={item.label} className="ms-stat-card">
                        <span className="ms-stat-label">{item.label}</span>
                        <strong>{item.value}</strong>
                        <small>{item.help}</small>
                    </div>
                ))}
            </div>
        </section>
    );
}
