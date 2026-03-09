<?php

namespace App\Http\Controllers;

use App\Models\Dependencia;
use App\Models\Proceso;
use App\Models\Servicio;
use App\Services\CatalogAuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class ProcessDependencyManagementController extends Controller
{
    public function __construct(
        private readonly CatalogAuditService $auditService,
    ) {}

    public function index(): View
    {
        $processes = Proceso::query()
            ->withCount([
                'dependencias as dependencias_totales',
                'respuestas as respuestas_totales',
                'users as usuarios_totales',
            ])
            ->orderBy('nombre')
            ->get(['id_proceso', 'nombre', 'activo']);

        return view('modules.procesos-dependencias-index', [
            'processes' => $processes,
        ]);
    }

    public function dependencies(Proceso $proceso): View
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

        return view('modules.procesos-dependencias-dependencias', [
            'activeProcesses' => $processes->where('activo', true)->values(),
            'dependencies' => $dependencies,
            'selectedProcess' => $proceso,
            'processes' => $processes,
        ]);
    }

    public function services(Dependencia $dependencia): View
    {
        $dependencia->load([
            'proceso:id_proceso,nombre,activo',
        ]);

        $services = Servicio::query()
            ->where('id_dependencia', $dependencia->id_dependencia)
            ->withCount([
                'respuestas as respuestas_totales',
            ])
            ->orderBy('nombre')
            ->get(['id_servicio', 'id_dependencia', 'nombre', 'activo']);

        $dependencies = Dependencia::query()
            ->where('id_proceso', $dependencia->id_proceso)
            ->orderBy('nombre')
            ->get(['id_dependencia', 'id_proceso', 'nombre', 'activo']);

        return view('modules.procesos-dependencias-servicios', [
            'activeDependencies' => $dependencies->where('activo', true)->values(),
            'dependencies' => $dependencies,
            'selectedDependency' => $dependencia,
            'selectedProcess' => $dependencia->proceso,
            'services' => $services,
        ]);
    }

    public function storeProcess(Request $request): RedirectResponse
    {
        $validator = $this->processValidator($request);

        if ($validator->fails()) {
            return redirect()
                ->route('process-dependency.index')
                ->withErrors($validator, 'createProcess')
                ->withInput()
                ->with('open_create_process', true);
        }

        $payload = $this->processPayload($request);

        DB::transaction(function () use ($request, $payload): void {
            $process = Proceso::query()->create($payload);

            $this->auditService->record(
                $request,
                'CREATE',
                'proceso',
                (int) $process->id_proceso,
                null,
                $this->processSnapshot($process),
                'Proceso creado desde el modulo de administracion.'
            );
        });

        return redirect()
            ->route('process-dependency.index')
            ->with('catalog_status', 'Proceso creado correctamente.');
    }

    public function updateProcess(Request $request, Proceso $proceso): RedirectResponse
    {
        $validator = $this->processValidator($request, $proceso);

        if ($validator->fails()) {
            return redirect()
                ->route('process-dependency.index')
                ->withErrors($validator, 'updateProcess')
                ->withInput()
                ->with('open_edit_process', (int) $proceso->id_proceso);
        }

        DB::transaction(function () use ($request, $proceso): void {
            $before = $this->processSnapshot($proceso->fresh());
            $payload = $this->processPayload($request, $proceso);

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
                $this->processSnapshot($proceso->fresh()),
                'Proceso actualizado desde el modulo de administracion.'
            );
        });

        return redirect()
            ->route('process-dependency.index')
            ->with('catalog_status', 'Proceso actualizado correctamente.');
    }

    public function deactivateProcess(Request $request, Proceso $proceso): RedirectResponse
    {
        if (! $proceso->activo) {
            return redirect()
                ->route('process-dependency.index')
                ->with('catalog_status', 'El proceso ya estaba inactivo.');
        }

        DB::transaction(function () use ($request, $proceso): void {
            $before = $this->processSnapshot($proceso->fresh());

            $proceso->activo = false;
            $proceso->save();

            $deactivatedDependencies = $this->deactivateDependenciesForProcess((int) $proceso->id_proceso);
            $deactivatedServices = $this->deactivateServicesForProcess((int) $proceso->id_proceso);
            $after = $this->processSnapshot($proceso->fresh());

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

    public function activateProcess(Request $request, Proceso $proceso): RedirectResponse
    {
        if ($proceso->activo) {
            return redirect()
                ->route('process-dependency.index')
                ->with('catalog_status', 'El proceso ya estaba activo.');
        }

        $activeNameConflict = Proceso::query()
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
            $before = $this->processSnapshot($proceso->fresh());

            $proceso->activo = true;
            $proceso->save();

            $this->auditService->record(
                $request,
                'ACTIVATE',
                'proceso',
                (int) $proceso->id_proceso,
                $before,
                $this->processSnapshot($proceso->fresh()),
                'Proceso activado desde el modulo de administracion.'
            );
        });

        return redirect()
            ->route('process-dependency.index')
            ->with('catalog_status', 'Proceso activado correctamente.');
    }

    public function storeDependency(Request $request): RedirectResponse
    {
        $validator = $this->dependencyValidator($request);

        if ($validator->fails()) {
            return $this->dependencyRedirect(
                $request,
                $this->normalizeId($request->input('id_proceso'))
            )
                ->withErrors($validator, 'createDependency')
                ->withInput()
                ->with('open_create_dependency', true);
        }

        $payload = $this->dependencyPayload($request);
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
                $this->dependencySnapshot($dependency),
                'Dependencia creada desde el modulo de administracion.'
            );
        });

        return $this->dependencyRedirect($request, $createdDependencyProcessId)
            ->with('catalog_status', 'Dependencia creada correctamente.');
    }

    public function updateDependency(Request $request, Dependencia $dependencia): RedirectResponse
    {
        $validator = $this->dependencyValidator($request, $dependencia);

        if ($validator->fails()) {
            return $this->dependencyRedirect($request, (int) $dependencia->id_proceso)
                ->withErrors($validator, 'updateDependency')
                ->withInput()
                ->with('open_edit_dependency', (int) $dependencia->id_dependencia);
        }

        $updatedDependencyProcessId = (int) $dependencia->id_proceso;

        DB::transaction(function () use ($request, $dependencia): void {
            $before = $this->dependencySnapshot($dependencia->fresh());
            $payload = $this->dependencyPayload($request, $dependencia);

            $dependencia->fill($payload);
            $dependencia->save();

            $this->auditService->record(
                $request,
                'UPDATE',
                'dependencia',
                (int) $dependencia->id_dependencia,
                $before,
                $this->dependencySnapshot($dependencia->fresh()),
                'Dependencia actualizada desde el modulo de administracion.'
            );
        });

        $updatedDependencyProcessId = (int) $dependencia->fresh()->id_proceso;

        return $this->dependencyRedirect($request, $updatedDependencyProcessId)
            ->with('catalog_status', 'Dependencia actualizada correctamente.');
    }

    public function deactivateDependency(Request $request, Dependencia $dependencia): RedirectResponse
    {
        if (! $dependencia->activo) {
            return $this->dependencyRedirect($request, (int) $dependencia->id_proceso)
                ->with('catalog_status', 'La dependencia ya estaba inactiva.');
        }

        DB::transaction(function () use ($request, $dependencia): void {
            $before = $this->dependencySnapshot($dependencia->fresh());

            $dependencia->activo = false;
            $dependencia->save();
            $deactivatedServices = $this->deactivateServicesForDependency((int) $dependencia->id_dependencia);

            $this->auditService->record(
                $request,
                'DEACTIVATE',
                'dependencia',
                (int) $dependencia->id_dependencia,
                $before,
                $this->dependencySnapshot($dependencia->fresh()),
                sprintf('Dependencia inactivada. Servicios inactivados por cascada logica: %d.', $deactivatedServices)
            );
        });

        return $this->dependencyRedirect($request, (int) $dependencia->id_proceso)
            ->with('catalog_status', 'Dependencia inactivada correctamente.');
    }

    public function activateDependency(Request $request, Dependencia $dependencia): RedirectResponse
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
            $before = $this->dependencySnapshot($dependencia->fresh());

            $dependencia->activo = true;
            $dependencia->save();

            $this->auditService->record(
                $request,
                'ACTIVATE',
                'dependencia',
                (int) $dependencia->id_dependencia,
                $before,
                $this->dependencySnapshot($dependencia->fresh()),
                'Dependencia activada desde el modulo de administracion.'
            );
        });

        return $this->dependencyRedirect($request, (int) $dependencia->id_proceso)
            ->with('catalog_status', 'Dependencia activada correctamente.');
    }

    public function storeService(Request $request): RedirectResponse
    {
        $validator = $this->serviceValidator($request);

        if ($validator->fails()) {
            return $this->serviceRedirect(
                $request,
                $this->normalizeId($request->input('id_dependencia')),
                $this->normalizeId($request->input('redirect_proceso'))
            )
                ->withErrors($validator, 'createService')
                ->withInput()
                ->with('open_create_service', true);
        }

        $payload = $this->servicePayload($request);
        $createdServiceDependencyId = null;

        DB::transaction(function () use ($request, $payload, &$createdServiceDependencyId): void {
            $service = Servicio::query()->create($payload);
            $createdServiceDependencyId = (int) $service->id_dependencia;

            $this->auditService->record(
                $request,
                'CREATE',
                'servicio',
                (int) $service->id_servicio,
                null,
                $this->serviceSnapshot($service),
                'Servicio creado desde el modulo de administracion.'
            );
        });

        return $this->serviceRedirect($request, $createdServiceDependencyId)
            ->with('catalog_status', 'Servicio creado correctamente.');
    }

    public function updateService(Request $request, Servicio $servicio): RedirectResponse
    {
        $validator = $this->serviceValidator($request, $servicio);

        if ($validator->fails()) {
            return $this->serviceRedirect($request, (int) $servicio->id_dependencia)
                ->withErrors($validator, 'updateService')
                ->withInput()
                ->with('open_edit_service', (int) $servicio->id_servicio);
        }

        DB::transaction(function () use ($request, $servicio): void {
            $before = $this->serviceSnapshot($servicio->fresh());
            $payload = $this->servicePayload($request, $servicio);

            $servicio->fill($payload);
            $servicio->save();

            $this->auditService->record(
                $request,
                'UPDATE',
                'servicio',
                (int) $servicio->id_servicio,
                $before,
                $this->serviceSnapshot($servicio->fresh()),
                'Servicio actualizado desde el modulo de administracion.'
            );
        });

        return $this->serviceRedirect($request, (int) $servicio->fresh()->id_dependencia)
            ->with('catalog_status', 'Servicio actualizado correctamente.');
    }

    public function deactivateService(Request $request, Servicio $servicio): RedirectResponse
    {
        if (! $servicio->activo) {
            return $this->serviceRedirect($request, (int) $servicio->id_dependencia)
                ->with('catalog_status', 'El servicio ya estaba inactivo.');
        }

        DB::transaction(function () use ($request, $servicio): void {
            $before = $this->serviceSnapshot($servicio->fresh());

            $servicio->activo = false;
            $servicio->save();

            $this->auditService->record(
                $request,
                'DEACTIVATE',
                'servicio',
                (int) $servicio->id_servicio,
                $before,
                $this->serviceSnapshot($servicio->fresh()),
                'Servicio inactivado desde el modulo de administracion.'
            );
        });

        return $this->serviceRedirect($request, (int) $servicio->id_dependencia)
            ->with('catalog_status', 'Servicio inactivado correctamente.');
    }

    public function activateService(Request $request, Servicio $servicio): RedirectResponse
    {
        if ($servicio->activo) {
            return $this->serviceRedirect($request, (int) $servicio->id_dependencia)
                ->with('catalog_status', 'El servicio ya estaba activo.');
        }

        $dependency = Dependencia::query()->with('proceso')->find($servicio->id_dependencia);

        if (! $dependency?->activo || ! $dependency->proceso?->activo) {
            return $this->serviceRedirect($request, (int) $servicio->id_dependencia)
                ->with('catalog_error', 'No se puede activar el servicio porque su dependencia o proceso esta inactivo.');
        }

        $activeNameConflict = Servicio::query()
            ->where('id_dependencia', $servicio->id_dependencia)
            ->where('activo', true)
            ->where('nombre', $servicio->nombre)
            ->where('id_servicio', '!=', $servicio->id_servicio)
            ->exists();

        if ($activeNameConflict) {
            return $this->serviceRedirect($request, (int) $servicio->id_dependencia)
                ->with('catalog_error', 'No se puede activar el servicio porque ya existe uno activo con el mismo nombre en esta dependencia.');
        }

        DB::transaction(function () use ($request, $servicio): void {
            $before = $this->serviceSnapshot($servicio->fresh());

            $servicio->activo = true;
            $servicio->save();

            $this->auditService->record(
                $request,
                'ACTIVATE',
                'servicio',
                (int) $servicio->id_servicio,
                $before,
                $this->serviceSnapshot($servicio->fresh()),
                'Servicio activado desde el modulo de administracion.'
            );
        });

        return $this->serviceRedirect($request, (int) $servicio->id_dependencia)
            ->with('catalog_status', 'Servicio activado correctamente.');
    }

    private function processValidator(Request $request, ?Proceso $process = null): ValidationValidator
    {
        $targetActive = $this->targetActiveValue($request, $process?->activo ?? true);
        $payload = ['nombre' => trim((string) $request->input('nombre'))] + $request->all();

        return Validator::make($payload, [
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique('proceso', 'nombre')
                    ->where(fn ($query) => $query->where('activo', $targetActive))
                    ->ignore($process?->id_proceso, 'id_proceso'),
            ],
            'activo' => ['nullable', 'boolean'],
        ]);
    }

    private function dependencyValidator(Request $request, ?Dependencia $dependency = null): ValidationValidator
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

    private function serviceValidator(Request $request, ?Servicio $service = null): ValidationValidator
    {
        $targetActive = $this->targetActiveValue($request, $service?->activo ?? true);
        $targetDependencyId = (int) $request->input('id_dependencia');
        $payload = ['nombre' => trim((string) $request->input('nombre'))] + $request->all();

        $validator = Validator::make($payload, [
            'id_dependencia' => ['required', 'integer', 'exists:dependencia,id_dependencia'],
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique('servicio', 'nombre')
                    ->where(fn ($query) => $query
                        ->where('id_dependencia', $targetDependencyId)
                        ->where('activo', $targetActive))
                    ->ignore($service?->id_servicio, 'id_servicio'),
            ],
            'activo' => ['nullable', 'boolean'],
        ]);

        $validator->after(function (ValidationValidator $validator) use ($service, $targetDependencyId, $targetActive): void {
            $dependency = Dependencia::query()
                ->with('proceso:id_proceso,activo')
                ->find($targetDependencyId);

            if (! $dependency) {
                return;
            }

            $isChangingDependency = ! $service || (int) $service->id_dependencia !== $targetDependencyId;

            if (! $dependency->activo && $isChangingDependency) {
                $validator->errors()->add('id_dependencia', 'Solo puedes asociar servicios a dependencias activas.');
            }

            if (! $dependency->proceso?->activo && $isChangingDependency) {
                $validator->errors()->add('id_dependencia', 'Solo puedes asociar servicios a dependencias con proceso activo.');
            }

            if ($targetActive && (! $dependency->activo || ! $dependency->proceso?->activo)) {
                $validator->errors()->add('activo', 'No se puede activar un servicio en una dependencia o proceso inactivo.');
            }
        });

        return $validator;
    }

    /**
     * @return array{nombre: string, activo: bool}
     */
    private function processPayload(Request $request, ?Proceso $process = null): array
    {
        return [
            'nombre' => trim((string) $request->input('nombre')),
            'activo' => $this->targetActiveValue($request, $process?->activo ?? true),
        ];
    }

    /**
     * @return array{id_proceso: int, nombre: string, activo: bool}
     */
    private function dependencyPayload(Request $request, ?Dependencia $dependency = null): array
    {
        return [
            'id_proceso' => (int) $request->input('id_proceso'),
            'nombre' => trim((string) $request->input('nombre')),
            'activo' => $this->targetActiveValue($request, $dependency?->activo ?? true),
        ];
    }

    /**
     * @return array{id_dependencia: int, nombre: string, activo: bool}
     */
    private function servicePayload(Request $request, ?Servicio $service = null): array
    {
        return [
            'id_dependencia' => (int) $request->input('id_dependencia'),
            'nombre' => trim((string) $request->input('nombre')),
            'activo' => $this->targetActiveValue($request, $service?->activo ?? true),
        ];
    }

    /**
     * @return array{id_proceso: int, nombre: string, activo: bool}
     */
    private function processSnapshot(Proceso $process): array
    {
        return [
            'id_proceso' => (int) $process->id_proceso,
            'nombre' => (string) $process->nombre,
            'activo' => (bool) $process->activo,
        ];
    }

    /**
     * @return array{id_dependencia: int, id_proceso: int, nombre: string, activo: bool}
     */
    private function dependencySnapshot(Dependencia $dependency): array
    {
        return [
            'id_dependencia' => (int) $dependency->id_dependencia,
            'id_proceso' => (int) $dependency->id_proceso,
            'nombre' => (string) $dependency->nombre,
            'activo' => (bool) $dependency->activo,
        ];
    }

    /**
     * @return array{id_servicio: int, id_dependencia: int, nombre: string, activo: bool}
     */
    private function serviceSnapshot(Servicio $service): array
    {
        return [
            'id_servicio' => (int) $service->id_servicio,
            'id_dependencia' => (int) $service->id_dependencia,
            'nombre' => (string) $service->nombre,
            'activo' => (bool) $service->activo,
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

    private function targetActiveValue(Request $request, bool $currentValue): bool
    {
        return $request->has('activo')
            ? $request->boolean('activo')
            : $currentValue;
    }

    private function dependencyRedirect(Request $request, ?int $fallbackProcessId = null): RedirectResponse
    {
        $candidateProcessIds = [
            $this->normalizeId($request->input('id_proceso')),
            $this->normalizeId($request->input('redirect_proceso')),
            $fallbackProcessId,
        ];

        $processId = null;

        foreach ($candidateProcessIds as $candidateProcessId) {
            if ($candidateProcessId === null) {
                continue;
            }

            if (Proceso::query()->where('id_proceso', $candidateProcessId)->exists()) {
                $processId = $candidateProcessId;
                break;
            }
        }

        if ($processId === null) {
            return redirect()->route('process-dependency.index');
        }

        return redirect()->route('process-dependency.processes.dependencies', [
            'proceso' => $processId,
        ]);
    }

    private function serviceRedirect(
        Request $request,
        ?int $fallbackDependencyId = null,
        ?int $fallbackProcessId = null,
    ): RedirectResponse {
        $candidateDependencyIds = [
            $this->normalizeId($request->input('id_dependencia')),
            $this->normalizeId($request->input('redirect_dependencia')),
            $fallbackDependencyId,
        ];

        $dependencyId = null;

        foreach ($candidateDependencyIds as $candidateDependencyId) {
            if ($candidateDependencyId === null) {
                continue;
            }

            if (Dependencia::query()->where('id_dependencia', $candidateDependencyId)->exists()) {
                $dependencyId = $candidateDependencyId;
                break;
            }
        }

        if ($dependencyId !== null) {
            return redirect()->route('process-dependency.dependencies.services', [
                'dependencia' => $dependencyId,
            ]);
        }

        return $this->dependencyRedirect($request, $fallbackProcessId);
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
