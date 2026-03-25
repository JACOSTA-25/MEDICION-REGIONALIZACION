<?php

namespace App\Http\Controllers\Organizacion;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Organizacion\Concerns\InteractuaConSolicitudesCatalogo;
use App\Models\Dependencia;
use App\Models\Proceso;
use App\Models\Servicio;
use App\Services\Organizacion\ServicioAuditoriaCatalogo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class DependenciaController extends Controller
{
    use InteractuaConSolicitudesCatalogo;

    public function __construct(
        private readonly ServicioAuditoriaCatalogo $auditService,
    ) {}

    public function index(Proceso $proceso): View
    {
        $dependencies = Dependencia::query()
            ->where('id_proceso', $proceso->id_proceso)
            ->withCount([
                'servicios as servicios_totales',
                'respuestas as respuestas_totales',
                'users as usuarios_totales',
            ])
            ->orderBy('nombre')
            ->get(['id_dependencia', 'id_proceso', 'nombre', 'activo']);

        $processes = Proceso::query()
            ->orderBy('nombre')
            ->get(['id_proceso', 'nombre', 'activo']);

        return view('organizacion.dependencias.index', [
            'activeProcesses' => $processes->where('activo', true)->values(),
            'dependencies' => $dependencies,
            'processes' => $processes,
            'selectedProcess' => $proceso,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = $this->validator($request);

        if ($validator->fails()) {
            return $this->dependencyRedirect(
                $request,
                $this->normalizeId($request->input('id_proceso'))
            )
                ->withErrors($validator, 'createDependency')
                ->withInput()
                ->with('open_create_dependency', true);
        }

        $payload = $this->payload($request);
        $createdDependencyProcessId = null;

        DB::transaction(function () use ($request, $payload, &$createdDependencyProcessId): void {
            $dependency = Dependencia::query()->create($payload);
            $createdDependencyProcessId = (int) $dependency->id_proceso;

            $this->auditService->record(
                $request,
                'CREATE',
                'dependencia',
                (int) $dependency->id_dependencia,
                null,
                $this->snapshot($dependency),
                'Dependencia creada desde el modulo de administracion.'
            );
        });

        return $this->dependencyRedirect($request, $createdDependencyProcessId)
            ->with('catalog_status', 'Dependencia creada correctamente.');
    }

    public function update(Request $request, Dependencia $dependencia): RedirectResponse
    {
        $validator = $this->validator($request, $dependencia);

        if ($validator->fails()) {
            return $this->dependencyRedirect($request, (int) $dependencia->id_proceso)
                ->withErrors($validator, 'updateDependency')
                ->withInput()
                ->with('open_edit_dependency', (int) $dependencia->id_dependencia);
        }

        $updatedDependencyProcessId = (int) $dependencia->id_proceso;

        DB::transaction(function () use ($request, $dependencia): void {
            $before = $this->snapshot($dependencia->fresh());
            $payload = $this->payload($request, $dependencia);

            $dependencia->fill($payload);
            $dependencia->save();

            $this->auditService->record(
                $request,
                'UPDATE',
                'dependencia',
                (int) $dependencia->id_dependencia,
                $before,
                $this->snapshot($dependencia->fresh()),
                'Dependencia actualizada desde el modulo de administracion.'
            );
        });

        $updatedDependencyProcessId = (int) $dependencia->fresh()->id_proceso;

        return $this->dependencyRedirect($request, $updatedDependencyProcessId)
            ->with('catalog_status', 'Dependencia actualizada correctamente.');
    }

    public function deactivate(Request $request, Dependencia $dependencia): RedirectResponse
    {
        if (! $dependencia->activo) {
            return $this->dependencyRedirect($request, (int) $dependencia->id_proceso)
                ->with('catalog_status', 'La dependencia ya estaba inactiva.');
        }

        DB::transaction(function () use ($request, $dependencia): void {
            $before = $this->snapshot($dependencia->fresh());

            $dependencia->activo = false;
            $dependencia->save();
            $deactivatedServices = $this->deactivateServicesForDependency((int) $dependencia->id_dependencia);

            $this->auditService->record(
                $request,
                'DEACTIVATE',
                'dependencia',
                (int) $dependencia->id_dependencia,
                $before,
                $this->snapshot($dependencia->fresh()),
                sprintf('Dependencia inactivada. Servicios inactivados por cascada logica: %d.', $deactivatedServices)
            );
        });

        return $this->dependencyRedirect($request, (int) $dependencia->id_proceso)
            ->with('catalog_status', 'Dependencia inactivada correctamente.');
    }

    public function activate(Request $request, Dependencia $dependencia): RedirectResponse
    {
        if ($dependencia->activo) {
            return $this->dependencyRedirect($request, (int) $dependencia->id_proceso)
                ->with('catalog_status', 'La dependencia ya estaba activa.');
        }

        $process = Proceso::query()->find($dependencia->id_proceso);

        if (! $process?->activo) {
            return $this->dependencyRedirect($request, (int) $dependencia->id_proceso)
                ->with('catalog_error', 'No se puede activar la dependencia porque su proceso esta inactivo.');
        }

        $activeNameConflict = Dependencia::query()
            ->where('id_proceso', $dependencia->id_proceso)
            ->where('activo', true)
            ->where('nombre', $dependencia->nombre)
            ->where('id_dependencia', '!=', $dependencia->id_dependencia)
            ->exists();

        if ($activeNameConflict) {
            return $this->dependencyRedirect($request, (int) $dependencia->id_proceso)
                ->with('catalog_error', 'No se puede activar la dependencia porque ya existe una activa con el mismo nombre en este proceso.');
        }

        DB::transaction(function () use ($request, $dependencia): void {
            $before = $this->snapshot($dependencia->fresh());

            $dependencia->activo = true;
            $dependencia->save();

            $this->auditService->record(
                $request,
                'ACTIVATE',
                'dependencia',
                (int) $dependencia->id_dependencia,
                $before,
                $this->snapshot($dependencia->fresh()),
                'Dependencia activada desde el modulo de administracion.'
            );
        });

        return $this->dependencyRedirect($request, (int) $dependencia->id_proceso)
            ->with('catalog_status', 'Dependencia activada correctamente.');
    }

    private function validator(Request $request, ?Dependencia $dependency = null): ValidationValidator
    {
        $targetActive = $this->targetActiveValue($request, $dependency?->activo ?? true);
        $targetProcessId = (int) $request->input('id_proceso');
        $payload = ['nombre' => trim((string) $request->input('nombre'))] + $request->all();

        $validator = Validator::make($payload, [
            'id_proceso' => ['required', 'integer', 'exists:proceso,id_proceso'],
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique('dependencia', 'nombre')
                    ->where(fn ($query) => $query
                        ->where('id_proceso', $targetProcessId)
                        ->where('activo', $targetActive))
                    ->ignore($dependency?->id_dependencia, 'id_dependencia'),
            ],
            'activo' => ['nullable', 'boolean'],
        ]);

        $validator->after(function (ValidationValidator $validator) use ($dependency, $targetProcessId): void {
            $process = Proceso::query()->find($targetProcessId);

            if (! $process) {
                return;
            }

            if (! $process->activo && (! $dependency || (int) $dependency->id_proceso !== $targetProcessId)) {
                $validator->errors()->add('id_proceso', 'Solo puedes asociar dependencias a procesos activos.');
            }
        });

        return $validator;
    }

    /**
     * @return array{id_proceso: int, nombre: string, activo: bool}
     */
    private function payload(Request $request, ?Dependencia $dependency = null): array
    {
        return [
            'id_proceso' => (int) $request->input('id_proceso'),
            'nombre' => trim((string) $request->input('nombre')),
            'activo' => $this->targetActiveValue($request, $dependency?->activo ?? true),
        ];
    }

    /**
     * @return array{id_dependencia: int, id_proceso: int, nombre: string, activo: bool}
     */
    private function snapshot(Dependencia $dependency): array
    {
        return [
            'id_dependencia' => (int) $dependency->id_dependencia,
            'id_proceso' => (int) $dependency->id_proceso,
            'nombre' => (string) $dependency->nombre,
            'activo' => (bool) $dependency->activo,
        ];
    }

    private function deactivateServicesForDependency(int $dependencyId): int
    {
        return Servicio::query()
            ->where('id_dependencia', $dependencyId)
            ->where('activo', true)
            ->update([
                'activo' => false,
                'updated_at' => now(),
            ]);
    }
}
