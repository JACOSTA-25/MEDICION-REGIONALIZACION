import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

import TarjetaGrafico from './components/TarjetaGrafico.jsx';
import TablaConsolidada from './components/TablaConsolidada.jsx';
import PanelFiltros from './components/PanelFiltros.jsx';
import ContadoresEstadisticos from './components/ContadoresEstadisticos.jsx';
import { CHART_COLORS, formatNumber, formatPercentage } from './utils/exportar.js';

const LEVEL_COPY = {
    processes: {
        etiquetaEntidad: 'Proceso',
        quantityTitle: 'Comparativa por cantidad',
        quantityDescription: 'Procesos ordenados por numero de encuestas completas en el trimestre.',
        satisfactionTitle: 'Comparativa por satisfaccion',
        satisfactionDescription: 'Procesos con mayor porcentaje de respuestas positivas.',
        topTitle: 'Procesos mas evaluados',
        topDescription: 'Ranking de procesos con mayor volumen de encuestas completas.',
        bestTitle: 'Procesos mejor calificados',
        bestDescription: 'Procesos con mayor satisfaccion y base minima valida.',
        worstTitle: 'Procesos peor calificados',
        worstDescription: 'Procesos con menor satisfaccion dentro del umbral configurado.',
    },
    dependencies: {
        etiquetaEntidad: 'Dependencia',
        quantityTitle: 'Comparativa por cantidad',
        quantityDescription: 'Dependencias ordenadas por numero de encuestas completas.',
        satisfactionTitle: 'Comparativa por satisfaccion',
        satisfactionDescription: 'Dependencias con mayor porcentaje de respuestas positivas.',
        topTitle: 'Dependencias mas evaluadas',
        topDescription: 'Ranking de dependencias con mayor volumen de encuestas completas.',
        bestTitle: 'Dependencias mejor calificadas',
        bestDescription: 'Dependencias con mejor percepcion del servicio.',
        worstTitle: 'Dependencias peor calificadas',
        worstDescription: 'Dependencias con menor porcentaje de satisfaccion.',
    },
    services: {
        etiquetaEntidad: 'Servicio',
        quantityTitle: 'Comparativa por cantidad',
        quantityDescription: 'Servicios ordenados por numero de encuestas completas.',
        satisfactionTitle: 'Comparativa por satisfaccion',
        satisfactionDescription: 'Servicios con mayor porcentaje de respuestas positivas.',
        topTitle: 'Servicios mas evaluados',
        topDescription: 'Ranking de servicios con mayor numero de encuestas completas.',
        bestTitle: 'Servicios mejor calificados',
        bestDescription: 'Servicios con mejor nivel de satisfaccion y base minima valida.',
        worstTitle: 'Servicios peor calificados',
        worstDescription: 'Servicios con menor satisfaccion dentro del umbral configurado.',
    },
};

function shallowEqual(left, right) {
    const leftKeys = Object.keys(left);
    const rightKeys = Object.keys(right);

    if (leftKeys.length !== rightKeys.length) {
        return false;
    }

    return leftKeys.every((key) => String(left[key] ?? '') === String(right[key] ?? ''));
}

function buildQuery(filters) {
    const params = new URLSearchParams();

    Object.entries(filters).forEach(([key, value]) => {
        if (value !== '' && value !== null && value !== undefined) {
            params.append(key, value);
        }
    });

    return params.toString();
}

function chartColumns(etiquetaEntidad, valueLabel) {
    return [
        { key: 'name', label: etiquetaEntidad },
        { key: 'value', label: valueLabel },
    ];
}

function tickLabel(value) {
    const label = String(value);

    return label.length > 22 ? `${label.slice(0, 19)}...` : label;
}

function TooltipBox({ active, payload, percent = false }) {
    if (!active || !payload?.length) {
        return null;
    }

    const row = payload[0];

    return (
        <div className="rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
            <strong className="block text-sm text-slate-900">{row.payload.name}</strong>
            <span className="text-xs text-slate-600">
                {percent ? formatPercentage(row.value) : formatNumber(row.value)}
            </span>
        </div>
    );
}

function HorizontalChart({ data, percent = false }) {
    return (
        <ResponsiveContainer width="100%" height="100%">
            <BarChart
                data={data}
                layout="vertical"
                margin={{ top: 8, right: 20, bottom: 8, left: 8 }}
            >
                <CartesianGrid strokeDasharray="3 3" stroke="#E2E8F0" />
                <XAxis
                    type="number"
                    tickFormatter={(value) => percent ? `${value}%` : formatNumber(value)}
                    domain={percent ? [0, 100] : [0, 'dataMax']}
                />
                <YAxis
                    dataKey="name"
                    type="category"
                    width={165}
                    tickFormatter={tickLabel}
                />
                <Tooltip content={<TooltipBox percent={percent} />} />
                <Bar dataKey="value" radius={[0, 8, 8, 0]}>
                    {data.map((entry, index) => (
                        <Cell key={`${entry.name}-${index}`} fill={CHART_COLORS[index % CHART_COLORS.length]} />
                    ))}
                </Bar>
            </BarChart>
        </ResponsiveContainer>
    );
}

function DistributionChart({ data }) {
    return (
        <div className="grid h-full grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_220px]">
            <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                    <Tooltip content={<TooltipBox />} />
                    <Pie
                        data={data}
                        dataKey="value"
                        nameKey="name"
                        innerRadius={60}
                        outerRadius={96}
                        paddingAngle={2}
                    >
                        {data.map((entry, index) => (
                            <Cell key={`${entry.name}-${index}`} fill={CHART_COLORS[index % CHART_COLORS.length]} />
                        ))}
                    </Pie>
                </PieChart>
            </ResponsiveContainer>

            <div className="grid content-start gap-2 overflow-auto pe-1">
                {data.map((entry, index) => (
                    <div key={`${entry.name}-${index}`} className="flex items-center gap-2 text-sm text-slate-700">
                        <span
                            className="inline-block h-3 w-3 rounded-full"
                            style={{ backgroundColor: CHART_COLORS[index % CHART_COLORS.length] }}
                        />
                        <span className="min-w-0 flex-1 truncate">{entry.name}</span>
                        <strong>{formatNumber(entry.value)}</strong>
                    </div>
                ))}
            </div>
        </div>
    );
}

export default function AppEstadisticas({ endpoint, level }) {
    const [payload, setPayload] = useState(null);
    const [filters, setFilters] = useState({});
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const defaultsRef = useRef(null);
    const abortRef = useRef(null);
    const copy = LEVEL_COPY[level] ?? LEVEL_COPY.services;

    const fetchData = useCallback(async (nextFilters = {}) => {
        if (abortRef.current) {
            abortRef.current.abort();
        }

        const controller = new AbortController();
        abortRef.current = controller;
        setLoading(true);
        setError(null);

        try {
            const query = buildQuery(nextFilters);
            const response = await fetch(
                `${endpoint}${query ? `?${query}` : ''}`,
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: controller.signal,
                },
            );

            if (!response.ok) {
                throw new Error(`Error ${response.status} al consultar estadisticas.`);
            }

            const data = await response.json();
            const sanitized = data.filters.selected;

            if (!defaultsRef.current) {
                defaultsRef.current = sanitized;
            }

            setPayload(data);
            setFilters((previous) => shallowEqual(previous, sanitized) ? previous : sanitized);
        } catch (fetchError) {
            if (fetchError.name === 'AbortError') {
                return;
            }

            setError(fetchError.message);
        } finally {
            setLoading(false);
        }
    }, [endpoint]);

    useEffect(() => {
        fetchData({});

        return () => {
            if (abortRef.current) {
                abortRef.current.abort();
            }
        };
    }, [fetchData]);

    const handleFilterChange = useCallback((field, value) => {
        setFilters((previous) => {
            const next = {
                ...previous,
                [field]: value === '' ? '' : value,
            };

            if (field === 'id_proceso') {
                next.id_dependencia = '';
                next.id_servicio = '';
            }

            if (field === 'id_dependencia') {
                next.id_servicio = '';
            }

            fetchData(next);

            return next;
        });
    }, [fetchData]);

    const handleReset = useCallback(() => {
        if (!defaultsRef.current) {
            return;
        }

        setFilters(defaultsRef.current);
        fetchData(defaultsRef.current);
    }, [fetchData]);

    const chartMeta = useMemo(() => {
        if (!payload) {
            return null;
        }

        const etiquetaEntidad = payload.charts.metadata.etiquetaEntidad;

        return {
            etiquetaEntidad,
            quantityColumns: chartColumns(etiquetaEntidad, 'Encuestas'),
            satisfactionColumns: chartColumns(etiquetaEntidad, '% Satisfaccion'),
            distributionColumns: chartColumns('Categoria', 'Encuestas'),
        };
    }, [payload]);

    if (!payload && loading) {
        return <div className="ms-inline-alert ms-inline-alert-soft">Cargando modulo de estadisticas...</div>;
    }

    if (error && !payload) {
        return <div className="ms-inline-alert">{error}</div>;
    }

    if (!payload || !chartMeta) {
        return null;
    }

    const charts = payload.charts;
    const rows = payload.table ?? [];

    return (
        <div className="grid gap-4">
            <section className="ms-report-card ms-report-card-accent">
                <div className="ms-report-card-header">
                    <h2>Contexto del analisis</h2>
                    <p>
                        {payload.scope.description} {payload.scope.quarterLabel}: {payload.scope.quarterPeriod}.
                    </p>
                </div>
            </section>

            <PanelFiltros
                payload={payload}
                filters={filters}
                onChange={handleFilterChange}
                onReset={handleReset}
                loading={loading}
            />

            {error && <div className="ms-inline-alert">{error}</div>}

            <ContadoresEstadisticos
                counters={payload.counters}
                etiquetaEntidad={chartMeta.etiquetaEntidad}
            />

            <section className="ms-statistics-grid">
                <TarjetaGrafico
                    title={copy.quantityTitle}
                    description={copy.quantityDescription}
                    exportColumns={chartMeta.quantityColumns}
                    exportRows={charts.quantityComparison}
                    isEmpty={charts.quantityComparison.length === 0}
                >
                    <HorizontalChart data={charts.quantityComparison} />
                </TarjetaGrafico>

                <TarjetaGrafico
                    title={copy.satisfactionTitle}
                    description={copy.satisfactionDescription}
                    exportColumns={chartMeta.satisfactionColumns}
                    exportRows={charts.satisfactionComparison}
                    isEmpty={charts.satisfactionComparison.length === 0}
                >
                    <HorizontalChart data={charts.satisfactionComparison} percent />
                </TarjetaGrafico>

                <TarjetaGrafico
                    title={copy.topTitle}
                    description={copy.topDescription}
                    exportColumns={chartMeta.quantityColumns}
                    exportRows={charts.topEvaluated}
                    isEmpty={charts.topEvaluated.length === 0}
                >
                    <HorizontalChart data={charts.topEvaluated} />
                </TarjetaGrafico>

                <TarjetaGrafico
                    title={copy.bestTitle}
                    description={copy.bestDescription}
                    exportColumns={chartMeta.satisfactionColumns}
                    exportRows={charts.topSatisfied}
                    isEmpty={charts.topSatisfied.length === 0}
                >
                    <HorizontalChart data={charts.topSatisfied} percent />
                </TarjetaGrafico>

                <TarjetaGrafico
                    title={copy.worstTitle}
                    description={copy.worstDescription}
                    exportColumns={chartMeta.satisfactionColumns}
                    exportRows={charts.lowestSatisfied}
                    isEmpty={charts.lowestSatisfied.length === 0}
                >
                    <HorizontalChart data={charts.lowestSatisfied} percent />
                </TarjetaGrafico>

                <TarjetaGrafico
                    title="Estamentos mas atendidos"
                    description="Distribucion de encuestas completas segun el estamento dentro del alcance actual."
                    exportColumns={chartMeta.distributionColumns}
                    exportRows={charts.byEstamento}
                    compact
                    isEmpty={charts.byEstamento.length === 0}
                >
                    <DistributionChart data={charts.byEstamento} />
                </TarjetaGrafico>

                <TarjetaGrafico
                    title="Programas mas atendidos"
                    description="Distribucion de encuestas completas segun el programa academico dentro del alcance filtrado."
                    exportColumns={chartMeta.distributionColumns}
                    exportRows={charts.byPrograma}
                    compact
                    isEmpty={charts.byPrograma.length === 0}
                >
                    <DistributionChart data={charts.byPrograma} />
                </TarjetaGrafico>
            </section>

            <TablaConsolidada
                rows={rows}
                etiquetaEntidad={chartMeta.etiquetaEntidad}
                encuestasMinimas={payload.filters.selected.min_surveys}
            />
        </div>
    );
}
