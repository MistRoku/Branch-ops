<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            if ($request->is('api/*') || $request->expectsJson()) {
                abort(403, 'Insufficient permissions for this action');
            }
            abort(403, 'Insufficient permissions for this action');
        }

        return $next($request);
    }
}
