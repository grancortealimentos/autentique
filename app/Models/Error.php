<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Error extends Model
{
    protected $table = 'error_logs';

    protected $fillable = [
        'user_id',
        'resolved_by',
        'is_resolved',
        'source',
        'level', 
        'exception_class',
        'message',
        'file',
        'line',
        'stack_trace',
        'http_method',
        'url',
        'route_name',
        'query_params',
        'request_payload',
        'request_headers',
        'ip_address',
        'user_agent',
        'app_module',
        'job_name',
        'correlation_id',
        'extra_data',
        'resolution_note',
        'resolved_at',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'request_payload' => 'array',
        'request_headers' => 'array',
        'extra_data' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
