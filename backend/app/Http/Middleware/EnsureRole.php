<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole(...$roles)) {
            return \App\Support\ApiResponse::error(__('api.forbidden'), 403);
        }
        if (!$user->is_active) {
            return \App\Support\ApiResponse::error(__('api.account_disabled'), 403);
        }
        return $next($request);
    }
}
