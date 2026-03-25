<?php

namespace App\Http\Controllers\Reportes;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReporteIndividualController extends ControladorReporteAbstracto
{
    public function index(Request $request): View|Response
    {
        return $this->renderReportModule($request);
    }

    protected function definition(): array
    {
        return [
            'type' => 'individual',
            'view' => 'reportes.individual.index',
            'title' => 'Reporte individual',
            'description' => 'Analisis puntual de la dependencia seleccionada dentro de su proceso.',
            'summary' => 'Selecciona trimestre, proceso y dependencia para calcular el detalle individual.',
        ];
    }
}
