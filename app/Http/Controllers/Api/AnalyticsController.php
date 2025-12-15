<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TenantAnalytics;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Analytics",
 *     description="Endpoints para métricas e analytics do portal"
 * )
 */
class AnalyticsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/analytics/dashboard",
     *     summary="Dashboard de analytics",
     *     description="Retorna estatísticas resumidas de analytics para o dashboard administrativo",
     *     tags={"Analytics"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="days", in="query", description="Número de dias para análise", @OA\Schema(type="integer", minimum=1, maximum=365, default=30)),
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard de analytics retornado com sucesso",
      *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object")
 *         )
     *     ),
     *     @OA\Response(response=400, description="Tenant não identificado"),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getDashboard(Request $request)
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $days = $request->get('days', 30);

            if (!$tenant) {
                return response()->json(['error' => 'Tenant não identificado'], 400);
            }

            $stats = TenantAnalytics::getStats($tenant->id, $days);

            // Calcular métricas adicionais
            $totalPageViews = $stats['page_view']['total'] ?? 0;
            $totalLeads = $stats['lead_created']['total'] ?? 0;
            $totalVehicleViews = $stats['vehicle_viewed']['total'] ?? 0;
            $totalSearches = $stats['search_performed']['total'] ?? 0;

            $dashboard = [
                'period' => [
                    'days' => $days,
                    'start_date' => now()->subDays($days)->toDateString(),
                    'end_date' => now()->toDateString()
                ],
                'overview' => [
                    'total_page_views' => $totalPageViews,
                    'total_leads' => $totalLeads,
                    'total_vehicle_views' => $totalVehicleViews,
                    'total_searches' => $totalSearches
                ],
                'conversion_rates' => [
                    'leads_per_page_view' => $totalPageViews > 0 ? round(($totalLeads / $totalPageViews) * 100, 2) : 0,
                    'leads_per_vehicle_view' => $totalVehicleViews > 0 ? round(($totalLeads / $totalVehicleViews) * 100, 2) : 0
                ],
                'daily_stats' => $stats,
                'top_pages' => $this->getTopPages($tenant->id, $days),
                'top_searches' => $this->getTopSearches($tenant->id, $days),
                'lead_sources' => $this->getLeadSources($tenant->id, $days)
            ];

            return response()->json([
                'success' => true,
                'data' => $dashboard
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter dashboard de analytics', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/analytics/page-views",
     *     summary="Visualizações de página",
     *     description="Retorna estatísticas detalhadas de visualizações de página",
     *     tags={"Analytics"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="page", in="query", description="Nome da página", @OA\Schema(type="string")),
     *     @OA\Parameter(name="days", in="query", description="Número de dias", @OA\Schema(type="integer", default=30)),
     *     @OA\Response(
     *         response=200,
     *         description="Estatísticas de visualizações retornadas com sucesso",
      *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object")
 *         )
     *     )
     * )
     */
    public function getPageViews(Request $request)
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $page = $request->get('page');
            $days = $request->get('days', 30);

            if (!$tenant) {
                return response()->json(['error' => 'Tenant não identificado'], 400);
            }

            $query = TenantAnalytics::byTenant($tenant->id)
                ->byMetricType('page_view')
                ->recent($days);

            if ($page) {
                $query->where('metric_name', $page);
            }

            $stats = $query->selectRaw('
                    metric_name,
                    DATE(recorded_at) as date,
                    COUNT(*) as views,
                    COUNT(DISTINCT ip_address) as unique_visitors
                ')
                ->groupBy('metric_name', 'date')
                ->orderBy('date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => ['days' => $days],
                    'stats' => $stats,
                    'summary' => [
                        'total_views' => $stats->sum('views'),
                        'total_unique_visitors' => $stats->sum('unique_visitors'),
                        'pages_tracked' => $stats->unique('metric_name')->count()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas de visualizações', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/analytics/leads",
     *     summary="Estatísticas de leads",
     *     description="Retorna estatísticas detalhadas de leads criados",
     *     tags={"Analytics"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="days", in="query", description="Número de dias", @OA\Schema(type="integer", default=30)),
     *     @OA\Response(
     *         response=200,
     *         description="Estatísticas de leads retornadas com sucesso",
      *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object")
 *         )
     *     )
     * )
     */
    public function getLeadStats(Request $request)
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $days = $request->get('days', 30);

            if (!$tenant) {
                return response()->json(['error' => 'Tenant não identificado'], 400);
            }

            $stats = TenantAnalytics::byTenant($tenant->id)
                ->byMetricType('lead_created')
                ->recent($days)
                ->selectRaw('
                    DATE(recorded_at) as date,
                    COUNT(*) as leads_created,
                    COUNT(DISTINCT ip_address) as unique_sources
                ')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => ['days' => $days],
                    'daily_stats' => $stats,
                    'summary' => [
                        'total_leads' => $stats->sum('leads_created'),
                        'total_days' => $stats->count(),
                        'average_leads_per_day' => $stats->count() > 0 ? round($stats->sum('leads_created') / $stats->count(), 2) : 0
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas de leads', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/analytics/search",
     *     summary="Estatísticas de busca",
     *     description="Retorna estatísticas detalhadas de buscas realizadas",
     *     tags={"Analytics"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="days", in="query", description="Número de dias", @OA\Schema(type="integer", default=30)),
     *     @OA\Response(
     *         response=200,
     *         description="Estatísticas de busca retornadas com sucesso",
      *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object")
 *         )
     *     )
     * )
     */
    public function getSearchStats(Request $request)
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $days = $request->get('days', 30);

            if (!$tenant) {
                return response()->json(['error' => 'Tenant não identificado'], 400);
            }

            $stats = TenantAnalytics::byTenant($tenant->id)
                ->byMetricType('search_performed')
                ->recent($days)
                ->selectRaw('
                    metric_data->>"$.search_term" as search_term,
                    COUNT(*) as searches,
                    COUNT(DISTINCT ip_address) as unique_users
                ')
                ->groupBy('search_term')
                ->orderBy('searches', 'desc')
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => ['days' => $days],
                    'top_searches' => $stats,
                    'summary' => [
                        'total_searches' => $stats->sum('searches'),
                        'unique_search_terms' => $stats->count(),
                        'average_searches_per_term' => $stats->count() > 0 ? round($stats->sum('searches') / $stats->count(), 2) : 0
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas de busca', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Obtém o tenant atual
     */
    private function getCurrentTenant(Request $request): ?Tenant
    {
        if ($request->has('current_tenant')) {
            return $request->get('current_tenant');
        }

        $subdomain = $request->header('X-Tenant-Subdomain');
        if ($subdomain) {
            return Tenant::where('subdomain', $subdomain)
                ->where('status', 'active')
                ->first();
        }

        return null;
    }

    /**
     * Obtém as páginas mais visitadas
     */
    private function getTopPages(int $tenantId, int $days): array
    {
        return TenantAnalytics::byTenant($tenantId)
            ->byMetricType('page_view')
            ->recent($days)
            ->selectRaw('
                metric_name,
                COUNT(*) as views,
                COUNT(DISTINCT ip_address) as unique_visitors
            ')
            ->groupBy('metric_name')
            ->orderBy('views', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Obtém as buscas mais populares
     */
    private function getTopSearches(int $tenantId, int $days): array
    {
        return TenantAnalytics::byTenant($tenantId)
            ->byMetricType('search_performed')
            ->recent($days)
            ->selectRaw('
                metric_data->>"$.search_term" as search_term,
                COUNT(*) as searches
            ')
            ->groupBy('search_term')
            ->orderBy('searches', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Obtém as fontes de leads
     */
    private function getLeadSources(int $tenantId, int $days): array
    {
        return TenantAnalytics::byTenant($tenantId)
            ->byMetricType('lead_created')
            ->recent($days)
            ->selectRaw('
                metric_data->>"$.source" as source,
                COUNT(*) as leads
            ')
            ->groupBy('source')
            ->orderBy('leads', 'desc')
            ->get()
            ->toArray();
    }
}
