<?php

namespace App\Providers;

use App\Support\SafeFilesystem;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('files', fn () => new SafeFilesystem);
        $this->app->alias('files', Filesystem::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ── Rate Limiters ────────────────────────────────────────────────────
        // Definidos aquí (en boot) para que el contenedor ya esté disponible.

        // Limitar intentos de login: 10 por minuto por IP
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Limitar escrituras del panel admin: 30 por minuto por usuario/IP
        RateLimiter::for('admin-writes', function (Request $request) {
            return Limit::perMinute(30)
                ->by(optional($request->user())->id ?: $request->ip());
        });
    }
}

