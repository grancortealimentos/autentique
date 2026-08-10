<?php

namespace App\DTOs;

final class UserData
{
    private function __construct(
        public readonly array $attributes,
    ) {}

    private const ALLOWED_FIELDS = [
        'person_id',
        'is_active',
        'name',
        'email',
        'email_verified_at',
        'password_changed_at',
        'force_password_change',
        'password',
        'previous_password',
        'password_reset_token',
        'password_reset_expires_at',
    ];

    public static function paraCriacao(array $data): self
    {
        $attributes = self::filter($data);
        $attributes['is_active'] = $data['is_active'] ?? true;
        return new self($attributes);
    }

    public static function paraEdicao(array $data): self
    {
        return new self(self::filter($data));
    }

    private static function filter(array $data): array
    {
        $attributes = [];
        foreach(self::ALLOWED_FIELDS as $field) {
            if(array_key_exists($field, $data)) {
                $attributes[$field] = $data[$field];
            }
        }

        return $attributes;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}