<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\ResetPasswordRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        try
        {
            $resultado = $this->authService->login(
                email: $request->validated('email'),
                password: $request->validated('password'),
            );

            return response()->json([
                'token' => $resultado['token'],
                'token_type' => 'Bearer',
                'user' => $resultado['user'],
                'force_password_change' => $resultado['user']->force_password_change,
            ]);
        }
        catch(ValidationException $e) 
        {
            throw $e;
        }
        catch(Throwable $e)
        {
            report($e);

            return response()->json([
                'message' => 'Não foi possível realizar o login'
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try
        {
            $this->authService->logout($request->user());
            return response()->json([
                'message' => 'Logout realizado com sucesso.'
            ]);
        }
        catch(Throwable $e)
        {
            report($e);
            return response()->json([
                'message' => 'Não foi possível realizar o logout'
            ], 500);
        }
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
            'force_password_change' => $request->user()->force_password_change
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try
        {
            $token = $this->authService->forgotPassword(
                email: $request->validated('email'),
            );
            if($token === null) {
                return response()->json([
                    'message' => 'Usuário não encontrado.'
                ], 404);
            }

            return response()->json([
                'reset_token' => $token,
                'expires_in_minutes' => 60,
            ]);
        }
        catch(ValidationException $e)
        {
            throw $e;
        }
        catch(Throwable $e)
        {
            report($e);
            return response()->json([
                'message' => 'Não foi possível gerar o token de redefinição'
            ], 500);
        }
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try
        {
            $this->authService->resetPassword(
                token: $request->validated('token'),
                password: $request->validated('password'),
            );

            return response()->json([
                'message' => 'Senha redefinida com sucesso'
            ]);
        }
        catch(ValidationException $e)
        {
            throw $e;
        }
        catch(Throwable $e)
        {
            report($e);
            return response()->json([
                'message' => 'Não foi possível redefinir a senha'
            ], 500);
        }
    }
}