<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Organization\Concerns\InteractsWithCatalogRequests;
use App\Models\Dependencia;
use App\Models\Servicio;
use App\Services\CatalogAuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class ServiceController extends Controller
{
    use InteractsWithCatalogRequests;

    public function __construct(
        private readonly CatalogAuditService $auditService,
    ) {}

    public function index(Dependencia $dependencia): View
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

        return view('organization.services.index', [
            'activeDependencies' => $dependencies->where('activo', true)->values(),
            'dependencies' => $dependencies,
            'selectedDependency' => $dependencia,
            'selectedProcess' => $dependencia->proceso,
            'services' => $services,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = $this->validator($request);

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

        $payload = $this->payload($request);
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
                $this->snapshot($service),
                'Servicio creado desde el modulo de administracion.'
            );
        });

        return $this->serviceRedirect($request, $createdServiceDependencyId)
            ->with('catalog_status', 'Servicio creado correctamente.');
    }

    public function update(Request $request, Servicio $servicio): RedirectResponse
    {
        $validator = $this->validator($request, $servicio);

        if ($validator->fails()) {
            return $this->serviceRedirect($request, (int) $servicio->id_dependencia)
                ->withErrors($validator, 'updateService')
                ->withInput()
                ->with('open_edit_service', (int) $servicio->id_servicio);
        }

        DB::transaction(function () use ($request, $servicio): void {
            $before = $this->snapshot($servicio->fresh());
            $payload = $this->payload($request, $servicio);

            $servicio->fill($payload);
            $servicio->save();

            $this->auditService->record(
                $request,
                'UPDATE',
                'servicio',
                (int) $servicio->id_servicio,
                $before,
                $this->snapshot($servicio->fresh()),
                'Servicio actualizado desde el modulo de administracion.'
            );
        });

        return $this->serviceRedirect($request, (int) $servicio->fresh()->id_dependencia)
            ->with('catalog_status', 'Servicio actualizado correctamente.');
    }

    public function deactivate(Request $request, Servicio $servicio): RedirectResponse
    {
        if (! $servicio->activo) {
            return $this->serviceRedirect($request, (int) $servicio->id_dependencia)
                ->with('catalog_status', 'El servicio ya estaba inactivo.');
        }

        DB::transaction(function () use ($request, $servicio): void {
            $before = $this->snapshot($servicio->fresh());

            $servicio->activo = false;
            $servicio->save();

            $this->auditService->record(
                $request,
                'DEACTIVATE',
                'servicio',
                (int) $servicio->id_servicio,
                $before,
                $this->snapshot($servicio->fresh()),
                'Servicio inactivado desde el modulo de administracion.'
            );
        });

        return $this->serviceRedirect($request, (int) $servicio->id_dependencia)
            ->with('catalog_status', 'Servicio inactivado correctamente.');
    }

    public function activate(Request $request, Servicio $servicio): RedirectResponse
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
            $before = $this->snapshot($servicio->fresh());

            $servicio->activo = true;
            $servicio->save();

            $this->auditService->record(
                $request,
                'ACTIVATE',
                'servicio',
                (int) $servicio->id_servicio,
                $before,
                $this->snapshot($servicio->fresh()),
                'Servicio activado desde el modulo de administracion.'
            );
        });

        return $this->serviceRedirect($request, (int) $servicio->id_dependencia)
            ->with('catalog_status', 'Servicio activado correctamente.');
    }

    private function validator(Request $request, ?Servicio $service = null): ValidationValidator
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
     * @return array{id_dependencia: int, nombre: string, activo: bool}
     */
    private function payload(Request $request, ?Servicio $service = null): array
    {
        return [
            'id_dependencia' => (int) $request->input('id_dependencia'),
            'nombre' => trim((string) $request->input('nombre')),
            'activo' => $this->targetActiveValue($request, $service?->activo ?? true),
        ];
    }

    /**
     * @return array{id_servicio: int, id_dependencia: int, nombre: string, activo: bool}
     */
    private function snapshot(Servicio $service): array
    {
        return [
            'id_servicio' => (int) $service->id_servicio,
            'id_dependencia' => (int) $service->id_dependencia,
            'nombre' => (string) $service->nombre,
            'activo' => (bool) $service->activo,
        ];
    }
}
