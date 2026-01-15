<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantRobotsConfig;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use function Illuminate\Log\log;

/**
 * @OA\Tag(
 *     name="Super Admin - Robots.txt",
 *     description="Gestão de robots.txt por tenant - Acesso exclusivo Super Admin"
 * )
 *
 * @OA\Schema(
 *     schema="RobotsConfigRequest",
 *     type="object",
 *     required={"tenant_id","locale"},
 *     @OA\Property(property="tenant_id", type="integer", example=1),
 *     @OA\Property(property="locale", type="string", example="pt-BR"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="user_agent_rules", type="object"),
 *     @OA\Property(property="sitemap_urls", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="custom_rules", type="string", example="# Custom rules"),
 *     @OA\Property(property="host_directive", type="string", example="www.example.com"),
 *     @OA\Property(property="include_sitemap_index", type="boolean", example=true),
 *     @OA\Property(property="include_sitemap_files", type="boolean", example=true),
 *     @OA\Property(property="notes", type="string", example="Configuração personalizada")
 * )
 *
 * @OA\Schema(
 *     schema="RobotsConfigResponse",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="tenant_id", type="integer", example=1),
 *     @OA\Property(property="tenant", type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="subdomain", type="string", example="omegaveiculos")
 *     ),
 *     @OA\Property(property="locale", type="string", example="pt-BR"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="user_agent_rules", type="object"),
 *     @OA\Property(property="sitemap_urls", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="custom_rules", type="string"),
 *     @OA\Property(property="host_directive", type="string"),
 *     @OA\Property(property="include_sitemap_index", type="boolean"),
 *     @OA\Property(property="include_sitemap_files", type="boolean"),
 *     @OA\Property(property="notes", type="string"),
 *     @OA\Property(property="last_generated_at", type="string", format="date-time"),
 *     @OA\Property(property="last_generated_by", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class TenantRobotsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/super-admin/robots/configs",
     *     summary="Listar configurações de robots.txt",
     *     description="Lista todas as configurações de robots.txt dos tenants",
     *     operationId="listRobotsConfigs",
     *     tags={"Super Admin - Robots.txt"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="tenant_id",
     *         in="query",
     *         description="ID do tenant para filtrar",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="locale",
     *         in="query",
     *         description="Locale para filtrar",
     *         required=false,
     *         @OA\Schema(type="string", example="pt-BR")
     *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filtrar por status ativo",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configurações listadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/RobotsConfigResponse"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Não autorizado")
     * )
     */
    public function listConfigs(Request $request): JsonResponse
    {
        try {
            $query = TenantRobotsConfig::with('tenant');

            if ($request->has('tenant_id')) {
                $query->where('tenant_id', $request->tenant_id);
            }

            if ($request->has('locale')) {
                $query->where('locale', $request->locale);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $configs = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $configs
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar configurações de robots', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/robots/configs",
     *     summary="Criar configuração de robots.txt",
     *     description="Cria uma nova configuração de robots.txt para um tenant",
     *     operationId="createRobotsConfig",
     *     tags={"Super Admin - Robots.txt"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RobotsConfigRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Configuração criada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/RobotsConfigResponse")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function createConfig(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'locale' => 'required|string|max:10',
            'is_active' => 'nullable|boolean',
            'user_agent_rules' => 'nullable|array',
            'sitemap_urls' => 'nullable|array',
            'custom_rules' => 'nullable|string',
            'host_directive' => 'nullable|string|max:255',
            'include_sitemap_index' => 'nullable|boolean',
            'include_sitemap_files' => 'nullable|boolean',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verificar se já existe configuração para o tenant+locale
            $existingConfig = TenantRobotsConfig::where('tenant_id', $request->tenant_id)
                                               ->where('locale', $request->locale)
                                               ->first();

            if ($existingConfig) {
                return response()->json([
                    'success' => false,
                    'message' => 'Já existe uma configuração para este tenant e locale'
                ], 409);
            }

            $config = TenantRobotsConfig::create($request->all());

            return response()->json([
                'success' => true,
                'data' => $config->load('tenant')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao criar configuração de robots', [
                'tenant_id' => $request->tenant_id,
                'locale' => $request->locale,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/super-admin/robots/configs/{id}",
     *     summary="Obter configuração específica",
     *     description="Retorna uma configuração específica de robots.txt",
     *     operationId="getRobotsConfig",
     *     tags={"Super Admin - Robots.txt"},
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
     *             @OA\Property(property="data", ref="#/components/schemas/RobotsConfigResponse")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=404, description="Configuração não encontrada")
     * )
     */
    public function getConfig(int $id): JsonResponse
    {
        try {
            $config = TenantRobotsConfig::with('tenant')->find($id);

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuração não encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $config
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter configuração de robots', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/super-admin/robots/configs/{id}",
     *     summary="Atualizar configuração de robots.txt",
     *     description="Atualiza uma configuração existente de robots.txt",
     *     operationId="updateRobotsConfig",
     *     tags={"Super Admin - Robots.txt"},
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
     *         @OA\JsonContent(ref="#/components/schemas/RobotsConfigRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuração atualizada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/RobotsConfigResponse")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=404, description="Configuração não encontrada"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function updateConfig(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'sometimes|integer|exists:tenants,id',
            'locale' => 'sometimes|string|max:10',
            'is_active' => 'nullable|boolean',
            'user_agent_rules' => 'nullable|array',
            'sitemap_urls' => 'nullable|array',
            'custom_rules' => 'nullable|string',
            'host_directive' => 'nullable|string|max:255',
            'include_sitemap_index' => 'nullable|boolean',
            'include_sitemap_files' => 'nullable|boolean',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $config = TenantRobotsConfig::find($id);

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuração não encontrada'
                ], 404);
            }

            $config->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $config->load('tenant')
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar configuração de robots', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/super-admin/robots/configs/{id}",
     *     summary="Deletar configuração de robots.txt",
     *     description="Remove uma configuração de robots.txt",
     *     operationId="deleteRobotsConfig",
     *     tags={"Super Admin - Robots.txt"},
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
     *         description="Configuração deletada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Configuração deletada com sucesso")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=404, description="Configuração não encontrada")
     * )
     */
    public function deleteConfig(int $id): JsonResponse
    {
        try {
            $config = TenantRobotsConfig::find($id);

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuração não encontrada'
                ], 404);
            }

            $config->delete();

            return response()->json([
                'success' => true,
                'message' => 'Configuração deletada com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao deletar configuração de robots', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/robots/generate",
     *     summary="Gerar robots.txt",
     *     description="Gera e salva o arquivo robots.txt para um tenant",
     *     operationId="generateRobots",
     *     tags={"Super Admin - Robots.txt"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tenant_id"},
     *             @OA\Property(property="tenant_id", type="integer", example=1),
     *             @OA\Property(property="locale", type="string", example="pt-BR"),
     *             @OA\Property(property="config_id", type="integer", example=1, description="ID da configuração específica (opcional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Robots.txt gerado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Robots.txt gerado com sucesso"),
     *             @OA\Property(property="file_path", type="string", example="storage/app/robots/omegaveiculos/robots.txt"),
     *             @OA\Property(property="content", type="string", example="User-agent: *\nDisallow: /admin/\nSitemap: https://example.com/sitemap.xml")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=404, description="Tenant ou configuração não encontrada"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function generateRobots(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'locale' => 'nullable|string|max:10',
            'config_id' => 'nullable|integer|exists:tenant_robots_configs,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tenant = Tenant::find($request->tenant_id);
            $locale = $request->locale ?? 'pt-BR';

            // Buscar configuração
            if ($request->config_id) {
                $config = TenantRobotsConfig::find($request->config_id);
            } else {
                $config = TenantRobotsConfig::where('tenant_id', $request->tenant_id)
                                           ->where('locale', $locale)
                                           ->active()
                                           ->first();
            }

            if (!$config) {
                // Criar configuração padrão se não existir
                $config = TenantRobotsConfig::create(
                    TenantRobotsConfig::getDefaultConfig($request->tenant_id, $locale)
                );
            }

            // Gerar conteúdo do robots.txt
            $content = $config->generateRobotsContent();

            // Salvar arquivo
            $filePath = $this->saveRobotsToFile($content, $tenant);

            // Atualizar metadados da configuração
            $config->update([
                'last_generated_at' => Carbon::now(),
                'last_generated_by' => $request->user()->id ?? 'system'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Robots.txt gerado com sucesso',
                'file_path' => $filePath,
                'content' => $content
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao gerar robots.txt', [
                'tenant_id' => $request->tenant_id,
                'locale' => $request->locale,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/super-admin/robots/preview",
     *     summary="Preview do robots.txt",
     *     description="Gera preview do conteúdo do robots.txt sem salvar",
     *     operationId="previewRobots",
     *     tags={"Super Admin - Robots.txt"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="tenant_id",
     *         in="query",
     *         description="ID do tenant",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="locale",
     *         in="query",
     *         description="Locale",
     *         required=false,
     *         @OA\Schema(type="string", example="pt-BR")
     *     ),
     *     @OA\Parameter(
     *         name="config_id",
     *         in="query",
     *         description="ID da configuração específica",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Preview gerado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="content", type="string", example="User-agent: *\nDisallow: /admin/\nSitemap: https://example.com/sitemap.xml")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=404, description="Tenant ou configuração não encontrada")
     * )
     */
    public function previewRobots(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'locale' => 'nullable|string|max:10',
            'config_id' => 'nullable|integer|exists:tenant_robots_configs,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $locale = $request->locale ?? 'pt-BR';

            // Buscar configuração
            if ($request->config_id) {
                $config = TenantRobotsConfig::find($request->config_id);
            } else {
                $config = TenantRobotsConfig::where('tenant_id', $request->tenant_id)
                                           ->where('locale', $locale)
                                           ->active()
                                           ->first();
            }

            if (!$config) {
                // Usar configuração padrão para preview
                $config = new TenantRobotsConfig(
                    TenantRobotsConfig::getDefaultConfig($request->tenant_id, $locale)
                );
            }

            // Gerar conteúdo do robots.txt
            $content = $config->generateRobotsContent();

            return response()->json([
                'success' => true,
                'content' => $content
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao gerar preview do robots.txt', [
                'tenant_id' => $request->tenant_id,
                'locale' => $request->locale,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/robots/serve",
     *     summary="Servir arquivo robots.txt",
     *     description="Serve o arquivo robots.txt de um tenant específico",
     *     operationId="serveRobotsFile",
     *     tags={"Robots.txt - Público"},
     *     @OA\Parameter(
     *         name="tenant",
     *         in="query",
     *         description="Subdomínio do tenant",
     *         required=true,
     *         @OA\Schema(type="string", example="omegaveiculos")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Arquivo robots.txt servido com sucesso",
     *         @OA\MediaType(
     *             mediaType="text/plain",
     *             @OA\Schema(type="string", example="User-agent: *\nDisallow: /admin/\nSitemap: https://example.com/sitemap.xml")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Tenant não encontrado ou arquivo não existe")
     * )
     */
    public function serveRobotsFile(Request $request): \Illuminate\Http\Response
    {
        $validator = Validator::make($request->all(), [
            'tenant' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response('Parâmetros inválidos', 400);
        }

        try {
            $tenant = Tenant::where('subdomain', $request->tenant)->first();

            if (!$tenant) {
                return response('Tenant não encontrado', 404);
            }

            $filePath = storage_path("app/robots/{$tenant->subdomain}/robots.txt");

            if (!file_exists($filePath)) {
                return response('Arquivo robots.txt não encontrado', 404);
            }

            $content = file_get_contents($filePath);

            return response($content, 200, [
                'Content-Type' => 'text/plain; charset=utf-8',
                'Cache-Control' => 'public, max-age=3600',
                'Last-Modified' => gmdate('D, d M Y H:i:s', filemtime($filePath)) . ' GMT'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao servir arquivo robots.txt', [
                'tenant' => $request->tenant,
                'error' => $e->getMessage()
            ]);

            return response('Erro interno do servidor', 500);
        }
    }

    /**
     * Salvar robots.txt em arquivo específico do tenant
     */
    private function saveRobotsToFile(string $content, Tenant $tenant): string
    {
        try {
            // Criar diretório específico do tenant no storage
            $tenantDir = storage_path("app/robots/{$tenant->subdomain}");

            if (!file_exists($tenantDir)) {
                mkdir($tenantDir, 0755, true);
            }

            // Definir caminho do arquivo
            $filepath = "{$tenantDir}/robots.txt";

            // Salvar arquivo
            file_put_contents($filepath, $content);

            Log::info('Robots.txt salvo com sucesso', [
                'tenant' => $tenant->subdomain,
                'filepath' => $filepath,
                'content_length' => strlen($content)
            ]);

            return $filepath;

        } catch (\Exception $e) {
            Log::error('Erro ao salvar robots.txt', [
                'tenant' => $tenant->subdomain,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
/**
 * Get robots.txt config for the current tenant
 */
public function getTenantConfig(Request $request): JsonResponse
{
    try {
        $tenant = $request->attributes->get('current_tenant');
        
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant não identificado'
            ], 404);
        }

        $config = TenantRobotsConfig::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $config
        ]);

    } catch (\Exception $e) {
        Log::error('Erro ao obter configuração de robots do tenant', [
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erro interno do servidor'
        ], 500);
    }
}

/**
 * Get preview/current content of robots.txt for the tenant
 */
public function getPreview(Request $request): JsonResponse
{
    try {
        $tenant = $request->attributes->get('current_tenant');
        Log::info(" $request" . $request);
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant não identificado',
                'content' => ''
            ], 404);
        }

        $filePath = storage_path("app/robots/{$tenant->subdomain}/robots.txt");
        Log::info("Filepath: " . $filePath);
        
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            Log::info("content: " . $content);

        } else {
            // Default content if file doesn't exist
            Log::info("DEFAULT CALLED");
            $sitemapUrl = $tenant->custom_domain 
                ? rtrim($tenant->custom_domain, '/') . '/sitemap.xml'
                : "https://{$tenant->subdomain}.omegaveiculos.com.br/sitemap.xml";
                
            $content = "User-agent: *\n";
            $content .= "Allow: /\n";
            $content .= "Disallow: /admin/\n";
            $content .= "Disallow: /api/\n";
            $content .= "Disallow: /_next/\n";
            $content .= "\n";
            $content .= "Sitemap: {$sitemapUrl}";
        }

        return response()->json([
            'success' => true,
            'content' => $content
        ]);

    } catch (\Exception $e) {
        Log::error('Erro ao obter preview do robots.txt', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erro interno do servidor',
            'content' => ''
        ], 500);
    }
}

/**
 * Save raw robots.txt content for the tenant
 */
public function saveContent(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'content' => 'required|string'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Conteúdo inválido',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        $tenant = $request->attributes->get('current_tenant');
        
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant não identificado'
            ], 404);
        }

        // Create tenant directory if it doesn't exist
        $tenantDir = storage_path("app/robots/{$tenant->subdomain}");

        if (!file_exists($tenantDir)) {
            mkdir($tenantDir, 0755, true);
        }

        $filePath = "{$tenantDir}/robots.txt";
        
        // Save the content
        file_put_contents($filePath, $request->content);

        Log::info('Robots.txt salvo com sucesso', [
            'tenant' => $tenant->subdomain,
            'tenant_id' => $tenant->id,
            'filepath' => $filePath,
            'content_length' => strlen($request->content),
            'user_id' => $request->attributes->get('current_user')?->id ?? 'unknown'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Robots.txt salvo com sucesso',
            'file_path' => $filePath
        ]);

    } catch (\Exception $e) {
        Log::error('Erro ao salvar robots.txt', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erro interno do servidor: ' . $e->getMessage()
        ], 500);
    }
}



}
