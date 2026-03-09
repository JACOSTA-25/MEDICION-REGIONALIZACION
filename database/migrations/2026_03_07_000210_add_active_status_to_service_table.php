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
        Schema::table('servicio', function (Blueprint $table): void {
            $table->boolean('activo')->default(true)->after('nombre');
            $table->dropUnique('servicio_dependencia_nombre_unique');
            $table->unique(['id_dependencia', 'nombre', 'activo'], 'servicio_dependencia_nombre_activo_unique');
            $table->index('activo', 'servicio_activo_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servicio', function (Blueprint $table): void {
            $table->dropIndex('servicio_activo_idx');
            $table->dropUnique('servicio_dependencia_nombre_activo_unique');
            $table->dropColumn('activo');
            $table->unique(['id_dependencia', 'nombre'], 'servicio_dependencia_nombre_unique');
        });
    }
};
