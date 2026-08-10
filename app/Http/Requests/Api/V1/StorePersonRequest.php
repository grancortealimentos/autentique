<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if($this->has('document')) {
            $this->merge([
                'document' => preg_replace('/\D/', '', (string) $this->input('document')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'person_type' => ['required', 'string', 'in:PF,PJ'],
            'document' => ['required', 'string', 'max:14', 'unique:persons,document'],
            'is_active' => ['sometimes', 'boolean'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:15'],
            'zip' => ['nullable', 'string', 'max:8'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:10'],
            'district' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'complement' => ['nullable', 'string', 'max:255'],
            'profile_picture_path' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'person_type.required' => 'O tipo de pessoa é obrigatório.',
            'person_type.in' => 'O tipo de pessoa deve ser PF ou PJ.',
            'document.required' => 'O documento é obrigatório.',
            'document.unique' => 'Já existe uma pessoa cadastrada com este documento.',
        ];
    }

    public function after(): array
    {
        return [
            function(Validator $validator) {
                $document = (string) $this->input('document');
                $type = (string) $this->input('person_type');
                if ($type === 'PF' && ! $this->documentoCpfValido($document)) {
                    $validator->errors()->add('document', 'CPF inválido.');
                }

                if ($type === 'PJ' && ! $this->documentoCnpjValido($document)) {
                    $validator->errors()->add('document', 'CNPJ inválido.');
                }
            }
        ];
    }

    // --- validação de dígito (portar para o trait ValidaDocumentos depois) ---
    private function documentoCpfValido(string $cpf): bool
    {
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }
 
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int) $cpf[$i] * (($t + 1) - $i);
            }
            $digito = ((10 * $soma) % 11) % 10;
            if ((int) $cpf[$t] !== $digito) {
                return false;
            }
        }
 
        return true;
    }
 
    private function documentoCnpjValido(string $cnpj): bool
    {
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $calcular = function (string $cnpj, int $tamanho): int {
            $soma = 0;
            $pos = $tamanho - 7;
            for ($i = $tamanho; $i >= 1; $i--) {
                $soma += (int) $cnpj[$tamanho - $i] * $pos--;
                if ($pos < 2) {
                    $pos = 9;
                }
            }
            $resultado = $soma % 11;
            return $resultado < 2 ? 0 : 11 - $resultado;
        };

        return (int) $cnpj[12] === $calcular($cnpj, 12)
            && (int) $cnpj[13] === $calcular($cnpj, 13);
    }
}