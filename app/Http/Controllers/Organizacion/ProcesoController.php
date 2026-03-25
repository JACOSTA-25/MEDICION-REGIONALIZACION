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

class ProcesoController extends Controller
{
    use InteractuaConSolicitudesCatalogo;

    public function __construct(
        private readonly ServicioAuditoriaCatalogo $auditService,
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

        return view('organizacion.procesos.index', [
            'processes' => $processes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
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

    /**
     * @return array{nombre: string, activo: bool}
     */
    private function payload(Request $request, ?Proceso $process = null): array
    {
        return [
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
}
