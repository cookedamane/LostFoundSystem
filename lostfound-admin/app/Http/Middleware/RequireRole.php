<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        abort_unless($user && $user->is_active && in_array($user->role, $roles, true), 403, 'You do not have permission to access this page.');

        return $next($request);
    }
}
