<?php

namespace App\Services;

use App\DTOs\UserData;
use App\Models\User;
use App\Repositories\PersonRepository;
use App\Repositories\UserRepository;
use App\Support\AuditContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PersonRepository $personRepository,
        private readonly AuditContext $context,
    ) {}

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);
        if($user === null || !Hash::check($password, $user->password)) {
            $this->falharCredencial();
        }

        $pertence = $this->personRepository->vinculoAtivoExiste(
            $user->person_id,
            $this->context->apiClientId()
        );
        if(!$pertence) {
            $this->falharCredencial();
        }

        $plainTextToken = $user->createToken(
            name: 'api-client-' . $this->context->apiClientId(),
        )->plainTextToken;

        return [
            'user' => $user,
            'token' => $plainTextToken,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function forgotPassword(string $email): ?string
    {
        $user = $this->userRepository->findByEmail($email);
        if($user === null) {
            return null;
        }

        $pertence = $this->personRepository->vinculoAtivoExiste(
            personId: $user->person_id,
            apiClientId: $this->context->apiClientId(),
        );
        if(!$pertence) {
            return null;
        }

        $plainToken = Str::random(64);

        DB::transaction(function () use ($user, $plainToken) {
            $dto = UserData::paraEdicao([
                'password_reset_token' => hash('sha256', $plainToken),
                'password_reset_expires_at' => now()->addMinutes(60),
            ]);

            $this->userRepository->update($user, $dto->toArray());
        });

        return $plainToken;
    }

    public function resetPassword(string $token, string $password): void
    {
        $user = $this->userRepository->findByTokenReset(
            token: hash('sha256', $token)
        );
        if($user === null) {
            throw ValidationException::withMessages([
                'token' => 'Token inválido',
            ]);
        }

        if($user->password_reset_expires_at === null
            || $user->password_reset_expires_at->isPast()) 
        {
            throw ValidationException::withMessages([
                'token' => 'Token expirado.',
            ]);
        }

        if(Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'A nova senha não pode ser igual à senha anterior.'
            ]);
        }

        if($user->previous_password !== null && Hash::check($password, $user->previous_password)) {
            throw ValidationException::withMessages([
                'password' => 'A nova senha não pode ser igual à senha anterior.',
            ]);
        }

        DB::transaction(function () use ($user, $password) {
            $dto = UserData::paraEdicao([
                'password' => $password,                 // texto puro; cast faz o hash
                'previous_password' => $user->password,  // hash atual → vira histórico
                'password_changed_at' => now(),
                'force_password_change' => false,
                'password_reset_token' => null,          // consome o token (uso único)
                'password_reset_expires_at' => null,
            ]);

            $this->userRepository->update($user, $dto->toArray());
        });
    }

    private function falharCredencial(): never
    {
        throw ValidationException::withMessages([
            'email' => 'Credenciais inválidas.',
        ]);
    }
}