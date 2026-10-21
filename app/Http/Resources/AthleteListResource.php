<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Versão enxuta do atleta para a listagem (Home).
 * Os dados completos ficam no AthletesResource, usado em show/store/update.
 */
class AthleteListResource extends JsonResource
{
    /**
     * Colunas necessárias para montar a listagem.
     */
    public const COLUMNS = [
        'id',
        'full_name',
        'birth_date',
        'document',
        'owner_id',
        'created_at',
        'updated_at',
    ];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'birth_date' => $this->birth_date,
            'document' => $this->document,
            'owner_id' => $this->owner_id,
            'updated_at' => $this->updated_at,
        ];
    }
}
