<?php

use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if (! Schema::hasTable('sede')) {
            Schema::create('sede', function (Blueprint $table) {
                $table->increments('id_sede');
                $table->string('codigo', 30)->unique();
                $table->string('slug', 60)->unique();
                $table->string('nombre', 120)->unique();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        DB::table('sede')->upsert([
            [
                'id_sede' => Sede::ID_MAICAO,
                'codigo' => 'MAICAO',
                'slug' => 'maicao',
                'nombre' => 'Sede Maicao',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_sede' => Sede::ID_FONSECA,
                'codigo' => 'FONSECA',
                'slug' => 'fonseca',
                'nombre' => 'Sede Fonseca',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_sede' => Sede::ID_VILLANUEVA,
                'codigo' => 'VILLANUEVA',
                'slug' => 'villanueva',
                'nombre' => 'Sede Villanueva',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['id_sede'], ['codigo', 'slug', 'nombre', 'activo', 'updated_at']);

        if (! Schema::hasColumn('proceso', 'id_sede')) {
            Schema::table('proceso', function (Blueprint $table) {
                $table->unsignedInteger('id_sede')->default(Sede::ID_MAICAO)->after('id_proceso');
            });
        }

        $this->ensureIndex('proceso', 'proceso_sede_activo_idx', function (Blueprint $table): void {
            $table->index(['id_sede', 'activo'], 'proceso_sede_activo_idx');
        });

        if (! Schema::hasColumn('dependencia', 'id_sede')) {
            Schema::table('dependencia', function (Blueprint $table) {
                $table->unsignedInteger('id_sede')->default(Sede::ID_MAICAO)->after('id_dependencia');
            });
        }

        $this->ensureIndex('dependencia', 'dependencia_sede_proceso_idx', function (Blueprint $table): void {
            $table->index(['id_sede', 'id_proceso'], 'dependencia_sede_proceso_idx');
        });

        if (! Schema::hasColumn('servicio', 'id_sede')) {
            Schema::table('servicio', function (Blueprint $table) {
                $table->unsignedInteger('id_sede')->default(Sede::ID_MAICAO)->after('id_servicio');
            });
        }

        $this->ensureIndex('servicio', 'servicio_sede_dependencia_idx', function (Blueprint $table): void {
            $table->index(['id_sede', 'id_dependencia'], 'servicio_sede_dependencia_idx');
        });

        if (! Schema::hasColumn('programa', 'id_sede')) {
            Schema::table('programa', function (Blueprint $table) {
                $table->unsignedInteger('id_sede')->default(Sede::ID_MAICAO)->after('id_programa');
            });
        }

        $this->ensureIndex('programa', 'programa_sede_nombre_idx', function (Blueprint $table): void {
            $table->index(['id_sede', 'nombre'], 'programa_sede_nombre_idx');
        });

        if (! Schema::hasColumn('users', 'id_sede')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedInteger('id_sede')->nullable()->after('rol');
            });
        }

        $this->ensureIndex('users', 'users_id_sede_idx', function (Blueprint $table): void {
            $table->index('id_sede', 'users_id_sede_idx');
        });

        if (! Schema::hasColumn('respuesta', 'id_sede')) {
            Schema::table('respuesta', function (Blueprint $table) {
                $table->unsignedInteger('id_sede')->default(Sede::ID_MAICAO)->after('id_respuesta');
            });
        }

        $this->ensureIndex('respuesta', 'respuesta_sede_fecha_idx', function (Blueprint $table): void {
            $table->index(['id_sede', 'fecha_respuesta'], 'respuesta_sede_fecha_idx');
        });

        $this->ensureIndex('respuesta', 'respuesta_sede_filtro_idx', function (Blueprint $table): void {
            $table->index(['id_sede', 'id_proceso', 'id_dependencia', 'fecha_respuesta'], 'respuesta_sede_filtro_idx');
        });

        DB::table('users')
            ->whereIn('rol', [User::ROLE_ADMIN, User::ROLE_ADMIN_2_0])
            ->update(['id_sede' => null]);

        DB::table('users')
            ->whereNotIn('rol', [User::ROLE_ADMIN, User::ROLE_ADMIN_2_0])
            ->update(['id_sede' => Sede::ID_MAICAO]);

        $this->ensureForeign('proceso', 'proceso_id_sede_fk', function (Blueprint $table): void {
            $table->foreign('id_sede', 'proceso_id_sede_fk')
                ->references('id_sede')
                ->on('sede')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        if ($driver !== 'sqlite') {
            $this->dropIndexIfExists('proceso', 'proceso_nombre_unique');
            $this->dropIndexIfExists('proceso', 'proceso_nombre_activo_unique');
            $this->dropIndexIfExists('proceso', 'proceso_sede_nombre_unique');
            $this->ensureUnique('proceso', 'proceso_sede_nombre_activo_unique', function (Blueprint $table): void {
                $table->unique(['id_sede', 'nombre', 'activo'], 'proceso_sede_nombre_activo_unique');
            });
        }

        $this->ensureForeign('dependencia', 'dependencia_id_sede_fk', function (Blueprint $table): void {
            $table->foreign('id_sede', 'dependencia_id_sede_fk')
                ->references('id_sede')
                ->on('sede')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        $this->ensureForeign('servicio', 'servicio_id_sede_fk', function (Blueprint $table): void {
            $table->foreign('id_sede', 'servicio_id_sede_fk')
                ->references('id_sede')
                ->on('sede')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        $this->ensureForeign('programa', 'programa_id_sede_fk', function (Blueprint $table): void {
            $table->foreign('id_sede', 'programa_id_sede_fk')
                ->references('id_sede')
                ->on('sede')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        if ($driver !== 'sqlite') {
            $this->dropIndexIfExists('programa', 'programa_nombre_unique');
            $this->ensureUnique('programa', 'programa_sede_nombre_unique', function (Blueprint $table): void {
                $table->unique(['id_sede', 'nombre'], 'programa_sede_nombre_unique');
            });
        }

        $this->ensureForeign('users', 'users_id_sede_fk', function (Blueprint $table): void {
            $table->foreign('id_sede', 'users_id_sede_fk')
                ->references('id_sede')
                ->on('sede')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        $this->ensureForeign('respuesta', 'respuesta_id_sede_fk', function (Blueprint $table): void {
            $table->foreign('id_sede', 'respuesta_id_sede_fk')
                ->references('id_sede')
                ->on('sede')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        $this->dropForeignIfExists('respuesta', 'respuesta_id_sede_fk');
        $this->dropIndexIfExists('respuesta', 'respuesta_sede_filtro_idx');
        $this->dropIndexIfExists('respuesta', 'respuesta_sede_fecha_idx');

        if (Schema::hasColumn('respuesta', 'id_sede')) {
            Schema::table('respuesta', function (Blueprint $table) {
                $table->dropColumn('id_sede');
            });
        }

        $this->dropForeignIfExists('users', 'users_id_sede_fk');
        $this->dropIndexIfExists('users', 'users_id_sede_idx');

        if (Schema::hasColumn('users', 'id_sede')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('id_sede');
            });
        }

        $this->dropForeignIfExists('programa', 'programa_id_sede_fk');
        $this->dropIndexIfExists('programa', 'programa_sede_nombre_idx');

        if ($driver !== 'sqlite') {
            $this->dropIndexIfExists('programa', 'programa_sede_nombre_unique');
            $this->ensureUnique('programa', 'programa_nombre_unique', function (Blueprint $table): void {
                $table->unique('nombre', 'programa_nombre_unique');
            });
        }

        if (Schema::hasColumn('programa', 'id_sede')) {
            Schema::table('programa', function (Blueprint $table) {
                $table->dropColumn('id_sede');
            });
        }

        $this->dropForeignIfExists('servicio', 'servicio_id_sede_fk');
        $this->dropIndexIfExists('servicio', 'servicio_sede_dependencia_idx');

        if (Schema::hasColumn('servicio', 'id_sede')) {
            Schema::table('servicio', function (Blueprint $table) {
                $table->dropColumn('id_sede');
            });
        }

        $this->dropForeignIfExists('dependencia', 'dependencia_id_sede_fk');
        $this->dropIndexIfExists('dependencia', 'dependencia_sede_proceso_idx');

        if (Schema::hasColumn('dependencia', 'id_sede')) {
            Schema::table('dependencia', function (Blueprint $table) {
                $table->dropColumn('id_sede');
            });
        }

        $this->dropForeignIfExists('proceso', 'proceso_id_sede_fk');
        $this->dropIndexIfExists('proceso', 'proceso_sede_activo_idx');

        if ($driver !== 'sqlite') {
            $this->dropIndexIfExists('proceso', 'proceso_sede_nombre_activo_unique');
            $this->dropIndexIfExists('proceso', 'proceso_sede_nombre_unique');
            $this->ensureUnique('proceso', 'proceso_nombre_activo_unique', function (Blueprint $table): void {
                $table->unique(['nombre', 'activo'], 'proceso_nombre_activo_unique');
            });
        }

        if (Schema::hasColumn('proceso', 'id_sede')) {
            Schema::table('proceso', function (Blueprint $table) {
                $table->dropColumn('id_sede');
            });
        }

        Schema::dropIfExists('sede');
    }

    private function ensureIndex(string $table, string $index, callable $callback): void
    {
        if ($this->indexExists($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($callback): void {
            $callback($blueprint);
        });
    }

    private function ensureUnique(string $table, string $index, callable $callback): void
    {
        $this->ensureIndex($table, $index, $callback);
    }

    private function ensureForeign(string $table, string $foreignKey, callable $callback): void
    {
        if ($this->foreignKeyExists($table, $foreignKey)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($callback): void {
            $callback($blueprint);
        });
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! $this->indexExists($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($index): void {
            $table->dropIndex($index);
        });
    }

    private function dropForeignIfExists(string $table, string $foreignKey): void
    {
        if (! $this->foreignKeyExists($table, $foreignKey)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($foreignKey): void {
            $table->dropForeign($foreignKey);
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        return match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => collect(DB::select("SHOW INDEX FROM `{$table}`"))
                ->contains(fn ($row): bool => (string) ($row->Key_name ?? '') === $index),
            'sqlite' => collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn ($row): bool => (string) ($row->name ?? '') === $index),
            default => false,
        };
    }

    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        return match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => DB::table('information_schema.TABLE_CONSTRAINTS')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', $table)
                ->where('CONSTRAINT_NAME', $foreignKey)
                ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
                ->exists(),
            default => false,
        };
    }
};
