<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * DTO de Person. Suporta dois modos:
 *  - Criação (paraCriacao): conjunto completo de dados.
 *  - Edição parcial (paraEdicao): carrega SOMENTE os campos presentes no request,
 *    para um PATCH nunca sobrescrever com null um campo que o cliente não enviou.
 */
final class PersonData
{
    private function __construct(
        public readonly array $attributes,
    ) {}

    /**
     * Criação: todos os campos são considerados (com defaults onde aplicável).
     */
    public static function paraCriacao(array $dados): self
    {
        return new self([
            'name' => $dados['name'],
            'person_type' => $dados['person_type'],
            'document' => $dados['document'],
            'is_active' => $dados['is_active'] ?? true,
            'email' => $dados['email'] ?? null,
            'phone' => $dados['phone'] ?? null,
            'zip' => $dados['zip'] ?? null,
            'street' => $dados['street'] ?? null,
            'number' => $dados['number'] ?? null,
            'district' => $dados['district'] ?? null,
            'city' => $dados['city'] ?? null,
            'state' => $dados['state'] ?? null,
            'complement' => $dados['complement'] ?? null,
            'profile_picture_path' => $dados['profile_picture_path'] ?? null,
        ]);
    }

    /**
     * Edição parcial: inclui apenas as chaves realmente enviadas.
     * Um campo ausente NÃO entra no array e, portanto, não é alterado no banco.
     */
    public static function paraEdicao(array $dados): self
    {
        $camposEditaveis = [
            'name', 
            'person_type', 
            'document', 
            'is_active',
            'email', 
            'phone', 
            'zip', 
            'street', 
            'number',
            'district', 
            'city', 
            'state', 
            'complement', 
            'profile_picture_path',
        ];

        // array_intersect_key preserva só o que veio; array_key_exists mantém nulls explícitos.
        $presentes = array_filter(
            $camposEditaveis,
            fn (string $campo) => array_key_exists($campo, $dados),
        );

        $attributes = [];
        foreach ($presentes as $campo) {
            $attributes[$campo] = $dados[$campo];
        }

        return new self($attributes);
    }

    /**
     * Saída canônica para o Repository: exatamente os campos a persistir.
     */
    public function toArray(): array
    {
        return $this->attributes;
    }
}