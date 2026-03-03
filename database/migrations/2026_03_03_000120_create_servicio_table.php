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
        Schema::create('servicio', function (Blueprint $table) {
            $table->increments('id_servicio');
            $table->unsignedInteger('id_dependencia');
            $table->string('nombre', 150);
            $table->timestamps();

            $table->unique(['id_dependencia', 'nombre'], 'servicio_dependencia_nombre_unique');
            $table->index('id_dependencia', 'servicio_id_dependencia_idx');

            $table->foreign('id_dependencia', 'servicio_id_dependencia_fk')
                ->references('id_dependencia')
                ->on('dependencia')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicio');
    }
};
