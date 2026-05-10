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
    private const ESTRUCTURA_PRUEBA = [
        4 => 5,   // Gestion Admisiones, Registro Y Control Academico => Admisiones y Registro
        5 => 12,  // Gestion Bienestar Social Universitario => Salud
        6 => 13,  // Gestion De Bienes, Servicios Academicos Y Bibliotecarios => Biblioteca
        11 => 20, // Gestion Docencia => Coordinacion Academica
    ];

    public function run(): void
    {
        $estructuraPorProceso = collect(DatosReferenciaLegado::organizationalStructure())
            ->keyBy('id_proceso');

        $estamentoIds = Estamento::query()->pluck('id_estamento')->all();

        foreach ([Sede::ID_FONSECA, Sede::ID_VILLANUEVA] as $sedeId) {
            $this->crearProgramas($sedeId);
            $this->crearEstructuraBase($sedeId, $estructuraPorProceso->all(), $estamentoIds);
        }
    }

    /**
     * @param  array<int, array{id_proceso:int, nombre:string, dependencias:array<int, array{id_dependencia:int, nombre:string, servicios:array<int, array{id_servicio:int, nombre:string>}>>}>  $estructuraPorProceso
     * @param  array<int, int>  $estamentoIds
     */
    private function crearEstructuraBase(int $sedeId, array $estructuraPorProceso, array $estamentoIds): void
    {
        foreach (self::ESTRUCTURA_PRUEBA as $idProcesoLegado => $idDependenciaLegado) {
            $procesoLegado = $estructuraPorProceso[$idProcesoLegado] ?? null;

            if ($procesoLegado === null) {
                throw new RuntimeException("No se encontro el proceso legado {$idProcesoLegado}.");
            }

            $dependenciaLegado = collect($procesoLegado['dependencias'])
                ->firstWhere('id_dependencia', $idDependenciaLegado);

            if ($dependenciaLegado === null) {
                throw new RuntimeException("No se encontro la dependencia legado {$idDependenciaLegado} para el proceso {$idProcesoLegado}.");
            }

            $proceso = Proceso::query()->updateOrCreate(
                [
                    'id_sede' => $sedeId,
                    'nombre' => $procesoLegado['nombre'],
                    'activo' => true,
                ],
                [
                    'id_sede' => $sedeId,
                    'nombre' => $procesoLegado['nombre'],
                    'activo' => true,
                ]
            );

            $dependencia = Dependencia::query()->updateOrCreate(
                [
                    'id_proceso' => $proceso->id_proceso,
                    'nombre' => $dependenciaLegado['nombre'],
                ],
                [
                    'id_sede' => $sedeId,
                    'id_proceso' => $proceso->id_proceso,
                    'nombre' => $dependenciaLegado['nombre'],
                    'activo' => true,
                ]
            );

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
