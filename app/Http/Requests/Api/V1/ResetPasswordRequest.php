<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'O token de redefinição é obrigatório.',
            'password.required' => 'A nova senha é obrigatória.',
            'password.min' => 'A senha deve ter ao menos 8 caracteres.',
        ];
    }
}