<?php

namespace App\Services\Estadisticas;

use App\Models\Dependencia;
use App\Models\Estamento;
use App\Models\Programa;
use App\Models\Proceso;
use App\Models\Servicio;
use App\Models\User;
use App\Services\Reportes\ServicioTrimestresReporte;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ServicioConsultaEstadisticas
{
    private const DEFAULT_MIN_SURVEYS = 10;

    private const QUESTION_COLUMNS = [
        'pregunta1',
        'pregunta2',
        'pregunta3',
        'pregunta4',
        'pregunta5',
        'pregunta6',
    ];

    public function __construct(
        private readonly ServicioTrimestresReporte $reportingQuarterService,
        private readonly ServicioAlcanceEstadisticas $scopeService,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function buildPayload(User $user, string $level, array $input): array
    {
        $years = $this->availableYears();
        $selectedYear = $this->resolveYear($input['year'] ?? null, $years);
        $quarterOptions = $this->reportingQuarterService->forYear($selectedYear);
        $selectedQuarter = $this->resolveQuarter($selectedYear, $input['quarter'] ?? null, $quarterOptions);
        $selectedFilters = [
            'year' => $selectedYear,
            'quarter' => $selectedQuarter,
            'id_estamento' => $this->normalizeId($input['id_estamento'] ?? null),
            'id_programa' => $this->normalizeId($input['id_programa'] ?? null),
            'id_proceso' => $this->normalizeId($input['id_proceso'] ?? null),
            'id_dependencia' => $this->normalizeId($input['id_dependencia'] ?? null),
            'id_servicio' => $this->normalizeId($input['id_servicio'] ?? null),
            'min_surveys' => $this->normalizarEncuestasMinimas($input['min_surveys'] ?? null),
        ];

        $selectedFilters = $this->applyRoleScope($user, $level, $selectedFilters);

        $processes = $this->processOptions($user);
        $selectedFilters['id_proceso'] = $this->sanitizeSelection($selectedFilters['id_proceso'], $processes);

        $dependencies = $this->dependencyOptions($user, $selectedFilters['id_proceso']);
        $selectedFilters['id_dependencia'] = $this->sanitizeSelection($selectedFilters['id_dependencia'], $dependencies);

        $services = $this->serviceOptions($user, $selectedFilters['id_proceso'], $selectedFilters['id_dependencia']);
        $selectedFilters['id_servicio'] = $this->sanitizeSelection($selectedFilters['id_servicio'], $services);

        $quarter = $this->reportingQuarterService->findForYear($selectedYear, $selectedQuarter);
        $baseQuery = $this->baseQuery($quarter->start_date, $quarter->end_date, $selectedFilters);
        $entityRows = $this->entityRows(clone $baseQuery, $level);
        $qualifiedRows = $entityRows->filter(
            fn (array $row): bool => $row['surveys'] >= $selectedFilters['min_surveys']
        )->values();

        return [
            'filters' => [
                'selected' => $selectedFilters,
                'locks' => [
                    'process' => $this->isProcessLocked($user),
                    'dependency' => $this->isDependencyLocked($user),
                ],
                'visibility' => [
                    'process' => true,
                    'dependency' => in_array($level, ['dependencies', 'services'], true),
                    'service' => $level === 'services',
                ],
                'options' => [
                    'years' => $years->map(fn (int $year): array => [
                        'value' => $year,
                        'label' => (string) $year,
                    ])->values()->all(),
                    'quarters' => $quarterOptions->map(fn ($quarterOption): array => [
                        'value' => (int) $quarterOption->quarter_number,
                        'label' => $quarterOption->label(),
                        'period' => $quarterOption->periodLabel(),
                    ])->values()->all(),
                    'estamentos' => $this->estamentoOptions(),
                    'programas' => $this->programaOptions(),
                    'procesos' => $processes,
                    'dependencias' => $dependencies,
                    'servicios' => $services,
                ],
            ],
            'scope' => [
                'nivelesPermitidos' => $this->scopeService->nivelesPermitidos($user),
                'description' => $this->scopeDescription($user, $level),
                'level' => $level,
                'title' => $this->scopeService->tituloParaNivel($level),
                'quarterLabel' => $quarter->label(),
                'quarterPeriod' => $quarter->periodLabel(),
            ],
            'counters' => $this->counters(clone $baseQuery, $entityRows),
            'charts' => $this->charts($entityRows, $qualifiedRows, clone $baseQuery, $level),
            'table' => $entityRows->values()->all(),
        ];
    }

    /**
     * @return Collection<int, int>
     */
    private function availableYears(): Collection
    {
        $currentYear = $this->reportingQuarterService->currentYear();
        $yearExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%Y', fecha_respuesta) AS INTEGER)"
            : 'YEAR(fecha_respuesta)';

        $years = DB::table('respuesta')
            ->selectRaw($yearExpression.' as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($year): int => (int) $year)
            ->filter(fn (int $year): bool => $year > 0)
            ->values();

        if (! $years->contains($currentYear)) {
            $years->prepend($currentYear);
        }

        return $years->unique()->sortDesc()->values();
    }

    private function resolveYear(mixed $value, Collection $years): int
    {
        $candidate = is_numeric($value) ? (int) $value : null;

        if ($candidate !== null && $years->contains($candidate)) {
            return $candidate;
        }

        return $years->first() ?? $this->reportingQuarterService->currentYear();
    }

    private function resolveQuarter(int $year, mixed $value, Collection $quarterOptions): int
    {
        $candidate = is_numeric($value) ? (int) $value : null;

        if ($candidate !== null && $quarterOptions->contains(fn ($quarter) => (int) $quarter->quarter_number === $candidate)) {
            return $candidate;
        }

        $now = now(config('app.timezone'));

        $matched = $quarterOptions->first(function ($quarter) use ($now, $year): bool {
            if ((int) $quarter->year !== $year || ! $quarter->start_date || ! $quarter->end_date) {
                return false;
            }

            $start = $quarter->start_date->copy()->startOfDay();
            $end = $quarter->end_date->copy()->endOfDay();

            return ! $now->lt($start) && ! $now->gt($end);
        });

        return $matched
            ? (int) $matched->quarter_number
            : (int) ($quarterOptions->first()?->quarter_number ?? 1);
    }

    private function normalizeId(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private function normalizarEncuestasMinimas(mixed $value): int
    {
        if (! is_numeric($value)) {
            return self::DEFAULT_MIN_SURVEYS;
        }

        return max((int) $value, 1);
    }

    /**
     * @param  array<string, int|null>  $filters
     * @return array<string, int|null>
     */
    private function applyRoleScope(User $user, string $level, array $filters): array
    {
        if ($user->isLiderProceso() && $user->id_proceso) {
            $filters['id_proceso'] = (int) $user->id_proceso;
        }

        if ($user->isLiderDependencia() && $user->id_proceso) {
            $filters['id_proceso'] = (int) $user->id_proceso;
        }

        if ($level === 'processes' && $user->isLiderProceso() && $user->id_proceso) {
            $filters['id_proceso'] = (int) $user->id_proceso;
        }

        if ($user->isLiderDependencia() && $user->id_dependencia) {
            $filters['id_dependencia'] = (int) $user->id_dependencia;
        }

        return $filters;
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function processOptions(User $user): array
    {
        $query = Proceso::query()->orderBy('nombre');

        if (($user->isLiderProceso() || $user->isLiderDependencia()) && $user->id_proceso) {
            $query->where('id_proceso', (int) $user->id_proceso);
        }

        return $query
            ->get(['id_proceso', 'nombre'])
            ->map(fn (Proceso $process): array => [
                'id' => (int) $process->id_proceso,
                'name' => (string) $process->nombre,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function dependencyOptions(User $user, ?int $processId): array
    {
        $query = Dependencia::query()->orderBy('nombre');

        if ($processId !== null) {
            $query->where('id_proceso', $processId);
        } elseif (($user->isLiderProceso() || $user->isLiderDependencia()) && $user->id_proceso) {
            $query->where('id_proceso', (int) $user->id_proceso);
        }

        if ($user->isLiderDependencia() && $user->id_dependencia) {
            $query->where('id_dependencia', (int) $user->id_dependencia);
        }

        return $query
            ->get(['id_dependencia', 'nombre'])
            ->map(fn (Dependencia $dependency): array => [
                'id' => (int) $dependency->id_dependencia,
                'name' => (string) $dependency->nombre,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function serviceOptions(User $user, ?int $processId, ?int $dependencyId): array
    {
        $query = Servicio::query()
            ->select('servicio.id_servicio', 'servicio.nombre')
            ->join('dependencia', 'dependencia.id_dependencia', '=', 'servicio.id_dependencia')
            ->orderBy('servicio.nombre');

        if ($dependencyId !== null) {
            $query->where('servicio.id_dependencia', $dependencyId);
        } elseif ($processId !== null) {
            $query->where('dependencia.id_proceso', $processId);
        } elseif (($user->isLiderProceso() || $user->isLiderDependencia()) && $user->id_proceso) {
            $query->where('dependencia.id_proceso', (int) $user->id_proceso);
        }

        if ($user->isLiderDependencia() && $user->id_dependencia) {
            $query->where('servicio.id_dependencia', (int) $user->id_dependencia);
        }

        return $query
            ->get()
            ->map(fn ($service): array => [
                'id' => (int) $service->id_servicio,
                'name' => (string) $service->nombre,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function estamentoOptions(): array
    {
        return Estamento::query()
            ->orderBy('nombre')
            ->get(['id_estamento', 'nombre'])
            ->map(fn (Estamento $item): array => [
                'id' => (int) $item->id_estamento,
                'name' => (string) $item->nombre,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function programaOptions(): array
    {
        return Programa::query()
            ->orderBy('nombre')
            ->get(['id_programa', 'nombre'])
            ->map(fn (Programa $item): array => [
                'id' => (int) $item->id_programa,
                'name' => (string) $item->nombre,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{id: int, name: string}>  $options
     */
    private function sanitizeSelection(?int $selectedId, array $options): ?int
    {
        if ($selectedId === null) {
            return null;
        }

        foreach ($options as $option) {
            if ((int) $option['id'] === $selectedId) {
                return $selectedId;
            }
        }

        return null;
    }

    private function baseQuery(CarbonInterface $from, CarbonInterface $to, array $filters): Builder
    {
        return DB::table('respuesta')
            ->whereDate('fecha_respuesta', '>=', $from->toDateString())
            ->whereDate('fecha_respuesta', '<=', $to->toDateString())
            ->when($filters['id_estamento'] !== null, fn (Builder $query) => $query->where('respuesta.id_estamento', $filters['id_estamento']))
            ->when($filters['id_programa'] !== null, fn (Builder $query) => $query->where('respuesta.id_programa', $filters['id_programa']))
            ->when($filters['id_proceso'] !== null, fn (Builder $query) => $query->where('respuesta.id_proceso', $filters['id_proceso']))
            ->when($filters['id_dependencia'] !== null, fn (Builder $query) => $query->where('respuesta.id_dependencia', $filters['id_dependencia']))
            ->when($filters['id_servicio'] !== null, fn (Builder $query) => $query->where('respuesta.id_servicio', $filters['id_servicio']));
    }

    /**
     * @return Collection<int, array<string, int|float|string|null>>
     */
    private function entityRows(Builder $baseQuery, string $level): Collection
    {
        $config = $this->levelConfig($level);
        $validExpr = $this->rowExpression('valid');
        $satisfiedExpr = $this->rowExpression('satisfied');
        $neutralExpr = $this->rowExpression('neutral');
        $dissatisfiedExpr = $this->rowExpression('dissatisfied');
        $scoreExpr = $this->rowExpression('score');

        $rows = $baseQuery
            ->leftJoin($config['table'].' as entity', 'entity.'.$config['id_column'], '=', 'respuesta.'.$config['response_column'])
            ->selectRaw('respuesta.'.$config['response_column'].' as entity_id')
            ->selectRaw('COALESCE(NULLIF(TRIM(entity.nombre), \'\'), ?) as entity_name', [$config['fallback']])
            ->selectRaw('COUNT(*) as surveys')
            ->selectRaw("SUM({$validExpr}) as valid_answers")
            ->selectRaw("SUM({$satisfiedExpr}) as satisfied_answers")
            ->selectRaw("SUM({$neutralExpr}) as neutral_answers")
            ->selectRaw("SUM({$dissatisfiedExpr}) as dissatisfied_answers")
            ->selectRaw("SUM({$scoreExpr}) as score_sum")
            ->groupBy('respuesta.'.$config['response_column'], 'entity.nombre')
            ->orderByDesc('surveys')
            ->orderBy('entity_name')
            ->get();

        return $rows->map(function ($row): array {
            $validAnswers = (int) ($row->valid_answers ?? 0);
            $satisfiedAnswers = (int) ($row->satisfied_answers ?? 0);
            $scoreSum = (int) ($row->score_sum ?? 0);

            return [
                'id' => $row->entity_id !== null ? (int) $row->entity_id : null,
                'name' => (string) $row->entity_name,
                'surveys' => (int) ($row->surveys ?? 0),
                'valid_answers' => $validAnswers,
                'satisfied_answers' => $satisfiedAnswers,
                'neutral_answers' => (int) ($row->neutral_answers ?? 0),
                'dissatisfied_answers' => (int) ($row->dissatisfied_answers ?? 0),
                'satisfaction_percentage' => $this->percentage($satisfiedAnswers, $validAnswers),
                'average_score' => $validAnswers > 0
                    ? round($scoreSum / $validAnswers, 2)
                    : 0.0,
            ];
        })->values();
    }

    /**
     * @param  Collection<int, array<string, int|float|string|null>>  $rows
     * @return array<string, int|float>
     */
    private function counters(Builder $baseQuery, Collection $rows): array
    {
        $validExpr = $this->rowExpression('valid');
        $satisfiedExpr = $this->rowExpression('satisfied');
        $scoreExpr = $this->rowExpression('score');

        $totals = $baseQuery
            ->selectRaw('COUNT(*) as surveys')
            ->selectRaw("SUM({$validExpr}) as valid_answers")
            ->selectRaw("SUM({$satisfiedExpr}) as satisfied_answers")
            ->selectRaw("SUM({$scoreExpr}) as score_sum")
            ->first();

        $validAnswers = (int) ($totals?->valid_answers ?? 0);
        $satisfiedAnswers = (int) ($totals?->satisfied_answers ?? 0);
        $scoreSum = (int) ($totals?->score_sum ?? 0);

        return [
            'surveys' => (int) ($totals?->surveys ?? 0),
            'entities' => $rows->count(),
            'satisfaction_percentage' => $this->percentage($satisfiedAnswers, $validAnswers),
            'average_score' => $validAnswers > 0
                ? round($scoreSum / $validAnswers, 2)
                : 0.0,
        ];
    }

    /**
     * @param  Collection<int, array<string, int|float|string|null>>  $rows
     * @param  Collection<int, array<string, int|float|string|null>>  $qualifiedRows
     * @return array<string, array<int, array<string, int|float|string>>>
     */
    private function charts(Collection $rows, Collection $qualifiedRows, Builder $baseQuery, string $level): array
    {
        return [
            'quantityComparison' => $this->chartItems(
                $rows->sortByDesc('surveys')->take(10),
                'surveys'
            ),
            'satisfactionComparison' => $this->chartItems(
                $qualifiedRows->sortByDesc('satisfaction_percentage')->take(10),
                'satisfaction_percentage'
            ),
            'topEvaluated' => $this->chartItems(
                $rows->sortByDesc('surveys')->take(10),
                'surveys'
            ),
            'topSatisfied' => $this->chartItems(
                $qualifiedRows->sortByDesc('satisfaction_percentage')->take(10),
                'satisfaction_percentage'
            ),
            'lowestSatisfied' => $this->chartItems(
                $qualifiedRows->sortBy('satisfaction_percentage')->take(10),
                'satisfaction_percentage'
            ),
            'byEstamento' => $this->distributionChart(
                clone $baseQuery,
                'estamento',
                'id_estamento',
                'Sin estamento'
            ),
            'byPrograma' => $this->distributionChart(
                clone $baseQuery,
                'programa',
                'id_programa',
                'Sin programa'
            ),
            'metadata' => [
                'etiquetaEntidad' => $this->etiquetaEntidad($level),
            ],
        ];
    }

    /**
     * @param  Collection<int, array<string, int|float|string|null>>  $rows
     * @return array<int, array{name: string, value: int|float}>
     */
    private function chartItems(Collection $rows, string $valueKey): array
    {
        return $rows
            ->map(fn (array $row): array => [
                'name' => (string) $row['name'],
                'value' => $valueKey === 'surveys'
                    ? (int) $row[$valueKey]
                    : round((float) $row[$valueKey], 2),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{name: string, value: int}>
     */
    private function distributionChart(Builder $baseQuery, string $table, string $idColumn, string $fallback): array
    {
        $rows = $baseQuery
            ->leftJoin($table, $table.'.'.$idColumn, '=', 'respuesta.'.$idColumn)
            ->selectRaw('COALESCE(NULLIF(TRIM('.$table.'.nombre), \'\'), ?) as label', [$fallback])
            ->selectRaw('COUNT(*) as total')
            ->groupBy($table.'.nombre')
            ->orderByDesc('total')
            ->orderBy('label')
            ->get()
            ->map(fn ($row): array => [
                'name' => (string) $row->label,
                'value' => (int) $row->total,
            ])
            ->values();

        if ($rows->count() <= 8) {
            return $rows->all();
        }

        $head = $rows->take(7);
        $tail = $rows->slice(7);

        $head->push([
            'name' => 'Otros',
            'value' => $tail->sum('value'),
        ]);

        return $head->values()->all();
    }

    /**
     * @return array{table: string, id_column: string, response_column: string, fallback: string}
     */
    private function levelConfig(string $level): array
    {
        return match ($level) {
            'processes' => [
                'table' => 'proceso',
                'id_column' => 'id_proceso',
                'response_column' => 'id_proceso',
                'fallback' => 'Proceso sin catalogar',
            ],
            'dependencies' => [
                'table' => 'dependencia',
                'id_column' => 'id_dependencia',
                'response_column' => 'id_dependencia',
                'fallback' => 'Dependencia sin catalogar',
            ],
            'services' => [
                'table' => 'servicio',
                'id_column' => 'id_servicio',
                'response_column' => 'id_servicio',
                'fallback' => 'Servicio sin catalogar',
            ],
            default => throw new \InvalidArgumentException('Nivel de estadisticas no soportado.'),
        };
    }

    private function rowExpression(string $type): string
    {
        $parts = [];

        foreach (self::QUESTION_COLUMNS as $column) {
            $parts[] = match ($type) {
                'valid' => "CASE WHEN respuesta.{$column} BETWEEN 1 AND 5 THEN 1 ELSE 0 END",
                'satisfied' => "CASE WHEN respuesta.{$column} IN (4, 5) THEN 1 ELSE 0 END",
                'neutral' => "CASE WHEN respuesta.{$column} = 3 THEN 1 ELSE 0 END",
                'dissatisfied' => "CASE WHEN respuesta.{$column} IN (1, 2) THEN 1 ELSE 0 END",
                'score' => "CASE WHEN respuesta.{$column} BETWEEN 1 AND 5 THEN respuesta.{$column} ELSE 0 END",
                default => throw new \InvalidArgumentException('Tipo de expresion no soportado.'),
            };
        }

        return implode(' + ', $parts);
    }

    private function percentage(int|float $value, int|float $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($value / $total) * 100, 2);
    }

    private function isProcessLocked(User $user): bool
    {
        return $user->isLiderProceso() || $user->isLiderDependencia();
    }

    private function isDependencyLocked(User $user): bool
    {
        return $user->isLiderDependencia();
    }

    private function etiquetaEntidad(string $level): string
    {
        return match ($level) {
            'processes' => 'Proceso',
            'dependencies' => 'Dependencia',
            'services' => 'Servicio',
            default => 'Entidad',
        };
    }

    private function scopeDescription(User $user, string $level): string
    {
        if ($user->isAdmin() || $user->isAdmin20()) {
            return 'Vista global habilitada para administradores.';
        }

        if ($user->isLiderProceso()) {
            return $level === 'processes'
                ? 'Vista restringida al proceso asignado al lider.'
                : 'Vista global de las dependencias del proceso asignado.';
        }

        if ($user->isLiderDependencia()) {
            return 'Vista restringida a los servicios de la dependencia asignada.';
        }

        return 'Vista filtrada por el alcance del usuario.';
    }
}
