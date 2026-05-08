<?php

namespace App\Http\Controllers\Organizacion;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Organizacion\Concerns\InteractuaConSolicitudesCatalogo;
use App\Models\Dependencia;
use App\Models\Proceso;
use App\Models\Servicio;
use App\Services\Organizacion\ServicioAuditoriaCatalogo;
use App\Services\Sedes\ServicioSedes;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class ProcesoController extends Controller
{
    use InteractuaConSolicitudesCatalogo;

    public function __construct(
        private readonly ServicioAuditoriaCatalogo $auditService,
        private readonly ServicioSedes $sedeService,
    ) {}

    public function index(Request $request): View
    {
        $selectedSedeId = $this->selectedSedeId($request);
        $processes = Proceso::query()
            ->forSede($selectedSedeId)
            ->withCount([
                'dependencias as dependencias_totales',
                'respuestas as respuestas_totales',
                'users as usuarios_totales',
            ])
            ->with('sede:id_sede,nombre')
            ->orderBy('nombre')
            ->get(['id_proceso', 'id_sede', 'nombre', 'activo']);

        return view('organizacion.procesos.index', [
            'canManageCatalogs' => $request->user()?->puedeModificarModuloEstructuraOrganizacional() ?? false,
            'processes' => $processes,
            'selectedSedeId' => $selectedSedeId,
            'sedes' => $this->sedeService->visibleTo($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->puedeModificarModuloEstructuraOrganizacional(), 403);

        $validator = $this->validator($request);

        if ($validator->fails()) {
            return redirect()
                ->route('process-dependency.index')
                ->withErrors($validator, 'createProcess')
                ->withInput()
                ->with('open_create_process', true);
        }

        $payload = $this->payload($request);

        DB::transaction(function () use ($request, $payload): void {
            $process = Proceso::query()->create($payload);

            $this->auditService->record(
                $request,
                'CREATE',
                'proceso',
                (int) $process->id_proceso,
                null,
                $this->snapshot($process),
                'Proceso creado desde el modulo de administracion.'
            );
        });

        return redirect()
            ->route('process-dependency.index')
            ->with('catalog_status', 'Proceso creado correctamente.');
    }

    public function update(Request $request, Proceso $proceso): RedirectResponse
    {
        abort_unless($request->user()?->puedeModificarModuloEstructuraOrganizacional(), 403);
        abort_unless($this->sedeService->canAccess($request->user(), (int) $proceso->id_sede), 403);

        $validator = $this->validator($request, $proceso);

        if ($validator->fails()) {
            return redirect()
                ->route('process-dependency.index')
                ->withErrors($validator, 'updateProcess')
                ->withInput()
                ->with('open_edit_process', (int) $proceso->id_proceso);
        }

        DB::transaction(function () use ($request, $proceso): void {
            $before = $this->snapshot($proceso->fresh());
            $payload = $this->payload($request, $proceso);

            $proceso->fill($payload);
            $proceso->save();

            if (! $proceso->activo) {
                $this->deactivateDependenciesForProcess((int) $proceso->id_proceso);
            }

            $this->auditService->record(
                $request,
                'UPDATE',
                'proceso',
                (int) $proceso->id_proceso,
                $before,
                $this->snapshot($proceso->fresh()),
                'Proceso actualizado desde el modulo de administracion.'
            );
        });

        return redirect()
            ->route('process-dependency.index')
            ->with('catalog_status', 'Proceso actualizado correctamente.');
    }

    public function deactivate(Request $request, Proceso $proceso): RedirectResponse
    {
        abort_unless($request->user()?->puedeModificarModuloEstructuraOrganizacional(), 403);
        abort_unless($this->sedeService->canAccess($request->user(), (int) $proceso->id_sede), 403);

        if (! $proceso->activo) {
            return redirect()
                ->route('process-dependency.index')
                ->with('catalog_status', 'El proceso ya estaba inactivo.');
        }

        DB::transaction(function () use ($request, $proceso): void {
            $before = $this->snapshot($proceso->fresh());

            $proceso->activo = false;
            $proceso->save();

            $deactivatedDependencies = $this->deactivateDependenciesForProcess((int) $proceso->id_proceso);
            $deactivatedServices = $this->deactivateServicesForProcess((int) $proceso->id_proceso);
            $after = $this->snapshot($proceso->fresh());

            $this->auditService->record(
                $request,
                'DEACTIVATE',
                'proceso',
                (int) $proceso->id_proceso,
                $before,
                $after,
                sprintf(
                    'Proceso inactivado. Dependencias inactivadas: %d. Servicios inactivados: %d.',
                    $deactivatedDependencies,
                    $deactivatedServices
                )
            );
        });

        return redirect()
            ->route('process-dependency.index')
            ->with('catalog_status', 'Proceso inactivado correctamente.');
    }

    public function activate(Request $request, Proceso $proceso): RedirectResponse
    {
        abort_unless($request->user()?->puedeModificarModuloEstructuraOrganizacional(), 403);
        abort_unless($this->sedeService->canAccess($request->user(), (int) $proceso->id_sede), 403);

        if ($proceso->activo) {
            return redirect()
                ->route('process-dependency.index')
                ->with('catalog_status', 'El proceso ya estaba activo.');
        }

        $activeNameConflict = Proceso::query()
            ->where('id_sede', $proceso->id_sede)
            ->where('activo', true)
            ->where('nombre', $proceso->nombre)
            ->where('id_proceso', '!=', $proceso->id_proceso)
            ->exists();

        if ($activeNameConflict) {
            return redirect()
                ->route('process-dependency.index')
                ->with('catalog_error', 'No se puede activar el proceso porque ya existe uno activo con el mismo nombre.');
        }

        DB::transaction(function () use ($request, $proceso): void {
            $before = $this->snapshot($proceso->fresh());

            $proceso->activo = true;
            $proceso->save();

            $this->auditService->record(
                $request,
                'ACTIVATE',
                'proceso',
                (int) $proceso->id_proceso,
                $before,
                $this->snapshot($proceso->fresh()),
                'Proceso activado desde el modulo de administracion.'
            );
        });

        return redirect()
            ->route('process-dependency.index')
            ->with('catalog_status', 'Proceso activado correctamente.');
    }

    private function validator(Request $request, ?Proceso $process = null): ValidationValidator
    {
        $targetActive = $this->targetActiveValue($request, $process?->activo ?? true);
        $targetSedeId = $this->resolvedSedeId($request, $process);
        $payload = ['nombre' => trim((string) $request->input('nombre'))] + $request->all();

        $validator = Validator::make($payload, [
            'id_sede' => ['nullable', 'integer', Rule::exists('sede', 'id_sede')->where(fn ($query) => $query->where('activo', true))],
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique('proceso', 'nombre')
                    ->where(fn ($query) => $query
                        ->where('id_sede', $targetSedeId)
                        ->where('activo', $targetActive))
                    ->ignore($process?->id_proceso, 'id_proceso'),
            ],
            'activo' => ['nullable', 'boolean'],
        ]);

        $validator->after(function (ValidationValidator $validator) use ($request, $targetSedeId): void {
            if ($targetSedeId === null) {
                $validator->errors()->add('id_sede', 'Debes seleccionar una sede valida para el proceso.');
                return;
            }

            if (! $this->sedeService->canAccess($request->user(), $targetSedeId)) {
                $validator->errors()->add('id_sede', 'No puedes administrar procesos fuera de tu alcance.');
            }
        });

        return $validator;
    }

    /**
     * @return array{id_sede: int, nombre: string, activo: bool}
     */
    private function payload(Request $request, ?Proceso $process = null): array
    {
        return [
            'id_sede' => $this->resolvedSedeId($request, $process),
            'nombre' => trim((string) $request->input('nombre')),
            'activo' => $this->targetActiveValue($request, $process?->activo ?? true),
        ];
    }

    /**
     * @return array{id_proceso: int, nombre: string, activo: bool}
     */
    private function snapshot(Proceso $process): array
    {
        return [
            'id_proceso' => (int) $process->id_proceso,
            'id_sede' => (int) $process->id_sede,
            'nombre' => (string) $process->nombre,
            'activo' => (bool) $process->activo,
        ];
    }

    private function deactivateDependenciesForProcess(int $processId): int
    {
        return Dependencia::query()
            ->where('id_proceso', $processId)
            ->where('activo', true)
            ->update([
                'activo' => false,
                'updated_at' => now(),
            ]);
    }

    private function deactivateServicesForProcess(int $processId): int
    {
        return Servicio::query()
            ->whereIn('id_dependencia', function ($query) use ($processId) {
                $query->from('dependencia')
                    ->select('id_dependencia')
                    ->where('id_proceso', $processId);
            })
            ->where('activo', true)
            ->update([
                'activo' => false,
                'updated_at' => now(),
            ]);
    }

    private function resolvedSedeId(Request $request, ?Proceso $process = null): ?int
    {
        if ($request->user()?->isAdminSede()) {
            return $request->user()?->id_sede ? (int) $request->user()->id_sede : null;
        }

        $input = $request->input('id_sede');

        if ($input !== null) {
            return $this->sedeService->normalizeId($input);
        }

        return $process?->id_sede ? (int) $process->id_sede : null;
    }

    private function selectedSedeId(Request $request): ?int
    {
        return $this->sedeService->resolveForRequest(
            $request->user(),
            $request,
            'id_sede',
            true,
            true
        );
    }
}
