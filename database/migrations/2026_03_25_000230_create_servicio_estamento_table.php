<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('servicio_estamento', function (Blueprint $table) {
            $table->unsignedInteger('id_servicio');
            $table->unsignedInteger('id_estamento');

            $table->primary(['id_servicio', 'id_estamento'], 'servicio_estamento_primary');
            $table->index('id_estamento', 'servicio_estamento_id_estamento_idx');

            $table->foreign('id_servicio', 'servicio_estamento_id_servicio_fk')
                ->references('id_servicio')
                ->on('servicio')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('id_estamento', 'servicio_estamento_id_estamento_fk')
                ->references('id_estamento')
                ->on('estamento')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

        $serviceIds = DB::table('servicio')->pluck('id_servicio');
        $estamentoIds = DB::table('estamento')->pluck('id_estamento');

        if ($serviceIds->isEmpty() || $estamentoIds->isEmpty()) {
            return;
        }

        $rows = [];

        foreach ($serviceIds as $serviceId) {
            foreach ($estamentoIds as $estamentoId) {
                $rows[] = [
                    'id_servicio' => (int) $serviceId,
                    'id_estamento' => (int) $estamentoId,
                ];
            }
        }

        DB::table('servicio_estamento')->insertOrIgnore($rows);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicio_estamento');
    }
};
