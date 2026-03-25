<?php

namespace App\Http\Controllers\Estadisticas;

use App\Http\Controllers\Controller;
use App\Services\Estadisticas\ServicioAlcanceEstadisticas;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PaginaEstadisticasController extends Controller
{
    public function __construct(
        private readonly ServicioAlcanceEstadisticas $scopeService,
    ) {}

    public function index(Request $request): View
    {
        $nivelesPermitidos = $this->scopeService->nivelesPermitidos($request->user());

        return view('estadisticas.index', [
            'nivelesPermitidos' => $nivelesPermitidos,
        ]);
    }

    public function processes(Request $request): View
    {
        return $this->renderLevelPage($request, 'processes');
    }

    public function dependencies(Request $request): View
    {
        return $this->renderLevelPage($request, 'dependencies');
    }

    public function services(Request $request): View
    {
        return $this->renderLevelPage($request, 'services');
    }

    private function renderLevelPage(Request $request, string $level): View
    {
        $user = $request->user();
        abort_unless($this->scopeService->puedeAccederNivel($user, $level), 403);

        return view('estadisticas.nivel', [
            'nivelesPermitidos' => $this->scopeService->nivelesPermitidos($user),
            'description' => $this->scopeService->descripcionParaNivel($level),
            'level' => $level,
            'title' => $this->scopeService->tituloParaNivel($level),
        ]);
    }
}
