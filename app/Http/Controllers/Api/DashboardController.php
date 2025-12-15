<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TenantUser;
use App\Models\Vehicle;
use App\Models\Lead;
use App\Models\Tenant;

/**
 * @OA\Tag(
 *     name="2. Admin Cliente",
 *     description="Endpoints para administradores do tenant (autenticação e gestão)"
 * )
 *
 * @OA\Schema(
 *     schema="DashboardStats",
 *     title="Estatísticas do Dashboard",
 *     description="Estatísticas gerais do dashboard",
 *     @OA\Property(property="total_vehicles", type="integer", example=150),
 *     @OA\Property(property="active_vehicles", type="integer", example=120),
 *     @OA\Property(property="total_leads", type="integer", example=25),
 *     @OA\Property(property="new_leads", type="integer", example=5),
 *     @OA\Property(property="total_users", type="integer", example=8),
 *     @OA\Property(property="active_users", type="integer", example=6)
 * )
 *
 * @OA\Schema(
 *     schema="SuperAdminDashboardStats",
 *     title="Estatísticas do Dashboard Super Admin",
 *     description="Estatísticas globais do sistema para super admin",
 *     @OA\Property(property="tenants", type="object",
 *         @OA\Property(property="total", type="integer", example=25),
 *         @OA\Property(property="active", type="integer", example=20),
 *         @OA\Property(property="inactive", type="integer", example=5)
 *     ),
 *     @OA\Property(property="users", type="object",
 *         @OA\Property(property="total", type="integer", example=150),
 *         @OA\Property(property="active", type="integer", example=120),
 *         @OA\Property(property="inactive", type="integer", example=30)
 *     ),
 *     @OA\Property(property="vehicles", type="object",
 *         @OA\Property(property="total", type="integer", example=1500),
 *         @OA\Property(property="active", type="integer", example=1200),
 *         @OA\Property(property="inactive", type="integer", example=300)
 *     ),
 *     @OA\Property(property="leads", type="object",
 *         @OA\Property(property="total", type="integer", example=500),
 *         @OA\Property(property="new", type="integer", example=50),
 *         @OA\Property(property="converted", type="integer", example=100)
 *     )
 * )
 */
class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/super-admin/dashboard",
     *     summary="Dashboard do Super Admin",
     *     description="Retorna estatísticas globais do sistema para super administradores",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard do super admin retornado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="stats", ref="#/components/schemas/SuperAdminDashboardStats"),
     *             @OA\Property(property="recent_tenants", type="array", @OA\Items(ref="#/components/schemas/Tenant")),
     *             @OA\Property(property="recent_activity", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="system_info", type="object",
     *                 @OA\Property(property="version", type="string", example="v2.0.0"),
     *                 @OA\Property(property="environment", type="string", example="production"),
     *                 @OA\Property(property="maintenance_mode", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=403, description="Acesso negado - apenas super admin")
     * )
     */
    public function index(Request $request)
    {
        $user = \App\Helpers\TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        // Determinar se é um tenant user ou super admin
        if ($user->role === 'super_admin') {
            return response()->json(['error' => 'Super admin não pode acessar dashboard de tenant'], 403);
        }

        // Buscar tenant do usuário
        $tenantUser = TenantUser::where('email', $user->email)->first();

        if (!$tenantUser) {
            return response()->json(['error' => 'Usuário não encontrado no tenant'], 404);
        }

        $tenant = $tenantUser->tenant;

        // Estatísticas
        $stats = [
            'total_vehicles' => Vehicle::where('tenant_id', $tenant->id)->count(),
            'active_vehicles' => Vehicle::where('tenant_id', $tenant->id)->where('status', 'active')->count(),
            'total_leads' => Lead::where('tenant_id', $tenant->id)->count(),
            'leads_this_month' => Lead::where('tenant_id', $tenant->id)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'total_users' => TenantUser::where('tenant_id', $tenant->id)->count(),
        ];

        // Veículos recentes
        $recentVehicles = Vehicle::where('tenant_id', $tenant->id)
            ->with(['brand', 'model'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'title' => $vehicle->title,
                    'brand' => $vehicle->brand?->name,
                    'model' => $vehicle->model?->name,
                    'price' => $vehicle->price,
                    'status' => $vehicle->status,
                    'created_at' => $vehicle->created_at
                ];
            });

        // Leads recentes
        $recentLeads = Lead::where('tenant_id', $tenant->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'vehicle_id' => $lead->vehicle_id,
                    'status' => $lead->status,
                    'created_at' => $lead->created_at
                ];
            });

        // Informações do usuário
        $userInfo = [
            'id' => $tenantUser->id,
            'name' => $tenantUser->name,
            'email' => $tenantUser->email,
            'role' => $tenantUser->role,
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'subdomain' => $tenant->subdomain,
                'plan' => $tenant->plan,
                'status' => $tenant->status
            ]
        ];

        return response()->json([
            'stats' => $stats,
            'recent_vehicles' => $recentVehicles,
            'recent_leads' => $recentLeads,
            'user' => $userInfo
        ]);
    }
}
