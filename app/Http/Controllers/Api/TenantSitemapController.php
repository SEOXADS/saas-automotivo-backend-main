<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantSitemapConfig;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\TenantSitemapRequest;

/**
 * @OA\Tag(
 *     name="2. Admin Cliente - Sitemaps",
 *     description="Gestão de configurações de sitemap por tenant"
 * )
 */
class TenantSitemapController extends Controller
{
    /**
     * Helper method to get tenant from request
     */
    private function getTenantFromRequest(Request $request)
    {
        return $request->attributes->get('current_tenant');
    }

    /**
     * Helper method to get tenant ID from request
     */
    private function getTenantIdFromRequest(Request $request): ?int
    {
        $tenant = $this->getTenantFromRequest($request);
        return $tenant ? $tenant->id : null;
    }

    /**
     * @OA\Get(
     *     path="/api/tenant/sitemap/configs",
     *     summary="Listar configurações de sitemap",
     *     tags={"2. Admin Cliente - Sitemaps"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrar por tipo de sitemap",
     *         required=false,
     *         @OA\Schema(type="string", enum={"index", "images", "videos", "articles", "vehicles", "pages"})
     *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filtrar por status ativo",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Buscar por nome",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Página",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Itens por página",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de configurações de sitemap",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="total", type="integer", example=50)
     *         )
     *     )
     * )
     */
    public function getConfigs(Request $request): JsonResponse
    {
        try {
            $tenant = $this->getTenantFromRequest($request);
            
            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'error' => 'Tenant não identificado'
                ], 401);
            }
            
            $tenantId = $tenant->id;

            $query = TenantSitemapConfig::forTenant($tenantId);

            // Filtros
            if ($request->has('type')) {
                $query->byType($request->type);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            // Ordenação
            $query->orderedByPriority();

            // Paginação
            $perPage = $request->get('per_page', 10);
            $configs = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $configs->items(),
                'current_page' => $configs->currentPage(),
                'last_page' => $configs->lastPage(),
                'per_page' => $configs->perPage(),
                'total' => $configs->total(),
                'from' => $configs->firstItem(),
                'to' => $configs->lastItem()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar configurações de sitemap', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'tenant_id' => $this->getTenantIdFromRequest($request)
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/tenant/sitemap/configs",
     *     summary="Criar configuração de sitemap",
     *     tags={"2. Admin Cliente - Sitemaps"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Sitemap de Veículos"),
     *             @OA\Property(property="type", type="string", enum={"index", "images", "videos", "articles", "vehicles", "pages"}, example="vehicles"),
     *             @OA\Property(property="url", type="string", example="https://seusite.com/sitemap-vehicles.xml"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="priority", type="number", format="float", example=0.8),
     *             @OA\Property(property="change_frequency", type="string", enum={"always", "hourly", "daily", "weekly", "monthly", "yearly", "never"}, example="daily"),
     *             @OA\Property(property="config_data", type="object", example={"include_images": true, "max_vehicles": 1000})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Configuração criada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Configuração de sitemap criada com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dados de validação inválidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function createConfig(TenantSitemapRequest $request): JsonResponse
    {
        try {
            $tenant = $this->getTenantFromRequest($request);
            
            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'error' => 'Tenant não identificado'
                ], 401);
            }
            
            $tenantId = $tenant->id;

            // Verificar se URL já existe para o tenant
            $existingConfig = TenantSitemapConfig::forTenant($tenantId)
                ->where('url', $request->url)
                ->first();

            if ($existingConfig) {
                return response()->json([
                    'success' => false,
                    'errors' => ['url' => ['Esta URL já está sendo usada']]
                ], 422);
            }

            $config = TenantSitemapConfig::create([
                'tenant_id' => $tenantId,
                'name' => $request->name,
                'type' => $request->type,
                'url' => $request->url,
                'is_active' => $request->get('is_active', true),
                'priority' => $request->get('priority', 0.5),
                'change_frequency' => $request->change_frequency,
                'config_data' => $request->get('config_data', [])
            ]);
    
            $this->handleSitemapScheduling($config);

            return response()->json([
                'success' => true,
                'message' => 'Configuração de sitemap criada com sucesso',
                'data' => $config
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao criar configuração de sitemap', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'tenant_id' => $this->getTenantIdFromRequest($request)
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Handles the immediate action after config creation.
     */
    protected function handleSitemapScheduling(TenantSitemapConfig $config)
    {
        try {
            // When a new config is created, generate ALL sitemaps for the tenant
            $this->generateSitemapForTenant($config->tenant_id, null, true);
            
        } catch (\Exception $e) {
            Log::error('Failed to generate initial sitemap', [
                'error' => $e->getMessage(),
                'tenant_id' => $config->tenant_id,
                'config_id' => $config->id
            ]);
        }
    }

 /**
 * Internal method to generate sitemap for a specific tenant
 */
private function generateSitemapForTenant(int $tenantId, ?string $type = null, bool $force = false): array
{
    Log::info('🎯 [Sitemap] Starting generateSitemapForTenant', [
        'tenant_id' => $tenantId,
        'type' => $type,
        'force' => $force
    ]);

    if ($type) {
        // Generate specific sitemap type
        Log::info('🎯 [Sitemap] Generating specific type', ['type' => $type]);
        
        $config = TenantSitemapConfig::forTenant($tenantId)
            ->byType($type)
            ->active()
            ->first();

        Log::info('🎯 [Sitemap] Config found for specific type', [
            'config_exists' => !is_null($config),
            'config_id' => $config->id ?? null,
            'config_type' => $config->type ?? null
        ]);

        if (!$config) {
            Log::warning('🎯 [Sitemap] No active config found for type', [
                'tenant_id' => $tenantId,
                'type' => $type
            ]);
            return [
                'success' => false,
                'error' => "Configuração de sitemap do tipo '{$type}' não encontrada"
            ];
        }

        $result = $this->generateSpecificSitemap($config, $force);
        Log::info('🎯 [Sitemap] Specific sitemap generation result', $result);
        return $result;
    } else {
        // Generate ALL sitemaps
        Log::info('🎯 [Sitemap] Generating ALL sitemaps for tenant', ['tenant_id' => $tenantId]);
        
        $configs = TenantSitemapConfig::forTenant($tenantId)->active()->get();
        
        Log::info('🎯 [Sitemap] Active configs found', [
            'count' => $configs->count(),
            'configs' => $configs->map(function($config) {
                return [
                    'id' => $config->id,
                    'type' => $config->type,
                    'name' => $config->name,
                    'is_active' => $config->is_active
                ];
            })->toArray()
        ]);

        $results = [];
        
        // First, generate all specific sitemaps (excluding index)
        $specificConfigs = $configs->where('type', '!=', 'index');
        Log::info('🎯 [Sitemap] Specific configs to generate', [
            'count' => $specificConfigs->count(),
            'types' => $specificConfigs->pluck('type')->toArray()
        ]);
        
        foreach ($specificConfigs as $config) {
            Log::info('🎯 [Sitemap] Generating specific sitemap', [
                'config_id' => $config->id,
                'type' => $config->type,
                'name' => $config->name
            ]);
            
            $results[$config->type] = $this->generateSpecificSitemap($config, $force);
            
            Log::info('🎯 [Sitemap] Generated specific sitemap result', [
                'type' => $config->type,
                'success' => $results[$config->type]['success'] ?? false
            ]);
        }
        
        // Then, generate the main index sitemap
        Log::info('🎯 [Sitemap] Generating main index sitemap');
        $mainIndexResult = $this->generateMainSitemap($tenantId, $force);
        $results['index'] = $mainIndexResult;
        
        Log::info('🎯 [Sitemap] All sitemaps generated', [
            'results' => array_keys($results),
            'overall_success' => !empty($results)
        ]);
        
        return [
            'success' => true,
            'message' => 'Todos os sitemaps gerados com sucesso',
            'results' => $results
        ];
    }
}


    /**
     * @OA\Get(
     *     path="/api/tenant/sitemap/configs/{id}",
     *     summary="Ver configuração de sitemap",
     *     tags={"2. Admin Cliente - Sitemaps"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da configuração",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuração encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Configuração não encontrada"
     *     )
     * )
     */
    public function getConfig(Request $request, int $id): JsonResponse
    {
        try {
            $tenant = $this->getTenantFromRequest($request);
            
            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'error' => 'Tenant não identificado'
                ], 401);
            }
            
            $tenantId = $tenant->id;

            $config = TenantSitemapConfig::forTenant($tenantId)->find($id);

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'error' => 'Configuração não encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $config
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar configuração de sitemap', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'tenant_id' => $this->getTenantIdFromRequest($request),
                'config_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/tenant/sitemap/configs/{id}",
     *     summary="Atualizar configuração de sitemap",
     *     tags={"2. Admin Cliente - Sitemaps"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da configuração",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Sitemap de Veículos Atualizado"),
     *             @OA\Property(property="type", type="string", enum={"index", "images", "videos", "articles", "vehicles", "pages"}),
     *             @OA\Property(property="url", type="string", example="https://seusite.com/sitemap-vehicles.xml"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="priority", type="number", format="float", example=0.8),
     *             @OA\Property(property="change_frequency", type="string", enum={"always", "hourly", "daily", "weekly", "monthly", "yearly", "never"}),
     *             @OA\Property(property="config_data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuração atualizada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Configuração atualizada com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Configuração não encontrada"
     *     )
     * )
     */
    public function updateConfig(TenantSitemapRequest $request, int $id): JsonResponse
    {
        try {
            $tenant = $this->getTenantFromRequest($request);
            
            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'error' => 'Tenant não identificado'
                ], 401);
            }
            
            $tenantId = $tenant->id;

            $config = TenantSitemapConfig::forTenant($tenantId)->find($id);

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'error' => 'Configuração não encontrada'
                ], 404);
            }

            // Verificar se URL já existe para outro config do mesmo tenant
            if ($request->has('url') && $request->url !== $config->url) {
                $existingConfig = TenantSitemapConfig::forTenant($tenantId)
                    ->where('url', $request->url)
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingConfig) {
                    return response()->json([
                        'success' => false,
                        'errors' => ['url' => ['Esta URL já está sendo usada']]
                    ], 422);
                }
            }

            $config->update($request->only([
                'name', 'type', 'url', 'is_active', 'priority', 'change_frequency', 'config_data'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Configuração atualizada com sucesso',
                'data' => $config->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar configuração de sitemap', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'tenant_id' => $this->getTenantIdFromRequest($request),
                'config_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/tenant/sitemap/configs/{id}",
     *     summary="Excluir configuração de sitemap",
     *     tags={"2. Admin Cliente - Sitemaps"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da configuração",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuração excluída com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Configuração excluída com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Configuração não encontrada"
     *     )
     * )
     */
    public function deleteConfig(Request $request, int $id): JsonResponse
    {
        try {
            $tenant = $this->getTenantFromRequest($request);
            
            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'error' => 'Tenant não identificado'
                ], 401);
            }
            
            $tenantId = $tenant->id;

            $config = TenantSitemapConfig::forTenant($tenantId)->find($id);

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'error' => 'Configuração não encontrada'
                ], 404);
            }

            $config->delete();

            return response()->json([
                'success' => true,
                'message' => 'Configuração excluída com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao excluir configuração de sitemap', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'tenant_id' => $this->getTenantIdFromRequest($request),
                'config_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/tenant/sitemap/generate",
     *     summary="Gerar sitemap.xml",
     *     tags={"2. Admin Cliente - Sitemaps"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", enum={"index", "images", "videos", "articles", "vehicles", "pages"}, description="Tipo específico para gerar"),
     *             @OA\Property(property="force", type="boolean", example=false, description="Forçar regeneração mesmo se arquivo existir")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sitemap gerado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sitemap gerado com sucesso"),
     *             @OA\Property(property="file_path", type="string", example="/public/sitemap.xml"),
     *             @OA\Property(property="url", type="string", example="https://seusite.com/sitemap.xml"),
     *             @OA\Property(property="generated_at", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function generateSitemap(Request $request): JsonResponse
    {
        try {
            $tenant = $this->getTenantFromRequest($request);
            
            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'error' => 'Tenant não identificado'
                ], 401);
            }
            
            $tenantId = $tenant->id;
            $type = $request->get('type');
            $force = $request->get('force', false);

            $validTypes = ['index', 'images', 'videos', 'articles', 'vehicles', 'pages', null];
            if ($type && !in_array($type, $validTypes)) {
                $type = null; // Generate all if invalid type is passed
            }


            $result = $this->generateSitemapForTenant($tenantId, $type, $force);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Erro ao gerar sitemap', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'tenant_id' => $this->getTenantIdFromRequest($request),
                'type' => $request->get('type')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }


    /**
     * Gerar sitemap principal (index)
     */
    private function generateMainSitemap(int $tenantId, bool $force = false): array
    {
        $filename = 'sitemap.xml';
        $filePath = "sitemaps/tenant_{$tenantId}/{$filename}";

        // Verificar se arquivo já existe e não é para forçar
        if (!$force && Storage::disk('public')->exists($filePath)) {
            return [
                'success' => true,
                'message' => 'Sitemap já existe',
                'file_path' => "/public/{$filePath}",
                'url' => config('app.url') . "/storage/{$filePath}",
                'generated_at' => Storage::disk('public')->lastModified($filePath)
            ];
        }

        // Obter configurações ativas
        $configs = TenantSitemapConfig::forTenant($tenantId)->active()->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($configs as $config) {
            $xml .= "  <sitemap>\n";
            $xml .= "    <loc>{$config->url}</loc>\n";
            $lastmod = $config->updated_at ? $config->updated_at->toISOString() : now()->toISOString();
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';

        // Salvar arquivo
        Storage::disk('public')->put($filePath, $xml);

        return [
            'success' => true,
            'message' => 'Sitemap gerado com sucesso',
            'file_path' => "/public/{$filePath}",
            'url' => config('app.url') . "/storage/{$filePath}",
            'generated_at' => now()->toISOString()
        ];
    }

    /**
     * Gerar sitemap específico por tipo
     */
    private function generateSpecificSitemap(TenantSitemapConfig $config, bool $force = false): array
{
    Log::info('📄 [Sitemap] Starting generateSpecificSitemap', [
        'config_id' => $config->id,
        'type' => $config->type,
        'tenant_id' => $config->tenant_id
    ]);

    $filename = "sitemap-{$config->type}.xml";
    $filePath = "sitemaps/tenant_{$config->tenant_id}/{$filename}";

    Log::info('📄 [Sitemap] File path info', [
        'filename' => $filename,
        'filePath' => $filePath,
        'disk' => 'public'
    ]);

    // Verificar se arquivo já existe e não é para forçar
    if (!$force && Storage::disk('public')->exists($filePath)) {
        Log::info('📄 [Sitemap] File already exists, skipping', [
            'filePath' => $filePath,
            'lastModified' => Storage::disk('public')->lastModified($filePath)
        ]);
        return [
            'success' => true,
            'message' => 'Sitemap já existe',
            'file_path' => "/public/{$filePath}",
            'url' => config('app.url') . "/storage/{$filePath}",
            'generated_at' => Storage::disk('public')->lastModified($filePath)
        ];
    }

    // Create directory if it doesn't exist
    $directory = dirname($filePath);
    if (!Storage::disk('public')->exists($directory)) {
        Storage::disk('public')->makeDirectory($directory);
        Log::info('📄 [Sitemap] Created directory', ['directory' => $directory]);
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    // Gerar URLs baseado no tipo
    Log::info('📄 [Sitemap] Generating URLs for type', ['type' => $config->type]);
    
    $urlsContent = '';
    switch ($config->type) {
        case 'vehicles':
            $urlsContent = $this->generateVehicleUrls($config);
            break;
        case 'images':
            $urlsContent = $this->generateImageUrls($config);
            break;
        case 'pages':
            $urlsContent = $this->generatePageUrls($config);
            break;
        default:
            $urlsContent = $this->generateDefaultUrls($config);
    }

    Log::info('📄 [Sitemap] Generated URL content length', [
        'type' => $config->type,
        'content_length' => strlen($urlsContent),
        'has_content' => !empty(trim($urlsContent))
    ]);

    $xml .= $urlsContent;
    $xml .= '</urlset>';

    // Salvar arquivo
    try {
        $bytesWritten = Storage::disk('public')->put($filePath, $xml);
        Log::info('📄 [Sitemap] File saved successfully', [
            'filePath' => $filePath,
            'bytesWritten' => $bytesWritten,
            'fileSize' => Storage::disk('public')->size($filePath)
        ]);
    } catch (\Exception $e) {
        Log::error('📄 [Sitemap] Failed to save file', [
            'error' => $e->getMessage(),
            'filePath' => $filePath
        ]);
        throw $e;
    }

    $fullUrl = config('app.url') . "/storage/{$filePath}";
    
    Log::info('📄 [Sitemap] Sitemap generation completed', [
        'type' => $config->type,
        'url' => $fullUrl,
        'file_exists' => Storage::disk('public')->exists($filePath)
    ]);

    return [
        'success' => true,
        'message' => 'Sitemap gerado com sucesso',
        'file_path' => "/public/{$filePath}",
        'url' => $fullUrl,
        'generated_at' => now()->toISOString()
    ];
}
/**
 * Gerar URLs de veículos
 */
private function generateVehicleUrls(TenantSitemapConfig $config): string
{
    Log::info('🚗 [Vehicle URLs] Starting generateVehicleUrls', [
        'config_id' => $config->id,
        'tenant_id' => $config->tenant_id
    ]);

    $urls = '';
    $tenantId = $config->tenant_id;
    
    // Get tenant
    $tenant = \App\Models\Tenant::find($tenantId);
    
    if (!$tenant) {
        Log::error('🚗 [Vehicle URLs] Tenant not found', ['tenant_id' => $tenantId]);
        return $urls;
    }

    // Use the tenant's custom domain
    $baseUrl = rtrim($tenant->custom_domain ?? config('app.url'), '/');
    Log::info('🚗 [Vehicle URLs] Base URL', ['baseUrl' => $baseUrl]);

    // Get vehicles
    $vehicles = Vehicle::where('tenant_id', $tenantId)
        ->where('is_active', true)
        ->orderBy('updated_at', 'desc')
        ->get();

    Log::info('🚗 [Vehicle URLs] Vehicles found', [
        'count' => $vehicles->count(),
        'tenant_id' => $tenantId
    ]);

    foreach ($vehicles as $vehicle) {
        // Check if vehicle has url column
        $vehicleSlug = $vehicle->url ?? $this->generateVehicleSlug($vehicle);
        
        $vehicleUrl = "{$baseUrl}/comprar-carro/{$vehicleSlug}";
        
        Log::debug('🚗 [Vehicle URLs] Adding vehicle', [
            'vehicle_id' => $vehicle->id,
            'slug' => $vehicleSlug,
            'url' => $vehicleUrl
        ]);

        $urls .= "  <url>\n";
        $urls .= "    <loc>{$vehicleUrl}</loc>\n";
        $urls .= "    <lastmod>" . $vehicle->updated_at->toISOString() . "</lastmod>\n";
        $urls .= "    <changefreq>daily</changefreq>\n";
        $urls .= "    <priority>0.8</priority>\n";
        $urls .= "  </url>\n";
    }

    Log::info('🚗 [Vehicle URLs] Generated URLs', [
        'total_urls' => $vehicles->count(),
        'content_length' => strlen($urls)
    ]);

    return $urls;
}

/**
 * Generate vehicle slug from vehicle data (fallback if url column is empty)
 */
private function generateVehicleSlug(Vehicle $vehicle): string
{
    // Get brand and model names from relationships
    $brand = $vehicle->brand ? $vehicle->brand->name : '';
    $model = $vehicle->model ? $vehicle->model->name : '';
    
    $parts = [
        $brand,
        $model,
        $vehicle->version ?? '',
        $vehicle->fuel_type ?? '',
        $vehicle->year ?? '',
        $vehicle->id
    ];
    
    // Create slug: brand-model-version-fuel-year-id
    $slug = collect($parts)
        ->filter()
        ->map(fn($part) => \Illuminate\Support\Str::slug($part))
        ->implode('-');
    
    return $slug;
}


 /**
 * Gerar URLs de imagens
 */
private function generateImageUrls(TenantSitemapConfig $config): string
{
    $urls = '';
    $tenantId = $config->tenant_id;
    
    // Images should use API URL (where they're actually served)
    $apiBaseUrl = "https://api.omegaveiculos.com.br";

    $images = VehicleImage::whereHas('vehicle', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId)->where('is_active', true);
        })
        ->orderBy('updated_at', 'desc')
        ->get();

    foreach ($images as $image) {
        $urls .= "  <url>\n";
        $urls .= "    <loc>{$apiBaseUrl}/api/public/images/{$tenantId}/{$image->vehicle_id}/{$image->filename}</loc>\n";
        $urls .= "    <lastmod>" . $image->updated_at->toISOString() . "</lastmod>\n";
        $urls .= "    <changefreq>{$config->change_frequency}</changefreq>\n";
        $urls .= "    <priority>{$config->priority}</priority>\n";
        $urls .= "  </url>\n";
    }

    return $urls;
}

/**
 * Gerar URLs de páginas estáticas
 */
private function generatePageUrls(TenantSitemapConfig $config): string
{
    $urls = '';
    $tenantId = $config->tenant_id;
    
    // Get tenant's custom domain
    $tenant = \App\Models\Tenant::find($tenantId);
    $baseUrl = $tenant->custom_domain ?? "https://{$tenant->subdomain}.com.br";
    $baseUrl = rtrim($baseUrl, '/');

    $pages = [
        '/' => 'Página inicial',
        '/sobre' => 'Sobre nós',
        '/contato' => 'Contato',
        '/veiculos' => 'Lista de veículos',
        '/marcas' => 'Marcas',
        '/financiamento' => 'Financiamento',
        '/seguros' => 'Seguros'
    ];

    foreach ($pages as $path => $title) {
        $urls .= "  <url>\n";
        $urls .= "    <loc>{$baseUrl}{$path}</loc>\n";
        $urls .= "    <lastmod>" . now()->toISOString() . "</lastmod>\n";
        $urls .= "    <changefreq>{$config->change_frequency}</changefreq>\n";
        $urls .= "    <priority>{$config->priority}</priority>\n";
        $urls .= "  </url>\n";
    }

    return $urls;
}


    /**
     * Gerar URLs padrão
     */
    private function generateDefaultUrls(TenantSitemapConfig $config): string
    {
        $urls = '';
        $baseUrl = config('app.url');

        $urls .= "  <url>\n";
        $urls .= "    <loc>{$baseUrl}</loc>\n";
        $urls .= "    <lastmod>" . now()->toISOString() . "</lastmod>\n";
        $urls .= "    <changefreq>{$config->change_frequency}</changefreq>\n";
        $urls .= "    <priority>{$config->priority}</priority>\n";
        $urls .= "  </url>\n";

        return $urls;
    }
}
