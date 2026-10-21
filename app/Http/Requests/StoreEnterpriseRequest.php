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
            'owner_name' => 'nullable|string|max:255',
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
            'logo_path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'active' => 'nullable|boolean',
        ];
    }

    // Sem messages() próprio: as mensagens vêm da tradução central em
    // lang/pt_BR/validation.php (regras + nomes de campos no bloco attributes).
}
