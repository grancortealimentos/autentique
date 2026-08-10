<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Contexto de auditoria da requisição atual.
 * Guarda 'quem' (user) e "de qual aplicação" (api_client) originou as ações,
 * para o trait não precisar acessar HTTP diretamente.
*/
class AuditContext
{
    private ?int $userId = null;
    private ?int $apiClientId = null;
    private ?string $correlationId = null;

    //Define explicitamente o usuário autor (ex.: logo após um login bem-sucedido)
    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    //Autor da ação: o que foi explicitamente ou, na falta, o do token Sanctum
    public function userId(): ?int
    {
        return $this->userId ?? Auth::id();
    }

    //Define a aplicação cliente (resolvida da API Key pelo middleware)
    public function setApiClientId(?int $apiClientId): void
    {
        $this->apiClientId = $apiClientId;
    }

    //Aplicação que originou a requisição 
    public function apiClientId(): ?int
    {
        return $this->apiClientId;
    }

    //Permite herdar um correlation_id vindo de outro serviço (via header)
    public function setCorrelationId(?string $correlationId): void
    {
        $this->correlationId = $correlationId;
    }

    //Correlation_id da requisição; gera um ULID na primeira chamada se não houver.
    public function correlationId(): string
    {
        return $this->correlationId ??= (string) Str::ulid();
    }
}