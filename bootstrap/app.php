<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RolMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\DB;
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

            Route::get('/up/database', static function () {
                try {
                    DB::select('select 1');

                    return response()->json([
                        'success' => true,
                        'database' => 'ok',
                        'connection' => config('database.default'),
                    ]);
                } catch (Throwable $exception) {
                    report($exception);

                    return response()->json([
                        'success' => false,
                        'database' => 'unavailable',
                    ], 503);
                }
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '*'));
        $middleware->preventRequestsDuringMaintenance(['/up', '/up/database']);

        $middleware->alias([
            'activo' => EnsureUserIsActive::class,
            'rol' => RolMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
