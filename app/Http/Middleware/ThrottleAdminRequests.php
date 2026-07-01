<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Middleware de rate limiting para rutas del panel administrativo.
 * Completamente fail-safe: si el caché no está disponible, permite
 * la solicitud sin throttling (no rompe la aplicación).
 */
class ThrottleAdminRequests
{
    public function handle(Request $request, Closure $next, string $key = 'admin', int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        try {
            $userId     = $request->user()?->id ?? 'guest';
            $limiterKey = "{$key}:{$userId}:{$request->ip()}";

            if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
                $seconds = RateLimiter::availableIn($limiterKey);

                return response()->json([
                    'message' => "Demasiadas solicitudes. Espera {$seconds} segundos.",
                ], Response::HTTP_TOO_MANY_REQUESTS)->withHeaders([
                    'Retry-After'           => $seconds,
                    'X-RateLimit-Limit'     => $maxAttempts,
                    'X-RateLimit-Remaining' => 0,
                ]);
            }

            RateLimiter::hit($limiterKey, $decayMinutes * 60);

            /** @var \Illuminate\Http\Response $response */
            $response = $next($request);

            $remaining = max(0, $maxAttempts - RateLimiter::attempts($limiterKey));

            return $response->withHeaders([
                'X-RateLimit-Limit'     => $maxAttempts,
                'X-RateLimit-Remaining' => $remaining,
            ]);
        } catch (Throwable $e) {
            // Si el throttle falla (ej. caché no disponible), proceder sin limitar
            report($e);
            return $next($request);
        }
    }
}
