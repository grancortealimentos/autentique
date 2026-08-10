<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens, Auditable;

    protected $table = 'users';

    protected $fillable = [
        'person_id',
        'is_active', //ativo ou inativo
        'name',
        'email',
        'password',
        'force_password_change', //força a troca de senha
        'previous_password', // grava a senha anterior
        'password_reset_token', 
        'password_reset_expires_at', //data de quando precisa alterar a senha
        'password_changed_at', //data quando a senha foi alterada
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'previous_password',
        'password_reset_token',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'password' => 'hashed',
        'previous_password' => 'hashed',
        'force_password_change' => 'boolean',
        'password_reset_expires_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'password_changed_at' => 'datetime',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if(blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'ilike', "%{$term}%")
                ->orWhere('email', 'ilike', "%{$term}%");
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
