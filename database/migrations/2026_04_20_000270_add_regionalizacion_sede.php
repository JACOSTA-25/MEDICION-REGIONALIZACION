<?php

use App\Models\Sede;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sede')) {
            return;
        }

        DB::table('sede')->upsert([
            [
                'id_sede' => Sede::ID_REGIONALIZACION,
                'codigo' => 'REGIONALIZACION',
                'slug' => 'regionalizacion',
                'nombre' => 'Sede Regionalizacion',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['id_sede'], ['codigo', 'slug', 'nombre', 'activo', 'updated_at']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('sede')) {
            return;
        }

        DB::table('sede')
            ->where('id_sede', Sede::ID_REGIONALIZACION)
            ->delete();
    }
};
