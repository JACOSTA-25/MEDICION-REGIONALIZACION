<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Dependencia;
use App\Models\Proceso;
use App\Models\ReportingQuarter;
use App\Models\User;
use App\Services\Reportes\ServicioConclusionesIa;
use App\Services\Reportes\ServicioImagenesGraficosPdf;
use App\Services\Reportes\ServicioTrimestresReporte;
use App\Services\Reportes\ServicioReportes;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

abstract class ControladorReporteAbstracto extends Controller
{
    public function __construct(
        protected readonly ServicioReportes $reportService,
        protected readonly ServicioConclusionesIa $aiConclusionService,
        protected readonly ServicioImagenesGraficosPdf $chartImageService,
        protected readonly ServicioTrimestresReporte $reportingQuarterService,
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
        $quarters = $this->reportingQuarterService->forYear($quarterYear);

        $selectedQuarterNumber = $this->normalizeQuarter($request->input('trimestre'));
        $selectedQuarter = $selectedQuarterNumber !== null
            ? $quarters->firstWhere('quarter_number', $selectedQuarterNumber)
            : null;
        $selectedFrom = $selectedQuarter?->start_date?->toDateString() ?? '';
        $selectedTo = $selectedQuarter?->end_date?->toDateString() ?? '';
        $selectedProcesoId = $this->normalizeId($request->input('id_proceso'));
        $selectedDependenciaId = $this->normalizeId($request->input('id_dependencia'));

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

        $validator = Validator::make($request->all(), [
            'trimestre' => ['required', 'integer', 'between:1,4'],
            'id_proceso' => $showProcessSelect ? ['required', 'integer'] : ['nullable', 'integer'],
            'id_dependencia' => $showDependencySelect ? ['required', 'integer'] : ['nullable', 'integer'],
        ]);

        $validator->after(function ($validator) use (
            $selectedQuarter,
            $showProcessSelect,
            $showDependencySelect,
            $selectedProceso,
            $selectedDependencia
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
            $selectedDependenciaId
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
        $quarters = $this->reportingQuarterService->forYear($quarterYear);

        $selectedQuarterNumber = $this->normalizeQuarter($request->query('trimestre'));
        $selectedQuarter = $selectedQuarterNumber !== null
            ? $quarters->firstWhere('quarter_number', $selectedQuarterNumber)
            : null;
        $selectedFrom = $selectedQuarter?->start_date?->toDateString() ?? '';
        $selectedTo = $selectedQuarter?->end_date?->toDateString() ?? '';
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
                'trimestre' => ['required', 'integer', 'between:1,4'],
                'id_proceso' => $showProcessSelect ? ['required', 'integer'] : ['nullable', 'integer'],
                'id_dependencia' => $showDependencySelect ? ['required', 'integer'] : ['nullable', 'integer'],
            ]);

            $validator->after(function ($validator) use (
                $selectedQuarter,
                $showProcessSelect,
                $showDependencySelect,
                $selectedProceso,
                $selectedDependencia
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
                            $selectedProceso?->nombre,
                            $selectedDependencia?->nombre
                        ),
                        $this->sanitizeConclusion($request->query('generated_conclusion'))
                    );
                }
            }
        }

        $routeName = $request->route()?->getName();
        $conclusionUrl = $routeName !== null
            ? route($routeName.'.conclusion')
            : null;
        $pdfUrl = null;

        if ($report && $routeName !== null) {
            $pdfUrl = route($routeName, array_filter([
                'trimestre' => $selectedQuarterNumber,
                'id_proceso' => $selectedProcesoId,
                'id_dependencia' => $selectedDependenciaId,
                'export_pdf' => 1,
            ], static fn ($value): bool => $value !== null && $value !== ''));
        }

        return view($definition['view'], [
            'dependencias' => $dependencias,
            'description' => $definition['description'],
            'filterError' => $filterError,
            'conclusionUrl' => $conclusionUrl,
            'pdfUrl' => $pdfUrl,
            'quarterYear' => $quarterYear,
            'quarters' => $quarters,
            'procesos' => $procesos,
            'report' => $report,
            'selectedDependenciaId' => $selectedDependenciaId,
            'selectedDependencyLocked' => $forcedDependenciaId !== null,
            'selectedProcessLocked' => $forcedProcesoId !== null,
            'selectedProcesoId' => $selectedProcesoId,
            'selectedQuarterNumber' => $selectedQuarterNumber,
            'selectedQuarterPeriod' => $selectedQuarter?->periodLabel() ?? '',
            'selectionSummary' => $this->selectionSummary(
                $selectedQuarter,
                $selectedProceso?->nombre,
                $selectedDependencia?->nombre
            ),
            'showDependencySelect' => $showDependencySelect,
            'showProcessSelect' => $showProcessSelect,
            'summary' => $definition['summary'],
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
    ): Response
    {
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
    private function buildContextRows(?ReportingQuarter $quarter, ?string $processName, ?string $dependencyName): array
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
        return $request->filled('trimestre')
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

    private function selectionSummary(?ReportingQuarter $quarter, ?string $processName, ?string $dependencyName): string
    {
        $parts = [];

        if ($quarter) {
            $parts[] = 'Trimestre: '.$quarter->label();
            $parts[] = 'Periodo: '.$quarter->periodLabel();
        }

        if ($processName) {
            $parts[] = 'Proceso: '.$processName;
        }

        if ($dependencyName) {
            $parts[] = 'Dependencia: '.$dependencyName;
        }

        return $parts !== []
            ? implode(' | ', $parts)
            : 'Selecciona un trimestre y genera el consolidado de satisfaccion.';
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
            'title' => 'Lider de la dependencia',
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
}
