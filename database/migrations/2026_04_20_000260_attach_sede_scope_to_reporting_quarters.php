<?php

use App\Models\Sede;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('reporting_quarters', 'id_sede')) {
            Schema::table('reporting_quarters', function (Blueprint $table) {
                $table->unsignedInteger('id_sede')->nullable()->after('id');
            });
        }

        if (! $this->indexExists('reporting_quarters', 'reporting_quarters_id_sede_index')) {
            Schema::table('reporting_quarters', function (Blueprint $table) {
                $table->index('id_sede', 'reporting_quarters_id_sede_index');
            });
        }

        if (! $this->indexExists('reporting_quarters', 'reporting_quarters_sede_year_quarter_unique')) {
            $this->dropIndexIfExists('reporting_quarters', 'reporting_quarters_year_quarter_unique');

            Schema::table('reporting_quarters', function (Blueprint $table) {
                $table->unique(['id_sede', 'year', 'quarter_number'], 'reporting_quarters_sede_year_quarter_unique');
            });
        }

        if (! $this->foreignKeyExists('reporting_quarters', 'reporting_quarters_id_sede_foreign')) {
            Schema::table('reporting_quarters', function (Blueprint $table) {
                $table->foreign('id_sede', 'reporting_quarters_id_sede_foreign')
                    ->references('id_sede')
                    ->on('sede')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $this->dropForeignIfExists('reporting_quarters', 'reporting_quarters_id_sede_foreign');
        $this->dropIndexIfExists('reporting_quarters', 'reporting_quarters_sede_year_quarter_unique');

        if (! $this->indexExists('reporting_quarters', 'reporting_quarters_year_quarter_unique')) {
            Schema::table('reporting_quarters', function (Blueprint $table) {
                $table->unique(['year', 'quarter_number'], 'reporting_quarters_year_quarter_unique');
            });
        }

        $this->dropIndexIfExists('reporting_quarters', 'reporting_quarters_id_sede_index');

        if (Schema::hasColumn('reporting_quarters', 'id_sede')) {
            Schema::table('reporting_quarters', function (Blueprint $table) {
                $table->dropColumn('id_sede');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
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

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! $this->indexExists($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($index) {
            $table->dropIndex($index);
        });
    }

    private function dropForeignIfExists(string $table, string $foreignKey): void
    {
        if (! $this->foreignKeyExists($table, $foreignKey)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($foreignKey) {
            $table->dropForeign($foreignKey);
        });
    }
};
