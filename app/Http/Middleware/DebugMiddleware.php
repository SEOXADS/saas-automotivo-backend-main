<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class DebugMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log da requisição para debug
        Log::info('Debug Middleware - Request', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ]);

        // Verificar se há token
        $token = $request->bearerToken();
        if ($token) {
            Log::info('Debug Middleware - Token encontrado', [
                'token_length' => strlen($token),
                'token_preview' => substr($token, 0, 20) . '...',
            ]);
        } else {
            Log::warning('Debug Middleware - Token não encontrado');
        }

        // Verificar se há usuário autenticado
        if ($request->attributes->has('auth_user')) {
            $user = $request->attributes->get('auth_user');
            Log::info('Debug Middleware - Usuário autenticado', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role,
                'tenant_id' => $user->tenant_id,
                'is_active' => $user->isActive(),
            ]);
        } else {
            Log::warning('Debug Middleware - Usuário não autenticado');
        }

        $response = $next($request);

        // Log da resposta para debug
        Log::info('Debug Middleware - Response', [
            'status_code' => $response->getStatusCode(),
            'headers' => $response->headers->all(),
            'timestamp' => now()->toISOString(),
        ]);

        return $response;
    }
}
