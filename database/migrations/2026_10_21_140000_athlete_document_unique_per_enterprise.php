<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Torna o CPF/CNPJ único POR EMPRESA (e não mais global).
 *
 * Antes: UNIQUE(document) — o mesmo CPF não podia existir em duas empresas
 * diferentes, o que não faz sentido num sistema multiempresa (a mesma pessoa
 * pode treinar em duas academias).
 *
 * Depois: UNIQUE(enterprise_id, document) WHERE deleted_at IS NULL.
 *   - composto por enterprise_id  -> separa a unicidade por empresa;
 *   - parcial (WHERE deleted_at IS NULL) -> um atleta na lixeira (soft delete)
 *     LIBERA o CPF, então a mesma pessoa pode ser recadastrada depois.
 *
 * O índice parcial é criado via SQL cru porque o schema builder do Laravel não
 * expressa a cláusula WHERE. A sintaxe usada é compatível com PostgreSQL e com
 * o SQLite usado nos testes.
 */
return new class extends Migration
{
    private string $newIndex = 'athletes_enterprise_document_unique';

    public function up(): void
    {
        // Remove o índice único global antigo (athletes_document_unique).
        Schema::table('athletes', function ($table) {
            $table->dropUnique(['document']);
        });

        DB::statement(
            "CREATE UNIQUE INDEX {$this->newIndex} "
            . 'ON athletes (enterprise_id, document) '
            . 'WHERE deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement("DROP INDEX {$this->newIndex}");

        Schema::table('athletes', function ($table) {
            $table->unique('document');
        });
    }
};
