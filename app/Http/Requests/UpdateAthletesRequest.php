<?php

namespace App\Http\Requests;

use App\Models\Athletes;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAthletesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $athlete = Athletes::find($this->route('id'));
        if (!$athlete) {
            return false;
        }
        return $this->user()->can('update', $athlete);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name'     => 'sometimes|required|string|max:255',
            'birth_date'    => 'sometimes|required|date',
            'marital_status'=> 'sometimes|required|string|max:50',
            'gender'        => 'sometimes|required|string|max:50',
            'document'      => 'sometimes|required|string|max:18',
            'address'       => 'sometimes|required|string|max:255',
            'number'        => 'sometimes|required|string|max:10',
            'neighborhood'  => 'sometimes|required|string|max:255',
            'zip_code'     => 'sometimes|required|string|max:10',
            'state'        => 'sometimes|required|string|max:100',
            'city'        => 'sometimes|required|string|max:100',
            'mobile_phone' => 'sometimes|required|string|max:15',
            'secondary_phone' => 'sometimes|required|string|max:15',
            'email'       => 'sometimes|required|email|max:255',
            'mother_name' => 'sometimes|required|string|max:255',
            'father_name' => 'sometimes|required|string|max:255',
            'owner_id'   => 'sometimes|required|exists:users,id',
        ];
    }
}
