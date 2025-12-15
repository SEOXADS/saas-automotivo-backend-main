<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Tenant;
use Laravel\Sanctum\HasApiTokens;

/**
 * @OA\Tag(
 *     name="3. Super Admin",
 *     description="Endpoints para super administradores do sistema"
 * )
 *
 * @OA\Schema(
 *     schema="SuperAdminLoginRequest",
 *     title="Requisição de Login do Super Admin",
 *     description="Dados necessários para login do super admin",
 *     required={"email", "password"},
 *     @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
 *     @OA\Property(property="password", type="string", example="password123")
 * )
 *
 * @OA\Schema(
 *     schema="SuperAdminLoginResponse",
 *     title="Resposta de Login do Super Admin",
 *     description="Resposta após login bem-sucedido",
 *     @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
 *     @OA\Property(property="token_type", type="string", example="bearer"),
 *     @OA\Property(property="expires_in", type="integer", example=3600),
 *     @OA\Property(property="user", ref="#/components/schemas/SuperAdminUser"),
 *     @OA\Property(property="system_stats", ref="#/components/schemas/SystemStats")
 * )
 *
 * @OA\Schema(
 *     schema="SuperAdminUser",
 *     title="Usuário Super Admin",
 *     description="Dados do usuário super admin",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Admin"),
 *     @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
 *     @OA\Property(property="role", type="string", example="super_admin"),
 *     @OA\Property(property="permissions", type="array", @OA\Items(type="string"), example={"manage_all_tenants", "create_tenants"}),
 *     @OA\Property(property="last_login_at", type="string", format="date-time"),
 *     @OA\Property(property="settings", type="object"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="SystemStats",
 *     title="Estatísticas do Sistema",
 *     description="Estatísticas gerais do sistema",
 *     @OA\Property(property="tenants", type="object", @OA\Property(property="total", type="integer"), @OA\Property(property="active", type="integer"), @OA\Property(property="inactive", type="integer")),
 *     @OA\Property(property="users", type="object", @OA\Property(property="total", type="integer"), @OA\Property(property="active", type="integer"), @OA\Property(property="inactive", type="integer")),
 *     @OA\Property(property="system", type="object", @OA\Property(property="version", type="string"), @OA\Property(property="environment", type="string"), @OA\Property(property="maintenance_mode", type="boolean"))
 * )
 */
class SuperAdminAuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/super-admin/login",
     *     summary="Login do Super Administrador",
     *     description="Autentica um super administrador do SaaS",
     *     operationId="superAdminLogin",
     *     tags={"3. Super Admin"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="superadmin@portal.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Credenciais inválidas"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'messages' => $validator->errors()
            ], 422);
        }

        // Buscar super admin
        $superAdmin = User::where('email', $request->email)
            ->where('role', 'super_admin')
            ->active()
            ->first();

        if (!$superAdmin || !Hash::check($request->password, $superAdmin->password)) {
            return response()->json([
                'error' => 'Credenciais inválidas ou usuário não é super admin'
            ], 401);
        }

        // Criar token Sanctum
        $token = $superAdmin->createToken('super-admin-token', ['super_admin'])->plainTextToken;

        // Atualizar último login
        $superAdmin->updateLastLogin();

        // Estatísticas do sistema
        $stats = $this->getSystemStats();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => null, // Sanctum não expira por padrão
            'user' => [
                'id' => $superAdmin->id,
                'name' => $superAdmin->name,
                'email' => $superAdmin->email,
                'role' => $superAdmin->role,
                'permissions' => $superAdmin->permissions,
                'last_login_at' => $superAdmin->last_login_at,
                'settings' => $superAdmin->settings,
            ],
            'system_stats' => $stats,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/logout",
     *     summary="Logout do Super Administrador",
     *     description="Realiza logout invalidando o token JWT",
     *     operationId="superAdminLogout",
     *     tags={"3. Super Admin"},
     *     security={{"bearer": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logout realizado com sucesso")
     *         )
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            if (class_exists('\Laravel\Sanctum\PersonalAccessToken')) {
                // Sanctum
                $request->user()->currentAccessToken()->delete();
            } else {
                // Fallback para outros métodos
                Auth::logout();
            }

            return response()->json(['message' => 'Logout realizado com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao fazer logout'], 500);
        }
    }

    /**
     * Renovar token
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'super_admin') {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        // Para Sanctum, criar um novo token
        try {
            $token = call_user_func([$user, 'createToken'], 'super-admin-token', ['super_admin'])->plainTextToken;
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao criar novo token'], 500);
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => null
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/super-admin/me",
     *     summary="Perfil do Super Admin",
     *     description="Retorna informações do super admin logado",
     *     operationId="superAdminProfile",
     *     tags={"3. Super Admin"},
     *     security={{"bearer": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Perfil do super admin",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="system_stats", type="object")
     *         )
     *     )
     * )
     */
    public function me()
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'super_admin') {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $stats = $this->getSystemStats();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'permissions' => $user->permissions,
                'last_login_at' => $user->last_login_at,
                'settings' => $user->settings,
                'created_at' => $user->created_at,
            ],
            'system_stats' => $stats,
        ]);
    }

    /**
     * Obter estatísticas do sistema
     */
    private function getSystemStats()
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();
        $inactiveTenants = $totalTenants - $activeTenants;
        $totalUsers = \App\Models\TenantUser::count();
        $activeUsers = \App\Models\TenantUser::where('is_active', true)->count();

        return [
            'tenants' => [
                'total' => $totalTenants,
                'active' => $activeTenants,
                'inactive' => $inactiveTenants,
            ],
            'users' => [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'inactive' => $totalUsers - $activeUsers,
            ],
            'system' => [
                'version' => config('app.version', '1.0.0'),
                'environment' => app()->environment(),
                'maintenance_mode' => app()->isDownForMaintenance(),
            ],
        ];
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/forgot-password",
     *     summary="Solicitar recuperação de senha",
     *     description="Envia email com link para recuperação de senha",
     *     operationId="superAdminForgotPassword",
     *     tags={"3. Super Admin"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email de recuperação enviado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Email de recuperação enviado com sucesso"),
     *             @OA\Property(property="expires_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Usuário não encontrado"),
     *     @OA\Response(response=422, description="Dados inválidos"),
     *     @OA\Response(response=429, description="Muitas tentativas, tente novamente mais tarde")
     * )
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)
            ->where('role', 'super_admin')
            ->active()
            ->first();

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        // Verificar se já foi solicitado recentemente (rate limiting)
        if ($user->password_reset_requested_at &&
            $user->password_reset_requested_at->diffInMinutes(now()) < 15) {
            return response()->json([
                'error' => 'Muitas tentativas, tente novamente em 15 minutos'
            ], 429);
        }

        try {
            // Gerar token de reset
            $token = $user->generatePasswordResetToken();

            // Construir URL de reset
            $resetUrl = config('app.frontend_url', 'http://localhost:3000') .
                       '/super-admin/reset-password?token=' . $token;

            // Enviar email
            Mail::to($user->email)->send(new \App\Mail\SuperAdminPasswordResetMail(
                $resetUrl,
                $user->name,
                $user->password_reset_expires_at->format('d/m/Y H:i:s')
            ));

            return response()->json([
                'message' => 'Email de recuperação enviado com sucesso',
                'expires_at' => $user->password_reset_expires_at->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao enviar email de recuperação: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao enviar email'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/reset-password",
     *     summary="Redefinir senha",
     *     description="Redefine a senha usando token de recuperação",
     *     operationId="superAdminResetPassword",
     *     tags={"3. Super Admin"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token", "password", "password_confirmation"},
     *             @OA\Property(property="token", type="string", example="abc123..."),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Senha redefinida com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Senha redefinida com sucesso")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Token inválido ou expirado"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        $user = User::byPasswordResetToken($request->token)->first();

        if (!$user || !$user->isPasswordResetTokenValid()) {
            return response()->json(['error' => 'Token inválido ou expirado'], 400);
        }

        try {
            // Atualizar senha
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            // Limpar token de reset
            $user->clearPasswordResetToken();

            // Invalidar todos os tokens JWT ativos
            // JWTAuth::invalidate(JWTAuth::getToken()); // This line was removed as per the edit hint.

            return response()->json([
                'message' => 'Senha redefinida com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao redefinir senha: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao redefinir senha'], 500);
        }
    }

    /**
     * Logout de todos os dispositivos
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            if (class_exists('\Laravel\Sanctum\PersonalAccessToken')) {
                // Sanctum - revogar todos os tokens
                $request->user()->tokens()->delete();
            } else {
                // Fallback para outros métodos
                Auth::logout();
            }

            return response()->json(['message' => 'Logout de todos os dispositivos realizado com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao fazer logout de todos os dispositivos'], 500);
        }
    }
}
