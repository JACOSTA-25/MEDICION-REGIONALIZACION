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
        if (! Schema::hasColumn('respuesta', 'pregunta6')) {
            return;
        }

        Schema::table('respuesta', function (Blueprint $table): void {
            $table->dropColumn('pregunta6');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('respuesta', 'pregunta6')) {
            return;
        }

        Schema::table('respuesta', function (Blueprint $table): void {
            $table->unsignedTinyInteger('pregunta6')->after('pregunta5');
        });
    }
};
