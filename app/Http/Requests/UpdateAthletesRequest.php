<?php

namespace App\Http\Requests;

use App\Models\Athletes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAthletesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Verifica se o usuário pode atualizar este atleta específico
        return $this->user()->can('update', $this->route('athlete'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $athlete = $this->route('athlete');

        return [
            'full_name'     => 'nullable|string|max:255',
            'birth_date'    => 'nullable|date',
            'marital_status' => 'nullable|string|max:50',
            'gender'        => 'nullable|string|max:50',
            'document'      => [
                'nullable',
                'string',
                'max:18',
                Rule::unique('athletes')->ignore($athlete->id)
            ],
            'address'       => 'nullable|string|max:255',
            'number'        => 'nullable|string|max:10',
            'neighborhood'  => 'nullable|string|max:255',
            'zip_code'      => 'nullable|string|max:10',
            'state'         => 'nullable|string|max:100',
            'city'          => 'nullable|string|max:100',
            'mobile_phone'  => [
                'nullable',
                'string',
                'max:15',
                Rule::unique('athletes')->ignore($athlete->id)
            ],
            'secondary_phone' => 'nullable|string|max:15',
            'email'         => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('athletes')->ignore($athlete->id)
            ],
            'mother_name'   => 'nullable|string|max:255',
            'father_name'   => 'nullable|string|max:255',
            'owner_id'      => 'nullable|exists:users,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'document.unique' => 'CPF já cadastrado para outro atleta',
            'email.unique' => 'Email já cadastrado para outro atleta',
            'mobile_phone.unique' => 'Telefone já cadastrado para outro atleta',
            'owner_id.exists' => 'Usuário proprietário não encontrado',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Remove máscaras dos campos antes da validação
        if ($this->has('document')) {
            $this->merge([
                'document' => preg_replace('/[^0-9]/', '', $this->document),
            ]);
        }

        if ($this->has('mobile_phone')) {
            $this->merge([
                'mobile_phone' => preg_replace('/[^0-9]/', '', $this->mobile_phone),
            ]);
        }

        if ($this->has('zip_code')) {
            $this->merge([
                'zip_code' => preg_replace('/[^0-9]/', '', $this->zip_code),
            ]);
        }
    }
}
