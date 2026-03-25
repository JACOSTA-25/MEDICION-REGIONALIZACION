import React, { useRef } from 'react';

import { downloadChartAsPng, downloadRowsAsExcel } from '../utils/exportar.js';

export default function TarjetaGrafico({
    title,
    description,
    exportColumns,
    exportRows,
    children,
    compact = false,
    isEmpty = false,
}) {
    const containerRef = useRef(null);

    return (
        <article className="ms-statistics-chart-card">
            <div className="ms-statistics-chart-header">
                <div>
                    <h3>{title}</h3>
                    <p>{description}</p>
                </div>

                {!isEmpty && (
                    <div className="ms-statistics-chart-actions">
                        <button
                            type="button"
                            className="ms-statistics-toolbar-btn"
                            onClick={() => downloadChartAsPng(containerRef.current, title)}
                        >
                            PNG
                        </button>
                        <button
                            type="button"
                            className="ms-statistics-toolbar-btn"
                            onClick={() => downloadRowsAsExcel(exportColumns, exportRows, title)}
                        >
                            Excel
                        </button>
                    </div>
                )}
            </div>

            <div
                ref={containerRef}
                className={compact ? 'ms-statistics-chart-surface ms-statistics-surface-compact' : 'ms-statistics-chart-surface'}
            >
                {isEmpty ? (
                    <div className="ms-statistics-empty">
                        No hay datos disponibles para este grafico con los filtros activos.
                    </div>
                ) : (
                    children
                )}
            </div>
        </article>
    );
}
