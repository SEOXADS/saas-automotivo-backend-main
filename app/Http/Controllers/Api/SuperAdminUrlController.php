<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\HierarchicalUrlService;
use App\Jobs\UrlMaintenanceJob;
use App\Jobs\SitemapMaintenanceJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SuperAdminUrlController extends Controller
{
    protected HierarchicalUrlService $urlService;

    public function __construct(HierarchicalUrlService $urlService)
    {
        $this->urlService = $urlService;
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/urls/generate",
     *     summary="Gerar URLs hierárquicas para um tenant",
     *     description="Gera todas as URLs hierárquicas (marcas, veículos, cidades, bairros) para um tenant específico",
     *     operationId="generateHierarchicalUrls",
     *     tags={"Super Admin - URLs"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tenant_id"},
     *             @OA\Property(property="tenant_id", type="integer", example=1, description="ID do tenant"),
     *             @OA\Property(property="clear_existing", type="boolean", example=false, description="Limpar URLs existentes antes de gerar novas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="URLs geradas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="URLs hierárquicas geradas com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="tenant_id", type="integer", example=1),
     *                 @OA\Property(property="results", type="object",
     *                     @OA\Property(property="brands", type="integer", example=5),
     *                     @OA\Property(property="vehicles", type="integer", example=150),
     *                     @OA\Property(property="city_urls", type="integer", example=750),
     *                     @OA\Property(property="neighborhood_urls", type="integer", example=2250),
     *                     @OA\Property(property="total_urls", type="integer", example=3155)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Tenant não encontrado"),
     *     @OA\Response(response=422, description="Dados inválidos"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function generateUrls(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'clear_existing' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tenantId = $request->tenant_id;
            $clearExisting = $request->boolean('clear_existing', false);

            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant não encontrado'
                ], 404);
            }

            // Limpar URLs existentes se solicitado
            if ($clearExisting) {
                $deletedCount = $this->urlService->clearTenantUrls($tenantId);
                Log::info('URLs existentes limpas', [
                    'tenant_id' => $tenantId,
                    'deleted_count' => $deletedCount
                ]);
            }

            // Gerar todas as URLs hierárquicas
            $results = $this->urlService->generateAllUrlsForTenant($tenantId);

            // Disparar job para atualizar sitemap
            SitemapMaintenanceJob::dispatch($tenantId, 'generate');

            return response()->json([
                'success' => true,
                'message' => 'URLs hierárquicas geradas com sucesso',
                'data' => [
                    'tenant_id' => $tenantId,
                    'tenant_name' => $tenant->name,
                    'tenant_subdomain' => $tenant->subdomain,
                    'results' => $results
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao gerar URLs hierárquicas', [
                'tenant_id' => $request->tenant_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/super-admin/urls/stats/{tenant_id}",
     *     summary="Obter estatísticas de URLs de um tenant",
     *     description="Retorna estatísticas detalhadas das URLs geradas para um tenant",
     *     operationId="getTenantUrlStats",
     *     tags={"Super Admin - URLs"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="tenant_id",
     *         in="path",
     *         description="ID do tenant",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estatísticas obtidas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="tenant_id", type="integer", example=1),
     *                 @OA\Property(property="total_urls", type="integer", example=3155),
     *                 @OA\Property(property="sitemap_urls", type="integer", example=3100),
     *                 @OA\Property(property="indexable_urls", type="integer", example=3155),
     *                 @OA\Property(property="by_type", type="object",
     *                     @OA\Property(property="collection", type="object",
     *                         @OA\Property(property="count", type="integer", example=2000),
     *                         @OA\Property(property="sitemap_count", type="integer", example=2000),
     *                         @OA\Property(property="indexable_count", type="integer", example=2000)
     *                     ),
     *                     @OA\Property(property="vehicle_detail", type="object",
     *                         @OA\Property(property="count", type="integer", example=1155),
     *                         @OA\Property(property="sitemap_count", type="integer", example=1100),
     *                         @OA\Property(property="indexable_count", type="integer", example=1155)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Tenant não encontrado")
     * )
     */
    public function getStats(int $tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant não encontrado'
                ], 404);
            }

            $stats = $this->urlService->getTenantUrlStats($tenantId);

            return response()->json([
                'success' => true,
                'data' => [
                    'tenant_id' => $tenantId,
                    'tenant_name' => $tenant->name,
                    'tenant_subdomain' => $tenant->subdomain,
                    ...$stats
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas de URLs', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/super-admin/urls/clear/{tenant_id}",
     *     summary="Limpar todas as URLs de um tenant",
     *     description="Remove todas as URLs SEO geradas para um tenant específico",
     *     operationId="clearTenantUrls",
     *     tags={"Super Admin - URLs"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="tenant_id",
     *         in="path",
     *         description="ID do tenant",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="URLs limpas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="URLs limpas com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="tenant_id", type="integer", example=1),
     *                 @OA\Property(property="deleted_count", type="integer", example=3155)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Tenant não encontrado")
     * )
     */
    public function clearUrls(int $tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant não encontrado'
                ], 404);
            }

            $deletedCount = $this->urlService->clearTenantUrls($tenantId);

            return response()->json([
                'success' => true,
                'message' => 'URLs limpas com sucesso',
                'data' => [
                    'tenant_id' => $tenantId,
                    'tenant_name' => $tenant->name,
                    'tenant_subdomain' => $tenant->subdomain,
                    'deleted_count' => $deletedCount
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao limpar URLs', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/urls/regenerate-all",
     *     summary="Regenerar URLs para todos os tenants",
     *     description="Regenera URLs hierárquicas para todos os tenants ativos",
     *     operationId="regenerateAllUrls",
     *     tags={"Super Admin - URLs"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="clear_existing", type="boolean", example=true, description="Limpar URLs existentes antes de gerar novas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Processo de regeneração iniciado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Regeneração de URLs iniciada para todos os tenants"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_tenants", type="integer", example=10),
     *                 @OA\Property(property="clear_existing", type="boolean", example=true)
     *             )
     *         )
     *     )
     * )
     */
    public function regenerateAllUrls(Request $request): JsonResponse
    {
        try {
            $clearExisting = $request->boolean('clear_existing', true);

            $tenants = Tenant::where('status', 'active')->get();

            Log::info('Iniciando regeneração de URLs para todos os tenants', [
                'total_tenants' => $tenants->count(),
                'clear_existing' => $clearExisting
            ]);

            foreach ($tenants as $tenant) {
                try {
                    if ($clearExisting) {
                        $this->urlService->clearTenantUrls($tenant->id);
                    }

                    $this->urlService->generateAllUrlsForTenant($tenant->id);
                    SitemapMaintenanceJob::dispatch($tenant->id, 'generate');

                } catch (\Exception $e) {
                    Log::error('Erro ao regenerar URLs para tenant', [
                        'tenant_id' => $tenant->id,
                        'tenant_subdomain' => $tenant->subdomain,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Regeneração de URLs iniciada para todos os tenants',
                'data' => [
                    'total_tenants' => $tenants->count(),
                    'clear_existing' => $clearExisting
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao regenerar URLs para todos os tenants', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
