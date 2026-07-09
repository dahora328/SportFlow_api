<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnterpriseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'social_reason' => $this->social_reason,
            'fantasy_name' => $this->fantasy_name,
            'owner_name' => $this->owner_name,
            'document' => $this->document,
            'foundation_date' => $this->foundation_date,
            'IE' => $this->IE,
            'address' => $this->address,
            'number' => $this->number,
            'complement' => $this->complement,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'phone' => $this->phone,
            'email' => $this->email,
            'logo_path' => $this->logo_path,
            'active' => $this->active,
        ];
    }
}
