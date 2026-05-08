<?php

namespace App\Http\Controllers\Estadisticas;

use App\Http\Controllers\Controller;
use App\Services\Estadisticas\ServicioConsultaEstadisticas;
use App\Services\Estadisticas\ServicioAlcanceEstadisticas;
use App\Services\Sedes\ServicioSedes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DatosEstadisticasController extends Controller
{
    public function __construct(
        private readonly ServicioConsultaEstadisticas $queryService,
        private readonly ServicioAlcanceEstadisticas $scopeService,
        private readonly ServicioSedes $sedeService,
    ) {}

    public function show(Request $request, string $level): JsonResponse
    {
        abort_unless($this->scopeService->puedeAccederNivel($request->user(), $level), 403);

        $selectedSedeId = $this->sedeService->resolveForRequest(
            $request->user(),
            $request,
            'id_sede',
            true,
            true
        );

        return response()->json(
            $this->queryService->buildPayload($request->user(), $level, $request->query(), $selectedSedeId)
        );
    }
}
