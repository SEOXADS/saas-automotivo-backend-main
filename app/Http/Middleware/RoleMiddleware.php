<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  string|array  $roles  Roles permitidos
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        try {
            $user = JWTAuth::user();

            if (!$user) {
                return response()->json([
                    'error' => 'Usuário não autenticado'
                ], 401);
            }

            // Se não foram especificados roles, permitir qualquer usuário autenticado
            if (empty($roles)) {
                return $next($request);
            }

            // Verificar se o usuário tem algum dos roles especificados
            $userRole = $user->role;

            if (!in_array($userRole, $roles)) {
                return response()->json([
                    'error' => 'Acesso negado',
                    'message' => "Você precisa ter um dos seguintes roles: " . implode(', ', $roles),
                    'user_role' => $userRole,
                    'required_roles' => $roles
                ], 403);
            }

            return $next($request);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro na verificação de permissão',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
