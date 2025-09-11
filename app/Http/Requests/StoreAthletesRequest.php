<?php

namespace App\Http\Requests;

use App\Models\Athletes;
use Illuminate\Foundation\Http\FormRequest;

class StoreAthletesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('create', Athletes::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'marital_status' => 'required|string|max:50',
            'gender' => 'required|string|max:50',
            'document' => 'required|string|max:18',
            'address' => 'required|string|max:255',
            'number' => 'required|string|max:10',
            'neighborhood' => 'required|string|max:100',
            'zip_code' => 'required|string|max:12',
            'state' => 'required|string|max:2',
            'city' => 'required|string|max:100',
            'mobile_phone' => 'required|string|max:20',
            'secondary_phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'mother_name' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'owner_id' => 'required|exists:users,id',
        ];
    }


    public function messages()
    {
        return [
            // Campos obrigatórios
            'full_name.required' => 'O campo nome completo é obrigatório.',
            'birth_date.required' => 'O campo data de nascimento é obrigatório.',
            'marital_status.required' => 'O campo estado civil é obrigatório.',
            'gender.required' => 'O campo gênero é obrigatório.',
            'document.required' => 'O campo documento é obrigatório.',
            'address.required' => 'O campo endereço é obrigatório.',
            'number.required' => 'O campo número é obrigatório.',
            'neighborhood.required' => 'O campo bairro é obrigatório.',
            'zip_code.required' => 'O campo CEP é obrigatório.',
            'state.required' => 'O campo estado é obrigatório.',
            'city.required' => 'O campo cidade é obrigatório.',
            'mobile_phone.required' => 'O campo celular é obrigatório.',
            'owner_id.required' => 'O campo proprietário é obrigatório.',

            // Validações de tipo
            'full_name.string' => 'O nome completo deve ser um texto.',
            'marital_status.string' => 'O estado civil deve ser um texto.',
            'gender.string' => 'O gênero deve ser um texto.',
            'document.string' => 'O documento deve ser um texto.',
            'address.string' => 'O endereço deve ser um texto.',
            'number.string' => 'O número deve ser um texto.',
            'neighborhood.string' => 'O bairro deve ser um texto.',
            'zip_code.string' => 'O CEP deve ser um texto.',
            'state.string' => 'O estado deve ser um texto.',
            'city.string' => 'A cidade deve ser um texto.',
            'mobile_phone.string' => 'O celular deve ser um texto.',
            'secondary_phone.string' => 'O telefone secundário deve ser um texto.',
            'mother_name.string' => 'O nome da mãe deve ser um texto.',
            'father_name.string' => 'O nome do pai deve ser um texto.',

            // Validações de formato
            'birth_date.date' => 'A data de nascimento deve ser uma data válida.',
            'email.email' => 'O e-mail deve ser um endereço de e-mail válido.',

            // Validações de tamanho máximo
            'full_name.max' => 'O nome completo não pode ter mais de 255 caracteres.',
            'marital_status.max' => 'O estado civil não pode ter mais de 50 caracteres.',
            'gender.max' => 'O gênero não pode ter mais de 50 caracteres.',
            'document.max' => 'O documento não pode ter mais de 18 caracteres.',
            'address.max' => 'O endereço não pode ter mais de 255 caracteres.',
            'number.max' => 'O número não pode ter mais de 10 caracteres.',
            'neighborhood.max' => 'O bairro não pode ter mais de 100 caracteres.',
            'zip_code.max' => 'O CEP não pode ter mais de 12 caracteres.',
            'state.max' => 'O estado não pode ter mais de 2 caracteres.',
            'city.max' => 'A cidade não pode ter mais de 100 caracteres.',
            'mobile_phone.max' => 'O celular não pode ter mais de 20 caracteres.',
            'secondary_phone.max' => 'O telefone secundário não pode ter mais de 20 caracteres.',
            'email.max' => 'O e-mail não pode ter mais de 255 caracteres.',
            'mother_name.max' => 'O nome da mãe não pode ter mais de 255 caracteres.',
            'father_name.max' => 'O nome do pai não pode ter mais de 255 caracteres.',

            // Validação de existência
            'owner_id.exists' => 'O proprietário selecionado é inválido.'
        ];
    }
}
