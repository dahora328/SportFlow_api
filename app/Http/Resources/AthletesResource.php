<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AthletesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $photoUrl = null;
        if ($this->photo_path) {
            $photoUrl = $request->getSchemeAndHttpHost() . Storage::url($this->photo_path);
        }

        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'birth_date' => $this->birth_date,
            'marital_status' => $this->marital_status,
            'gender' => $this->gender,
            'position' => $this->position,
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
            'photo_path' => $this->photo_path,
            'observations' => $this->observations,
            'photo_url' => $photoUrl,
            'path_photo' => $photoUrl,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
