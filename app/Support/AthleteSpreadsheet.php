<?php

namespace App\Support;

/**
 * Definição ÚNICA das colunas da planilha de atletas.
 *
 * Export, template de importação e importação usam esta mesma fonte para
 * garantir que a ordem e os nomes das colunas nunca divirjam entre si
 * (o template que o usuário baixa é exatamente o que o export produz).
 */
class AthleteSpreadsheet
{
    /**
     * Colunas na ordem oficial: [rótulo visível => campo no banco].
     *
     * @return array<string, string>
     */
    public static function columns(): array
    {
        return [
            'Nome completo'       => 'full_name',
            'Data de nascimento'  => 'birth_date',
            'CPF'                 => 'document',
            'Gênero'              => 'gender',
            'Estado civil'        => 'marital_status',
            'Posição'             => 'position',
            'Celular'             => 'mobile_phone',
            'Telefone secundário' => 'secondary_phone',
            'E-mail'              => 'email',
            'CEP'                 => 'zip_code',
            'Endereço'            => 'address',
            'Número'              => 'number',
            'Bairro'              => 'neighborhood',
            'Cidade'              => 'city',
            'Estado (UF)'         => 'state',
            'Nome da mãe'         => 'mother_name',
            'Nome do pai'         => 'father_name',
            'Observações'         => 'observations',
        ];
    }

    /**
     * Rótulos das colunas na ordem (cabeçalho da planilha).
     *
     * @return array<int, string>
     */
    public static function headings(): array
    {
        return array_keys(self::columns());
    }

    /**
     * Valores aceitos em cada campo de lista (usados nos dropdowns do
     * template e também na importação). O VALOR armazenado é o da esquerda.
     *
     * @return array<string, array<int, string>>
     */
    public static function allowedValues(): array
    {
        return [
            'gender'         => ['masculino', 'feminino', 'outro'],
            'marital_status' => ['solteiro', 'casado', 'divorciado', 'viuvo'],
            'position'       => ['Goleiro', 'Zagueiro', 'Lateral', 'Volante', 'Meia', 'Atacante', 'Outro'],
            'state'          => [
                'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
                'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
                'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
            ],
        ];
    }

    /**
     * Normaliza um cabeçalho vindo do arquivo para comparação robusta:
     * minúsculas, sem acento e com "_" no lugar de separadores.
     * Ex.: "Nome completo" -> "nome_completo"; "Estado (UF)" -> "estado_uf".
     */
    public static function normalizeHeader(string $header): string
    {
        $value = mb_strtolower(trim($header));
        // Remove acentos de forma independente de locale/intl.
        $value = strtr($value, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ]);
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);

        return trim((string) $value, '_');
    }

    /**
     * Mapa [cabeçalho normalizado => campo], incluindo alguns apelidos comuns
     * para que a importação continue funcionando mesmo com pequenas variações.
     *
     * @return array<string, string>
     */
    public static function headerToField(): array
    {
        $map = [];

        foreach (self::columns() as $label => $field) {
            $map[self::normalizeHeader($label)] = $field;
            // O próprio nome do campo também é aceito como cabeçalho.
            $map[$field] = $field;
        }

        // Apelidos tolerantes a variações de digitação.
        $aliases = [
            'nome'            => 'full_name',
            'nome_do_atleta'  => 'full_name',
            'nascimento'      => 'birth_date',
            'data_nascimento' => 'birth_date',
            'documento'       => 'document',
            'sexo'            => 'gender',
            'genero'          => 'gender',
            'estado'          => 'state',
            'uf'              => 'state',
            'telefone'        => 'mobile_phone',
            'celular'         => 'mobile_phone',
            'telefone2'       => 'secondary_phone',
            'email'           => 'email',
            'e_mail'          => 'email',
            'cep'             => 'zip_code',
            'posicao'         => 'position',
            'mae'             => 'mother_name',
            'pai'             => 'father_name',
            'obs'             => 'observations',
            'observacao'      => 'observations',
        ];

        return array_merge($aliases, $map);
    }
}
