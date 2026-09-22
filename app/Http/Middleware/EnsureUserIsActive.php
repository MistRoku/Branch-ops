<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (! $user->is_active || $user->isLocked())) {
            if ($request->is('api/*') || $request->expectsJson()) {
                abort(403, $user->isLocked() ? 'Account locked. Try again later.' : 'Account deactivated');
            }

            auth()->guard('web')->logout();
            $request->session()->invalidate();
            abort(403, 'Account deactivated');
        }

        return $next($request);
    }
}
