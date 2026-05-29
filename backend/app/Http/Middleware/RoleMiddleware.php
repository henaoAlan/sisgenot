<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                abort(Response::HTTP_FORBIDDEN, 'No tienes permisos para acceder a esta seccion.');
            }

            return response()->json([
                'message' => 'No tienes permisos para realizar esta accion.',
                'error' => 'FORBIDDEN',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
