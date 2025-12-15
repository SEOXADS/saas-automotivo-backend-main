<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;
use Tymon\JWTAuth\Facades\JWTAuth;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Para rotas públicas, usar header X-Tenant-Subdomain
        if ($this->isPublicRoute($request)) {
            $subdomain = $request->header('X-Tenant-Subdomain');

            if (!$subdomain) {
                return response()->json([
                    'error' => 'Header X-Tenant-Subdomain é obrigatório para rotas públicas'
                ], 400);
            }

            $tenant = Tenant::bySubdomain($subdomain)->active()->first();

            if (!$tenant) {
                return response()->json([
                    'error' => 'Tenant não encontrado ou inativo'
                ], 404);
            }

            // Disponibilizar tenant na request
            $request->merge(['current_tenant' => $tenant]);

            return $next($request);
        }

        // Para rotas autenticadas, o usuário já foi autenticado pelo JwtMiddleware
        // Apenas verificar se o tenant está ativo e disponibilizar na request
        try {
            // Pegar o usuário já autenticado pelo JwtMiddleware
            $user = $request->attributes->get('auth_user');

            if (!$user) {
                // Fallback: tentar autenticar novamente se necessário
                $token = $request->bearerToken();
                if (!$token) {
                    return response()->json(['error' => 'Token não fornecido'], 401);
                }
                $user = JWTAuth::parseToken()->authenticate();
            }

            if (!$user) {
                return response()->json(['error' => 'Usuário não encontrado'], 401);
            }

            // Verificar se o tenant está ativo
            $tenant = $user->tenant;

            if (!$tenant || !$tenant->isActive()) {
                return response()->json([
                    'error' => 'Tenant não encontrado ou inativo',
                    'tenant_status' => $tenant ? $tenant->status : 'not_found'
                ], 403);
            }

            // Disponibilizar tenant e usuário na request
            $request->merge([
                'current_tenant' => $tenant,
                'current_user' => $user
            ]);

            return $next($request);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro na verificação do tenant',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar se é uma rota pública
     */
    private function isPublicRoute(Request $request): bool
    {
        $publicRoutes = [
            'api/public/*',
            'api/auth/login',
            'api/auth/register'
        ];

        foreach ($publicRoutes as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
