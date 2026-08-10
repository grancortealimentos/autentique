<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiClient extends Model
{
    use Auditable;

    protected $table = 'api_clients';

    protected $fillable = [
        'is_active',
        'name',
        'url',
    ];

    protected $hidden = [
        'api_key_hash',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function personApiClients(): HasMany
    {
        return $this->hasMany(PersonApiClient::class, 'api_client_id');
    }
}
