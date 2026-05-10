<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Dependencia;
use App\Models\Proceso;
use App\Models\ReportingQuarter;
use App\Models\Servicio;
use App\Models\User;
use App\Services\Reportes\ServicioConclusionesIa;
use App\Services\Reportes\ServicioImagenesGraficosPdf;
use App\Services\Reportes\ServicioReportes;
use App\Services\Reportes\ServicioTrimestresReporte;
use App\Services\Sedes\ServicioSedes;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

abstract class ControladorReporteAbstracto extends Controller
{
    protected const INDIVIDUAL_SERVICE_FILTER_PROCESS = 'Gestion Bienestar Social Universitario';

    public function __construct(
        protected readonly ServicioReportes $reportService,
        protected readonly ServicioConclusionesIa $aiConclusionService,
        protected readonly ServicioImagenesGraficosPdf $chartImageService,
        protected readonly ServicioTrimestresReporte $reportingQuarterService,
        protected readonly ServicioSedes $sedeService,
    ) {}

    /**
     * @return array{
     *     type: string,
     *     view: string,
     *     title: string,
     *     description: string,
     *     summary: string
     * }
     */
    abstract protected function definition(): array;

    public function generateConclusion(Request $request): JsonResponse
    {
        $definition = $this->definition();
        $type = $definition['type'];
        $user = $request->user();
        $showProcessSelect = $type !== 'general';
        $showDependencySelect = $type === 'individual';
        $quarterYear = $this->reportingQuarterService->currentYear();
        $selectedSedeId = $this->selectedSedeId($request, $type);
        $quarters = $this->reportingQuarterService->forYear($quarterYear, $selectedSedeId);

        $selectedQuarterNumber = $this->normalizeQuarter($request->input('trimestre'));
        $selectedQuarter = $selectedQuarterNumber !== null
            ? $quarters->firstWhere('quarter_number', $selectedQuarterNumber)
            : null;
        $selectedFrom = $selectedQuarter?->start_date?->toDateString() ?? '';
        $selectedTo = $selectedQuarter?->end_date?->toDateString() ?? '';
        $selectedProcesoId = $this->normalizeId($request->input('id_proceso'));
        $selectedDependenciaId = $this->normalizeId($request->input('id_dependencia'));
        $requestedServiceIds = $this->normalizeIds($request->input('id_servicios'));

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
            ? $this->availableProcesos($forcedProcesoId, $selectedSedeId)
            : collect();

        if (
            $showProcessSelect &&
            $selectedProcesoId !== null &&
            ! $procesos->contains(fn (Proceso $proceso) => (int) $proceso->id_proceso === $selectedProcesoId)
        ) {
            $selectedProcesoId = $forcedProcesoId;
        }

        $dependencias = $showDependencySelect
            ? $this->dependenciasForProceso($selectedProcesoId, $forcedDependenciaId, $selectedSedeId)
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
        $serviceSelectionEnabled = $this->serviceSelectionEnabled($type, $selectedProceso);
        $servicios = $showDependencySelect && $serviceSelectionEnabled
            ? $this->servicesForDependencia($selectedDependenciaId, $selectedSedeId)
            : collect();
        $invalidSelectedServiceIds = $this->invalidSelectedServiceIds($requestedServiceIds, $servicios);
        $selectedServiceIds = $this->resolveSelectedServiceIds($requestedServiceIds, $servicios);
        $selectedServiceNames = $this->selectedServiceNames($servicios, $selectedServiceIds);

        $validator = Validator::make($request->all(), [
            'trimestre' => ['required', 'integer', 'between:1,4'],
            'id_proceso' => $showProcessSelect ? ['required', 'integer'] : ['nullable', 'integer'],
            'id_dependencia' => $showDependencySelect ? ['required', 'integer'] : ['nullable', 'integer'],
            'id_servicios' => $showDependencySelect && $serviceSelectionEnabled ? ['nullable', 'array'] : ['nullable'],
            'id_servicios.*' => $showDependencySelect && $serviceSelectionEnabled ? ['integer'] : ['nullable'],
        ]);

        $validator->after(function ($validator) use (
            $selectedQuarter,
            $showProcessSelect,
            $showDependencySelect,
            $selectedProceso,
            $selectedDependencia,
            $type,
            $servicios,
            $selectedServiceIds,
            $invalidSelectedServiceIds
        ): void {
            if (! $selectedQuarter) {
                $validator->errors()->add('trimestre', 'Selecciona un trimestre valido para generar el reporte.');
            }

            if ($showProcessSelect && ! $selectedProceso) {
                $validator->errors()->add('id_proceso', 'Selecciona un proceso valido para generar el reporte.');
            }

            if ($showDependencySelect && ! $selectedDependencia) {
                $validator->errors()->add('id_dependencia', 'Selecciona una dependencia valida para generar el reporte.');
            }

            if ($showDependencySelect && $invalidSelectedServiceIds !== []) {
                $validator->errors()->add('id_servicios', 'Selecciona servicios validos para generar el reporte.');
            }

            if ($this->requiresServiceSelection($type, $servicios) && $selectedServiceIds === []) {
                $validator->errors()->add('id_servicios', 'Selecciona uno o varios servicios para generar el reporte individual.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $report = $this->reportService->generate(
            $type,
            $selectedFrom,
            $selectedTo,
            $selectedProcesoId,
            $selectedDependenciaId,
            $selectedServiceIds,
            $selectedSedeId
        );

        if (($report['observations'] ?? []) === []) {
            return response()->json([
                'message' => 'No hay observaciones recientes para generar la conclusion.',
            ], 422);
        }

        try {
            $conclusion = $this->aiConclusionService->generate($type, $report, [
                'dependency' => $selectedDependencia?->nombre,
                'period' => $selectedQuarter?->periodLabel(),
                'process' => $selectedProceso?->nombre,
                'quarter' => $selectedQuarter?->label(),
                'sede' => $this->selectedSedeLabel($selectedSedeId),
                'services' => $selectedServiceNames !== [] ? implode(', ', $selectedServiceNames) : null,
                'title' => $definition['title'],
            ]);
        } catch (\RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 503);
        }

        return response()->json([
            'conclusion' => $conclusion,
        ]);
    }

    final protected function renderReportModule(Request $request): View|Response
    {
        $definition = $this->definition();
        $type = $definition['type'];
        $user = $request->user();
        $showProcessSelect = $type !== 'general';
        $showDependencySelect = $type === 'individual';
        $quarterYear = $this->reportingQuarterService->currentYear();
        $selectedSedeId = $this->selectedSedeId($request, $type);
        $quarters = $this->reportingQuarterService->forYear($quarterYear, $selectedSedeId);
        $selectedSedeLabel = $this->selectedSedeLabel($selectedSedeId);

        $selectedQuarterNumber = $this->normalizeQuarter($request->query('trimestre'));
        $selectedQuarter = $selectedQuarterNumber !== null
            ? $quarters->firstWhere('quarter_number', $selectedQuarterNumber)
            : null;
        $selectedFrom = $selectedQuarter?->start_date?->toDateString() ?? '';
        $selectedTo = $selectedQuarter?->end_date?->toDateString() ?? '';
        $selectedProcesoId = $this->normalizeId($request->query('id_proceso'));
        $selectedDependenciaId = $this->normalizeId($request->query('id_dependencia'));
        $requestedServiceIds = $this->normalizeIds($request->query('id_servicios'));

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
            ? $this->availableProcesos($forcedProcesoId, $selectedSedeId)
            : collect();

        if (
            $showProcessSelect &&
            $selectedProcesoId !== null &&
            ! $procesos->contains(fn (Proceso $proceso) => (int) $proceso->id_proceso === $selectedProcesoId)
        ) {
            $selectedProcesoId = $forcedProcesoId;
        }

        $dependencias = $showDependencySelect
            ? $this->dependenciasForProceso($selectedProcesoId, $forcedDependenciaId, $selectedSedeId)
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
        $serviceSelectionEnabled = $this->serviceSelectionEnabled($type, $selectedProceso);
        $servicios = $showDependencySelect && $serviceSelectionEnabled
            ? $this->servicesForDependencia($selectedDependenciaId, $selectedSedeId)
            : collect();
        $invalidSelectedServiceIds = $this->invalidSelectedServiceIds($requestedServiceIds, $servicios);
        $selectedServiceIds = $this->resolveSelectedServiceIds($requestedServiceIds, $servicios);
        $selectedServiceNames = $this->selectedServiceNames($servicios, $selectedServiceIds);
        $serviceFilterProcessId = $this->serviceFilterProcessId($procesos);

        $attempted = $this->filtersWereSubmitted($request, $showProcessSelect, $showDependencySelect);
        $filterError = null;
        $pdfUnavailableReason = null;
        $report = null;
        $requiresConclusionConfirmation = false;

        if ($attempted) {
            $validator = Validator::make($request->query(), [
                'trimestre' => ['required', 'integer', 'between:1,4'],
                'id_proceso' => $showProcessSelect ? ['required', 'integer'] : ['nullable', 'integer'],
                'id_dependencia' => $showDependencySelect ? ['required', 'integer'] : ['nullable', 'integer'],
                'id_servicios' => $showDependencySelect && $serviceSelectionEnabled ? ['nullable', 'array'] : ['nullable'],
                'id_servicios.*' => $showDependencySelect && $serviceSelectionEnabled ? ['integer'] : ['nullable'],
            ]);

            $validator->after(function ($validator) use (
                $selectedQuarter,
                $showProcessSelect,
                $showDependencySelect,
                $selectedProceso,
                $selectedDependencia,
                $type,
                $servicios,
                $selectedServiceIds,
                $invalidSelectedServiceIds
            ): void {
                if (! $selectedQuarter) {
                    $validator->errors()->add('trimestre', 'Selecciona un trimestre valido para generar el reporte.');
                }

                if ($showProcessSelect && ! $selectedProceso) {
                    $validator->errors()->add('id_proceso', 'Selecciona un proceso valido para generar el reporte.');
                }

                if ($showDependencySelect && ! $selectedDependencia) {
                    $validator->errors()->add('id_dependencia', 'Selecciona una dependencia valida para generar el reporte.');
                }

                if ($showDependencySelect && $invalidSelectedServiceIds !== []) {
                    $validator->errors()->add('id_servicios', 'Selecciona servicios validos para generar el reporte.');
                }

                if ($this->requiresServiceSelection($type, $servicios) && $selectedServiceIds === []) {
                    $validator->errors()->add('id_servicios', 'Selecciona uno o varios servicios para generar el reporte individual.');
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
                        $selectedDependenciaId,
                        $selectedServiceIds,
                        $selectedSedeId
                    );

                $pdfUnavailableReason = $this->pdfUnavailableReason($type, $report, $selectedSedeId, $selectedServiceIds);
                $requiresConclusionConfirmation = $this->requiresConclusionConfirmation($report);
                $generatedConclusion = $this->sanitizeConclusion($request->query('generated_conclusion'));

                if ($request->boolean('export_pdf')) {
                    if ($pdfUnavailableReason !== null) {
                        $filterError = $pdfUnavailableReason;
                    } elseif ($requiresConclusionConfirmation && $generatedConclusion === null) {
                        $filterError = 'Debes generar o confirmar la conclusion antes de descargar el PDF.';
                    } else {
                        return $this->exportReport(
                            $type,
                            $definition['title'],
                            $definition['description'],
                            $report,
                            $this->resolveSignature(
                                $type,
                                $selectedProcesoId,
                                $selectedDependenciaId,
                                $selectedProceso?->nombre,
                                $selectedDependencia?->nombre
                            ),
                            $this->buildContextRows(
                                $selectedQuarter,
                                $selectedSedeLabel,
                                $selectedProceso?->nombre,
                                $selectedDependencia?->nombre,
                                $selectedServiceNames
                            ),
                            $generatedConclusion
                        );
                    }
                }
            }
        }

        $routeName = $request->route()?->getName();
        $conclusionUrl = $routeName !== null
            ? route($routeName.'.conclusion')
            : null;
        $pdfUrl = null;

        if ($report && $routeName !== null && $pdfUnavailableReason === null) {
            $pdfUrl = route($routeName, array_filter([
                'trimestre' => $selectedQuarterNumber,
                'id_proceso' => $selectedProcesoId,
                'id_dependencia' => $selectedDependenciaId,
                'id_sede' => $selectedSedeId,
                'id_servicios' => $serviceSelectionEnabled && $selectedServiceIds !== [] ? $selectedServiceIds : null,
                'export_pdf' => 1,
            ], static fn ($value): bool => $value !== null && $value !== ''));
        }

        return view($definition['view'], [
            'dependencias' => $dependencias,
            'description' => $definition['description'],
            'filterError' => $filterError,
            'conclusionUrl' => $conclusionUrl,
            'pdfUrl' => $pdfUrl,
            'pdfUnavailableReason' => $pdfUnavailableReason,
            'quarterYear' => $quarterYear,
            'quarters' => $quarters,
            'procesos' => $procesos,
            'report' => $report,
            'requiresConclusionConfirmation' => $requiresConclusionConfirmation,
            'selectedSedeId' => $selectedSedeId,
            'selectedDependenciaId' => $selectedDependenciaId,
            'selectedDependencyLocked' => $forcedDependenciaId !== null,
            'selectedProcessLocked' => $forcedProcesoId !== null,
            'selectedProcesoId' => $selectedProcesoId,
            'selectedQuarterNumber' => $selectedQuarterNumber,
            'selectedQuarterPeriod' => $selectedQuarter?->periodLabel() ?? '',
            'selectedServiceIds' => $selectedServiceIds,
            'sedes' => $this->availableSedesForReport($user, $type),
            'showSedeSelect' => $this->showSedeSelect($user, $type),
            'serviceFilterProcessId' => $serviceFilterProcessId,
            'serviceSelectionEnabled' => $serviceSelectionEnabled,
            'selectionSummary' => $this->selectionSummary(
                $selectedQuarter,
                $selectedSedeLabel,
                $selectedProceso?->nombre,
                $selectedDependencia?->nombre,
                $selectedServiceNames
            ),
            'servicios' => $servicios,
            'showDependencySelect' => $showDependencySelect,
            'showProcessSelect' => $showProcessSelect,
            'summary' => $this->summaryForView($definition, $serviceSelectionEnabled),
            'title' => $definition['title'],
        ]);
    }

    /**
     * @param  array{name: string, title: string, scope: string}|null  $signature
     * @param  array<int, array{label: string, value: string}>  $contextRows
     */
    private function exportReport(
        string $type,
        string $title,
        string $description,
        array $report,
        ?array $signature,
        array $contextRows,
        ?string $generatedConclusion = null
    ): Response {
        $html = view('reportes.exportar', [
            'chartImages' => $this->chartImageService->build($report),
            'contextRows' => $contextRows,
            'description' => $description,
            'generatedConclusion' => $generatedConclusion,
            'printFallback' => ! class_exists(\Dompdf\Dompdf::class),
            'report' => $report,
            'reportType' => $type,
            'signature' => $signature,
            'title' => $title,
        ])->render();

        $options = new \Dompdf\Options;
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();
        $this->applyPdfFooter($dompdf);

        return response($dompdf->output(), 200, [
            'Content-Disposition' => 'attachment; filename="'.Str::slug($title).'-'.$report['from'].'-'.$report['to'].'.pdf"',
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function applyPdfFooter(\Dompdf\Dompdf $dompdf): void
    {
        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans', 'normal')
            ?? $fontMetrics->getFont(null, 'normal');

        if ($font === null) {
            return;
        }

        $pageWidth = $canvas->get_width();
        $pageHeight = $canvas->get_height();
        $fontSize = 7.5;
        $lineHeight = 9;
        $rightMargin = 24;
        $bottomMargin = 28;
        $revisionLabel = 'REV.01/JUN/23';

        $canvas->page_script(
            static function (
                int $pageNumber,
                int $pageCount,
                \Dompdf\Canvas $canvas,
                \Dompdf\FontMetrics $fontMetrics
            ) use (
                $bottomMargin,
                $font,
                $fontSize,
                $lineHeight,
                $pageHeight,
                $pageWidth,
                $revisionLabel,
                $rightMargin
            ): void {
                if ($pageNumber === 1) {
                    return;
                }

                $pageLabel = sprintf("P\u{00E1}gina %d de %d", $pageNumber, $pageCount);
                $footerLines = [
                    $pageLabel,
                    $revisionLabel,
                ];
                $baseY = $pageHeight - $bottomMargin;

                foreach ($footerLines as $index => $line) {
                    $textWidth = $fontMetrics->getTextWidth($line, $font, $fontSize);
                    $x = $pageWidth - $rightMargin - $textWidth;
                    $y = $baseY - ($lineHeight * (count($footerLines) - $index - 1));

                    $canvas->text($x, $y, $line, $font, $fontSize, [0.2, 0.2, 0.2]);
                }
            }
        );
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function buildContextRows(
        ?ReportingQuarter $quarter,
        ?string $sedeName,
        ?string $processName,
        ?string $dependencyName,
        array $serviceNames = []
    ): array
    {
        return array_values(array_filter([
            $quarter ? [
                'label' => 'Trimestre',
                'value' => $quarter->label(),
            ] : null,
            $quarter ? [
                'label' => 'Periodo',
                'value' => $quarter->periodLabel(),
            ] : null,
            $sedeName ? [
                'label' => 'Sede',
                'value' => $sedeName,
            ] : null,
            $processName ? [
                'label' => 'Proceso',
                'value' => $processName,
            ] : null,
            $dependencyName ? [
                'label' => 'Dependencia',
                'value' => $dependencyName,
            ] : null,
            $serviceNames !== [] ? [
                'label' => 'Servicios',
                'value' => implode(', ', $serviceNames),
            ] : null,
        ]));
    }

    private function filtersWereSubmitted(Request $request, bool $showProcessSelect, bool $showDependencySelect): bool
    {
        return $request->filled('trimestre')
            || ($showProcessSelect && $request->filled('id_proceso'))
            || ($showDependencySelect && $request->filled('id_dependencia'))
            || $request->boolean('export_pdf');
    }

    /**
     * @return Collection<int, Proceso>
     */
    private function availableProcesos(?int $forcedProcesoId, ?int $sedeId): Collection
    {
        return Proceso::query()
            ->forSede($sedeId)
            ->when($forcedProcesoId !== null, fn ($query) => $query->where('id_proceso', $forcedProcesoId))
            ->orderBy('nombre')
            ->get(['id_proceso', 'nombre']);
    }

    /**
     * @return Collection<int, Dependencia>
     */
    private function dependenciasForProceso(?int $procesoId, ?int $forcedDependenciaId, ?int $sedeId): Collection
    {
        if ($procesoId === null) {
            return collect();
        }

        return Dependencia::query()
            ->forSede($sedeId)
            ->where('id_proceso', $procesoId)
            ->when($forcedDependenciaId !== null, fn ($query) => $query->where('id_dependencia', $forcedDependenciaId))
            ->orderBy('nombre')
            ->get(['id_dependencia', 'nombre']);
    }

    /**
     * @return Collection<int, Servicio>
     */
    private function servicesForDependencia(?int $dependenciaId, ?int $sedeId): Collection
    {
        if ($dependenciaId === null) {
            return collect();
        }

        return Servicio::query()
            ->forSede($sedeId)
            ->where('id_dependencia', $dependenciaId)
            ->orderBy('nombre')
            ->get(['id_servicio', 'nombre', 'activo']);
    }

    private function selectionSummary(
        ?ReportingQuarter $quarter,
        ?string $sedeName,
        ?string $processName,
        ?string $dependencyName,
        array $serviceNames = []
    ): string
    {
        $parts = [];

        if ($quarter) {
            $parts[] = 'Trimestre: '.$quarter->label();
            $parts[] = 'Periodo: '.$quarter->periodLabel();
        }

        if ($sedeName) {
            $parts[] = 'Sede: '.$sedeName;
        }

        if ($processName) {
            $parts[] = 'Proceso: '.$processName;
        }

        if ($dependencyName) {
            $parts[] = 'Dependencia: '.$dependencyName;
        }

        if ($serviceNames !== []) {
            $parts[] = 'Servicios: '.implode(', ', $serviceNames);
        }

        return $parts !== []
            ? implode(' | ', $parts)
            : 'Selecciona un trimestre y genera el consolidado de satisfaccion.';
    }

    /**
     * @param  array{type: string, summary: string}  $definition
     */
    private function summaryForView(array $definition, bool $serviceSelectionEnabled): string
    {
        if ($definition['type'] !== 'individual' || $serviceSelectionEnabled) {
            return $definition['summary'];
        }

        return 'Selecciona trimestre, proceso y dependencia para calcular el detalle individual.';
    }

    private function pdfUnavailableReason(
        string $type,
        array $report,
        ?int $selectedSedeId = null,
        array $selectedServiceIds = []
    ): ?string
    {
        $surveyCount = (int) ($report['totals']['survey_count'] ?? 0);

        if ($surveyCount > 0) {
            return null;
        }

        return match ($type) {
            'general' => $selectedSedeId !== null
                ? 'No se puede descargar el PDF porque la sede seleccionada no tiene respuestas en el periodo.'
                : 'No se puede descargar el PDF porque no hay respuestas en el periodo seleccionado.',
            'process' => 'No se puede descargar el PDF porque el proceso seleccionado no tiene respuestas en el periodo.',
            'individual' => $selectedServiceIds !== []
                ? 'No se puede descargar el PDF porque los servicios seleccionados no tienen respuestas en el periodo.'
                : 'No se puede descargar el PDF porque la dependencia seleccionada no tiene respuestas en el periodo.',
            default => null,
        };
    }

    private function requiresConclusionConfirmation(?array $report): bool
    {
        if ($report === null) {
            return false;
        }

        return (int) ($report['totals']['survey_count'] ?? 0) > 0;
    }

    /**
     * @return array{name: string, title: string, scope: string}|null
     */
    private function resolveSignature(
        string $type,
        ?int $processId,
        ?int $dependencyId,
        ?string $processName,
        ?string $dependencyName
    ): ?array {
        return match ($type) {
            'process' => $this->signatureForProcess($processId, $processName),
            'individual' => $this->signatureForDependency($dependencyId, $dependencyName),
            default => null,
        };
    }

    /**
     * @return array{name: string, title: string, scope: string}|null
     */
    private function signatureForProcess(?int $processId, ?string $processName): ?array
    {
        if ($processId === null || blank($processName)) {
            return null;
        }

        $leader = User::query()
            ->where('rol', User::ROLE_LIDER_PROCESO)
            ->where('id_proceso', $processId)
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->first();

        if (! $leader) {
            return null;
        }

        return [
            'name' => (string) $leader->nombre,
            'title' => 'Lider del proceso de',
            'scope' => mb_strtoupper($processName, 'UTF-8'),
        ];
    }

    /**
     * @return array{name: string, title: string, scope: string}|null
     */
    private function signatureForDependency(?int $dependencyId, ?string $dependencyName): ?array
    {
        if ($dependencyId === null || blank($dependencyName)) {
            return null;
        }

        $leader = User::query()
            ->where('rol', User::ROLE_LIDER_DEPENDENCIA)
            ->where('id_dependencia', $dependencyId)
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->first();

        if (! $leader) {
            return null;
        }

        return [
            'name' => (string) $leader->nombre,
            'title' => 'Lider de dependencia de',
            'scope' => mb_strtoupper($dependencyName, 'UTF-8'),
        ];
    }

    private function normalizeId(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private function selectedSedeId(Request $request, string $type): ?int
    {
        $user = $request->user();

        if ($type === 'general' && $user?->puedeAccederConsolidadoUniversitario() && ! $user->hasGlobalSedeAccess()) {
            if (! $request->exists('id_sede')) {
                return $user->id_sede ? (int) $user->id_sede : null;
            }

            $requestedSedeId = $this->sedeService->normalizeId($request->input('id_sede'));

            if ($requestedSedeId === null) {
                return null;
            }

            return (int) $user->id_sede === $requestedSedeId
                ? $requestedSedeId
                : ($user->id_sede ? (int) $user->id_sede : null);
        }

        return $this->sedeService->resolveForRequest(
            $user,
            $request,
            'id_sede',
            true,
            true
        );
    }

    private function selectedSedeLabel(?int $sedeId): string
    {
        return $this->sedeService->selectionLabel($sedeId);
    }

    private function showSedeSelect(?User $user, string $type): bool
    {
        if ($user?->hasGlobalSedeAccess()) {
            return true;
        }

        return $type === 'general' && ($user?->puedeAccederConsolidadoUniversitario() ?? false);
    }

    private function availableSedesForReport(?User $user, string $type): Collection
    {
        if ($type === 'general' && ($user?->puedeAccederConsolidadoUniversitario() ?? false) && ! $user->hasGlobalSedeAccess()) {
            return $this->sedeService->visibleTo($user);
        }

        return $this->sedeService->visibleTo($user);
    }

    /**
     * @return array<int, int>
     */
    private function normalizeIds(mixed $value): array
    {
        $values = is_array($value) ? $value : [$value];
        $normalized = [];

        foreach ($values as $item) {
            $id = $this->normalizeId($item);

            if ($id !== null) {
                $normalized[] = $id;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeQuarter(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return in_array($normalized, [1, 2, 3, 4], true) ? $normalized : null;
    }

    private function sanitizeConclusion(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @param  array<int, int>  $selectedServiceIds
     * @return array<int, int>
     */
    private function invalidSelectedServiceIds(array $selectedServiceIds, Collection $services): array
    {
        if ($selectedServiceIds === [] || $services->isEmpty()) {
            return [];
        }

        $validIds = $services
            ->pluck('id_servicio')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        return array_values(array_diff($selectedServiceIds, $validIds));
    }

    /**
     * @param  array<int, int>  $selectedServiceIds
     * @return array<int, int>
     */
    private function resolveSelectedServiceIds(array $selectedServiceIds, Collection $services): array
    {
        if ($services->isEmpty()) {
            return [];
        }

        $validIds = $services
            ->pluck('id_servicio')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $resolved = array_values(array_intersect($selectedServiceIds, $validIds));

        if ($resolved === [] && count($validIds) === 1) {
            return [(int) $validIds[0]];
        }

        return $resolved;
    }

    private function requiresServiceSelection(string $type, Collection $services): bool
    {
        return $type === 'individual' && $services->count() > 1;
    }

    /**
     * @param  array<int, int>  $selectedServiceIds
     * @return array<int, string>
     */
    private function selectedServiceNames(Collection $services, array $selectedServiceIds): array
    {
        if ($selectedServiceIds === []) {
            return [];
        }

        $selectedLookup = array_flip($selectedServiceIds);

        return $services
            ->filter(static fn (Servicio $servicio): bool => isset($selectedLookup[(int) $servicio->id_servicio]))
            ->pluck('nombre')
            ->map(static fn (mixed $name): string => trim((string) $name))
            ->filter(static fn (string $name): bool => $name !== '')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Proceso>  $procesos
     */
    protected function serviceFilterProcessId(Collection $procesos): ?int
    {
        $process = $procesos->first(
            fn (Proceso $proceso): bool => $this->isIndividualServiceFilterProcess($proceso->nombre)
        );

        return $process instanceof Proceso ? (int) $process->id_proceso : null;
    }

    protected function serviceSelectionEnabled(string $type, ?Proceso $selectedProceso): bool
    {
        return $type === 'individual'
            && $selectedProceso instanceof Proceso
            && $this->isIndividualServiceFilterProcess($selectedProceso->nombre);
    }

    protected function isIndividualServiceFilterProcess(?string $processName): bool
    {
        return trim(mb_strtolower((string) $processName, 'UTF-8'))
            === trim(mb_strtolower(self::INDIVIDUAL_SERVICE_FILTER_PROCESS, 'UTF-8'));
    }
}
