<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionSecurity
{
    public const LAST_ACTIVITY_KEY = 'auth.last_activity_at';

    private const INACTIVITY_TIMEOUT_MINUTES = 10;

    public function handle(Request $request, Closure $next): Response
    {
        $userWasAuthenticated = $request->user() !== null;

        if ($userWasAuthenticated) {
            if ($this->sessionExpiredByInactivity($request)) {
                return $this->logoutDueToInactivity($request);
            }

            $request->session()->put(self::LAST_ACTIVITY_KEY, now()->getTimestamp());
        } else {
            $request->session()->forget(self::LAST_ACTIVITY_KEY);
        }

        $response = $next($request);

        if ($userWasAuthenticated) {
            $this->applyNoStoreHeaders($response);
        }

        return $response;
    }

    private function sessionExpiredByInactivity(Request $request): bool
    {
        $lastActivity = $request->session()->get(self::LAST_ACTIVITY_KEY);

        if (! is_numeric($lastActivity)) {
            return false;
        }

        return (now()->getTimestamp() - (int) $lastActivity) >= (self::INACTIVITY_TIMEOUT_MINUTES * 60);
    }

    private function logoutDueToInactivity(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $response = redirect()
            ->route('login')
            ->with('status', 'Tu sesion se cerro tras 10 minutos de inactividad. Ingresa nuevamente.');

        $this->applyNoStoreHeaders($response);

        return $response;
    }

    private function applyNoStoreHeaders(Response $response): void
    {
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
    }
}
