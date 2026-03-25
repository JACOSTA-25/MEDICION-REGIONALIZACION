<?php

namespace App\Services\Estadisticas;

use App\Models\User;

class ServicioAlcanceEstadisticas
{
    /**
     * @return list<string>
     */
    public function nivelesPermitidos(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $levels = [];

        foreach (['processes', 'dependencies', 'services'] as $level) {
            if ($this->puedeAccederNivel($user, $level)) {
                $levels[] = $level;
            }
        }

        return $levels;
    }

    public function puedeAccederNivel(?User $user, string $level): bool
    {
        if (! $user) {
            return false;
        }

        return match ($level) {
            'processes' => $user->puedeAccederEstadisticasProcesos(),
            'dependencies' => $user->puedeAccederEstadisticasDependencias(),
            'services' => $user->puedeAccederEstadisticasServicios(),
            default => false,
        };
    }

    public function tituloParaNivel(string $level): string
    {
        return match ($level) {
            'processes' => 'Estadisticas por procesos',
            'dependencies' => 'Estadisticas por dependencias',
            'services' => 'Estadisticas por servicios',
            default => 'Estadisticas',
        };
    }

    public function descripcionParaNivel(string $level): string
    {
        return match ($level) {
            'processes' => 'Compara procesos por volumen de encuestas y satisfaccion dentro del trimestre seleccionado.',
            'dependencies' => 'Analiza dependencias dentro del alcance permitido para el usuario activo.',
            'services' => 'Compara servicios por cantidad de encuestas y calidad percibida.',
            default => 'Analiza el comportamiento de las encuestas por nivel jerarquico.',
        };
    }
}
