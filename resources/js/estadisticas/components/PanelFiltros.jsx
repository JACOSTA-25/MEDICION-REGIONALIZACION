import React from 'react';

function SelectField({ id, label, value, onChange, options, disabled = false, placeholder = 'Todos' }) {
    return (
        <div className="ms-field">
            <label htmlFor={id}>{label}</label>
            <select
                id={id}
                value={value ?? ''}
                onChange={(event) => onChange(event.target.value)}
                disabled={disabled}
            >
                <option value="">{placeholder}</option>
                {options.map((option) => (
                    <option key={option.id ?? option.value} value={option.id ?? option.value}>
                        {option.name ?? option.label}
                    </option>
                ))}
            </select>
        </div>
    );
}

export default function PanelFiltros({ payload, filters, onChange, onReset, loading }) {
    const { selected, locks, options, visibility } = payload.filters;

    return (
        <section className="ms-report-card">
            <div className="ms-report-card-header">
                <h2>Filtros de estadisticas</h2>
                <p>Cada cambio actualiza automaticamente las comparativas y los rankings del modulo.</p>
            </div>

            <div className="ms-report-form">
                <div className="ms-report-fields">
                    {visibility.sede && (
                        <SelectField
                            id="statistics_sede"
                            label="Sede"
                            value={filters.id_sede ?? selected.id_sede}
                            onChange={(value) => onChange('id_sede', value)}
                            options={options.sedes}
                            disabled={locks.sede}
                            placeholder="Todas las sedes"
                        />
                    )}

                    <SelectField
                        id="statistics_year"
                        label="Anio"
                        value={filters.year ?? selected.year}
                        onChange={(value) => onChange('year', value)}
                        options={options.years}
                        placeholder="Seleccione un anio"
                    />

                    <SelectField
                        id="statistics_quarter"
                        label="Trimestre"
                        value={filters.quarter ?? selected.quarter}
                        onChange={(value) => onChange('quarter', value)}
                        options={options.quarters}
                        placeholder="Seleccione un trimestre"
                    />

                    <SelectField
                        id="statistics_estamento"
                        label="Estamento"
                        value={filters.id_estamento ?? selected.id_estamento}
                        onChange={(value) => onChange('id_estamento', value)}
                        options={options.estamentos}
                    />

                    <SelectField
                        id="statistics_programa"
                        label="Programa"
                        value={filters.id_programa ?? selected.id_programa}
                        onChange={(value) => onChange('id_programa', value)}
                        options={options.programas}
                    />

                    {visibility.process && (
                        <SelectField
                            id="statistics_process"
                            label="Proceso"
                            value={filters.id_proceso ?? selected.id_proceso}
                            onChange={(value) => onChange('id_proceso', value)}
                            options={options.procesos}
                            disabled={locks.process}
                        />
                    )}

                    {visibility.dependency && (
                        <SelectField
                            id="statistics_dependency"
                            label="Dependencia"
                            value={filters.id_dependencia ?? selected.id_dependencia}
                            onChange={(value) => onChange('id_dependencia', value)}
                            options={options.dependencias}
                            disabled={locks.dependency}
                        />
                    )}

                    {visibility.service && (
                        <SelectField
                            id="statistics_service"
                            label="Servicio"
                            value={filters.id_servicio ?? selected.id_servicio}
                            onChange={(value) => onChange('id_servicio', value)}
                            options={options.servicios}
                        />
                    )}

                    <div className="ms-field">
                        <label htmlFor="statistics_min_surveys">Minimo de encuestas</label>
                        <input
                            id="statistics_min_surveys"
                            type="number"
                            min="1"
                            value={filters.min_surveys ?? selected.min_surveys}
                            onChange={(event) => onChange('min_surveys', event.target.value)}
                        />
                        <small className="ms-field-help">
                            Umbral usado para los rankings de mejor y peor calificacion.
                        </small>
                    </div>
                </div>

                <div className="ms-form-actions">
                    <button type="button" className="ms-btn ms-btn-secondary" onClick={onReset}>
                        Restablecer filtros
                    </button>

                    {loading && <span className="ms-statistics-note">Actualizando informacion...</span>}
                </div>
            </div>
        </section>
    );
}
