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
            'general_reports' => $user->canAccessGeneralReports(),
            'process_reports' => $user->canAccessProcessReports(),
            'individual_reports' => $user->canAccessIndividualReports(),
            'users' => $user->canAccessUsersModule(),
            default => false,
        };

        if (! $allowed) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
