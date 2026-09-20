<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            abort(403, 'Account deactivated');
        }
        return $next($request);
    }
}
