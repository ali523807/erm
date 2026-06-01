<?php

use App\Http\Middleware\EnsureCompanyIsSelected;
use App\Http\Middleware\EnsurePlatformAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'company.selected' => EnsureCompanyIsSelected::class,
            'platform.admin' => EnsurePlatformAdmin::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request): string {
            return $request->is('platform*') ? route('platform.login') : route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
