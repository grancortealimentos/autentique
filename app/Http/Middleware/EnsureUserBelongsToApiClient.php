<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Repositories\PersonRepository;
use App\Support\AuditContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToApiClient
{
    public function __construct(
        private readonly AuditContext $context,
        private readonly PersonRepository $personRepository,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->route('user');
        if($user instanceof User) {
            $pertence = $this->personRepository->vinculoAtivoExiste(
                personId: $user->person_id,
                apiClientId: $this->context->apiClientId(),
            );

            if(!$pertence) {
                return response()->json([
                    'message' => 'Usuário não encontrado.'
                ], 404);
            }
        }

        return $next($request);
    }
}
