<?php

namespace App\Services;

use App\Models\Audit;
use App\Support\AuditContext;

class AuditService
{
    public function __construct(
        private readonly AuditContext $context,
    ) {}

    public function register(
        string $action,
        string $entityType,
        string $entityId,
        ?array $beforeData = null,
        ?array $afterData = null,
        ?string $description = null,
    ): Audit
    {
        return Audit::create([
            'user_id' => $this->context->userId(),
            'api_client_id' => $this->context->apiClientId(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'before_data' => $beforeData,
            'after_data' => $afterData,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'correlation_id' => $this->context->correlationId(),
        ]);
    }
}