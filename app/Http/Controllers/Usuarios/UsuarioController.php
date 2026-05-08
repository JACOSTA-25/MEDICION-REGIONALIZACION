<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\Controller;
use App\Models\Dependencia;
use App\Models\Proceso;
use App\Models\Sede;
use App\Models\User;
use App\Services\Sedes\ServicioSedes;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class UsuarioController extends Controller
{
    public function __construct(
        private readonly ServicioSedes $sedeService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $selectedSedeId = $this->selectedSedeId($request);

        $dependencies = Dependencia::query()
            ->forSede($selectedSedeId)
            ->active()
            ->orderBy('nombre')
            ->get(['id_dependencia', 'id_proceso', 'id_sede', 'nombre']);

        $dependenciesByProcess = $dependencies
            ->groupBy('id_proceso')
            ->map(static fn ($items) => $items
                ->map(static fn (Dependencia $dependency): array => [
                    'id' => $dependency->id_dependencia,
                    'nombre' => $dependency->nombre,
                    'id_sede' => $dependency->id_sede,
                ])
                ->values()
                ->all()
            )
            ->all();

        return view('usuarios.index', [
            'dependenciesByProcess' => $dependenciesByProcess,
            'processes' => Proceso::query()
                ->forSede($selectedSedeId)
                ->active()
                ->orderBy('nombre')
                ->get(['id_proceso', 'id_sede', 'nombre']),
            'roles' => $this->roles($user),
            'selectedSedeId' => $selectedSedeId,
            'sedes' => $this->sedeService->visibleTo($user),
            'users' => User::query()
                ->when($selectedSedeId !== null, fn ($query) => $query->where('id_sede', $selectedSedeId))
                ->with([
                    'dependencia:id_dependencia,nombre',
                    'proceso:id_proceso,nombre',
                    'sede:id_sede,nombre',
                ])
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->puedeGestionarUsuarios(), 403);

        $validator = $this->validator($request);

        if ($validator->fails()) {
            return redirect()
                ->route('users.index')
                ->withErrors($validator, 'createUser')
                ->withInput($request->except('password'))
                ->with('open_create_user', true);
        }

        User::query()->create($this->payload($request));

        return redirect()
            ->route('users.index')
            ->with('user_status', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->puedeGestionarUsuarios(), 403);
        abort_unless($this->canManageUser($request->user(), $user), 403);

        $validator = $this->validator($request, $user);

        if ($validator->fails()) {
            return redirect()
                ->route('users.index')
                ->withErrors($validator, 'updateUser')
                ->withInput($request->except('password'))
                ->with('open_edit_user', $user->id);
        }

        $user->fill($this->payload($request));
        $user->save();

        return redirect()
            ->route('users.index')
            ->with('user_status', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->puedeGestionarUsuarios(), 403);
        abort_unless($this->canManageUser($request->user(), $user), 403);

        if ($request->user()?->is($user)) {
            return redirect()
                ->route('users.index')
                ->with('user_error', 'No puedes eliminar tu propio usuario.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('user_status', 'Usuario eliminado correctamente.');
    }

    /**
     * @return array<string, string>
     */
    private function roles(?User $user): array
    {
        $roles = [
            User::ROLE_ADMIN => 'Super Administrador',
            User::ROLE_ADMIN_2_0 => 'Administrador 2.0',
            User::ROLE_ADMIN_SEDE => 'Administrador de sede',
            User::ROLE_LIDER_PROCESO => 'Lider de proceso',
            User::ROLE_LIDER_DEPENDENCIA => 'Lider de dependencia',
        ];

        if ($user?->isAdminSede()) {
            return array_filter($roles, fn (string $role): bool => in_array($role, [
                'Administrador de sede',
                'Lider de proceso',
                'Lider de dependencia',
            ], true));
        }

        return $roles;
    }

    private function validator(Request $request, ?User $user = null): ValidationValidator
    {
        $actor = $request->user();
        $roles = array_keys($this->roles($actor));

        $validator = Validator::make($request->all(), [
            'username' => [
                'required',
                'string',
                'max:80',
                Rule::unique('users', 'username')->ignore($user?->id),
            ],
            'nombre' => ['required', 'string', 'max:120'],
            'rol' => ['required', 'string', Rule::in($roles)],
            'id_sede' => ['nullable', 'integer', Rule::exists('sede', 'id_sede')->where(fn ($query) => $query->where('activo', true))],
            'password' => $user
                ? ['nullable', 'string', 'min:8']
                : ['required', 'string', 'min:8'],
            'id_proceso' => ['nullable', 'integer', Rule::exists('proceso', 'id_proceso')->where(fn ($query) => $query->where('activo', true))],
            'id_dependencia' => ['nullable', 'integer', Rule::exists('dependencia', 'id_dependencia')->where(fn ($query) => $query->where('activo', true))],
            'activo' => ['nullable', 'boolean'],
        ]);

        $validator->after(function (ValidationValidator $validator) use ($request, $actor): void {
            $role = (string) $request->input('rol');
            $sedeId = $this->roleNeedsSede($role)
                ? $this->resolvedRoleSedeId($request)
                : null;
            $processId = $request->filled('id_proceso') ? (int) $request->input('id_proceso') : null;
            $dependencyId = $request->filled('id_dependencia') ? (int) $request->input('id_dependencia') : null;

            if ($this->roleNeedsSede($role) && $sedeId === null) {
                $validator->errors()->add('id_sede', 'Debes asignar una sede para este rol.');
            }

            if (in_array($role, [User::ROLE_LIDER_PROCESO, User::ROLE_LIDER_DEPENDENCIA], true) && $processId === null) {
                $validator->errors()->add('id_proceso', 'Debes asignar un proceso para este rol.');
            }

            if ($role === User::ROLE_LIDER_DEPENDENCIA && $dependencyId === null) {
                $validator->errors()->add('id_dependencia', 'Debes asignar una dependencia para este rol.');
            }

            if ($dependencyId !== null && $processId !== null) {
                $dependency = Dependencia::query()->find($dependencyId);

                if ($dependency && (int) $dependency->id_proceso !== $processId) {
                    $validator->errors()->add('id_dependencia', 'La dependencia seleccionada no pertenece al proceso indicado.');
                }
            }

            if ($processId !== null) {
                $process = Proceso::query()->find($processId);

                if (! $process) {
                    return;
                }

                if ($sedeId !== null && (int) $process->id_sede !== $sedeId) {
                    $validator->errors()->add('id_proceso', 'El proceso seleccionado no pertenece a la sede indicada.');
                }
            }

            if ($dependencyId !== null) {
                $dependency = Dependencia::query()->find($dependencyId);

                if (! $dependency) {
                    return;
                }

                if ($sedeId !== null && (int) $dependency->id_sede !== $sedeId) {
                    $validator->errors()->add('id_dependencia', 'La dependencia seleccionada no pertenece a la sede indicada.');
                }
            }

            if ($actor?->isAdminSede() && $sedeId !== null && (int) $actor->id_sede !== $sedeId) {
                $validator->errors()->add('id_sede', 'Solo puedes administrar usuarios de tu propia sede.');
            }
        });

        return $validator;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        $role = (string) $request->input('rol');
        $sedeId = $this->roleNeedsSede($role)
            ? $this->resolvedRoleSedeId($request)
            : null;
        $processId = in_array($role, [User::ROLE_LIDER_PROCESO, User::ROLE_LIDER_DEPENDENCIA], true)
            ? (int) $request->input('id_proceso')
            : null;
        $dependencyId = $role === User::ROLE_LIDER_DEPENDENCIA
            ? (int) $request->input('id_dependencia')
            : null;

        $payload = [
            'activo' => $request->boolean('activo'),
            'id_dependencia' => $dependencyId,
            'id_proceso' => $processId,
            'id_sede' => $sedeId,
            'nombre' => trim((string) $request->input('nombre')),
            'rol' => $role,
            'username' => trim((string) $request->input('username')),
        ];

        if ($request->filled('password')) {
            $payload['password_hash'] = (string) $request->input('password');
        }

        return $payload;
    }

    private function roleNeedsSede(string $role): bool
    {
        return in_array($role, [
            User::ROLE_ADMIN_SEDE,
            User::ROLE_LIDER_PROCESO,
            User::ROLE_LIDER_DEPENDENCIA,
        ], true);
    }

    private function resolvedRoleSedeId(Request $request): ?int
    {
        if ($request->user()?->isAdminSede()) {
            return $request->user()?->id_sede ? (int) $request->user()->id_sede : null;
        }

        return $this->sedeService->normalizeId($request->input('id_sede'));
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

    private function canManageUser(?User $actor, User $managedUser): bool
    {
        if (! $actor) {
            return false;
        }

        if ($actor->isAdmin()) {
            return true;
        }

        if ($actor->isAdminSede()) {
            return (int) $actor->id_sede === (int) $managedUser->id_sede;
        }

        return false;
    }
}
