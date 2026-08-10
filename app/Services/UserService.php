<?php

namespace App\Services;

use App\DTOs\UserData;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function paginate(?string $term, int $perPage = 10): LengthAwarePaginator
    {
        return $this->userRepository->paginate($term, $perPage);
    }

    public function findById(int $id): User
    {
        return $this->userRepository->findById($id);
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $this->ensureEmailIsUnique($data['email']);
            $this->ensurePersonHasNoUser($data['person_id']);
            $data['force_password_change'] = true;
            $data['password_changed_at'] = now();
            $dto = UserData::paraCriacao($data);

            return $this->userRepository->create($dto->toArray());
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            if(array_key_exists('email', $data)) {
                $this->ensureEmailIsUnique($data['email'], $user->id);
            }

            if (array_key_exists('password', $data)) {
                $data['previous_password'] = $user->password;
                $data['password_changed_at'] = now();
                $data['force_password_change'] = false;
            }

            $dto = UserData::paraEdicao(data: $data);

            return $this->userRepository->update(user: $user, data: $dto->toArray());
        });
    }

    public function activate(User $user): User
    {
        return DB::transaction(function () use ($user) {
            return $this->userRepository->changeStatus($user, true);
        });
    }

    public function deactivate(User $user): User
    {
        return DB::transaction(function () use ($user) {
            return $this->userRepository->changeStatus($user, false);
        });
    }

    private function ensureEmailIsUnique(string $email, ?int $exceptId = null): void
    {
        if($this->userRepository->emailAlreadyRegistered($email, $exceptId)) {
            throw ValidationException::withMessages([
                'email' => 'Este e-mail já está em uso.',
            ]);
        }
    }

    private function ensurePersonHasNoUser(int $personId): void
    {
        if($this->userRepository->findByPerson($personId) !== null) {
            throw ValidationException::withMessages([
                'person_id' => 'Esta pessoa já possui um usuário.',
            ]);
        }
    }
}