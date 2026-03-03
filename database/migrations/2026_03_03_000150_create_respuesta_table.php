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
        Schema::create('respuesta', function (Blueprint $table) {
            $table->increments('id_respuesta');

            $table->unsignedInteger('id_estamento');
            $table->unsignedInteger('id_programa')->nullable();
            $table->unsignedInteger('id_proceso');
            $table->unsignedInteger('id_dependencia')->nullable();
            $table->unsignedInteger('id_servicio')->nullable();

            $table->unsignedTinyInteger('pregunta1');
            $table->unsignedTinyInteger('pregunta2');
            $table->unsignedTinyInteger('pregunta3');
            $table->unsignedTinyInteger('pregunta4');
            $table->unsignedTinyInteger('pregunta5');
            $table->unsignedTinyInteger('pregunta6');

            $table->text('observaciones')->nullable();
            $table->timestamp('fecha_respuesta')->useCurrent();

            $table->index(['id_proceso', 'id_dependencia', 'fecha_respuesta'], 'respuesta_filtro_idx');
            $table->index('id_estamento', 'respuesta_id_estamento_idx');
            $table->index('id_programa', 'respuesta_id_programa_idx');
            $table->index('id_servicio', 'respuesta_id_servicio_idx');

            $table->foreign('id_estamento', 'respuesta_id_estamento_fk')
                ->references('id_estamento')
                ->on('estamento')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('id_programa', 'respuesta_id_programa_fk')
                ->references('id_programa')
                ->on('programa')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreign('id_proceso', 'respuesta_id_proceso_fk')
                ->references('id_proceso')
                ->on('proceso')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('id_dependencia', 'respuesta_id_dependencia_fk')
                ->references('id_dependencia')
                ->on('dependencia')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreign('id_servicio', 'respuesta_id_servicio_fk')
                ->references('id_servicio')
                ->on('servicio')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('respuesta');
    }
};
