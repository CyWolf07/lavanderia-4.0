<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RolMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        if (! auth()->user()->tieneRol(...$roles)) {
            abort(403, 'No tienes permisos para acceder');
        }

        return $next($request);
    }
}
