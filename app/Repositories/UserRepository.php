<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository
{
    public function paginate(?string $term, int $perPage = 10): LengthAwarePaginator
    {
        return User::active()
            ->search($term)
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findById(int $id): ?User
    {
        return User::findOrFail($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::active()->where('email', $email)->first();
    }

    public function findByPerson(int $personId): ?User
    {
        return User::where('person_id', $personId)->first();
    }

    public function emailAlreadyRegistered(string $email, ?int $exceptId = null): bool
    {
        return User::where('email', $email)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }

    public function findByTokenReset(string $token): ?User
    {
        return User::where('password_reset_token', $token)->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->refresh();
    }

    public function changeStatus(User $user, bool $isActive): User
    {
        $user->update(['is_active' => $isActive]);
        return $user->refresh();
    }
}