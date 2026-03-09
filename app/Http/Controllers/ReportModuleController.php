<?php

namespace App\Http\Controllers;

use App\Models\Dependencia;
use App\Models\Proceso;
use App\Services\PdfChartImageService;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ReportModuleController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly PdfChartImageService $chartImageService,
    ) {}

    public function general(Request $request): View|Response
    {
        return $this->renderModuleView($request, 'general', 'modules.reportes-general', [
            'title' => 'Reporte general',
            'description' => 'Consolidado de todos los procesos dentro del rango de fechas seleccionado.',
            'summary' => 'Filtra por fechas y calcula el comportamiento global de satisfaccion de todas las encuestas.',
        ]);
    }

    public function process(Request $request): View|Response
    {
        return $this->renderModuleView($request, 'process', 'modules.reportes-proceso', [
            'title' => 'Reporte por proceso',
            'description' => 'Consolidado de todas las dependencias que pertenecen al proceso seleccionado.',
            'summary' => 'Selecciona un proceso y el rango de fechas para agrupar todas sus dependencias.',
        ]);
    }

    public function individual(Request $request): View|Response
    {
        return $this->renderModuleView($request, 'individual', 'modules.reportes-individual', [
            'title' => 'Reporte individual',
            'description' => 'Analisis puntual de la dependencia seleccionada dentro de su proceso.',
            'summary' => 'Selecciona proceso, dependencia y rango de fechas para calcular el detalle individual.',
        ]);
    }

    /**
     * @param  array{title: string, description: string, summary: string}  $meta
     */
    private function renderModuleView(Request $request, string $type, string $view, array $meta): View|Response
    {
        $user = $request->user();
        $showProcessSelect = $type !== 'general';
        $showDependencySelect = $type === 'individual';

        $selectedFrom = (string) $request->query('desde', '');
        $selectedTo = (string) $request->query('hasta', '');
        $selectedProcesoId = $this->normalizeId($request->query('id_proceso'));
        $selectedDependenciaId = $this->normalizeId($request->query('id_dependencia'));

        $forcedProcesoId = match (true) {
            $showProcessSelect && $user?->isLiderProceso() && $user->id_proceso => (int) $user->id_proceso,
            $showProcessSelect && $user?->isLiderDependencia() && $user->id_proceso => (int) $user->id_proceso,
            default => null,
        };

        $forcedDependenciaId = $showDependencySelect && $user?->isLiderDependencia() && $user->id_dependencia
            ? (int) $user->id_dependencia
            : null;

        if ($forcedProcesoId !== null) {
            $selectedProcesoId = $forcedProcesoId;
        }

        if ($forcedDependenciaId !== null) {
            $selectedDependenciaId = $forcedDependenciaId;
        }

        $procesos = $showProcessSelect
            ? $this->availableProcesos($forcedProcesoId)
            : collect();

        if (
            $showProcessSelect &&
            $selectedProcesoId !== null &&
            ! $procesos->contains(fn (Proceso $proceso) => (int) $proceso->id_proceso === $selectedProcesoId)
        ) {
            $selectedProcesoId = $forcedProcesoId;
        }

        $dependencias = $showDependencySelect
            ? $this->dependenciasForProceso($selectedProcesoId, $forcedDependenciaId)
            : collect();

        if (
            $showDependencySelect &&
            $selectedDependenciaId !== null &&
            ! $dependencias->contains(fn (Dependencia $dependencia) => (int) $dependencia->id_dependencia === $selectedDependenciaId)
        ) {
            $selectedDependenciaId = $forcedDependenciaId;
        }

        $selectedProceso = $showProcessSelect
            ? $procesos->firstWhere('id_proceso', $selectedProcesoId)
            : null;
        $selectedDependencia = $showDependencySelect
            ? $dependencias->firstWhere('id_dependencia', $selectedDependenciaId)
            : null;

        $attempted = $this->filtersWereSubmitted($request, $showProcessSelect, $showDependencySelect);
        $filterError = null;
        $report = null;

        if ($attempted) {
            $validator = Validator::make($request->query(), [
                'desde' => ['required', 'date'],
                'hasta' => ['required', 'date', 'after_or_equal:desde'],
                'id_proceso' => $showProcessSelect ? ['required', 'integer'] : ['nullable', 'integer'],
                'id_dependencia' => $showDependencySelect ? ['required', 'integer'] : ['nullable', 'integer'],
            ]);

            $validator->after(function ($validator) use (
                $showProcessSelect,
                $showDependencySelect,
                $selectedProceso,
                $selectedDependencia
            ): void {
                if ($showProcessSelect && ! $selectedProceso) {
                    $validator->errors()->add('id_proceso', 'Selecciona un proceso valido para generar el reporte.');
                }

                if ($showDependencySelect && ! $selectedDependencia) {
                    $validator->errors()->add('id_dependencia', 'Selecciona una dependencia valida para generar el reporte.');
                }
            });

            if ($validator->fails()) {
                $filterError = $validator->errors()->first();
            } else {
                $report = $this->reportService->generate(
                    $type,
                    $selectedFrom,
                    $selectedTo,
                    $selectedProcesoId,
                    $selectedDependenciaId
                );

                if ($request->boolean('export_pdf')) {
                    return $this->exportReport(
                        $meta['title'],
                        $meta['description'],
                        $report,
                        $this->buildContextRows($selectedFrom, $selectedTo, $selectedProceso?->nombre, $selectedDependencia?->nombre)
                    );
                }
            }
        }

        return view($view, array_merge($meta, [
            'dependencias' => $dependencias,
            'filterError' => $filterError,
            'pdfUrl' => $report
                ? route(request()->route()->getName(), array_filter([
                    'desde' => $selectedFrom,
                    'hasta' => $selectedTo,
                    'id_proceso' => $selectedProcesoId,
                    'id_dependencia' => $selectedDependenciaId,
                    'export_pdf' => 1,
                ], static fn ($value): bool => $value !== null && $value !== ''))
                : null,
            'procesos' => $procesos,
            'report' => $report,
            'selectionSummary' => $this->selectionSummary($selectedFrom, $selectedTo, $selectedProceso?->nombre, $selectedDependencia?->nombre),
            'showDependencySelect' => $showDependencySelect,
            'showProcessSelect' => $showProcessSelect,
            'selectedDependenciaId' => $selectedDependenciaId,
            'selectedDependencyLocked' => $forcedDependenciaId !== null,
            'selectedFrom' => $selectedFrom,
            'selectedProcesoId' => $selectedProcesoId,
            'selectedProcessLocked' => $forcedProcesoId !== null,
            'selectedTo' => $selectedTo,
        ]));
    }

    /**
     * @param  array<int, array{label: string, value: string}>  $contextRows
     */
    private function exportReport(string $title, string $description, array $report, array $contextRows): Response
    {
        $html = view('modules.report-export', [
            'chartImages' => $this->chartImageService->build($report),
            'contextRows' => $contextRows,
            'description' => $description,
            'printFallback' => ! class_exists(\Dompdf\Dompdf::class),
            'report' => $report,
            'title' => $title,
        ])->render();

        $options = new \Dompdf\Options;
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Disposition' => 'attachment; filename="'.Str::slug($title).'-'.$report['from'].'-'.$report['to'].'.pdf"',
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function buildContextRows(string $from, string $to, ?string $processName, ?string $dependencyName): array
    {
        return array_values(array_filter([
            [
                'label' => 'Periodo',
                'value' => $from.' a '.$to,
            ],
            $processName ? [
                'label' => 'Proceso',
                'value' => $processName,
            ] : null,
            $dependencyName ? [
                'label' => 'Dependencia',
                'value' => $dependencyName,
            ] : null,
        ]));
    }

    private function filtersWereSubmitted(Request $request, bool $showProcessSelect, bool $showDependencySelect): bool
    {
        return $request->filled('desde')
            || $request->filled('hasta')
            || ($showProcessSelect && $request->filled('id_proceso'))
            || ($showDependencySelect && $request->filled('id_dependencia'))
            || $request->boolean('export_pdf');
    }

    /**
     * @return Collection<int, Proceso>
     */
    private function availableProcesos(?int $forcedProcesoId): Collection
    {
        return Proceso::query()
            ->when($forcedProcesoId !== null, fn ($query) => $query->where('id_proceso', $forcedProcesoId))
            ->orderBy('nombre')
            ->get(['id_proceso', 'nombre']);
    }

    /**
     * @return Collection<int, Dependencia>
     */
    private function dependenciasForProceso(?int $procesoId, ?int $forcedDependenciaId): Collection
    {
        if ($procesoId === null) {
            return collect();
        }

        return Dependencia::query()
            ->where('id_proceso', $procesoId)
            ->when($forcedDependenciaId !== null, fn ($query) => $query->where('id_dependencia', $forcedDependenciaId))
            ->orderBy('nombre')
            ->get(['id_dependencia', 'nombre']);
    }

    private function selectionSummary(string $from, string $to, ?string $processName, ?string $dependencyName): string
    {
        $parts = [];

        if ($from !== '' && $to !== '') {
            $parts[] = 'Periodo: '.$from.' a '.$to;
        }

        if ($processName) {
            $parts[] = 'Proceso: '.$processName;
        }

        if ($dependencyName) {
            $parts[] = 'Dependencia: '.$dependencyName;
        }

        return $parts !== []
            ? implode(' | ', $parts)
            : 'Define los filtros y genera el consolidado de satisfaccion.';
    }

    private function normalizeId(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }
}
