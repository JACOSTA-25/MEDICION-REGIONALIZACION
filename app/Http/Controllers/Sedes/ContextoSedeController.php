<?php

namespace App\Http\Controllers\Sedes;

use App\Http\Controllers\Controller;
use App\Services\Sedes\ServicioSedes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContextoSedeController extends Controller
{
    public function __construct(
        private readonly ServicioSedes $sedeService,
    ) {}

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasGlobalSedeAccess(), 403);

        $this->sedeService->rememberSelection(
            $request,
            $request->user(),
            $request->input('id_sede')
        );

        return redirect()->to(
            $this->sanitizeRedirectDestination($request->input('redirect_to'))
        );
    }

    private function sanitizeRedirectDestination(mixed $redirectTo): string
    {
        $fallback = route('dashboard');

        if (! is_string($redirectTo) || trim($redirectTo) === '') {
            return $fallback;
        }

        $parsed = parse_url(trim($redirectTo));

        if ($parsed === false) {
            return $fallback;
        }

        $appHost = parse_url(url('/'), PHP_URL_HOST);
        $candidateHost = $parsed['host'] ?? null;

        if ($candidateHost !== null && $candidateHost !== $appHost) {
            return $fallback;
        }

        $path = $parsed['path'] ?? '/';
        $path = str_starts_with($path, '/') ? $path : '/'.$path;
        $query = [];

        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }

        unset($query['id_sede']);

        $destination = $path;

        if ($query !== []) {
            $destination .= '?'.http_build_query($query);
        }

        if (isset($parsed['fragment']) && $parsed['fragment'] !== '') {
            $destination .= '#'.$parsed['fragment'];
        }

        return $destination;
    }
}
