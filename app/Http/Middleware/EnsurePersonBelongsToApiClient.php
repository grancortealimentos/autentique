<?php

namespace App\Http\Middleware;

use App\Models\Person;
use App\Repositories\PersonRepository;
use App\Support\AuditContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePersonBelongsToApiClient
{
    public function __construct(
        private readonly AuditContext $context,
        private readonly PersonRepository $personRepository,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $person = $request->route('person');
        if($person instanceof Person) {
            $pertence = $this->personRepository->vinculoAtivoExiste(
                personId: $person->id,
                apiClientId: $this->context->apiClientId(),
            );

            if(!$pertence) {
                return response()->json(['message' => 'Pessoa não encontrada.'], 404);
            }
        }

        return $next($request);
    }
}
