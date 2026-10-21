<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Índices de desempenho adicionais (revisão ampla de consultas).
 *
 * 1. users.refresh_token — AuthController::refresh() procura o usuário por
 *    esse hash a cada renovação de token (consulta frequente). Sem índice,
 *    era uma varredura da tabela inteira de usuários.
 *
 * 2. athletes (enterprise_id, email) — a checagem de e-mail duplicado roda
 *    uma vez por linha na importação e na validação de cadastro/edição. O
 *    índice é parcial (WHERE deleted_at IS NULL) para casar com o escopo de
 *    empresa + soft delete usado nessas consultas, igual aos demais índices
 *    de atletas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('refresh_token', 'users_refresh_token_index');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE INDEX athletes_enterprise_email_index '
                . 'ON athletes (enterprise_id, email) '
                . 'WHERE deleted_at IS NULL'
            );
        } else {
            Schema::table('athletes', function (Blueprint $table) {
                $table->index(['enterprise_id', 'email'], 'athletes_enterprise_email_index');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS athletes_enterprise_email_index');
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_refresh_token_index');
            });
            return;
        }

        Schema::table('athletes', function (Blueprint $table) {
            $table->dropIndex('athletes_enterprise_email_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_refresh_token_index');
        });
    }
};
