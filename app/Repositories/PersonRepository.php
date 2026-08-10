<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Person;
use App\Models\PersonApiClient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PersonRepository
{
    public function paginateByApiClient(
        int $apiClientId, 
        ?string $search,
        int $perPage = 10
    ): LengthAwarePaginator
    {
        return Person::query()
            ->whereHas('apiClients', function ($query) use ($apiClientId) {
                $query->where('api_clients.id', $apiClientId)
                    ->where('person_api_clients.is_active', true);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                        ->orWhere('document', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findByDocument(string $document): ?Person
    {
        return Person::query()
            ->where('document', $document)
            ->first();
    }

    public function create(array $data): Person
    {
        return Person::create($data);
    }

    public function update(Person $person, array $data): Person
    {
        $person->update($data);
        return $person->refresh();
    }

    public function vinculoAtivoExiste(int $personId, int $apiClientId): bool
    {
        return PersonApiClient::query()
            ->where('person_id', $personId)
            ->where('api_client_id', $apiClientId)
            ->where('is_active', true)
            ->exists();
    }
}