<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\PersonData;
use App\Models\Person;
use App\Models\PersonApiClient;
use App\Repositories\PersonRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PersonService
{
    public function __construct(
        private readonly PersonRepository $personRepository
    ) {}

    //cria uma pessoa e vincula automaticamente a um aplicação
    public function create(
        PersonData $data,
        int $apiClientId,
        int $grantedBy,
    ): Person
    {
        return DB::transaction(function () use ($data, $apiClientId, $grantedBy) {
            $this->garantirDocumentoUnico($data->attributes['document']);

            $person = $this->personRepository->create($data->toArray());

            $this->vincular(
                personId: $person->id,
                apiClientId: $apiClientId,
                grantedBy: $grantedBy,
            );

            return $person;
        });
    }

    //atualiza os dados cadastrais de uma pessoa existente.
    public function update(Person $person, PersonData $data): Person
    {
        return DB::transaction(function () use ($person, $data) {
            return $this->personRepository->update($person, $data->toArray());
        });
    }

    /**
     * vincula uma pessoa jpa existente a aplicação solicitada
     * permitindo ajustar os dados no mesmo passo
    */
    public function link(
        Person $person, 
        PersonData $data, 
        int $apiClientId, 
        int $grantedBy
    ): Person
    {
        return DB::transaction(function () use ($person, $data, $apiClientId, $grantedBy) {
            $vinculoExistente = PersonApiClient::query()
                ->where('person_id',$person->id)
                ->where('api_client_id', $apiClientId)
                ->first();

            if($vinculoExistente && $vinculoExistente->is_active) {
                throw ValidationException::withMessages([
                    'person' => 'Esta pessoa já está vinculada a esta aplicação.',
                ]);
            }

            $this->personRepository->update($person, $data->toArray());
            if($vinculoExistente) {
                $vinculoExistente->update([
                    'is_active' => true,
                    'granted_by' => $grantedBy,
                    'revoked_by' => null,
                    'revoked_at' => null,
                ]);
            }
            else {
                $this->vincular(
                    personId: $person->id,
                    apiClientId: $apiClientId,
                    grantedBy: $grantedBy,
                );
            }

            return $person->refresh();
        });
    }

    /**
     * 
    */
    public function unlink(
        Person $person,
        int $apiClientId,
        int $revokedBy,
    ): void
    {
        DB::transaction(function () use ($person, $apiClientId, $revokedBy) {
            $vinculo = PersonApiClient::query()
                ->where('person_id', $person->id)
                ->where('api_client_id', $apiClientId)
                ->where('is_active', true)
                ->first();

            if(!$vinculo) {
                throw ValidationException::withMessages([
                    'person' => 'Esta pessoa não está vinculada a esta aplicação.',
                ]);
            }

            $vinculo->update([
                'is_active' => false,
                'revoked_by' => $revokedBy,
                'revoked_at' => now(),
            ]);
        });
    }

    public function verificarDocumento(string $document, int $apiClientId): array
    {
        $person = $this->personRepository->findByDocument($document);
        if(!$person) {
            return ['status' => 'not_found', 'person' => null];
        }

        $vinculadoAqui = $this->personRepository->vinculoAtivoExiste(
            personId: $person->id,
            apiClientId: $apiClientId,
        );

        return [
            'status' => $vinculadoAqui ? 'linked_here' : 'exists_unlinked',
            'person' => $person,
        ];
    }

    private function vincular(
        int $personId,
        int $apiClientId,
        int $grantedBy,
    ): PersonApiClient
    {
        return PersonApiClient::create([
            'person_id' => $personId,
            'api_client_id' => $apiClientId,
            'is_active' => true,
            'granted_by' => $grantedBy,
            'created_at' => now(),
        ]);
    }

    private function garantirDocumentoUnico(string $document): void
    {
        $existe = Person::query()
            ->where('document', $document)
            ->exists();

        if($existe) {
            throw ValidationException::withMessages([
                'document' => 'Já existe uma pessoa cadastrada com este documento.',
            ]);
        }
    }
}