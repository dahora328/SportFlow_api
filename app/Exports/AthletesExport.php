<?php

namespace App\Exports;

use App\Models\Athlete;
use App\Support\AthleteSpreadsheet;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exporta atletas para .xlsx.
 *
 * O escopo de empresa (enterprise_id) é aplicado automaticamente pelo
 * global scope do model Athlete, então aqui só precisamos do filtro de busca.
 */
class AthletesExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting, WithStyles, ShouldAutoSize
{
    public function __construct(private ?string $search = null)
    {
    }

    public function query(): Builder
    {
        $query = Athlete::query()->orderBy('full_name');

        if ($this->search !== null && $this->search !== '') {
            $query->whereRaw('LOWER(full_name) LIKE ?', ['%' . mb_strtolower($this->search) . '%']);
        }

        return $query;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return AthleteSpreadsheet::headings();
    }

    /**
     * @param  Athlete  $athlete
     * @return array<int, mixed>
     */
    public function map($athlete): array
    {
        $row = [];

        foreach (AthleteSpreadsheet::columns() as $field) {
            $value = $athlete->{$field};

            if ($field === 'birth_date' && !empty($value)) {
                // Mostra a data em dd/mm/aaaa (padrão brasileiro).
                $value = \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
            }

            // CPF sempre como texto, para não perder o zero à esquerda.
            if ($field === 'document' && $value !== null) {
                $value = (string) $value;
            }

            $row[] = $value;
        }

        return $row;
    }

    /**
     * Força a coluna do CPF a ser tratada como texto no Excel.
     *
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Cabeçalho em negrito.
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
