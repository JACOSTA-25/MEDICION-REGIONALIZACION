<?php

namespace App\Http\Controllers\Encuesta;

use App\Http\Controllers\Controller;
use App\Http\Requests\Encuesta\GuardarRespuestaPublicaEncuestaRequest;
use App\Models\Dependencia;
use App\Models\Estamento;
use App\Models\Proceso;
use App\Models\Programa;
use App\Models\Respuesta;
use App\Models\Sede;
use App\Models\Servicio;
use App\Services\Sedes\ServicioSedes;
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

    public function __construct(
        private readonly ServicioSedes $sedeService,
    ) {}

    public function create(Request $request, ?Sede $sede = null)
    {
        return $this->renderizarVistaEncuesta($request, false, $sede);
    }

    public function access(Request $request)
    {
        return $this->renderizarVistaEncuesta($request, true, $this->resolveSede($request));
    }

    public function store(GuardarRespuestaPublicaEncuestaRequest $request)
    {
        $validated = $request->validated();
        $estamento = Estamento::query()->findOrFail((int) $validated['id_estamento']);
        $sede = Sede::query()->findOrFail((int) $validated['id_sede']);

        Respuesta::query()->create([
            'id_sede' => (int) $validated['id_sede'],
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
            ->route('survey.create', ['sede' => $sede->slug])
            ->with('survey_submitted', true);
    }

    public function dependencias(Request $request): JsonResponse
    {
        $includeInactive = $request->boolean('include_inactive');
        $resolvedSedeId = $this->normalizeId($request->query('id_sede')) ?? Sede::ID_MAICAO;

        $validator = Validator::make($request->query(), [
            'id_sede' => ['nullable', 'integer', 'exists:sede,id_sede'],
            'id_proceso' => [
                'required',
                'integer',
                $includeInactive
                    ? Rule::exists('proceso', 'id_proceso')->where(fn ($query) => $query->where('id_sede', $resolvedSedeId))
                    : Rule::exists('proceso', 'id_proceso')->where(fn ($query) => $query
                        ->where('activo', true)
                        ->where('id_sede', $resolvedSedeId)),
            ],
            'id_estamento' => ['sometimes', 'integer', 'exists:estamento,id_estamento'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $estamentoId = $this->normalizeId($request->query('id_estamento'));

        $dependencias = Dependencia::query()
            ->where('id_sede', $resolvedSedeId)
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
        $resolvedSedeId = $this->normalizeId($request->query('id_sede')) ?? Sede::ID_MAICAO;

        $validator = Validator::make($request->query(), [
            'id_sede' => ['nullable', 'integer', 'exists:sede,id_sede'],
            'id_dependencia' => [
                'required',
                'integer',
                Rule::exists('dependencia', 'id_dependencia')->where(fn ($query) => $query
                    ->where('activo', true)
                    ->where('id_sede', $resolvedSedeId)),
            ],
            'id_estamento' => ['sometimes', 'integer', 'exists:estamento,id_estamento'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $estamentoId = $this->normalizeId($request->query('id_estamento'));

        $servicios = Servicio::query()
            ->where('id_sede', $resolvedSedeId)
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

    private function dependenciasForProceso(?int $sedeId, mixed $procesoId, mixed $estamentoId = null): Collection
    {
        if (blank($procesoId) || $sedeId === null) {
            return collect();
        }

        return Dependencia::query()
            ->where('id_sede', $sedeId)
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

    private function serviciosForDependencia(?int $sedeId, mixed $dependenciaId, mixed $estamentoId = null): Collection
    {
        if (blank($dependenciaId) || $sedeId === null) {
            return collect();
        }

        return Servicio::query()
            ->where('id_sede', $sedeId)
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

    private function renderizarVistaEncuesta(Request $request, bool $accesoDirecto = false, ?Sede $sede = null)
    {
        $selectedSede = $sede;

        if (! $selectedSede) {
            return view('encuesta.select-sede', [
                'sedes' => $this->sedeService->active(),
            ]);
        }

        $selectedEstamentoId = $this->normalizeId(old('id_estamento', $request->query('id_estamento')));
        $selectedProcesoId = $this->normalizeId(old('id_proceso', $request->query('id_proceso')));
        $selectedDependenciaId = $this->normalizeId(old('id_dependencia', $request->query('id_dependencia')));

        $procesos = Proceso::query()
            ->forSede((int) $selectedSede->id_sede)
            ->active()
            ->orderBy('nombre')
            ->get(['id_proceso', 'nombre']);
        $dependencias = $this->dependenciasForProceso((int) $selectedSede->id_sede, $selectedProcesoId, $selectedEstamentoId);

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
            $directAccessNotice = 'Acceso directo habilitado para '.$selectedSede->nombre.' / '.$selectedProceso->nombre;

            if ($selectedDependencia) {
                $directAccessNotice .= ' / '.$selectedDependencia->nombre;
            }
        }

        return view('encuesta.create', [
            'dependencias' => $dependencias,
            'directAccessNotice' => $directAccessNotice,
            'estamentos' => Estamento::query()->orderBy('nombre')->get(['id_estamento', 'nombre']),
            'selectedSede' => $selectedSede,
            'procesos' => $procesos,
            'programas' => Programa::query()
                ->forSede((int) $selectedSede->id_sede)
                ->orderBy('nombre')
                ->get(['id_programa', 'nombre']),
            'servicios' => $this->serviciosForDependencia((int) $selectedSede->id_sede, $selectedDependenciaId, $selectedEstamentoId),
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

    private function resolveSede(Request $request): ?Sede
    {
        $requested = $request->query('sede');

        if (is_string($requested) && $requested !== '') {
            return Sede::query()
                ->active()
                ->where('slug', $requested)
                ->first();
        }

        $id = $this->normalizeId($request->query('id_sede'));

        if ($id === null) {
            return null;
        }

        return Sede::query()
            ->active()
            ->find($id);
    }
}
