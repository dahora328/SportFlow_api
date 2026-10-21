<?php
namespace App\Http\Requests;

use App\Models\Athlete;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\CpfCnpjRule;

class StoreAthletesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('create', Athlete::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name'       => 'required|string|max:255',
            'birth_date'      => 'required|date',
            'marital_status'  => 'required|string|max:50',
            'gender'          => 'required|string|max:50',
            'position'        => 'nullable|string|max:100',
            'document'        => [
                'required',
                'string',
                'max:18',
                new CpfCnpjRule(),
                // Único por empresa e ignorando atletas na lixeira (soft delete),
                // espelhando o índice UNIQUE(enterprise_id, document) WHERE deleted_at IS NULL.
                Rule::unique('athletes', 'document')->where(function ($query) {
                    return $query
                        ->where('enterprise_id', $this->user()->enterprise_id)
                        ->whereNull('deleted_at');
                }),
            ],
            'address'         => 'required|string|max:255',
            'number'          => 'required|string|max:10',
            'neighborhood'    => 'required|string|max:100',
            'zip_code'        => 'required|string|max:12',
            'state'           => 'required|string|max:2',
            'city'            => 'required|string|max:100',
            'mobile_phone'    => 'required|string|max:20',
            'secondary_phone' => 'nullable|string|max:20',

            'email'           => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('athletes')->where(function ($query) {
                    return $query
                        ->where('enterprise_id', $this->user()->enterprise_id)
                        ->whereNull('deleted_at');
                })
            ],
            'mother_name'     => 'nullable|string|max:255',
            'father_name'     => 'nullable|string|max:255',
            'photo_path'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'observations'    => 'nullable|string',
        ];
    }

    public function messages()
    {
        // As mensagens genéricas (required/string/max/date/email) vêm da
        // tradução central em lang/pt_BR/validation.php. Aqui fica apenas a
        // mensagem de domínio, mais clara que o "já está em uso." padrão.
        return [
            'document.unique' => 'Já existe um atleta cadastrado com este CPF/CNPJ.',
        ];
    }

    /**
     * Remove máscaras antes da validação, para que a checagem de unicidade
     * compare o documento no mesmo formato canônico (dígitos) em que ele é
     * gravado. Mesmo tratamento já usado no UpdateAthletesRequest.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('document')) {
            $this->merge([
                'document' => preg_replace('/\D/', '', (string) $this->document),
            ]);
        }

        if ($this->has('mobile_phone')) {
            $this->merge([
                'mobile_phone' => preg_replace('/\D/', '', (string) $this->mobile_phone),
            ]);
        }

        if ($this->has('zip_code')) {
            $this->merge([
                'zip_code' => preg_replace('/\D/', '', (string) $this->zip_code),
            ]);
        }
    }
}