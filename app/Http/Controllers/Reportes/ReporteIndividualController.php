<?php

namespace App\Http\Controllers\Reportes;

use App\Models\Dependencia;
use App\Models\Servicio;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class ReporteIndividualController extends ControladorReporteAbstracto
{
    public function index(Request $request): View|Response
    {
        return $this->renderReportModule($request);
    }

    public function services(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'id_dependencia' => ['required', 'integer', 'exists:dependencia,id_dependencia'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $dependency = Dependencia::query()
            ->with('proceso:id_proceso,nombre')
            ->findOrFail((int) $validator->validated()['id_dependencia']);
        $user = $request->user();

        if ($user && ! $user->hasGlobalSedeAccess() && $user->id_sede && (int) $user->id_sede !== (int) $dependency->id_sede) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if ($user?->isLiderDependencia() && (int) $user->id_dependencia !== (int) $dependency->id_dependencia) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if (
            ($user?->isLiderProceso() || $user?->isLiderDependencia())
            && $user->id_proceso
            && (int) $user->id_proceso !== (int) $dependency->id_proceso
        ) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if (! $this->isIndividualServiceFilterProcess($dependency->proceso?->nombre)) {
            return response()->json([]);
        }

        $services = Servicio::query()
            ->where('id_dependencia', $dependency->id_dependencia)
            ->orderBy('nombre')
            ->get(['id_servicio', 'nombre', 'activo'])
            ->map(fn (Servicio $service): array => [
                'activo' => (bool) $service->activo,
                'id' => (int) $service->id_servicio,
                'nombre' => (string) $service->nombre,
            ])
            ->values();

        return response()->json($services);
    }

    protected function definition(): array
    {
        return [
            'type' => 'individual',
            'view' => 'reportes.individual.index',
            'title' => 'Reporte individual',
            'description' => 'Analisis puntual de la dependencia seleccionada dentro de su proceso.',
            'summary' => 'Selecciona trimestre, proceso, dependencia y uno o varios servicios para calcular el detalle individual.',
        ];
    }
}
