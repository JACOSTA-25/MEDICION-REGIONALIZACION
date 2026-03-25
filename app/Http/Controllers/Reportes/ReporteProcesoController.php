<?php

namespace App\Http\Controllers\Reportes;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReporteProcesoController extends ControladorReporteAbstracto
{
    public function index(Request $request): View|Response
    {
        return $this->renderReportModule($request);
    }

    protected function definition(): array
    {
        return [
            'type' => 'process',
            'view' => 'reportes.proceso.index',
            'title' => 'Reporte por proceso',
            'description' => 'Consolidado de todas las dependencias que pertenecen al proceso seleccionado en el trimestre indicado.',
            'summary' => 'Selecciona un trimestre y un proceso para agrupar todas sus dependencias.',
        ];
    }
}
