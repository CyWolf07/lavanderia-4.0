<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RolMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: null,
        then: function (): void {
            Route::get('/up', static fn () => response()->json([
                'success' => true,
            ]));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '*'));
        $middleware->preventRequestsDuringMaintenance(['/up']);

        $middleware->alias([
            'activo' => EnsureUserIsActive::class,
            'rol' => RolMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
