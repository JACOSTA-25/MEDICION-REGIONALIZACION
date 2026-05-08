<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $allowed = match ($module) {
            'statistics' => $user->puedeAccederModuloEstadisticas(),
            'statistics_processes' => $user->puedeAccederEstadisticasProcesos(),
            'statistics_dependencies' => $user->puedeAccederEstadisticasDependencias(),
            'statistics_services' => $user->puedeAccederEstadisticasServicios(),
            'general_reports' => $user->puedeAccederReportesGenerales(),
            'process_reports' => $user->puedeAccederReportesProceso(),
            'individual_reports' => $user->puedeAccederReportesIndividuales(),
            'programs' => $user->puedeAccederModuloProgramas(),
            'users' => $user->puedeAccederModuloUsuarios(),
            'process_dependency' => $user->puedeAccederModuloEstructuraOrganizacional(),
            default => false,
        };

        if (! $allowed) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
