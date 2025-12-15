<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\TokenHelper;
use App\Models\User;
use App\Models\TenantUser;

class UnifiedAuthMiddleware
{
    /**
     * Handle an incoming request.
     * Permite acesso tanto para usuários da tabela users quanto tenant_users
     */
    public function handle(Request $request, Closure $next)
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json([
                'error' => 'Não autorizado',
                'message' => 'Token inválido ou expirado'
            ], 401);
        }

        // Verificar se é um usuário válido (users ou tenant_users)
        if (!($user instanceof User || $user instanceof TenantUser)) {
            return response()->json([
                'error' => 'Tipo de usuário inválido',
                'message' => 'Usuário não reconhecido pelo sistema'
            ], 403);
        }

        // Se for um TenantUser, verificar se o tenant está ativo
        if ($user instanceof TenantUser) {
            $tenant = $user->tenant;
            if (!$tenant || $tenant->status !== 'active') {
                return response()->json([
                    'error' => 'Tenant inativo',
                    'message' => 'Este tenant está temporariamente indisponível'
                ], 403);
            }

            // Armazenar tenant no request
            $request->attributes->set('current_tenant', $tenant);
        }

        // Se for um User (não super admin), verificar se está ativo
        if ($user instanceof User && $user->role !== 'super_admin') {
            if (!$user->is_active) {
                return response()->json([
                    'error' => 'Usuário inativo',
                    'message' => 'Sua conta está desativada'
                ], 403);
            }
        }

        // Armazenar usuário no request
        $request->attributes->set('current_user', $user);
        $request->merge(['user' => $user]);

        return $next($request);
    }
}
