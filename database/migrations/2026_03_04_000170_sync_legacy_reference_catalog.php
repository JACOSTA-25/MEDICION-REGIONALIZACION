<?php

use App\Support\Legacy\DatosReferenciaLegado;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $timestamp = now();

        DB::table('estamento')->upsert(
            array_map(static fn (array $item): array => [
                'nombre' => $item['nombre'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ], DatosReferenciaLegado::estamentos()),
            ['nombre'],
            ['updated_at']
        );

        DB::table('programa')->upsert(
            array_map(static fn (array $item): array => [
                'nombre' => $item['nombre'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ], DatosReferenciaLegado::programas()),
            ['nombre'],
            ['updated_at']
        );

        $processes = [];
        $dependencies = [];
        $services = [];

        foreach (DatosReferenciaLegado::organizationalStructure() as $process) {
            $processes[] = [
                'id_proceso' => $process['id_proceso'],
                'nombre' => $process['nombre'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            foreach ($process['dependencias'] as $dependency) {
                $dependencies[] = [
                    'id_dependencia' => $dependency['id_dependencia'],
                    'id_proceso' => $process['id_proceso'],
                    'nombre' => $dependency['nombre'],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];

                foreach ($dependency['servicios'] as $service) {
                    $services[] = [
                        'id_servicio' => $service['id_servicio'],
                        'id_dependencia' => $dependency['id_dependencia'],
                        'nombre' => $service['nombre'],
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
            }
        }

        DB::table('proceso')->upsert(
            $processes,
            ['id_proceso'],
            ['nombre', 'updated_at']
        );

        DB::table('dependencia')->upsert(
            $dependencies,
            ['id_dependencia'],
            ['id_proceso', 'nombre', 'updated_at']
        );

        DB::table('servicio')->upsert(
            $services,
            ['id_servicio'],
            ['id_dependencia', 'nombre', 'updated_at']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Legacy catalog synchronization is intentionally non-destructive.
    }
};
