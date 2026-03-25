<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\Controller;
use App\Models\Dependencia;
use App\Models\Proceso;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class UsuarioController extends Controller
{
    public function index(): View
    {
        $dependencies = Dependencia::query()
            ->active()
            ->orderBy('nombre')
            ->get(['id_dependencia', 'id_proceso', 'nombre']);

        $dependenciesByProcess = $dependencies
            ->groupBy('id_proceso')
            ->map(static fn ($items) => $items
                ->map(static fn (Dependencia $dependency): array => [
                    'id' => $dependency->id_dependencia,
                    'nombre' => $dependency->nombre,
                ])
                ->values()
                ->all()
            )
            ->all();

        return view('usuarios.index', [
            'dependenciesByProcess' => $dependenciesByProcess,
            'processes' => Proceso::query()->active()->orderBy('nombre')->get(['id_proceso', 'nombre']),
            'roles' => $this->roles(),
            'users' => User::query()
                ->with([
                    'dependencia:id_dependencia,nombre',
                    'proceso:id_proceso,nombre',
                ])
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
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

    /**
     * @return array<string, string>
     */
    private function roles(): array
    {
        return [
            User::ROLE_ADMIN => 'Super Administrador',
            User::ROLE_ADMIN_2_0 => 'Administrador',
            User::ROLE_LIDER_PROCESO => 'Lider de proceso',
            User::ROLE_LIDER_DEPENDENCIA => 'Lider de dependencia',
        ];
    }

    private function validator(Request $request, ?User $user = null): ValidationValidator
    {
        $validator = Validator::make($request->all(), [
            'username' => [
                'required',
                'string',
                'max:80',
                Rule::unique('users', 'username')->ignore($user?->id),
            ],
            'nombre' => ['required', 'string', 'max:120'],
            'rol' => ['required', 'string', Rule::in(array_keys($this->roles()))],
            'password' => $user
                ? ['nullable', 'string', 'min:8']
                : ['required', 'string', 'min:8'],
            'id_proceso' => ['nullable', 'integer', Rule::exists('proceso', 'id_proceso')->where(fn ($query) => $query->where('activo', true))],
            'id_dependencia' => ['nullable', 'integer', Rule::exists('dependencia', 'id_dependencia')->where(fn ($query) => $query->where('activo', true))],
            'activo' => ['nullable', 'boolean'],
        ]);

        $validator->after(function (ValidationValidator $validator) use ($request): void {
            $role = (string) $request->input('rol');
            $processId = $request->filled('id_proceso') ? (int) $request->input('id_proceso') : null;
            $dependencyId = $request->filled('id_dependencia') ? (int) $request->input('id_dependencia') : null;

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
        });

        return $validator;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        $role = (string) $request->input('rol');
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
            'nombre' => trim((string) $request->input('nombre')),
            'rol' => $role,
            'username' => trim((string) $request->input('username')),
        ];

        if ($request->filled('password')) {
            $payload['password_hash'] = (string) $request->input('password');
        }

        return $payload;
    }
}
