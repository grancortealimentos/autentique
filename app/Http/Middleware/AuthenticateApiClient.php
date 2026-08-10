<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use App\Support\AuditContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiClient
{
    public function __construct(
        private readonly AuditContext $context,
    ) {}
    
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY');
        if(empty($apiKey)) {
            return response()->json(['message' => 'API Key ausente.'], 401);
        }

        $apiClient = ApiClient::query()
            ->where('is_active', true)
            ->where('api_key_hash', hash('sha256', $apiKey))
            ->first();
        if($apiClient === null) {
            return response()->json(['message' => 'API KEY inválida.'], 401);
        }

        $this->context->setApiClientId($apiClient->id);

        return $next($request);
    }
}
