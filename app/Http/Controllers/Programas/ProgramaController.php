<?php

namespace App\Http\Controllers\Programas;

use App\Http\Controllers\Controller;
use App\Models\Programa;
use App\Services\Organizacion\ServicioAuditoriaCatalogo;
use App\Services\Sedes\ServicioSedes;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class ProgramaController extends Controller
{
    public function __construct(
        private readonly ServicioAuditoriaCatalogo $auditService,
        private readonly ServicioSedes $sedeService,
    ) {}

    public function index(Request $request): View
    {
        $selectedSedeId = $this->selectedSedeId($request);

        $programs = Programa::query()
            ->forSede($selectedSedeId)
            ->withCount('respuestas as respuestas_totales')
            ->with('sede:id_sede,nombre')
            ->orderBy('nombre')
            ->get(['id_programa', 'id_sede', 'nombre']);

        return view('programas.index', [
            'canManagePrograms' => $request->user()?->puedeGestionarProgramas() ?? false,
            'programs' => $programs,
            'selectedSedeId' => $selectedSedeId,
            'sedes' => $this->sedeService->visibleTo($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->puedeGestionarProgramas(), 403);

        $validator = $this->validator($request);

        if ($validator->fails()) {
            return $this->redirectToIndex($request)
                ->withErrors($validator, 'createProgram')
                ->withInput()
                ->with('open_create_program', true);
        }

        $payload = $this->payload($request);
        $createdProgram = null;

        DB::transaction(function () use ($request, $payload, &$createdProgram): void {
            $createdProgram = Programa::query()->create($payload);

            $this->auditService->record(
                $request,
                'CREATE',
                'programa',
                (int) $createdProgram->id_programa,
                null,
                $this->snapshot($createdProgram),
                'Programa creado desde el modulo de administracion.'
            );
        });

        return $this->redirectToIndex($request, (int) $createdProgram->id_sede)
            ->with('catalog_status', 'Programa creado correctamente.');
    }

    public function update(Request $request, Programa $programa): RedirectResponse
    {
        abort_unless($request->user()?->puedeGestionarProgramas(), 403);
        abort_unless($this->sedeService->canAccess($request->user(), (int) $programa->id_sede), 403);

        $validator = $this->validator($request, $programa);

        if ($validator->fails()) {
            return $this->redirectToIndex($request, (int) $programa->id_sede)
                ->withErrors($validator, 'updateProgram')
                ->withInput()
                ->with('open_edit_program', (int) $programa->id_programa);
        }

        DB::transaction(function () use ($request, $programa): void {
            $before = $this->snapshot($programa->fresh());
            $payload = $this->payload($request, $programa);

            $programa->fill($payload);
            $programa->save();

            $this->auditService->record(
                $request,
                'UPDATE',
                'programa',
                (int) $programa->id_programa,
                $before,
                $this->snapshot($programa->fresh()),
                'Programa actualizado desde el modulo de administracion.'
            );
        });

        return $this->redirectToIndex($request, (int) $programa->fresh()->id_sede)
            ->with('catalog_status', 'Programa actualizado correctamente.');
    }

    public function destroy(Request $request, Programa $programa): RedirectResponse
    {
        abort_unless($request->user()?->puedeGestionarProgramas(), 403);
        abort_unless($this->sedeService->canAccess($request->user(), (int) $programa->id_sede), 403);

        if ($programa->respuestas()->exists()) {
            return $this->redirectToIndex($request, (int) $programa->id_sede)
                ->with('catalog_error', 'No se puede eliminar el programa porque ya tiene respuestas asociadas.');
        }

        DB::transaction(function () use ($request, $programa): void {
            $before = $this->snapshot($programa->fresh());
            $programId = (int) $programa->id_programa;

            $programa->delete();

            $this->auditService->record(
                $request,
                'DELETE',
                'programa',
                $programId,
                $before,
                null,
                'Programa eliminado desde el modulo de administracion.'
            );
        });

        return $this->redirectToIndex($request)
            ->with('catalog_status', 'Programa eliminado correctamente.');
    }

    private function validator(Request $request, ?Programa $programa = null): ValidationValidator
    {
        $targetSedeId = $this->resolvedSedeId($request, $programa);
        $payload = ['nombre' => trim((string) $request->input('nombre'))] + $request->all();

        $validator = Validator::make($payload, [
            'id_sede' => ['nullable', 'integer', Rule::exists('sede', 'id_sede')->where(fn ($query) => $query->where('activo', true))],
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique('programa', 'nombre')
                    ->where(fn ($query) => $query->where('id_sede', $targetSedeId))
                    ->ignore($programa?->id_programa, 'id_programa'),
            ],
        ]);

        $validator->after(function (ValidationValidator $validator) use ($request, $targetSedeId, $programa): void {
            if ($targetSedeId === null) {
                $validator->errors()->add('id_sede', 'Debes seleccionar una sede valida para el programa.');

                return;
            }

            if (! $this->sedeService->canAccess($request->user(), $targetSedeId)) {
                $validator->errors()->add('id_sede', 'No puedes administrar programas fuera de tu alcance.');
            }

            if (
                $programa
                && (int) $programa->id_sede !== $targetSedeId
                && $programa->respuestas()->exists()
            ) {
                $validator->errors()->add('id_sede', 'No puedes mover de sede un programa que ya tiene respuestas asociadas.');
            }
        });

        return $validator;
    }

    /**
     * @return array{id_sede: int, nombre: string}
     */
    private function payload(Request $request, ?Programa $programa = null): array
    {
        return [
            'id_sede' => $this->resolvedSedeId($request, $programa),
            'nombre' => trim((string) $request->input('nombre')),
        ];
    }

    /**
     * @return array{id_programa: int, id_sede: int, nombre: string}
     */
    private function snapshot(Programa $programa): array
    {
        return [
            'id_programa' => (int) $programa->id_programa,
            'id_sede' => (int) $programa->id_sede,
            'nombre' => (string) $programa->nombre,
        ];
    }

    private function resolvedSedeId(Request $request, ?Programa $programa = null): ?int
    {
        if ($request->user()?->isAdminSede()) {
            return $request->user()?->id_sede ? (int) $request->user()->id_sede : null;
        }

        $input = $request->input('id_sede');

        if ($input !== null && $input !== '') {
            return $this->sedeService->normalizeId($input);
        }

        return $programa?->id_sede ? (int) $programa->id_sede : null;
    }

    private function selectedSedeId(Request $request): ?int
    {
        if ($request->user()?->isAdminSede()) {
            return $request->user()?->id_sede ? (int) $request->user()->id_sede : null;
        }

        return $this->sedeService->resolveForRequest(
            $request->user(),
            $request,
            'id_sede',
            true,
            true
        );
    }

    private function redirectToIndex(Request $request, ?int $fallbackSedeId = null): RedirectResponse
    {
        $parameters = [];

        if ($request->user()?->hasGlobalSedeAccess()) {
            $parameters['id_sede'] = $this->sedeService->normalizeId($request->input('redirect_sede'))
                ?? $this->sedeService->normalizeId($request->input('id_sede'))
                ?? $fallbackSedeId;
        }

        return redirect()->route(
            'programs.index',
            array_filter($parameters, static fn ($value): bool => $value !== null)
        );
    }
}
