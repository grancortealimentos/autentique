<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Audit extends Model
{
    const UPDATED_AT = null;

    protected $table = 'audits';

    protected $fillable = [
        'user_id',
        'api_client_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'before_data',
        'after_data',
        'ip_address',
        'user_agent',
        'correlation_id',
        'created_at',
    ];

    protected $casts = [
        'before_data' => 'array',
        'after_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class, 'api_client_id');
    }
}
