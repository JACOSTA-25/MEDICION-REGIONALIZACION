import React from 'react';

import { downloadRowsAsExcel, formatNumber, formatPercentage } from '../utils/exportar.js';

const EXPORT_COLUMNS = [
    { key: 'name', label: 'Nombre' },
    { key: 'surveys', label: 'Encuestas' },
    { key: 'satisfaction_percentage', label: '% Satisfaccion' },
    { key: 'average_score', label: 'Promedio' },
    { key: 'satisfied_answers', label: 'Positivas' },
    { key: 'neutral_answers', label: 'Neutras' },
    { key: 'dissatisfied_answers', label: 'Negativas' },
];

export default function TablaConsolidada({ rows, etiquetaEntidad, encuestasMinimas }) {
    const exportRows = rows.map((row) => ({
        name: row.name,
        surveys: row.surveys,
        satisfaction_percentage: `${Number(row.satisfaction_percentage ?? 0).toFixed(2)}%`,
        average_score: Number(row.average_score ?? 0).toFixed(2),
        satisfied_answers: row.satisfied_answers,
        neutral_answers: row.neutral_answers,
        dissatisfied_answers: row.dissatisfied_answers,
    }));

    return (
        <section className="ms-report-card">
            <div className="ms-statistics-table-header">
                <div>
                    <h2>Tabla consolidada</h2>
                    <p>Detalle por {etiquetaEntidad.toLowerCase()} con volumen, promedio y distribucion de satisfaccion.</p>
                </div>

                <button
                    type="button"
                    className="ms-btn ms-btn-secondary"
                    onClick={() => downloadRowsAsExcel(EXPORT_COLUMNS, exportRows, `tabla-${etiquetaEntidad}`)}
                >
                    Exportar Excel
                </button>
            </div>

            <p className="ms-form-note">
                Los rankings de calidad consideran un minimo de {formatNumber(encuestasMinimas)} encuestas completas.
            </p>

            <div className="ms-table-shell">
                <table className="ms-data-table">
                    <thead>
                        <tr>
                            <th>{etiquetaEntidad}</th>
                            <th>Encuestas</th>
                            <th>% Satisfaccion</th>
                            <th>Promedio</th>
                            <th>Positivas</th>
                            <th>Neutras</th>
                            <th>Negativas</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 ? (
                            <tr>
                                <td colSpan="7">Sin registros para el periodo seleccionado.</td>
                            </tr>
                        ) : rows.map((row) => (
                            <tr key={`${row.id ?? 'null'}-${row.name}`}>
                                <td>{row.name}</td>
                                <td>{formatNumber(row.surveys)}</td>
                                <td>{formatPercentage(row.satisfaction_percentage)}</td>
                                <td>{Number(row.average_score ?? 0).toFixed(2)}</td>
                                <td>{formatNumber(row.satisfied_answers)}</td>
                                <td>{formatNumber(row.neutral_answers)}</td>
                                <td>{formatNumber(row.dissatisfied_answers)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </section>
    );
}
