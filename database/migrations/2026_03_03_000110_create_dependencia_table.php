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
        Schema::create('dependencia', function (Blueprint $table) {
            $table->increments('id_dependencia');
            $table->unsignedInteger('id_proceso');
            $table->string('nombre', 150);
            $table->timestamps();

            $table->unique(['id_proceso', 'nombre'], 'dependencia_proceso_nombre_unique');
            $table->index('id_proceso', 'dependencia_id_proceso_idx');

            $table->foreign('id_proceso', 'dependencia_id_proceso_fk')
                ->references('id_proceso')
                ->on('proceso')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dependencia');
    }
};
