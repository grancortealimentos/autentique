<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PersonController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas de Person
|--------------------------------------------------------------------------
| Todas exigem as DUAS camadas:
|   - 'api.client' → valida X-API-KEY (qual aplicação) e popula o AuditContext
|   - 'auth:sanctum' → valida o token do usuário (necessário p/ granted_by)
*/
Route::prefix('v1')->group(function () {
    Route::middleware(['api.client', 'auth:sanctum'])->group(function () {

        //Busca GLOBAL por documento (fluxo do CPF)
        Route::get('persons/check-document', [PersonController::class, 'checkDocument'])
            ->name('persons.check-document');

        //lista de pessoas VINCULADAS a esta aplicação (paginado com busca/status)
        Route::get('persons', [PersonController::class, 'index'])
            ->name('persons.index');
        
        //cria uma pessoa nova e vincula automaticamanete a uma aplicação
        Route::post('persons', [PersonController::class, 'store'])
            ->name('persons.store');

        Route::middleware('person.scope')->group(function () {
            //detalha uma pessoa (precisa estar vinculada a uma aplicação)
            Route::get('persons/{person}', [PersonController::class, 'show'])
                ->name('persons.show');

            //atualiza os dados cadastrais de uma pessoa.
            Route::patch('persons/{person}', [PersonController::class, 'update'])
                ->name('persons.update');

            //desvincula a pessoa da aplicação
            Route::delete('persons/{person}/link', [PersonController::class, 'unlink'])
                ->name('persons.unlink');
        });

        //vincula uma pessoa já existente a outra aplicação.
        Route::post('persons/{person}/link', [PersonController::class, 'link'])
            ->name('persons.link');
    });
});


/*
|--------------------------------------------------------------------------
| Rotas de User
|--------------------------------------------------------------------------
| Todas exigem as DUAS camadas:
|   - 'api.client'   → valida X-API-KEY (qual aplicação) e popula o AuditContext
|   - 'auth:sanctum' → valida o token do usuário (necessário p/ granted_by)
*/
Route::prefix('v1')->group(function () {
    Route::middleware(['api.client', 'auth:sanctum'])->group(function () {

        //lista de usuários ativos (paginado com busca: ?term=&per_page=)
        Route::get('users', [UserController::class, 'index'])
            ->name('users.index');

        //cria um usuário e vincula à pessoa (relação 1:1)
        Route::post('users', [UserController::class, 'store'])
            ->name('users.store');

        Route::middleware('user.scope')->group(function () {
            //detalha um usuário (precisa pertencer à aplicação)
            Route::get('users/{user}', [UserController::class, 'show'])
                ->name('users.show');

            //atualiza dados do usuário (PATCH parcial)
            Route::patch('users/{user}', [UserController::class, 'update'])
                ->name('users.update');

            //ativa o usuário
            Route::patch('users/{user}/activate', [UserController::class, 'activate'])
                ->name('users.activate');

            //inativa o usuário
            Route::patch('users/{user}/deactivate', [UserController::class, 'deactivate'])
                ->name('users.deactivate');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Rotas de Autenticação
|--------------------------------------------------------------------------
| login  → exige apenas 'api.client' (X-API-KEY). NÃO exige auth:sanctum,
|          pois é ele que emite o token. Valida vínculo pessoa↔app no Service.
| logout → exige as DUAS camadas: precisa do token (auth:sanctum) para saber
|   e me    QUAL token revogar / QUAL usuário retornar, e do api.client para
|          manter o contexto da aplicação.
*/
Route::prefix('v1')->group(function () {

    // Só X-API-KEY: o login gera o token, então não pode exigir Bearer.
    // throttle: limita força bruta de senha/token — chamadas legítimas são
    // sempre servidor-a-servidor (backend da app cliente), nunca do browser.
    Route::middleware(['api.client', 'throttle:10,1'])->group(function () {
        Route::post('login', [AuthController::class, 'login'])
            ->name('auth.login');

        //solicita redefinição: gera e devolve o token de reset (uso servidor-a-servidor;
        //quem envia o e-mail ao usuário final é a aplicação cliente, não esta API)
        Route::post('password/forgot', [AuthController::class, 'forgotPassword'])
            ->name('auth.password.forgot');

        //redefine a senha a partir de um token válido
        Route::post('password/reset', [AuthController::class, 'resetPassword'])
            ->name('auth.password.reset');
    });

    // X-API-KEY + Bearer: exigem usuário autenticado.
    Route::middleware(['api.client', 'auth:sanctum'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])
            ->name('auth.logout');

        Route::get('me', [AuthController::class, 'me'])
            ->name('auth.me');

        //troca de senha do próprio usuário logado, exige a senha atual
        Route::middleware('throttle:10,1')->put('password/change', [AuthController::class, 'changePassword'])
            ->name('auth.password.change');
    });
});
