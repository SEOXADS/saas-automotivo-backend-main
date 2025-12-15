<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\TenantConfiguration;
use App\Models\Vehicle;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Helpers\TokenHelper;

/**
 * @OA\Tag(
 *     name="3. Super Admin",
 *     description="Endpoints para super administradores do sistema"
 * )
 *
 * @OA\Schema(
 *     schema="Tenant",
 *     title="Tenant",
 *     description="Modelo de dados para tenants",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Concessionária ABC"),
 *     @OA\Property(property="subdomain", type="string", example="abc"),
 *     @OA\Property(property="custom_domain", type="string", nullable=true, example="abc.com.br"),
 *     @OA\Property(property="email", type="string", format="email", example="contato@abc.com.br"),
 *     @OA\Property(property="phone", type="string", example="(11) 99999-9999"),
 *     @OA\Property(property="plan", type="string", enum={"basic", "premium", "enterprise"}, example="premium"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="TenantStats",
 *     title="Estatísticas do Tenant",
 *     description="Estatísticas de um tenant específico",
 *     @OA\Property(property="users_count", type="integer", example=5),
 *     @OA\Property(property="vehicles_count", type="integer", example=150),
 *     @OA\Property(property="leads_count", type="integer", example=25),
 *     @OA\Property(property="active_users_count", type="integer", example=4),
 *     @OA\Property(property="active_vehicles_count", type="integer", example=120)
 * )
 */
class TenantController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/super-admin/tenants",
     *     summary="Listar todos os tenants",
     *     description="Retorna uma lista paginada de todos os tenants do sistema com estatísticas",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número da página",
     *         @OA\Schema(type="integer", minimum=1, default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Itens por página",
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=15)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Termo de busca (nome, email, subdomain)",
     *         @OA\Schema(type="string", maxLength=100)
     *     ),
     *     @OA\Parameter(
     *         name="plan",
     *         in="query",
     *         description="Filtrar por plano",
     *         @OA\Schema(type="string", enum={"basic", "premium", "enterprise"})
     *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filtrar por status ativo",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de tenants retornada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Tenant")),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="per_page", type="integer", example=15),
     *             @OA\Property(property="total", type="integer", example=25),
     *             @OA\Property(property="last_page", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=403, description="Acesso negado - apenas super admin")
     * )
     */
    public function indexForSuperAdmin()
    {
        $tenants = Tenant::with(['users' => function ($query) {
            $query->select('id', 'tenant_id', 'name', 'email', 'role', 'is_active');
        }])
        ->withCount(['users', 'vehicles', 'leads'])
        ->latest()
        ->paginate(20);

        return response()->json([
            'data' => $tenants->items(),
            'current_page' => $tenants->currentPage(),
            'per_page' => $tenants->perPage(),
            'total' => $tenants->total(),
            'last_page' => $tenants->lastPage(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/tenants",
     *     summary="Criar novo tenant",
     *     description="Cria um novo tenant no sistema com um usuário administrador inicial",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "subdomain", "email", "plan", "admin_name", "admin_email", "admin_password"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Concessionária ABC"),
     *             @OA\Property(property="subdomain", type="string", maxLength=100, example="abc", description="Subdomínio único (apenas letras, números e hífens)"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255, example="contato@abc.com.br"),
     *             @OA\Property(property="phone", type="string", maxLength=20, example="(11) 99999-9999"),
     *             @OA\Property(property="plan", type="string", enum={"trial", "basic", "premium", "enterprise"}, example="premium"),
     *             @OA\Property(property="admin_name", type="string", maxLength=255, example="João Silva"),
     *             @OA\Property(property="admin_email", type="string", format="email", maxLength=255, example="admin@abc.com.br"),
     *             @OA\Property(property="admin_password", type="string", minLength=6, example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tenant criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Tenant criado com sucesso"),
     *             @OA\Property(property="tenant", ref="#/components/schemas/Tenant"),
     *             @OA\Property(property="admin_user", type="object", description="Usuário administrador criado")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=403, description="Acesso negado - apenas super admin"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'super_admin') {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'subdomain' => 'required|string|max:100|unique:tenants,subdomain|regex:/^[a-z0-9\-]+$/',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'plan' => 'required|string|in:trial,basic,premium,enterprise',
            // Dados do admin do tenant
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        // Verificar se o email do admin já existe em algum tenant
        $existingAdmin = TenantUser::where('email', $request->admin_email)->first();
        if ($existingAdmin) {
            return response()->json(['error' => 'Email do administrador já está em uso'], 409);
        }

        // Definir features baseado no plano
        $features = $this->getPlanFeatures($request->plan);

        // Criar o tenant
        $tenant = Tenant::create([
            'name' => $request->name,
            'subdomain' => $request->subdomain,
            'email' => $request->email,
            'phone' => $request->phone,
            'plan' => $request->plan,
            'status' => 'active',
            'features' => $features,
            'trial_ends_at' => $request->plan === 'trial' ? now()->addDays(30) : null,
            'created_by' => Auth::user()->id,
        ]);

        // Criar o usuário admin do tenant
        $admin = TenantUser::create([
            'tenant_id' => $tenant->id,
            'name' => $request->admin_name,
            'email' => $request->admin_email,
            'password' => Hash::make($request->admin_password),
            'role' => 'admin',
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Tenant criado com sucesso',
            'tenant' => $tenant->load('users'),
            'admin' => $admin->makeHidden(['password'])
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/super-admin/tenants/{id}",
     *     summary="Exibir detalhes do tenant",
     *     tags={"Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalhes do tenant")
     * )
     */
    public function showForSuperAdmin($id)
    {
        $tenant = Tenant::with([
            'users' => function ($query) {
                $query->select('id', 'tenant_id', 'name', 'email', 'role', 'is_active', 'last_login_at', 'created_at');
            }
        ])
        ->withCount(['users', 'vehicles', 'leads'])
        ->find($id);

        if (!$tenant) {
            return response()->json(['error' => 'Tenant não encontrado'], 404);
        }

        // Estatísticas adicionais
        $stats = [
            'vehicles_this_month' => $tenant->vehicles()->whereMonth('created_at', now()->month)->count(),
            'leads_this_month' => $tenant->leads()->whereMonth('created_at', now()->month)->count(),
            'active_users' => $tenant->users()->where('is_active', true)->count(),
            'latest_login' => $tenant->users()->whereNotNull('last_login_at')->latest('last_login_at')->value('last_login_at'),
        ];

        return response()->json([
            'tenant' => $tenant,
            'stats' => $stats
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/super-admin/tenants/{id}",
     *     summary="Atualizar tenant",
     *     tags={"Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Tenant atualizado")
     * )
     */
    public function update(Request $request, $id)
    {
        $tenant = Tenant::find($id);

        if (!$tenant) {
            return response()->json(['error' => 'Tenant não encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'subdomain' => 'sometimes|required|string|max:100|regex:/^[a-z0-9\-]+$/|unique:tenants,subdomain,' . $id,
            'email' => 'sometimes|required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'plan' => 'sometimes|required|string|in:trial,basic,premium,enterprise',
            'status' => 'sometimes|required|string|in:active,inactive,suspended',
            'domain' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        $updateData = $request->only(['name', 'subdomain', 'email', 'phone', 'plan', 'status', 'domain']);

        // Atualizar features se o plano mudou
        if ($request->filled('plan') && $request->plan !== $tenant->plan) {
            $updateData['features'] = $this->getPlanFeatures($request->plan);
        }

        $tenant->update($updateData);

        return response()->json([
            'message' => 'Tenant atualizado com sucesso',
            'tenant' => $tenant->fresh()->load('users')
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/super-admin/tenants/{id}",
     *     summary="Deletar tenant",
     *     tags={"Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Tenant deletado")
     * )
     */
    public function destroy($id)
    {
        $tenant = Tenant::find($id);

        if (!$tenant) {
            return response()->json(['error' => 'Tenant não encontrado'], 404);
        }

        // Verificar se o tenant tem dados importantes
        $hasData = $tenant->vehicles()->count() > 0 || $tenant->leads()->count() > 0;

        if ($hasData) {
            return response()->json([
                'error' => 'Não é possível deletar um tenant com veículos ou leads. Desative-o em vez disso.'
            ], 422);
        }

        // Deletar usuários primeiro
        $tenant->users()->delete();

        // Deletar o tenant
        $tenant->delete();

        return response()->json(['message' => 'Tenant deletado com sucesso']);
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/tenants/{id}/activate",
     *     summary="Ativar tenant",
     *     tags={"Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Tenant ativado")
     * )
     */
    public function activate($id)
    {
        $tenant = Tenant::find($id);

        if (!$tenant) {
            return response()->json(['error' => 'Tenant não encontrado'], 404);
        }

        $tenant->update(['status' => 'active']);

        return response()->json([
            'message' => 'Tenant ativado com sucesso',
            'tenant' => $tenant
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/tenants/{id}/deactivate",
     *     summary="Desativar tenant",
     *     tags={"Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Tenant desativado")
     * )
     */
    public function deactivate($id)
    {
        $tenant = Tenant::find($id);

        if (!$tenant) {
            return response()->json(['error' => 'Tenant não encontrado'], 404);
        }

        $tenant->update(['status' => 'inactive']);

        return response()->json([
            'message' => 'Tenant desativado com sucesso',
            'tenant' => $tenant
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/super-admin/tenants/{id}/users",
     *     summary="Listar usuários do tenant",
     *     tags={"Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Lista de usuários")
     * )
     */
    public function getUsersForSuperAdmin($id)
    {
        $tenant = Tenant::find($id);

        if (!$tenant) {
            return response()->json(['error' => 'Tenant não encontrado'], 404);
        }

        $users = $tenant->users()
            ->select('id', 'name', 'email', 'role', 'is_active', 'last_login_at', 'created_at')
            ->latest()
            ->paginate(20);

        return response()->json($users);
    }

    /**
     * @OA\Get(
     *     path="/api/super-admin/tenants/{id}/stats",
     *     summary="Estatísticas do tenant",
     *     tags={"Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Estatísticas")
     * )
     */
    public function getStatsForSuperAdmin($id)
    {
        $tenant = Tenant::find($id);

        if (!$tenant) {
            return response()->json(['error' => 'Tenant não encontrado'], 404);
        }

        $stats = [
            'users' => [
                'total' => $tenant->users()->count(),
                'active' => $tenant->users()->where('is_active', true)->count(),
                'by_role' => $tenant->users()->selectRaw('role, COUNT(*) as count')->groupBy('role')->get(),
            ],
            'vehicles' => [
                'total' => $tenant->vehicles()->count(),
                'active' => $tenant->vehicles()->where('is_active', true)->count(),
                'this_month' => $tenant->vehicles()->whereMonth('created_at', now()->month)->count(),
            ],
            'leads' => [
                'total' => $tenant->leads()->count(),
                'this_month' => $tenant->leads()->whereMonth('created_at', now()->month)->count(),
                'by_status' => $tenant->leads()->selectRaw('status, COUNT(*) as count')->groupBy('status')->get(),
            ]
        ];

        return response()->json($stats);
    }

    /**
     * Obter features baseado no plano
     */
    private function getPlanFeatures($plan)
    {
        $features = [
            'trial' => [
                'max_vehicles' => 5,
                'max_users' => 2,
                'max_storage' => '100MB',
                'custom_domain' => false,
                'advanced_analytics' => false,
                'api_access' => false,
            ],
            'basic' => [
                'max_vehicles' => 50,
                'max_users' => 5,
                'max_storage' => '1GB',
                'custom_domain' => false,
                'advanced_analytics' => false,
                'api_access' => false,
            ],
            'premium' => [
                'max_vehicles' => 200,
                'max_users' => 15,
                'max_storage' => '5GB',
                'custom_domain' => true,
                'advanced_analytics' => true,
                'api_access' => true,
            ],
            'enterprise' => [
                'max_vehicles' => -1, // Ilimitado
                'max_users' => -1,    // Ilimitado
                'max_storage' => '50GB',
                'custom_domain' => true,
                'advanced_analytics' => true,
                'api_access' => true,
            ],
        ];

        return $features[$plan] ?? $features['trial'];
    }
}

/**
 * @OA\Schema(
 *     schema="Tenant",
 *     title="Tenant",
 *     description="Modelo de dados de um tenant",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Empresa Demo"),
 *     @OA\Property(property="subdomain", type="string", example="demo"),
 *     @OA\Property(property="custom_domain", type="string", nullable=true, example=null),
 *     @OA\Property(property="description", type="string", example="Portal de anúncios da Empresa Demo"),
 *     @OA\Property(property="contact_email", type="string", format="email", example="contato@demo.com"),
 *     @OA\Property(property="contact_phone", type="string", example="(11) 99999-9999"),
 *     @OA\Property(property="address", type="string", example="Rua das Flores, 123 - São Paulo/SP"),
 *     @OA\Property(property="theme_color", type="string", example="#28a745"),
 *     @OA\Property(property="logo_url", type="string", nullable=true, example="https://via.placeholder.com/200x80/007bff/ffffff?text=Empresa Demo"),
 *     @OA\Property(property="social_media", type="object",
 *         @OA\Property(property="facebook", type="string", example="https://facebook.com/demo"),
 *         @OA\Property(property="whatsapp", type="string", example="https://wa.me/5511999999999"),
 *         @OA\Property(property="instagram", type="string", example="https://instagram.com/demo")
 *     ),
 *     @OA\Property(property="business_hours", type="object"),
 *     @OA\Property(property="allow_registration", type="integer", example=1),
 *     @OA\Property(property="require_approval", type="integer", example=1),
 *     @OA\Property(property="is_default", type="integer", example=1),
 *     @OA\Property(property="email", type="string", format="email", example="contato@demo.com"),
 *     @OA\Property(property="phone", type="string", example="(11) 99999-9999"),
 *     @OA\Property(property="logo", type="string", nullable=true),
 *     @OA\Property(property="config", type="object"),
 *     @OA\Property(property="status", type="string", example="active"),
 *     @OA\Property(property="plan", type="string", example="premium"),
 *     @OA\Property(property="trial_ends_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="subscription_ends_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="features", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="created_by", type="integer", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="users_count", type="integer", example=3),
 *     @OA\Property(property="vehicles_count", type="integer", example=0),
 *     @OA\Property(property="leads_count", type="integer", example=0),
 *     @OA\Property(property="users", type="array", @OA\Items(ref="#/components/schemas/TenantUser"))
 * )
 */
