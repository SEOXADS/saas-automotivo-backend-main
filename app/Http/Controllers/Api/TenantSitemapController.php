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
            $tenantId = $request->user()->tenant_id;

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
                'tenant_id' => $request->user()->tenant_id ?? null
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
            $tenantId = $request->user()->tenant_id;

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

            return response()->json([
                'success' => true,
                'message' => 'Configuração de sitemap criada com sucesso',
                'data' => $config
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao criar configuração de sitemap', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
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
            $tenantId = $request->user()->tenant_id;

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
                'tenant_id' => $request->user()->tenant_id ?? null,
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
            $tenantId = $request->user()->tenant_id;

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
                'tenant_id' => $request->user()->tenant_id ?? null,
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
            $tenantId = $request->user()->tenant_id;

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
                'tenant_id' => $request->user()->tenant_id ?? null,
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
            $tenantId = $request->user()->tenant_id;
            $type = $request->get('type');
            $force = $request->get('force', false);

            // Se tipo específico foi solicitado
            if ($type) {
                $config = TenantSitemapConfig::forTenant($tenantId)
                    ->byType($type)
                    ->active()
                    ->first();

                if (!$config) {
                    return response()->json([
                        'success' => false,
                        'error' => "Configuração de sitemap do tipo '{$type}' não encontrada"
                    ], 404);
                }

                $result = $this->generateSpecificSitemap($config, $force);
            } else {
                // Gerar sitemap principal (index)
                $result = $this->generateMainSitemap($tenantId, $force);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Erro ao gerar sitemap', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null,
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
            $xml .= "    <lastmod>" . $config->updated_at->toISOString() . "</lastmod>\n";
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
        $filename = "sitemap-{$config->type}.xml";
        $filePath = "sitemaps/tenant_{$config->tenant_id}/{$filename}";

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

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Gerar URLs baseado no tipo
        switch ($config->type) {
            case 'vehicles':
                $xml .= $this->generateVehicleUrls($config);
                break;
            case 'images':
                $xml .= $this->generateImageUrls($config);
                break;
            case 'pages':
                $xml .= $this->generatePageUrls($config);
                break;
            default:
                $xml .= $this->generateDefaultUrls($config);
        }

        $xml .= '</urlset>';

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
     * Gerar URLs de veículos
     */
    private function generateVehicleUrls(TenantSitemapConfig $config): string
    {
        $urls = '';
        $tenantId = $config->tenant_id;
        $configData = $config->getConfigForType();

        $vehicles = Vehicle::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->limit($configData['max_vehicles'] ?? 1000)
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach ($vehicles as $vehicle) {
            $urls .= "  <url>\n";
            $urls .= "    <loc>" . url("/veiculos/{$vehicle->id}") . "</loc>\n";
            $urls .= "    <lastmod>" . $vehicle->updated_at->toISOString() . "</lastmod>\n";
            $urls .= "    <changefreq>{$config->change_frequency}</changefreq>\n";
            $urls .= "    <priority>{$config->priority}</priority>\n";
            $urls .= "  </url>\n";

            // Incluir imagens se configurado
            if ($configData['include_images'] ?? true) {
                $images = VehicleImage::where('vehicle_id', $vehicle->id)
                    ->where('is_active', true)
                    ->get();

                foreach ($images as $image) {
                    $urls .= "  <url>\n";
                    $urls .= "    <loc>" . url("/api/public/images/{$tenantId}/{$vehicle->id}/{$image->filename}") . "</loc>\n";
                    $urls .= "    <lastmod>" . $image->updated_at->toISOString() . "</lastmod>\n";
                    $urls .= "    <changefreq>monthly</changefreq>\n";
                    $urls .= "    <priority>0.3</priority>\n";
                    $urls .= "  </url>\n";
                }
            }
        }

        return $urls;
    }

    /**
     * Gerar URLs de imagens
     */
    private function generateImageUrls(TenantSitemapConfig $config): string
    {
        $urls = '';
        $tenantId = $config->tenant_id;
        $configData = $config->getConfigForType();

        $images = VehicleImage::whereHas('vehicle', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)->where('is_active', true);
            })
            ->where('is_active', true)
            ->limit($configData['max_images'] ?? 500)
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach ($images as $image) {
            $urls .= "  <url>\n";
            $urls .= "    <loc>" . url("/api/public/images/{$tenantId}/{$image->vehicle_id}/{$image->filename}") . "</loc>\n";
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
        $baseUrl = config('app.url');

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
