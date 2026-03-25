<x-app-layout>
    @include('reportes.parciales.form', [
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
        'quarters' => $quarters,
        'quarterYear' => $quarterYear,
        'selectedQuarterNumber' => $selectedQuarterNumber,
        'selectedQuarterPeriod' => $selectedQuarterPeriod,
        'selectionSummary' => $selectionSummary,
        'conclusionUrl' => $conclusionUrl,
        'filterError' => $filterError,
        'pdfUrl' => $pdfUrl,
        'report' => $report,
    ])
</x-app-layout>
