<?php

use App\Http\Middleware\AuthenticateApiClient;
use App\Http\Middleware\EnsurePersonBelongsToApiClient;
use App\Http\Middleware\EnsureUserBelongsToApiClient;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.client' => AuthenticateApiClient::class,
            'person.scope' => EnsurePersonBelongsToApiClient::class,
            'user.scope' => EnsureUserBelongsToApiClient::class,
        ]);

        //Requisições de API nunca redirecionam para login: retornan 401 JSON
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->dontReport([
            Illuminate\Validation\ValidationException::class,
            Illuminate\Auth\AuthenticationException::class,
            Illuminate\Auth\Access\AuthorizationException::class,
            Illuminate\Database\Eloquent\ModelNotFoundException::class,
            Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        ]);

        $exceptions->report(function (Throwable $e) {
            app(App\Services\ErrorLogService::class)->log($e);
        });
    })->create();
