<?php

namespace App\Providers;

use App\Support\AuditContext;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditContext::class);
    }

    public function boot(): void
    {
        Password::defaults(function  () {
            return Password::min(8)
                ->mixedCase()
                ->symbols();
        });
    }
}
