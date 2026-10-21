<?php

namespace App\Imports;

use App\Models\Athlete;
use App\Rules\CpfCnpjRule;
use App\Support\AthleteSpreadsheet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToArray;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Importa atletas de .csv ou .xlsx seguindo o template oficial.
 *
 * Lê as linhas cruas (sem WithHeadingRow) para controlar o mapeamento de
 * cabeçalhos, tratar CPF/CEP com zero à esquerda e validar cada linha,
 * acumulando os erros para devolver um resumo ao usuário.
 */
class AthletesImport implements ToArray
{
    private int $imported = 0;

    /** @var array<int, array{line:int, errors:array<int,string>}> */
    private array $errors = [];

    public function importedCount(): int
    {
        return $this->imported;
    }

    /**
     * @return array<int, array{line:int, errors:array<int,string>}>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function array(array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        // 1ª linha = cabeçalho. Mapeia cada coluna (índice) para o campo.
        $header = array_shift($rows);
        $map = AthleteSpreadsheet::headerToField();

        $columnFields = [];
        foreach ($header as $i => $label) {
            $normalized = AthleteSpreadsheet::normalizeHeader((string) $label);
            if (isset($map[$normalized])) {
                $columnFields[$i] = $map[$normalized];
            }
        }

        if (empty($columnFields)) {
            $this->errors[] = [
                'line'   => 1,
                'errors' => ['Nenhuma coluna reconhecida. Baixe o modelo e use os mesmos cabeçalhos.'],
            ];
            return;
        }

        $seenDocuments = []; // duplicados dentro do próprio arquivo

        foreach ($rows as $index => $row) {
            $line = $index + 2; // +1 do cabeçalho, +1 porque a planilha é 1-based

            $data = [];
            foreach ($columnFields as $i => $field) {
                $data[$field] = $this->cell($row[$i] ?? null);
            }

            // Pula linhas totalmente vazias (sobras no fim da planilha).
            if (collect($data)->filter(fn ($v) => $v !== null && $v !== '')->isEmpty()) {
                continue;
            }

            // Normalizações brasileiras.
            if (array_key_exists('birth_date', $data)) {
                $data['birth_date'] = $this->parseDate($data['birth_date'] ?? null);
            }
            if (!empty($data['document'])) {
                $data['document'] = $this->normalizeDocument($data['document']);
            }
            if (!empty($data['zip_code'])) {
                $data['zip_code'] = $this->normalizeZip($data['zip_code']);
            }

            $validator = Validator::make($data, $this->rules(), $this->messages());

            if ($validator->fails()) {
                $this->errors[] = [
                    'line'   => $line,
                    'errors' => $validator->errors()->all(),
                ];
                continue;
            }

            // Duplicado dentro do próprio arquivo.
            $docDigits = preg_replace('/\D/', '', (string) $data['document']);
            if (isset($seenDocuments[$docDigits])) {
                $this->errors[] = [
                    'line'   => $line,
                    'errors' => ["CPF/CNPJ duplicado dentro do arquivo (linha {$seenDocuments[$docDigits]})."],
                ];
                continue;
            }

            // Duplicado já existente no banco (o global scope limita à empresa).
            if ($this->documentAlreadyExists($docDigits)) {
                $this->errors[] = [
                    'line'   => $line,
                    'errors' => ['Já existe um atleta cadastrado com este CPF/CNPJ.'],
                ];
                continue;
            }

            if (!empty($data['email']) && $this->emailAlreadyExists($data['email'])) {
                $this->errors[] = [
                    'line'   => $line,
                    'errors' => ['Já existe um atleta cadastrado com este e-mail.'],
                ];
                continue;
            }

            // owner_id e enterprise_id são preenchidos pelo booted() do model.
            Athlete::create($data);
            $this->imported++;
            $seenDocuments[$docDigits] = $line;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'full_name'       => 'required|string|max:255',
            'birth_date'      => 'required|date',
            'marital_status'  => 'required|string|max:50',
            'gender'          => 'required|string|max:50',
            'position'        => 'nullable|string|max:100',
            'document'        => ['required', 'string', 'max:18', new CpfCnpjRule()],
            'address'         => 'required|string|max:255',
            'number'          => 'required|string|max:10',
            'neighborhood'    => 'required|string|max:100',
            'zip_code'        => 'required|string|max:12',
            'state'           => 'required|string|max:2',
            'city'            => 'required|string|max:100',
            'mobile_phone'    => 'required|string|max:20',
            'secondary_phone' => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:255',
            'mother_name'     => 'nullable|string|max:255',
            'father_name'     => 'nullable|string|max:255',
            'observations'    => 'nullable|string',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'full_name.required'     => 'Nome completo é obrigatório.',
            'birth_date.required'    => 'Data de nascimento é obrigatória.',
            'birth_date.date'        => 'Data de nascimento inválida (use dd/mm/aaaa).',
            'marital_status.required' => 'Estado civil é obrigatório.',
            'gender.required'        => 'Gênero é obrigatório.',
            'document.required'      => 'CPF/CNPJ é obrigatório.',
            'address.required'       => 'Endereço é obrigatório.',
            'number.required'        => 'Número é obrigatório.',
            'neighborhood.required'  => 'Bairro é obrigatório.',
            'zip_code.required'      => 'CEP é obrigatório.',
            'state.required'         => 'Estado (UF) é obrigatório.',
            'state.max'              => 'Estado (UF) deve ter 2 letras (ex.: SP).',
            'city.required'          => 'Cidade é obrigatória.',
            'mobile_phone.required'  => 'Celular é obrigatório.',
            'email.email'            => 'E-mail inválido.',
        ];
    }

    /**
     * Converte um valor de célula em string limpa (ou null se vazio).
     */
    private function cell(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Números inteiros vindos do Excel não devem virar "100.0".
        if (is_float($value) && floor($value) === $value) {
            $value = (int) $value;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Interpreta datas vindas como serial do Excel ou texto (dd/mm/aaaa, etc.).
     */
    private function parseDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                // segue para o parse textual
            }
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y'] as $format) {
            $dt = \DateTime::createFromFormat($format, $value);
            if ($dt !== false && $dt->format($format) === $value) {
                return $dt->format('Y-m-d');
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return $value; // deixa o validador acusar o erro
        }
    }

    /**
     * CPF/CNPJ sempre como dígitos, recuperando zeros à esquerda perdidos
     * quando o Excel tratou o campo como número.
     */
    private function normalizeDocument(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value);

        if ($digits === '') {
            return $value;
        }

        if (strlen($digits) <= 11) {
            return str_pad($digits, 11, '0', STR_PAD_LEFT);
        }

        if (strlen($digits) <= 14) {
            return str_pad($digits, 14, '0', STR_PAD_LEFT);
        }

        return $digits;
    }

    /**
     * Recupera o zero à esquerda do CEP (ex.: 1001000 -> 01001000).
     */
    private function normalizeZip(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value);

        if ($digits !== '' && strlen($digits) <= 8) {
            return str_pad($digits, 8, '0', STR_PAD_LEFT);
        }

        return $value;
    }

    private function documentAlreadyExists(string $docDigits): bool
    {
        if ($docDigits === '') {
            return false;
        }

        $driver = Athlete::query()->getConnection()->getDriverName();

        // No Postgres (produção) compara ignorando a máscara direto no banco.
        if ($driver === 'pgsql') {
            return Athlete::whereRaw(
                "regexp_replace(document, '[^0-9]', '', 'g') = ?",
                [$docDigits]
            )->exists();
        }

        // Fallback portátil: compara contra as formas usuais (com e sem máscara).
        return Athlete::whereIn('document', $this->documentVariants($docDigits))->exists();
    }

    /**
     * Gera as formas usuais de um documento (apenas dígitos e mascarado) para
     * comparação em bancos sem regexp_replace.
     *
     * @return array<int, string>
     */
    private function documentVariants(string $digits): array
    {
        $variants = [$digits];

        if (strlen($digits) === 11) {
            $variants[] = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits);
        } elseif (strlen($digits) === 14) {
            $variants[] = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $digits);
        }

        return array_unique($variants);
    }

    private function emailAlreadyExists(string $email): bool
    {
        return Athlete::where('email', $email)->exists();
    }
}
