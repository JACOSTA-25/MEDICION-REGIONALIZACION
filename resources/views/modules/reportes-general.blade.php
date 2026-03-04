<x-app-layout>
    @include('modules.partials.report-form', [
        'title' => $title,
        'description' => $description,
        'summary' => $summary,
        'procesos' => $procesos,
        'dependencias' => $dependencias,
        'showProcessSelect' => $showProcessSelect,
        'showDependencySelect' => $showDependencySelect,
        'selectedProcesoId' => $selectedProcesoId,
        'selectedProcessLocked' => $selectedProcessLocked,
        'selectedDependenciaId' => $selectedDependenciaId,
        'selectedDependencyLocked' => $selectedDependencyLocked,
        'selectedFrom' => $selectedFrom,
        'selectedTo' => $selectedTo,
        'selectionSummary' => $selectionSummary,
        'filterError' => $filterError,
        'pdfUrl' => $pdfUrl,
        'report' => $report,
    ])
</x-app-layout>
