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
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('admin.web', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \App\Http\Middleware\ConfigureAdminSession::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->appendToGroup('web', \App\Http\Middleware\SecurityHeaders::class);
        $middleware->appendToGroup('admin.web', \App\Http\Middleware\SecurityHeaders::class);

        $middleware->alias([
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'admin.ip' => \App\Http\Middleware\EnsureAdminAllowedIp::class,
            'admin.access' => \App\Http\Middleware\EnsureAdminAccess::class,
            'admin.password' => \App\Http\Middleware\EnsureAdminPasswordSession::class,
            'admin.totp' => \App\Http\Middleware\EnsureAdminTotpComplete::class,
            /** @deprecated Use admin.access */
            'admin.secret' => \App\Http\Middleware\EnsureAdminAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
