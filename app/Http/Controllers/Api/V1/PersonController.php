<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\PersonData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePersonRequest;
use App\Http\Requests\Api\V1\UpdatePersonRequest;
use App\Models\Person;
use App\Repositories\PersonRepository;
use App\Services\PersonService;
use App\Support\AuditContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class PersonController extends Controller
{
    public function __construct(
        private readonly PersonService $personService,
        private readonly PersonRepository $personRepository,
        private readonly AuditContext $context,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $persons = $this->personRepository->paginateByApiClient(
            apiClientId: $this->context->apiClientId(),
            search: $request->query('search'),
        );

        return response()->json($persons);
    }

    public function show(Person $person): JsonResponse
    {
        return response()->json($person);
    }

    public function store(StorePersonRequest $request): JsonResponse
    {
        try
        {
            $person = $this->personService->create(
                data: PersonData::paraCriacao($request->validated()),
                apiClientId: $this->context->apiClientId(),
                grantedBy: $request->user()->id,
            );

            return response()->json($person, 201);
        }
        catch(ValidationException $e)
        {
            throw $e;
        }
        catch(Throwable $e)
        {
            report($e);
            return response()->json(['message' => 'Erro ao criar pessoa.'], 500);
        }
    }

    public function update(UpdatePersonRequest $request, Person $person): JsonResponse
    {
        try
        {
            $atualizada = $this->personService->update(
                person: $person,
                data: PersonData::paraEdicao($request->validated()),
            );

            return response()->json($atualizada);
        }
        catch(ValidationException $e)
        {
            throw $e;
        }
        catch(Throwable $e)
        {
            report($e);
            return response()->json(['message' => 'Erro ao atualizar pessoa.'], 500);
        }
    }

    public function link(Request $request, Person $person): JsonResponse
    {
        try
        {
            $vinculada = $this->personService->link(
                person: $person,
                apiClientId: $this->context->apiClientId(),
                grantedBy: $request->user()->id,
            );

            return response()->json($vinculada);
        }
        catch(ValidationException $e)
        {
            throw $e;
        }
        catch(Throwable $e)
        {
            report($e);
            return response()->json(['message' => 'Erro ao vincular pessoa.'], 500);
        }
    }

    public function unlink(Person $person, Request $request): JsonResponse
    {
        try
        {
            $this->personService->unlink(
                person: $person,
                apiClientId: $this->context->apiClientId(),
                revokedBy: $request->user()->id,
            );

            return response()->json(['message' => 'Pessoa desvinculada com sucesso.']);
        }
        catch(ValidationException $e)
        {
            throw $e;
        }
        catch(Throwable $e)
        {
            report($e);
            return response()->json(['message' => 'Erro ao vincular pessoa.'], 500);
        }
    }

    public function checkDocument(Request $request): JsonResponse
    {
        $document = preg_replace('/\D/', '', (string) $request->query('document', ''));
        if ($document === '') {
            return response()->json(['message' => 'Documento não informado.'], 422);
        }

        $resultado = $this->personService->verificarDocumento(
            document: $document,
            apiClientId: $this->context->apiClientId(),
        );
        if ($resultado['status'] === 'not_found') {
            return response()->json(['status' => 'not_found'], 404);
        }

        $person = $resultado['person'];

        return response()->json([
            'status' => $resultado['status'],
            'person' => [
                'id' => $person->id,
                'name' => $person->name,
                'document' => $person->document,
                'person_type' => $person->person_type,
            ],
        ]);
    }
}