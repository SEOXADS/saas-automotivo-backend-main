<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantUser;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Helpers\TokenHelper;

/**
 * @OA\Tag(
 *     name="Autenticação JWT",
 *     description="Endpoints de autenticação JWT para tenants (legado) - Sistema de tokens unificado (JWT + Sanctum)"
 * )
 */
class AuthController extends Controller
{
    /**
     * Login JWT para tenants (legado)
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'tenant_subdomain' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verificar se o tenant existe e está ativo
        $tenant = Tenant::where('subdomain', $request->tenant_subdomain)->first();
        if (!$tenant || $tenant->status !== 'active') {
            return response()->json(['error' => 'Tenant não encontrado ou inativo'], 404);
        }

        // Verificar credenciais do usuário
        $user = TenantUser::where('email', $request->email)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Credenciais inválidas'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['error' => 'Usuário inativo'], 403);
        }

        try {
            // Gerar token JWT
            $token = JWTAuth::fromUser($user);

            // Atualizar último login
            $user->update(['last_login_at' => now()]);

            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'tenant_id' => $user->tenant_id
                ],
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'subdomain' => $tenant->subdomain
                ]
            ]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Erro ao gerar token'], 500);
        }
    }

    /**
     * Logout JWT
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Logout realizado com sucesso']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Erro ao fazer logout'], 500);
        }
    }

    /**
     * Obter dados do usuário JWT
     */
    public function me(Request $request)
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $tenant = Tenant::find($user->tenant_id);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'tenant_id' => $user->tenant_id
            ],
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'subdomain' => $tenant->subdomain
            ]
        ]);
    }

    /**
     * Renovar token JWT
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60
            ]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Erro ao renovar token'], 500);
        }
    }
}
