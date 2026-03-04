<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $canonicalName = 'Licenciatura en Educacion Infantil';
        $legacyName = 'Lic. Educacion Infantil';

        $canonical = DB::table('programa')
            ->where('nombre', $canonicalName)
            ->first();

        $legacy = DB::table('programa')
            ->where('nombre', $legacyName)
            ->first();

        if ($canonical && $legacy) {
            DB::table('respuesta')
                ->where('id_programa', $legacy->id_programa)
                ->update(['id_programa' => $canonical->id_programa]);

            DB::table('programa')
                ->where('id_programa', $legacy->id_programa)
                ->delete();

            return;
        }

        if (! $canonical && $legacy) {
            DB::table('programa')
                ->where('id_programa', $legacy->id_programa)
                ->update([
                    'nombre' => $canonicalName,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Program normalization is intentionally irreversible.
    }
};
