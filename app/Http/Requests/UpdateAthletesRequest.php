<?php

namespace App\Http\Requests;

use App\Models\Athlete;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\CpfCnpjRule;
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
        $athlete = $this->route('athlete') ? $this->route('athlete')->id : null;

        return [
            'full_name'     => 'nullable|string|max:255',
            'birth_date'    => 'nullable|date',
            'marital_status' => 'nullable|string|max:50',
            'gender'        => 'nullable|string|max:50',
            'position'      => 'nullable|string|max:100',
            'document'      => [
                'nullable',
                'string',
                'max:18',
                new CpfCnpjRule(),
                // Único por empresa, ignorando a lixeira e o próprio registro,
                // espelhando o índice UNIQUE(enterprise_id, document) WHERE deleted_at IS NULL.
                Rule::unique('athletes', 'document')
                    ->ignore($athlete)
                    ->where(function ($query) {
                        return $query
                            ->where('enterprise_id', $this->user()->enterprise_id)
                            ->whereNull('deleted_at');
                    }),
            ],
            'address'       => 'nullable|string|max:255',
            'number'        => 'nullable|string|max:10',
            'neighborhood'  => 'nullable|string|max:255',
            'zip_code'      => 'nullable|string|max:10',
            'state'         => 'nullable|string|max:100',
            'city'          => 'nullable|string|max:100',
            'mobile_phone'  => 'nullable|string|max:15',
            'secondary_phone' => 'nullable|string|max:15',
            'email'         => 'nullable|email|max:255',
            'mother_name'   => 'nullable|string|max:255',
            'father_name'   => 'nullable|string|max:255',
            'photo_path'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'observations'  => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        // Só a mensagem de domínio do CPF duplicado; o restante vem da
        // tradução central (lang/pt_BR). As entradas de email/mobile_phone/
        // owner_id foram removidas por não terem regra correspondente.
        return [
            'document.unique' => 'Já existe um atleta cadastrado com este CPF/CNPJ.',
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
