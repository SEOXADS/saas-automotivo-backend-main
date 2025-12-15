<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\User;
use App\Models\TenantUser;
use Laravel\Sanctum\PersonalAccessToken;

class TokenHelper
{
    /**
     * Detecta o tipo de token e retorna o usuário autenticado
     */
    public static function getAuthenticatedUser(Request $request)
    {
        $token = self::extractToken($request);

        if (!$token) {
            return null;
        }

        // Tentar autenticar com JWT primeiro (para super-admins)
        try {
            $user = JWTAuth::setToken($token)->authenticate();
            if ($user) {
                // Se for um usuário da tabela users (super-admin), retornar diretamente
                if ($user->getTable() === 'users') {
                    return $user;
                }
                // Se for um TenantUser, buscar na tabela correta
                if (isset($user->tenant_id)) {
                    return TenantUser::find($user->id);
                }
                return $user;
            }
        } catch (JWTException $e) {
            // Token JWT inválido, continuar para Sanctum
        }

        // Tentar autenticar com Sanctum (para tenant users)
        try {
            // Verificar se já não há usuário autenticado para evitar recursão
            if (Auth::guard('sanctum')->check()) {
                return Auth::guard('sanctum')->user();
            }

            // Tentar autenticar com o token
            $personalAccessToken = PersonalAccessToken::findToken($token);
            if ($personalAccessToken) {
                $user = $personalAccessToken->tokenable;
                if ($user) {
                    // Se for um TenantUser, garantir que está na tabela correta
                    if (method_exists($user, 'getTable') && $user->getTable() === 'tenant_users') {
                        // Definir o usuário no guard para evitar recursão
                        Auth::guard('sanctum')->setUser($user);
                        return $user;
                    }

                    // Se for um User (super-admin), retornar diretamente
                    if (method_exists($user, 'getTable') && $user->getTable() === 'users') {
                        return $user;
                    }
                }
            }
        } catch (\Exception $e) {
            // Log do erro para debug
            Log::warning('Erro na autenticação Sanctum', [
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...'
            ]);
        }

        return null;
    }

    /**
     * Extrai o token do request
     */
    public static function extractToken(Request $request): ?string
    {
        $token = $request->bearerToken();

        if (!$token) {
            $token = $request->get('token');
        }

        return $token;
    }

    /**
     * Detecta o tipo de token
     */
    public static function detectTokenType(string $token): string
    {
        // Verificar se é JWT (base64 com 3 partes separadas por ponto)
        if (preg_match('/^[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+$/', $token)) {
            return 'jwt';
        }

        // Verificar se é Sanctum (formato: "id|hash")
        if (preg_match('/^\d+\|[A-Za-z0-9]+$/', $token)) {
            return 'sanctum';
        }

        return 'unknown';
    }

    /**
     * Autentica usuário baseado no tipo de token
     */
    public static function authenticateUser(string $token, string $type = null)
    {
        if (!$type) {
            $type = self::detectTokenType($token);
        }

        switch ($type) {
            case 'jwt':
                return self::authenticateJWT($token);

            case 'sanctum':
                return self::authenticateSanctum($token);

            default:
                return null;
        }
    }

    /**
     * Autentica com JWT
     */
    private static function authenticateJWT(string $token)
    {
        try {
            $user = JWTAuth::setToken($token)->authenticate();

            if ($user) {
                // Se for um TenantUser, buscar na tabela correta
                if (isset($user->tenant_id)) {
                    return TenantUser::find($user->id);
                }

                return $user;
            }
        } catch (JWTException $e) {
            return null;
        }

        return null;
    }

    /**
     * Autentica com Sanctum
     */
    private static function authenticateSanctum(string $token)
    {
        try {
            // Buscar token no banco
            $tokenModel = PersonalAccessToken::findToken($token);

            if (!$tokenModel) {
                return null;
            }

            $user = $tokenModel->tokenable;

            if ($user) {
                // Se for um TenantUser, buscar na tabela correta
                if (method_exists($user, 'getTable') && $user->getTable() === 'tenant_users') {
                    return TenantUser::find($user->id);
                }

                return $user;
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * Verifica se o usuário tem role específico
     */
    public static function hasRole($user, string $role): bool
    {
        if (!$user) {
            return false;
        }

        // Verificar se é Super Admin
        if ($role === 'super_admin') {
            // Para super-admin, verificar se é da tabela users e tem role super_admin
            if (method_exists($user, 'getTable') && $user->getTable() === 'users') {
                return $user->role === 'super_admin';
            }
            return false;
        }

        // Verificar se é Admin de Tenant
        if ($role === 'admin') {
            // Para admin de tenant, verificar se é da tabela tenant_users e tem role admin
            if (method_exists($user, 'getTable') && $user->getTable() === 'tenant_users') {
                return $user->role === 'admin';
            }
            return false;
        }

        return false;
    }

    /**
     * Retorna o tenant ID do usuário
     */
    public static function getTenantId($user): ?int
    {
        if (!$user) {
            return null;
        }

        // Se for TenantUser
        if (method_exists($user, 'getTable') && $user->getTable() === 'tenant_users') {
            return $user->tenant_id;
        }

        // Se for User (Super Admin), super-admins não têm tenant_id
        if (method_exists($user, 'getTable') && $user->getTable() === 'users') {
            return null; // Super-admins não pertencem a um tenant específico
        }

        return null;
    }

    /**
     * Valida se o token está expirado (para JWT)
     */
    public static function isTokenExpired(string $token): bool
    {
        try {
            $payload = JWTAuth::setToken($token)->getPayload();
            return $payload->isExpired();
        } catch (JWTException $e) {
            return true;
        }
    }

    /**
     * Middleware helper para verificar autenticação
     */
    public static function middleware(Request $request, callable $next, string $requiredRole = null)
    {
        $user = self::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        if ($requiredRole && !self::hasRole($user, $requiredRole)) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        // Definir o usuário autenticado no request para uso posterior
        $request->merge(['authenticated_user' => $user]);

        // Definir o usuário no Auth facade para que $request->user() funcione
        if (method_exists($user, 'getTable') && $user->getTable() === 'tenant_users') {
            // Para TenantUser, usar o guard 'api' (sanctum)
            Auth::guard('api')->setUser($user);
        } else if (method_exists($user, 'getTable') && $user->getTable() === 'users') {
            // Para User (Super Admin), usar o guard 'super_admin' (jwt)
            Auth::guard('super_admin')->setUser($user);
        }

        return $next($request);
    }
}
