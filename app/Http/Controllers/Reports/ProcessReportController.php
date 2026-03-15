<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProcessReportController extends AbstractReportController
{
    public function index(Request $request): View|Response
    {
        return $this->renderReportModule($request);
    }

    protected function definition(): array
    {
        return [
            'type' => 'process',
            'view' => 'reports.process.index',
            'title' => 'Reporte por proceso',
            'description' => 'Consolidado de todas las dependencias que pertenecen al proceso seleccionado en el trimestre indicado.',
            'summary' => 'Selecciona un trimestre y un proceso para agrupar todas sus dependencias.',
        ];
    }
}
