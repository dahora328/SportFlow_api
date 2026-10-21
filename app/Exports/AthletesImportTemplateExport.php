<?php

namespace App\Exports;

use App\Support\AthleteSpreadsheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Modelo (template) oficial de importação de atletas.
 *
 * Gera a planilha com os cabeçalhos na ordem correta, uma linha de exemplo
 * e listas suspensas (data validation) para gênero, estado civil, posição e UF.
 */
class AthletesImportTemplateExport implements FromArray, WithHeadings, WithColumnFormatting, WithStyles, WithEvents, ShouldAutoSize
{
    private const EXAMPLE_ROWS = 300; // linhas com dropdown pré-aplicado

    /**
     * Uma linha de exemplo para orientar o preenchimento.
     *
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            [
                'João da Silva',          // Nome completo
                '15/03/2005',             // Data de nascimento
                '529.982.247-25',         // CPF (exemplo válido)
                'masculino',              // Gênero
                'solteiro',               // Estado civil
                'Meia',                   // Posição
                '(11) 98888-7777',        // Celular
                '',                       // Telefone secundário
                'joao@email.com',         // E-mail
                '01001-000',              // CEP
                'Praça da Sé',            // Endereço
                '100',                    // Número
                'Sé',                     // Bairro
                'São Paulo',              // Cidade
                'SP',                     // Estado (UF)
                'Maria da Silva',         // Nome da mãe
                'José da Silva',          // Nome do pai
                'Apague esta linha de exemplo antes de importar',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return AthleteSpreadsheet::headings();
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_TEXT, // CPF como texto
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $allowed = AthleteSpreadsheet::allowedValues();

                // Coluna (letra) de cada campo que tem lista suspensa.
                $dropdownColumns = [
                    'D' => $allowed['gender'],
                    'E' => $allowed['marital_status'],
                    'F' => $allowed['position'],
                    'O' => $allowed['state'],
                ];

                foreach ($dropdownColumns as $column => $options) {
                    for ($row = 2; $row <= self::EXAMPLE_ROWS; $row++) {
                        $validation = $sheet->getCell("{$column}{$row}")->getDataValidation();
                        $validation->setType(DataValidation::TYPE_LIST);
                        $validation->setErrorStyle(DataValidation::STYLE_STOP);
                        $validation->setAllowBlank(true);
                        $validation->setShowInputMessage(true);
                        $validation->setShowErrorMessage(true);
                        $validation->setShowDropDown(true);
                        $validation->setErrorTitle('Valor inválido');
                        $validation->setError('Escolha um valor da lista.');
                        $validation->setFormula1('"' . implode(',', $options) . '"');
                    }
                }
            },
        ];
    }
}
