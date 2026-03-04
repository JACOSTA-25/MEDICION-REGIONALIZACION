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
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_verified_at', 'email']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('name', 'nombre');
            $table->renameColumn('password', 'password_hash');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 80)->nullable()->after('id');
            $table->string('rol', 40)->default('LIDER_PROCESO')->after('nombre');
            $table->unsignedInteger('id_proceso')->nullable()->after('rol');
            $table->unsignedInteger('id_dependencia')->nullable()->after('id_proceso');
            $table->boolean('activo')->default(true)->after('id_dependencia');

            $table->unique('username', 'users_username_unique');
            $table->index('rol', 'users_rol_idx');
            $table->index(['id_proceso', 'id_dependencia'], 'users_proceso_dependencia_idx');

            $table->foreign('id_proceso', 'users_id_proceso_fk')
                ->references('id_proceso')
                ->on('proceso')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreign('id_dependencia', 'users_id_dependencia_fk')
                ->references('id_dependencia')
                ->on('dependencia')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_id_dependencia_fk');
            $table->dropForeign('users_id_proceso_fk');
            $table->dropIndex('users_proceso_dependencia_idx');
            $table->dropIndex('users_rol_idx');
            $table->dropUnique('users_username_unique');
            $table->dropColumn(['username', 'rol', 'id_proceso', 'id_dependencia', 'activo']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('nombre', 'name');
            $table->renameColumn('password_hash', 'password');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('name');
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });
    }
};
