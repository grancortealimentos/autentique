<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $users = $this->userService->paginate(
            $request->query('term'),
            (int) $request->query('per_page', 10),
        );

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        try
        {
            $user = $this->userService->create($request->validated());
            return response()->json($user, 201);
        }
        catch(ValidationException $e)
        {
            throw $e;
        }
        catch(Throwable $e)
        {
            report($e);
            return response()->json([
                'message' => 'Não foi possível criar o usuário.',
            ], 500);
        }
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try
        {
            $user = $this->userService->update($user, $request->validated());
            return response()->json($user);
        }
        catch(ValidationException $e)
        {
            throw $e;
        }
        catch(Throwable $e)
        {
            report($e);
            return response()->json([
                'message' => 'Não foi possível atualizar o usuário.',
            ], 500);
        }
    }

    public function activate(User $user): JsonResponse
    {
        try
        {
            $user = $this->userService->activate($user);
            return response()->json($user);
        }
        catch(ValidationException $e)
        {
            throw $e;
        }
        catch(Throwable $e)
        {
            report($e);
            return response()->json([
                'message' => 'Não foi possível ativar o usuário.',
                500
            ]);
        }
    }

    public function deactivate(User $user): JsonResponse
    {
        try
        {
            $user = $this->userService->deactivate($user);
            return response()->json($user);
        }
        catch(ValidationException $e)
        {
            throw $e;
        }
        catch(Throwable $e)
        {
            report($e);
            return response()->json([
                'message' => 'Não foi possível inativar o usuário.',
                500
            ]);
        }
    }
}