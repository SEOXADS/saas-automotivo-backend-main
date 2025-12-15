<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\TokenHelper;

class TokenAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $requiredRole = null)
    {
        return TokenHelper::middleware($request, $next, $requiredRole);
    }
}
