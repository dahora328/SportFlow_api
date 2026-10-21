<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Padroniza a coluna athletes.document para o formato canônico (só dígitos).
 *
 * Antes desta mudança, o cadastro pela tela gravava o CPF mascarado
 * (ex.: 529.982.247-25) enquanto a edição e a importação gravavam só dígitos.
 * Isso fazia o índice UNIQUE(document) tratar o mesmo CPF em formatos
 * diferentes como valores distintos.
 *
 * A migration remove as máscaras dos registros existentes. ANTES de alterar,
 * ela verifica se a normalização criaria duplicados (mesmo CPF já gravado em
 * dois formatos). Se encontrar, ABORTA sem alterar nada e lista os ids, para
 * que um humano reconcilie os registros manualmente — nunca mescla sozinha.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        $collisions = $this->findCollisions($driver);

        if (!empty($collisions)) {
            $detail = collect($collisions)
                ->map(fn ($c) => "documento {$c->d} nos ids [{$c->ids}]")
                ->implode('; ');

            throw new \RuntimeException(
                'Normalização de CPF abortada: há registros com o mesmo documento '
                . 'em formatos diferentes. Reconcilie-os antes de migrar. Detalhes: '
                . $detail
            );
        }

        if ($driver === 'pgsql') {
            DB::statement(
                "UPDATE athletes SET document = regexp_replace(document, '[^0-9]', '', 'g') "
                . "WHERE document ~ '[^0-9]'"
            );
            return;
        }

        // Fallback portátil (sqlite/mysql): normaliza linha a linha.
        DB::table('athletes')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                $digits = preg_replace('/\D/', '', (string) $row->document);
                if ($digits !== (string) $row->document) {
                    DB::table('athletes')->where('id', $row->id)->update(['document' => $digits]);
                }
            }
        });
    }

    public function down(): void
    {
        // Irreversível: as máscaras originais não são recuperáveis.
        // Documento permanece em dígitos (formato canônico), o que é inofensivo.
    }

    /**
     * Retorna os grupos de documentos que, normalizados, colidiriam.
     *
     * @return array<int, object>
     */
    private function findCollisions(string $driver): array
    {
        // A unicidade do CPF é POR EMPRESA e ignora registros na lixeira
        // (soft delete), então a colisão também é avaliada nesse mesmo escopo:
        // só aborta se a normalização criar duplicado dentro da mesma empresa
        // entre atletas ativos.
        if ($driver === 'pgsql') {
            return DB::select(
                "SELECT regexp_replace(document, '[^0-9]', '', 'g') AS d, "
                . "COUNT(*) AS c, string_agg(id::text, ',') AS ids "
                . 'FROM athletes WHERE deleted_at IS NULL '
                . "GROUP BY enterprise_id, regexp_replace(document, '[^0-9]', '', 'g') "
                . 'HAVING COUNT(*) > 1'
            );
        }

        // Fallback em PHP para os demais drivers.
        $map = [];
        DB::table('athletes')->select('id', 'document', 'enterprise_id')
            ->whereNull('deleted_at')->orderBy('id')
            ->chunkById(500, function ($rows) use (&$map) {
                foreach ($rows as $row) {
                    $digits = preg_replace('/\D/', '', (string) $row->document);
                    $key = $row->enterprise_id . '|' . $digits;
                    $map[$key][] = $row->id;
                }
            });

        $collisions = [];
        foreach ($map as $key => $ids) {
            if (count($ids) > 1) {
                // $key = "<enterprise_id>|<digits>"; mostra só os dígitos.
                $digits = substr($key, strpos($key, '|') + 1);
                $collisions[] = (object) ['d' => $digits, 'c' => count($ids), 'ids' => implode(',', $ids)];
            }
        }

        return $collisions;
    }
};
