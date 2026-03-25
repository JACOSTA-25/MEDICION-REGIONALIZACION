<?php

namespace App\Http\Controllers\Reportes;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReporteGeneralController extends ControladorReporteAbstracto
{
    public function index(Request $request): View|Response
    {
        return $this->renderReportModule($request);
    }

    protected function definition(): array
    {
        return [
            'type' => 'general',
            'view' => 'reportes.general.index',
            'title' => 'Reporte general',
            'description' => 'Consolidado de todos los procesos dentro del trimestre seleccionado.',
            'summary' => 'Selecciona un trimestre y calcula el comportamiento global de satisfaccion de todas las encuestas.',
        ];
    }
}
