<?php

namespace App\Services\Sedes;

use App\Models\Sede;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ServicioSedes
{
    public const SESSION_SCOPE_KEY = 'selected_sede_id';

    public function active(): Collection
    {
        return Sede::query()
            ->active()
            ->orderBy('id_sede')
            ->get(['id_sede', 'codigo', 'slug', 'nombre']);
    }

    public function visibleTo(?User $user): Collection
    {
        $sedes = $this->active();

        if (! $user) {
            return $sedes;
        }

        if ($user->hasGlobalSedeAccess()) {
            return $sedes;
        }

        if (! $user->id_sede) {
            return collect();
        }

        return $sedes
            ->where('id_sede', (int) $user->id_sede)
            ->values();
    }

    public function normalizeId(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    public function resolveForUser(?User $user, mixed $requestedSedeId, bool $allowGlobalAll = true): ?int
    {
        if (! $user) {
            return $this->normalizeId($requestedSedeId);
        }

        if (! $user->hasGlobalSedeAccess()) {
            return $user->id_sede ? (int) $user->id_sede : null;
        }

        $normalized = $this->normalizeId($requestedSedeId);

        if ($normalized !== null && $this->exists($normalized)) {
            return $normalized;
        }

        if ($allowGlobalAll) {
            return null;
        }

        return $this->defaultActiveSedeId();
    }

    public function resolveForRequest(
        ?User $user,
        Request $request,
        string $inputKey = 'id_sede',
        bool $allowGlobalAll = true,
        bool $rememberExplicit = false,
    ): ?int {
        $requestedSedeId = $request->exists($inputKey)
            ? $request->input($inputKey)
            : $request->session()->get(self::SESSION_SCOPE_KEY);

        $resolved = $this->resolveForUser($user, $requestedSedeId, $allowGlobalAll);

        if ($rememberExplicit && $request->exists($inputKey) && $user?->hasGlobalSedeAccess()) {
            $this->storeSelection($request, $resolved);
        }

        return $resolved;
    }

    public function rememberSelection(
        Request $request,
        ?User $user,
        mixed $requestedSedeId,
        bool $allowGlobalAll = true,
    ): ?int {
        $resolved = $this->resolveForUser($user, $requestedSedeId, $allowGlobalAll);

        if ($user?->hasGlobalSedeAccess()) {
            $this->storeSelection($request, $resolved);
        }

        return $resolved;
    }

    public function selectionLabel(?int $sedeId): string
    {
        if ($sedeId === null) {
            return 'Todas las sedes';
        }

        return (string) ($this->active()->firstWhere('id_sede', $sedeId)?->nombre ?? 'Sede no disponible');
    }

    public function exists(int $sedeId): bool
    {
        return Sede::query()
            ->where('id_sede', $sedeId)
            ->where('activo', true)
            ->exists();
    }

    public function canAccess(?User $user, ?int $sedeId): bool
    {
        if ($sedeId === null) {
            return $user?->hasGlobalSedeAccess() ?? false;
        }

        if (! $user) {
            return $this->exists($sedeId);
        }

        if ($user->hasGlobalSedeAccess()) {
            return $this->exists($sedeId);
        }

        return (int) $user->id_sede === $sedeId;
    }

    private function storeSelection(Request $request, ?int $sedeId): void
    {
        if ($sedeId === null) {
            $request->session()->forget(self::SESSION_SCOPE_KEY);

            return;
        }

        $request->session()->put(self::SESSION_SCOPE_KEY, $sedeId);
    }

    private function defaultActiveSedeId(): int
    {
        if ($this->exists(Sede::ID_MAICAO)) {
            return Sede::ID_MAICAO;
        }

        return (int) ($this->active()->first()?->id_sede ?? Sede::ID_MAICAO);
    }
}
