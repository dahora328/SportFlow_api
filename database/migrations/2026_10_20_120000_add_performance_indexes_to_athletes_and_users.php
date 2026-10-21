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
        // O Postgres não cria índices automaticamente para chaves estrangeiras
        Schema::table('athletes', function (Blueprint $table) {
            $table->index('owner_id', 'athletes_owner_id_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('enterprise_id', 'users_enterprise_id_index');
        });

        if (DB::getDriverName() === 'pgsql') {
            // Listagem: filtra por empresa (escopo global), ignora soft deletes e ordena por data
            DB::statement('CREATE INDEX athletes_enterprise_created_at_index ON athletes (enterprise_id, created_at DESC) WHERE deleted_at IS NULL');
            DB::statement('CREATE INDEX athletes_enterprise_full_name_index ON athletes (enterprise_id, full_name) WHERE deleted_at IS NULL');

            // Busca LOWER(full_name) LIKE '%termo%' só usa índice com trigram
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement('CREATE INDEX athletes_full_name_trgm_index ON athletes USING gin (LOWER(full_name) gin_trgm_ops)');
        } else {
            Schema::table('athletes', function (Blueprint $table) {
                $table->index(['enterprise_id', 'created_at'], 'athletes_enterprise_created_at_index');
                $table->index(['enterprise_id', 'full_name'], 'athletes_enterprise_full_name_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS athletes_full_name_trgm_index');
        }

        Schema::table('athletes', function (Blueprint $table) {
            $table->dropIndex('athletes_enterprise_full_name_index');
            $table->dropIndex('athletes_enterprise_created_at_index');
            $table->dropIndex('athletes_owner_id_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_enterprise_id_index');
        });
    }
};
