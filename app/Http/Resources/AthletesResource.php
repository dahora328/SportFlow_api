<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AthletesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'full_name' => $this->full_name,
            'birth_date' => $this->birth_date,
            'marital_status' => $this->marital_status,
            'gender' => $this->gender,
            'document' => $this->document,
            'address' => $this->address,
            'number' => $this->number,
            'neighborhood' => $this->neighborhood,
            'zip_code' => $this->zip_code,
            'state' => $this->state,
            'city' => $this->city,
            'mobile_phone' => $this->mobile_phone,
            'secondary_phone' => $this->secondary_phone,
            'email' => $this->email,
            'mother_name' => $this->mother_name,
            'father_name' => $this->father_name,
            'owner_id' => $this->owner_id,
        ];
    }
}
