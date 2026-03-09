<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proceso', function (Blueprint $table): void {
            $table->boolean('activo')->default(true)->after('nombre');
            $table->dropUnique('proceso_nombre_unique');
            $table->unique(['nombre', 'activo'], 'proceso_nombre_activo_unique');
            $table->index('activo', 'proceso_activo_idx');
        });

        Schema::table('dependencia', function (Blueprint $table): void {
            $table->boolean('activo')->default(true)->after('nombre');
            $table->dropUnique('dependencia_proceso_nombre_unique');
            $table->unique(['id_proceso', 'nombre', 'activo'], 'dependencia_proceso_nombre_activo_unique');
            $table->index('activo', 'dependencia_activo_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dependencia', function (Blueprint $table): void {
            $table->dropIndex('dependencia_activo_idx');
            $table->dropUnique('dependencia_proceso_nombre_activo_unique');
            $table->dropColumn('activo');
            $table->unique(['id_proceso', 'nombre'], 'dependencia_proceso_nombre_unique');
        });

        Schema::table('proceso', function (Blueprint $table): void {
            $table->dropIndex('proceso_activo_idx');
            $table->dropUnique('proceso_nombre_activo_unique');
            $table->dropColumn('activo');
            $table->unique('nombre', 'proceso_nombre_unique');
        });
    }
};
