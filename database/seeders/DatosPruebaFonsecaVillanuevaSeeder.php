<?php

namespace Database\Seeders;

use App\Models\Dependencia;
use App\Models\Estamento;
use App\Models\Proceso;
use App\Models\Programa;
use App\Models\Sede;
use App\Models\Servicio;
use App\Support\Legacy\DatosReferenciaLegado;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class DatosPruebaFonsecaVillanuevaSeeder extends Seeder
{
    private const PROGRAMAS_PRUEBA = [
        'Administracion de Empresas',
        'Contaduria Publica',
        'Ingenieria de Sistemas',
        'Trabajo Social',
    ];

    /**
     * Proceso legado => dependencia legado.
     *
     * Se toman dependencias de referencia de Maicao y se replican con sus
     * servicios para Fonseca y Villanueva.
     */
    private const ESTRUCTURA_BASE = [
        4 => 5,   // Gestion Admisiones, Registro Y Control Academico => Admisiones y Registro
        5 => 12,  // Gestion Bienestar Social Universitario => Salud
        6 => 13,  // Gestion De Bienes, Servicios Academicos Y Bibliotecarios => Biblioteca
        11 => 20, // Gestion Docencia => Coordinacion Academica
    ];

    /**
     * Fonseca ya tenia procesos adicionales creados manualmente. Los completamos
     * con su estructura de referencia para que los reportes por proceso no queden vacios.
     */
    private const ESTRUCTURA_EXTRA_FONSECA = [
        1 => 1,   // Aseguramiento de la Calidad => Aseguramiento
        14 => 30, // Planeacion Institucional => Planeacion
    ];

    public function run(): void
    {
        $estructuraPorProceso = collect(DatosReferenciaLegado::organizationalStructure())
            ->keyBy('id_proceso');

        $estamentoIds = Estamento::query()->pluck('id_estamento')->all();

        foreach ([Sede::ID_FONSECA, Sede::ID_VILLANUEVA] as $sedeId) {
            $this->crearProgramas($sedeId);
            $this->crearEstructuraBase(
                $sedeId,
                $estructuraPorProceso->all(),
                $estamentoIds,
                $this->estructuraObjetivoPorSede($sedeId)
            );
        }
    }

    /**
     * @param  array<int, array{id_proceso:int, nombre:string, dependencias:array<int, array{id_dependencia:int, nombre:string, servicios:array<int, array{id_servicio:int, nombre:string>}>>}>  $estructuraPorProceso
     * @param  array<int, int>  $estamentoIds
     * @param  array<int, int>  $estructuraObjetivo
     */
    private function crearEstructuraBase(
        int $sedeId,
        array $estructuraPorProceso,
        array $estamentoIds,
        array $estructuraObjetivo
    ): void
    {
        foreach ($estructuraObjetivo as $idProcesoLegado => $idDependenciaLegado) {
            $procesoLegado = $estructuraPorProceso[$idProcesoLegado] ?? null;

            if ($procesoLegado === null) {
                throw new RuntimeException("No se encontro el proceso legado {$idProcesoLegado}.");
            }

            $dependenciaLegado = collect($procesoLegado['dependencias'])
                ->firstWhere('id_dependencia', $idDependenciaLegado);

            if ($dependenciaLegado === null) {
                throw new RuntimeException("No se encontro la dependencia legado {$idDependenciaLegado} para el proceso {$idProcesoLegado}.");
            }

            $proceso = $this->resolverProceso($sedeId, $procesoLegado['nombre']);
            $proceso->forceFill([
                'id_sede' => $sedeId,
                'nombre' => $proceso->nombre ?: $procesoLegado['nombre'],
                'activo' => true,
            ])->save();

            $dependencia = $this->resolverDependencia($sedeId, $proceso->id_proceso, $dependenciaLegado['nombre']);
            $dependencia->forceFill([
                'id_sede' => $sedeId,
                'id_proceso' => $proceso->id_proceso,
                'nombre' => $dependencia->nombre ?: $dependenciaLegado['nombre'],
                'activo' => true,
            ])->save();

            foreach ($dependenciaLegado['servicios'] as $servicioLegado) {
                $servicio = Servicio::query()->updateOrCreate(
                    [
                        'id_dependencia' => $dependencia->id_dependencia,
                        'nombre' => $servicioLegado['nombre'],
                    ],
                    [
                        'id_sede' => $sedeId,
                        'id_dependencia' => $dependencia->id_dependencia,
                        'nombre' => $servicioLegado['nombre'],
                        'activo' => true,
                    ]
                );

                if ($estamentoIds !== []) {
                    $servicio->estamentos()->syncWithoutDetaching($estamentoIds);
                }
            }
        }
    }

    /**
     * @return array<int, int>
     */
    private function estructuraObjetivoPorSede(int $sedeId): array
    {
        if ($sedeId === Sede::ID_FONSECA) {
            return self::ESTRUCTURA_BASE + self::ESTRUCTURA_EXTRA_FONSECA;
        }

        return self::ESTRUCTURA_BASE;
    }

    private function resolverProceso(int $sedeId, string $nombreLegado): Proceso
    {
        $normalized = $this->normalize($nombreLegado);

        $existing = Proceso::query()
            ->where('id_sede', $sedeId)
            ->get()
            ->first(fn (Proceso $proceso): bool => $this->normalize((string) $proceso->nombre) === $normalized);

        if ($existing) {
            return $existing;
        }

        return Proceso::query()->create([
            'id_sede' => $sedeId,
            'nombre' => $nombreLegado,
            'activo' => true,
        ]);
    }

    private function resolverDependencia(int $sedeId, int $procesoId, string $nombreLegado): Dependencia
    {
        $dependencias = Dependencia::query()
            ->where('id_sede', $sedeId)
            ->where('id_proceso', $procesoId)
            ->get();
        $normalized = $this->normalize($nombreLegado);

        $existing = $dependencias->first(
            fn (Dependencia $dependencia): bool => $this->normalize((string) $dependencia->nombre) === $normalized
        );

        if ($existing) {
            return $existing;
        }

        if ($dependencias->count() === 1) {
            return $dependencias->first();
        }

        return Dependencia::query()->create([
            'id_sede' => $sedeId,
            'id_proceso' => $procesoId,
            'nombre' => $nombreLegado,
            'activo' => true,
        ]);
    }

    private function normalize(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();
    }

    private function crearProgramas(int $sedeId): void
    {
        foreach (self::PROGRAMAS_PRUEBA as $programa) {
            Programa::query()->updateOrCreate(
                [
                    'id_sede' => $sedeId,
                    'nombre' => $programa,
                ],
                [
                    'id_sede' => $sedeId,
                    'nombre' => $programa,
                ]
            );
        }
    }
}
