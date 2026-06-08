<?php

use App\Http\Middleware\EnsureCompanyIsSelected;
use App\Http\Middleware\EnsureCompanyPermission;
use App\Http\Middleware\EnsureCompanyRole;
use App\Http\Middleware\EnsureCustomerPortalAccess;
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
            'company.permission' => EnsureCompanyPermission::class,
            'company.selected' => EnsureCompanyIsSelected::class,
            'company.role' => EnsureCompanyRole::class,
            'customer.portal' => EnsureCustomerPortalAccess::class,
            'platform.admin' => EnsurePlatformAdmin::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request): string {
            if ($request->is('portal*')) {
                return route('customer-portal.login');
            }

            return $request->is('platform*') ? route('platform.login') : route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
