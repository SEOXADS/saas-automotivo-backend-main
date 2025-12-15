<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\TenantProfile;
use App\Models\TenantTheme;
use App\Models\TenantSeo;
use App\Models\TenantPortalSettings;
use App\Helpers\TokenHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="2. Admin Cliente",
 *     description="Endpoints para administradores do tenant (autenticação e gestão)"
 * )
 */
class TenantConfigurationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/tenant/configuration",
     *     summary="Obter configurações completas do tenant",
     *     description="Retorna todas as configurações do tenant (perfil, tema, SEO, portal)",
     *     tags={"2. Admin Cliente"},
     *     @OA\Response(
     *         response=200,
     *         description="Configurações retornadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function index(Request $request)
    {
        try {
            $user = TokenHelper::getAuthenticatedUser($request);
            $tenant = $user->tenant;

            if (!$tenant) {
                return response()->json(['error' => 'Tenant não encontrado'], 404);
            }

            $data = [
                'profile' => $tenant->profile,
                'theme' => $tenant->theme,
                'seo' => $tenant->seo,
                'portal_settings' => $tenant->portalSettings
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter configurações do tenant: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/tenant/configuration/profile",
     *     summary="Atualizar perfil da empresa",
     *     description="Atualiza as informações da empresa (nome, descrição, endereço, etc.)",
     *     tags={"2. Admin Cliente"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="company_name", type="string", description="Nome da empresa", example="Empresa Demo"),
     *             @OA\Property(property="company_description", type="string", description="Descrição da empresa", example="Empresa especializada em veículos"),
     *             @OA\Property(property="company_cnpj", type="string", description="CNPJ da empresa", example="12.345.678/0001-90"),
     *             @OA\Property(property="company_phone", type="string", description="Telefone da empresa", example="(11) 99999-9999"),
     *             @OA\Property(property="company_email", type="string", description="Email da empresa", example="contato@empresa.com"),
     *             @OA\Property(property="company_website", type="string", description="Website da empresa", example="https://empresa.com"),
     *             @OA\Property(property="address_street", type="string", description="Rua do endereço", example="Rua das Concessionárias"),
     *             @OA\Property(property="address_number", type="string", description="Número do endereço", example="123"),
     *             @OA\Property(property="address_complement", type="string", description="Complemento do endereço", example="Sala 101"),
     *             @OA\Property(property="address_district", type="string", description="Bairro", example="Centro"),
     *             @OA\Property(property="address_city", type="string", description="Cidade", example="São Paulo"),
     *             @OA\Property(property="address_state", type="string", description="Estado (UF)", example="SP"),
     *             @OA\Property(property="address_zipcode", type="string", description="CEP", example="01234-567"),
     *             @OA\Property(property="address_country", type="string", description="País", example="Brasil"),
     *             @OA\Property(property="business_hours", type="object", description="Horários de funcionamento"),
     *             @OA\Property(property="social_media", type="object", description="Redes sociais"),
     *             @OA\Property(property="logo_url", type="string", description="URL ou Base64 da logo", example="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=="),
     *             @OA\Property(property="favicon_url", type="string", description="URL ou Base64 do favicon", example="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=="),
     *             @OA\Property(property="banner_url", type="string", description="URL ou Base64 do banner", example="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Perfil atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object", description="Dados do perfil atualizado")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Dados inválidos"),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function updateProfile(Request $request)
    {
        try {
            Log::info('Tentando atualizar profile:', [
                'request_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            $user = TokenHelper::getAuthenticatedUser($request);
            $tenant = $user->tenant;

            if (!$tenant) {
                return response()->json(['error' => 'Tenant não encontrado'], 404);
            }

            $validator = Validator::make($request->all(), [
                'company_name' => 'required|string|max:255',
                'company_description' => 'nullable|string|max:1000',
                'company_cnpj' => 'nullable|string|max:18',
                'company_phone' => 'nullable|string|max:20',
                'company_email' => 'required|email|max:255',
                'company_website' => 'nullable|url|max:255',
                'address_street' => 'nullable|string|max:255',
                'address_number' => 'nullable|string|max:20',
                'address_complement' => 'nullable|string|max:255',
                'address_district' => 'nullable|string|max:255',
                'address_city' => 'nullable|string|max:255',
                'address_state' => 'nullable|string|max:2',
                'address_zipcode' => 'nullable|string|max:9',
                'address_country' => 'nullable|string|max:255',
                'business_hours' => 'nullable|array',
                'social_media' => 'nullable|array',
                'logo_url' => 'nullable|string',
                'favicon_url' => 'nullable|string',
                'banner_url' => 'nullable|string'
            ]);

            Log::info('Dados recebidos para validação:', [
                'data' => $request->all(),
                'rules' => [
                    'company_name' => 'required|string|max:255',
                    'company_email' => 'required|email|max:255'
                ]
            ]);

            if ($validator->fails()) {
                Log::warning('Validação falhou para profile:', [
                    'data' => $request->all(),
                    'errors' => $validator->errors()->toArray()
                ]);
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 400);
            }

            $profile = $tenant->profile;
            if (!$profile) {
                $profile = new TenantProfile(['tenant_id' => $tenant->id]);
            }

            $profile->fill($request->all());
            $profile->save();

            return response()->json([
                'success' => true,
                'message' => 'Perfil atualizado com sucesso',
                'data' => $profile
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar perfil do tenant: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/tenant/configuration/theme",
     *     summary="Atualizar configurações de tema",
     *     description="Atualiza as configurações visuais do tema (cores, fontes, layout, animações)",
     *     tags={"2. Admin Cliente"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="theme_name", type="string", description="Nome do tema", example="default"),
     *             @OA\Property(property="primary_color", type="string", description="Cor primária (hex)", example="#007bff"),
     *             @OA\Property(property="secondary_color", type="string", description="Cor secundária (hex)", example="#6c757d"),
     *             @OA\Property(property="accent_color", type="string", description="Cor de destaque (hex)", example="#28a745"),
     *             @OA\Property(property="font_family", type="string", description="Família da fonte", example="Inter, sans-serif"),
     *             @OA\Property(property="border_radius", type="string", description="Raio da borda", example="0.375rem"),
     *             @OA\Property(property="enable_dark_mode", type="boolean", description="Habilitar modo escuro", example=false),
     *             @OA\Property(property="enable_animations", type="boolean", description="Habilitar animações", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tema atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object", description="Configuração completa do tema")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Dados inválidos"),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function updateTheme(Request $request)
    {
        try {
            $user = TokenHelper::getAuthenticatedUser($request);
            $tenant = $user->tenant;

            if (!$tenant) {
                return response()->json(['error' => 'Tenant não encontrado'], 404);
            }

            $validator = Validator::make($request->all(), [
                'theme_name' => 'nullable|string|max:100',
                'primary_color' => 'required|string|max:7',
                'secondary_color' => 'required|string|max:7',
                'accent_color' => 'required|string|max:7',
                'font_family' => 'nullable|string|max:255',
                'border_radius' => 'nullable|string|max:50',
                'enable_dark_mode' => 'boolean',
                'enable_animations' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 400);
            }

            $theme = $tenant->theme;
            if (!$theme) {
                $theme = new TenantTheme(['tenant_id' => $tenant->id]);
            }

            $theme->fill($request->all());
            $theme->save();

            return response()->json([
                'success' => true,
                'message' => 'Tema atualizado com sucesso',
                'data' => $theme->getFrontendConfig()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar tema do tenant: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/tenant/configuration/seo",
     *     summary="Obter configurações de SEO",
     *     description="Retorna as configurações de SEO e meta tags do tenant",
     *     tags={"2. Admin Cliente"},
     *     @OA\Response(
     *         response=200,
     *         description="Configurações de SEO obtidas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=404, description="Tenant não encontrado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getSeo(Request $request)
    {
        try {
            $user = TokenHelper::getAuthenticatedUser($request);
            $tenant = $user->tenant;

            if (!$tenant) {
                return response()->json(['error' => 'Tenant não encontrado'], 404);
            }

            $seo = $tenant->seo;
            if (!$seo) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $seo->getFrontendConfig()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter SEO do tenant: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/tenant/configuration/seo",
     *     summary="Atualizar configurações de SEO",
     *     description="Atualiza as configurações de SEO e meta tags",
     *     tags={"2. Admin Cliente"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="meta_title", type="string", description="Título da página (máx. 60 chars)", example="Empresa Demo - Concessionária de Veículos"),
     *             @OA\Property(property="meta_description", type="string", description="Descrição da página (máx. 160 chars)", example="Encontre o veículo ideal para você na Empresa Demo. Veículos novos e usados com as melhores condições."),
     *             @OA\Property(property="meta_keywords", type="string", description="Palavras-chave", example="veículos, carros, motos, concessionária"),
     *             @OA\Property(property="meta_author", type="string", description="Autor da página", example="Empresa Demo"),
     *             @OA\Property(property="og_title", type="string", description="Título para Open Graph", example="Empresa Demo - Concessionária de Veículos"),
     *             @OA\Property(property="og_description", type="string", description="Descrição para Open Graph", example="Encontre o veículo ideal para você na Empresa Demo."),
     *             @OA\Property(property="og_image", type="string", description="Imagem para Open Graph", example="https://empresa.com/og-image.jpg"),
     *             @OA\Property(property="twitter_title", type="string", description="Título para Twitter", example="Empresa Demo - Concessionária de Veículos"),
     *             @OA\Property(property="twitter_description", type="string", description="Descrição para Twitter", example="Encontre o veículo ideal para você na Empresa Demo."),
     *             @OA\Property(property="enable_sitemap", type="boolean", description="Habilitar sitemap", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="SEO atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object", description="Configuração SEO atualizada")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Dados inválidos"),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function updateSeo(Request $request)
    {
        try {
            $user = TokenHelper::getAuthenticatedUser($request);
            $tenant = $user->tenant;

            if (!$tenant) {
                return response()->json(['error' => 'Tenant não encontrado'], 404);
            }

            $validator = Validator::make($request->all(), [
                'meta_title' => 'required|string|max:60',
                'meta_description' => 'required|string|max:160',
                'meta_keywords' => 'nullable|string|max:255',
                'meta_author' => 'nullable|string|max:255',
                'og_title' => 'nullable|string|max:60',
                'og_description' => 'nullable|string|max:160',
                'og_image' => 'nullable|url|max:255',
                'twitter_title' => 'nullable|string|max:60',
                'twitter_description' => 'nullable|string|max:160',
                'enable_sitemap' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 400);
            }

            $seo = $tenant->seo;
            if (!$seo) {
                $seo = new TenantSeo(['tenant_id' => $tenant->id]);
            }

            $seo->fill($request->all());
            $seo->save();

            return response()->json([
                'success' => true,
                'message' => 'SEO atualizado com sucesso',
                'data' => $seo->getFrontendConfig()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar SEO do tenant: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/tenant/configuration/portal",
     *     summary="Atualizar configurações do portal",
     *     description="Atualiza as configurações de funcionalidades do portal",
     *     tags={"2. Admin Cliente"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="enable_search", type="boolean"),
     *             @OA\Property(property="enable_filters", type="boolean"),
     *             @OA\Property(property="enable_comparison", type="boolean"),
     *             @OA\Property(property="vehicles_per_page", type="integer"),
     *             @OA\Property(property="whatsapp_number", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configurações do portal atualizadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Dados inválidos"),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function updatePortalSettings(Request $request)
    {
        try {
            $user = TokenHelper::getAuthenticatedUser($request);
            $tenant = $user->tenant;

            if (!$tenant) {
                return response()->json(['error' => 'Tenant não encontrado'], 404);
            }

            $validator = Validator::make($request->all(), [
                'enable_search' => 'boolean',
                'enable_filters' => 'boolean',
                'enable_comparison' => 'boolean',
                'enable_wishlist' => 'boolean',
                'enable_reviews' => 'boolean',
                'enable_newsletter' => 'boolean',
                'enable_chat_widget' => 'boolean',
                'enable_whatsapp_button' => 'boolean',
                'vehicles_per_page' => 'integer|min:1|max:100',
                'max_vehicles_comparison' => 'integer|min:2|max:10',
                'whatsapp_number' => 'nullable|string|max:20',
                'google_analytics_id' => 'nullable|string|max:50',
                'facebook_pixel_id' => 'nullable|string|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 400);
            }

            $portalSettings = $tenant->portalSettings;
            if (!$portalSettings) {
                $portalSettings = new TenantPortalSettings(['tenant_id' => $tenant->id]);
            }

            $portalSettings->fill($request->all());
            $portalSettings->save();

            // Validar configurações
            $errors = $portalSettings->validateSettings();
            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configurações inválidas',
                    'errors' => $errors
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Configurações do portal atualizadas com sucesso',
                'data' => $portalSettings->getAdminConfig()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar configurações do portal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/tenant/configuration/preview",
     *     summary="Visualizar configurações do portal",
     *     description="Retorna as configurações formatadas para visualização no frontend",
     *     tags={"2. Admin Cliente"},
     *     @OA\Response(
     *         response=200,
     *         description="Configurações para preview retornadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function preview(Request $request)
    {
        try {
            $user = TokenHelper::getAuthenticatedUser($request);
            $tenant = $user->tenant;

            if (!$tenant) {
                return response()->json(['error' => 'Tenant não encontrado'], 404);
            }

            $data = [
                'theme' => $tenant->theme?->getFrontendConfig() ?? [],
                'seo' => $tenant->seo?->getFrontendConfig() ?? [],
                'portal_settings' => $tenant->portalSettings?->getFrontendConfig() ?? [],
                'profile' => $tenant->profile?->getContactInfo() ?? []
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao gerar preview das configurações: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }
}
