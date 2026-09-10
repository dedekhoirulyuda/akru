<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware — applied to all requests
        $middleware->append(\App\Http\Middleware\CorrelationId::class);

        // Web middleware group additions
        $middleware->web(append: [
            \App\Modules\Platform\Http\Middleware\HandleImpersonation::class,
        ]);

        // Middleware aliases for route-level use
        $middleware->alias([
            'tenant' => \App\Http\Middleware\TenantResolver::class,
            'superadmin' => \App\Modules\Platform\Http\Middleware\EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
