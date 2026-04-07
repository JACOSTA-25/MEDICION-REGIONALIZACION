<?php

namespace App\Http\Controllers\Encuesta;

use App\Http\Controllers\Controller;
use App\Http\Requests\Encuesta\GuardarRespuestaPublicaEncuestaRequest;
use App\Models\Dependencia;
use App\Models\Estamento;
use App\Models\Proceso;
use App\Models\Programa;
use App\Models\Respuesta;
use App\Models\Servicio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EncuestaController extends Controller
{
    private const ESTAMENTOS_REQUIEREN_PROGRAMA = [
        'docente',
        'egresado',
        'estudiante',
    ];

    public function create(Request $request)
    {
        return $this->renderizarVistaEncuesta($request);
    }

    public function access(Request $request)
    {
        return $this->renderizarVistaEncuesta($request, true);
    }

    public function store(GuardarRespuestaPublicaEncuestaRequest $request)
    {
        $validated = $request->validated();
        $estamento = Estamento::query()->findOrFail((int) $validated['id_estamento']);

        Respuesta::query()->create([
            'id_dependencia' => (int) $validated['id_dependencia'],
            'id_estamento' => (int) $validated['id_estamento'],
            'id_programa' => $this->estamentoRequierePrograma($estamento->nombre)
                ? (int) $validated['id_programa']
                : null,
            'id_proceso' => (int) $validated['id_proceso'],
            'id_servicio' => (int) $validated['id_servicio'],
            'observaciones' => $validated['observaciones'] ?? null,
            'pregunta1' => (int) $validated['pregunta1'],
            'pregunta2' => (int) $validated['pregunta2'],
            'pregunta3' => (int) $validated['pregunta3'],
            'pregunta4' => (int) $validated['pregunta4'],
            'pregunta5' => (int) $validated['pregunta5'],
        ]);

        return redirect()
            ->route('survey.create')
            ->with('survey_submitted', true);
    }

    public function dependencias(Request $request): JsonResponse
    {
        $includeInactive = $request->boolean('include_inactive');

        $validator = Validator::make($request->query(), [
            'id_proceso' => [
                'required',
                'integer',
                $includeInactive
                    ? Rule::exists('proceso', 'id_proceso')
                    : Rule::exists('proceso', 'id_proceso')->where(fn ($query) => $query->where('activo', true)),
            ],
            'id_estamento' => ['sometimes', 'integer', 'exists:estamento,id_estamento'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $estamentoId = $this->normalizeId($request->query('id_estamento'));

        $dependencias = Dependencia::query()
            ->where('id_proceso', (int) $validator->validated()['id_proceso'])
            ->when(! $includeInactive, fn ($query) => $query->where('activo', true))
            ->when(
                ! $includeInactive && filled($estamentoId),
                fn ($query) => $query->whereHas('servicios', fn ($servicesQuery) => $servicesQuery
                    ->availableForSurvey()
                    ->allowedForEstamento($estamentoId))
            )
            ->orderBy('nombre')
            ->get(['id_dependencia', 'nombre'])
            ->map(fn (Dependencia $dependencia) => [
                'id' => $dependencia->id_dependencia,
                'nombre' => $dependencia->nombre,
            ])
            ->values();

        return response()->json($dependencias);
    }

    public function servicios(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'id_dependencia' => [
                'required',
                'integer',
                Rule::exists('dependencia', 'id_dependencia')->where(fn ($query) => $query->where('activo', true)),
            ],
            'id_estamento' => ['sometimes', 'integer', 'exists:estamento,id_estamento'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $estamentoId = $this->normalizeId($request->query('id_estamento'));

        $servicios = Servicio::query()
            ->where('id_dependencia', (int) $validator->validated()['id_dependencia'])
            ->availableForSurvey()
            ->allowedForEstamento($estamentoId)
            ->orderBy('nombre')
            ->get(['id_servicio', 'nombre'])
            ->map(fn (Servicio $servicio) => [
                'id' => $servicio->id_servicio,
                'nombre' => $servicio->nombre,
            ])
            ->values();

        return response()->json($servicios);
    }

    private function dependenciasForProceso(mixed $procesoId, mixed $estamentoId = null): Collection
    {
        if (blank($procesoId)) {
            return collect();
        }

        return Dependencia::query()
            ->where('id_proceso', (int) $procesoId)
            ->where('activo', true)
            ->when(
                filled($estamentoId),
                fn ($query) => $query->whereHas('servicios', fn ($servicesQuery) => $servicesQuery
                    ->availableForSurvey()
                    ->allowedForEstamento($estamentoId))
            )
            ->orderBy('nombre')
            ->get(['id_dependencia', 'nombre']);
    }

    private function serviciosForDependencia(mixed $dependenciaId, mixed $estamentoId = null): Collection
    {
        if (blank($dependenciaId)) {
            return collect();
        }

        return Servicio::query()
            ->where('id_dependencia', (int) $dependenciaId)
            ->availableForSurvey()
            ->allowedForEstamento($estamentoId)
            ->orderBy('nombre')
            ->get(['id_servicio', 'nombre']);
    }

    private function estamentoRequierePrograma(string $nombreEstamento): bool
    {
        return in_array(mb_strtolower($nombreEstamento), self::ESTAMENTOS_REQUIEREN_PROGRAMA, true);
    }

    private function renderizarVistaEncuesta(Request $request, bool $accesoDirecto = false)
    {
        $selectedEstamentoId = $this->normalizeId(old('id_estamento', $request->query('id_estamento')));
        $selectedProcesoId = $this->normalizeId(old('id_proceso', $request->query('id_proceso')));
        $selectedDependenciaId = $this->normalizeId(old('id_dependencia', $request->query('id_dependencia')));

        $procesos = Proceso::query()
            ->active()
            ->orderBy('nombre')
            ->get(['id_proceso', 'nombre']);
        $dependencias = $this->dependenciasForProceso($selectedProcesoId, $selectedEstamentoId);

        if (
            filled($selectedDependenciaId) &&
            ! $dependencias->contains(fn (Dependencia $dependencia) => (int) $dependencia->id_dependencia === (int) $selectedDependenciaId)
        ) {
            $selectedDependenciaId = null;
        }

        $selectedProceso = $procesos->firstWhere('id_proceso', (int) $selectedProcesoId);
        $selectedDependencia = filled($selectedDependenciaId)
            ? $dependencias->firstWhere('id_dependencia', (int) $selectedDependenciaId)
            : null;

        $directAccessNotice = null;

        if ($accesoDirecto && $selectedProceso) {
            $directAccessNotice = 'Acceso directo habilitado para '.$selectedProceso->nombre;

            if ($selectedDependencia) {
                $directAccessNotice .= ' / '.$selectedDependencia->nombre;
            }
        }

        return view('encuesta.create', [
            'dependencias' => $dependencias,
            'directAccessNotice' => $directAccessNotice,
            'estamentos' => Estamento::query()->orderBy('nombre')->get(['id_estamento', 'nombre']),
            'procesos' => $procesos,
            'programas' => Programa::query()->orderBy('nombre')->get(['id_programa', 'nombre']),
            'servicios' => $this->serviciosForDependencia($selectedDependenciaId, $selectedEstamentoId),
            'surveySubmitted' => (bool) session('survey_submitted', false),
        ]);
    }

    private function validationErrorResponse(array $errors): JsonResponse
    {
        return response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $errors,
        ], 422);
    }

    private function normalizeId(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }
}
