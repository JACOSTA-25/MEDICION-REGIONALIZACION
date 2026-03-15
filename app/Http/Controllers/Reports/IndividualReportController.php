<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IndividualReportController extends AbstractReportController
{
    public function index(Request $request): View|Response
    {
        return $this->renderReportModule($request);
    }

    protected function definition(): array
    {
        return [
            'type' => 'individual',
            'view' => 'reports.individual.index',
            'title' => 'Reporte individual',
            'description' => 'Analisis puntual de la dependencia seleccionada dentro de su proceso.',
            'summary' => 'Selecciona trimestre, proceso y dependencia para calcular el detalle individual.',
        ];
    }
}
