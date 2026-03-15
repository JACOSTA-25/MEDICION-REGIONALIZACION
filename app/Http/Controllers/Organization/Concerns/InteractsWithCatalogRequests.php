<?php

namespace App\Http\Controllers\Organization\Concerns;

use App\Models\Dependencia;
use App\Models\Proceso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait InteractsWithCatalogRequests
{
    protected function targetActiveValue(Request $request, bool $currentValue): bool
    {
        return $request->has('activo')
            ? $request->boolean('activo')
            : $currentValue;
    }

    protected function dependencyRedirect(Request $request, ?int $fallbackProcessId = null): RedirectResponse
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

    protected function serviceRedirect(
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

    protected function normalizeId(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }
}
