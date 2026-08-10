<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'persons';

    protected $fillable = [
        'is_active',
        'profile_picture_path',
        'name',
        'person_type',
        'document',
        'email',
        'phone',
        'zip',
        'street',
        'number',
        'district',
        'city',
        'state',
        'complement',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function apiClients(): BelongsToMany
    {
        return $this->belongsToMany(ApiClient::class, 'person_api_clients')
            ->withPivot(['is_active', 'granted_by', 'revoked_by', 'revoked_at']);
    }
}
