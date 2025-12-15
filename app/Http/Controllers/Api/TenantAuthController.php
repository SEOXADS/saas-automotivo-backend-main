<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\TenantUser;
use App\Models\Tenant;

/**
 * @OA\Tag(
 *     name="2. Admin Cliente",
 *     description="Endpoints para administradores do tenant (autenticação e gestão)"
 * )
 */
class TenantAuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/tenant/login",
     *     summary="Login para administradores de tenants",
     *     description="Autentica um administrador de tenant usando email e senha",
     *     operationId="tenantLogin",
     *     tags={"2. Admin Cliente"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@demo.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="string", example=null),
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="tenant", type="object")
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
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        $tenantUser = TenantUser::where('email', $request->email)->first();

        if (!$tenantUser || !Hash::check($request->password, $tenantUser->password)) {
            return response()->json(['error' => 'Credenciais inválidas'], 401);
        }

        if (!$tenantUser->is_active) {
            return response()->json(['error' => 'Conta desativada'], 403);
        }

        // Verificar se o tenant está ativo
        $tenant = $tenantUser->tenant;
        if (!$tenant || $tenant->status !== 'active') {
            return response()->json(['error' => 'Tenant inativo ou suspenso'], 403);
        }

        // Criar token Sanctum
        $token = $tenantUser->createToken('tenant-token', ['tenant'])->plainTextToken;

        // Atualizar último login
        $tenantUser->update(['last_login_at' => now()]);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => null,
            'user' => [
                'id' => $tenantUser->id,
                'name' => $tenantUser->name,
                'email' => $tenantUser->email,
                'role' => $tenantUser->role,
                'is_active' => $tenantUser->is_active,
                'last_login_at' => $tenantUser->last_login_at
            ],
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'subdomain' => $tenant->subdomain,
                'plan' => $tenant->plan,
                'status' => $tenant->status
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/tenant/logout",
     *     summary="Logout para administradores de tenants",
     *     description="Realiza logout invalidando o token atual",
     *     operationId="tenantLogout",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Logout realizado com sucesso"),
     *     @OA\Response(response=401, description="Não autorizado")
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado com sucesso']);
    }

    /**
     * @OA\Get(
     *     path="/api/tenant/me",
     *     summary="Obter informações do tenant logado",
     *     description="Retorna informações do tenant e usuário autenticado",
     *     operationId="tenantMe",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informações retornadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="tenant", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Não autorizado")
     * )
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $tenantUser = TenantUser::where('email', $user->email)->first();

        if (!$tenantUser) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        $tenant = $tenantUser->tenant;

        return response()->json([
            'user' => [
                'id' => $tenantUser->id,
                'name' => $tenantUser->name,
                'email' => $tenantUser->email,
                'role' => $tenantUser->role,
                'is_active' => $tenantUser->is_active,
                'last_login_at' => $tenantUser->last_login_at
            ],
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'subdomain' => $tenant->subdomain,
                'plan' => $tenant->plan,
                'status' => $tenant->status
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/tenant/register",
     *     summary="Registro de novo tenant",
     *     description="Cria um novo tenant com administrador",
     *     operationId="tenantRegister",
     *     tags={"2. Admin Cliente"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tenant_name","subdomain","admin_name","admin_email","admin_password"},
     *             @OA\Property(property="tenant_name", type="string", example="Empresa Demo"),
     *             @OA\Property(property="subdomain", type="string", example="demo"),
     *             @OA\Property(property="admin_name", type="string", example="Admin Demo"),
     *             @OA\Property(property="admin_email", type="string", format="email", example="admin@demo.com"),
     *             @OA\Property(property="admin_password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tenant criado com sucesso"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tenant_name' => 'required|string|max:255',
            'subdomain' => 'required|string|max:100|regex:/^[a-z0-9\-]+$/|unique:tenants,subdomain',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255|unique:tenant_users,email',
            'admin_password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        // Criar tenant
        $tenant = Tenant::create([
            'name' => $request->tenant_name,
            'subdomain' => $request->subdomain,
            'email' => $request->admin_email,
            'status' => 'active',
            'plan' => 'trial',
            'trial_ends_at' => now()->addDays(30)
        ]);

        // Criar usuário admin
        $admin = TenantUser::create([
            'tenant_id' => $tenant->id,
            'name' => $request->admin_name,
            'email' => $request->admin_email,
            'password' => Hash::make($request->admin_password),
            'role' => 'admin',
            'is_active' => true
        ]);

        return response()->json([
            'message' => 'Tenant criado com sucesso',
            'tenant' => $tenant,
            'admin' => $admin->makeHidden(['password'])
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/tenant/forgot-password",
     *     summary="Solicitar recuperação de senha",
     *     description="Envia email com link para recuperação de senha",
     *     operationId="tenantForgotPassword",
     *     tags={"2. Admin Cliente"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@demo.com")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Email de recuperação enviado"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        $tenantUser = TenantUser::where('email', $request->email)->first();

        if (!$tenantUser) {
            return response()->json(['message' => 'Se o email existir, um link de recuperação será enviado'], 200);
        }

        // TODO: Implementar envio de email de recuperação
        // Por enquanto, apenas retorna sucesso

        return response()->json(['message' => 'Se o email existir, um link de recuperação será enviado']);
    }

    /**
     * @OA\Post(
     *     path="/api/tenant/reset-password",
     *     summary="Redefinir senha",
     *     description="Redefine a senha usando token de recuperação",
     *     operationId="tenantResetPassword",
     *     tags={"2. Admin Cliente"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token","email","password","password_confirmation"},
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", minLength=6),
     *             @OA\Property(property="password_confirmation", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Senha redefinida com sucesso"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        // TODO: Implementar validação de token e redefinição de senha
        // Por enquanto, apenas retorna sucesso

        return response()->json(['message' => 'Senha redefinida com sucesso']);
    }
}
