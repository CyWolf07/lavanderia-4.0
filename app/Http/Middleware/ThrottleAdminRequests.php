<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter as RateLimiterFacade;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: Throttle de rutas del panel administrativo.
 * Limita las peticiones excesivas para proteger endpoints sensibles
 * contra abusos y picos de tráfico inesperados.
 */
class ThrottleAdminRequests
{
    public function handle(Request $request, Closure $next, string $key = 'admin', int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $userId  = $request->user()?->id ?? 'guest';
        $limiterKey = "{$key}:{$userId}:{$request->ip()}";

        if (RateLimiterFacade::tooManyAttempts($limiterKey, $maxAttempts)) {
            $seconds = RateLimiterFacade::availableIn($limiterKey);

            return response()->json([
                'message' => 'Demasiadas solicitudes. Por favor, espera ' . $seconds . ' segundos.',
            ], Response::HTTP_TOO_MANY_REQUESTS)->withHeaders([
                'Retry-After'               => $seconds,
                'X-RateLimit-Limit'         => $maxAttempts,
                'X-RateLimit-Remaining'     => 0,
                'X-RateLimit-Reset'         => now()->addSeconds($seconds)->timestamp,
            ]);
        }

        RateLimiterFacade::hit($limiterKey, $decayMinutes * 60);

        $response = $next($request);

        $remaining = max(0, $maxAttempts - RateLimiterFacade::attempts($limiterKey));

        return $response->withHeaders([
            'X-RateLimit-Limit'     => $maxAttempts,
            'X-RateLimit-Remaining' => $remaining,
        ]);
    }
}
