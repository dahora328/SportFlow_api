<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\CpfCnpjRule;

class StoreEnterpriseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'social_reason' =>  'required|string|max:255',
            'fantasy_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'document' => ['required', 'string', 'max:255', new CpfCnpjRule()],
            'foundation_date' => 'required|date',
            'IE' => 'nullable|string|max:255',
            'address' => 'required|string|max:255',
            'number' => 'required|string|max:255',
            'complement' => 'nullable|string|max:255',
            'neighborhood' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'zip_code' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'required|email|unique:enterprises,email',
            'logo_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'active' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'social_reason.required' => 'A razão social é obrigatória.',
            'fantasy_name.required' => 'O nome fantasia é obrigatório.',
            'owner_name.required' => 'O nome do proprietário é obrigatório.',
            'document.required' => 'O documento é obrigatório.',
            'foundation_date.required' => 'A data de fundação é obrigatória.',
            'IE.required' => 'O IE é obrigatório.',
            'address.required' => 'O endereço é obrigatório.',
            'number.required' => 'O número é obrigatório.',
            'neighborhood.required' => 'O bairro é obrigatório.',
            'city.required' => 'A cidade é obrigatória.',
            'state.required' => 'O estado é obrigatório.',
            'zip_code.required' => 'O código postal é obrigatório.',
            'phone.required' => 'O telefone é obrigatório.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email deve ser um endereço de email válido.',
            'email.unique' => 'O email já está em uso.',
            'logo_path.image' => 'O logo deve ser uma imagem válida.',
            'logo_path.mimes' => 'O logo deve ser um arquivo do tipo: jpeg, png, jpg, gif, svg.',
            'logo_path.max' => 'O logo não pode exceder 2048 kilobytes.',
            'active.required' => 'A situação é obrigatória.',
        ];
    }
}
