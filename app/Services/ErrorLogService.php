<?php

namespace App\Services;

use App\Models\Error;
use App\Support\AuditContext;
use Illuminate\Support\Facades\App;
use Throwable;

class ErrorLogService
{
    private const CAMPOS_SENSIVEIS = [
        'password', 
        'previous_password', 
        'password_confirmation',
        'password_reset_token', 
        'token', 
        'api_key', 
        'secret',
    ];

    private const HEADERS_SENSIVEIS = [
        'authorization', 
        'x-api-key', 
        'cookie'
    ];

    public function __construct(
        private readonly AuditContext $context,
    ) {}

    // Grava o erro. NUNCA relança: se a própria gravação falhar, cai no log nativo.
    public function log(
        Throwable $e, 
        ?string $appModule = null, 
        ?array $extraData = null
    ): void
    {
        try
        {
            $isHttp = !App::runningInConsole();
            $request = $isHttp ? request() : null;

            Error::create([
                'user_id' => $this->context->userId(),
                'is_resolved' => false,
                'source' => $isHttp ? 'http' : 'console',
                'level' => 'error',
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => (string) $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
                'http_method' => $request?->method(),
                'url' => $request?->fullUrl(),
                'route_name' => $request?->route()?->getName(),
                'query_params' => $request ? json_encode($request->query()) : null,
                'request_payload' => $request ? $this->redigir($request->except(self::CAMPOS_SENSIVEIS)) : null,
                'request_headers' => $request ? $this->redigirHeaders($request->headers->all()) : null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'app_module' => $appModule,
                'job_name' => null,
                'correlation_id' => $this->context->correlationId(),
                'extra_data' => $extraData,
            ]);
        }
        catch(Throwable $falhaInterna) 
        {
            logger()->error('Falha ao gravar error_logs', [
                'erro_original' => $e->getMessage(),
                'falha_interna' => $falhaInterna->getMessage(),
            ]);
        }
    }

    // Substitui valores sensíveis remanescentes por [REDACTED] (defesa em profundidade).
    private function redigir(array $dados): array 
    {
        foreach ($dados as $chave => $valor) {
            if (in_array(strtolower((string) $chave), self::CAMPOS_SENSIVEIS, true)) {
                $dados[$chave] = '[REDACTED]';
            }
        }
        return $dados;
    }

    // Redige headers sensíveis (Authorization, X-API-KEY, Cookie).
    private function redigirHeaders(array $headers): array
    {
        foreach($headers as $chave => $valor) {
            if(in_array(strtolower($chave), self::HEADERS_SENSIVEIS, true)) {
                $headers[$chave] = ['[REDACTED]'];
            }
        }

        return $headers;
    }
}