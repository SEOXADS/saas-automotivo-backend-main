<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="2. Admin Cliente",
 *     description="Endpoints para administradores do tenant (autenticação e gestão)"
 * )
 */
class SiteConfigurationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/site-config",
     *     summary="Obter configurações atuais do site",
     *     description="Retorna todas as configurações configuráveis do site para o tenant autenticado",
     *     tags={"2. Admin Cliente"},
     *     security={{"tenant_auth":{}}},
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
    public function getConfig(Request $request)
    {
        try {
            $tenant = $request->user()->tenant;

            return response()->json([
                'success' => true,
                'data' => [
                    'basic_info' => [
                        'name' => $tenant->name,
                        'description' => $tenant->description,
                        'contact_email' => $tenant->contact_email,
                        'contact_phone' => $tenant->contact_phone,
                        'address' => $tenant->address,
                    ],
                    'branding' => [
                        'theme_color' => $tenant->getThemeColor(),
                        'logo_url' => $tenant->logo_url,
                    ],
                    'social_media' => $tenant->getSocialMedia(),
                    'business_hours' => $tenant->getBusinessHours(),
                    'portal_settings' => [
                        'allow_registration' => $tenant->allowsRegistration(),
                        'require_approval' => $tenant->requiresApproval(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao obter configurações do site', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/site-config",
     *     summary="Atualizar configurações básicas do site",
     *     description="Atualiza as configurações básicas do site (nome, descrição, contatos)",
     *     tags={"2. Admin Cliente"},
     *     security={{"tenant_auth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255, example="Empresa Demo", description="Nome da empresa"),
     *             @OA\Property(property="description", type="string", maxLength=1000, example="Portal de anúncios da Empresa Demo", description="Descrição da empresa"),
     *             @OA\Property(property="contact_email", type="string", format="email", maxLength=255, example="contato@empresa.com", description="Email de contato"),
     *             @OA\Property(property="contact_phone", type="string", maxLength=20, example="(11) 99999-9999", description="Telefone de contato"),
     *             @OA\Property(property="address", type="string", maxLength=500, example="Rua Exemplo, 123 - São Paulo/SP", description="Endereço da empresa")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configurações atualizadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Configurações atualizadas com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Dados inválidos"),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function updateBasicConfig(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'contact_email' => 'required|email|max:255',
                'contact_phone' => 'required|string|max:20',
                'address' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dados inválidos',
                    'messages' => $validator->errors()
                ], 400);
            }

            $tenant = $request->user()->tenant;

            $tenant->update([
                'name' => $request->name,
                'description' => $request->description,
                'contact_email' => $request->contact_email,
                'contact_phone' => $request->contact_phone,
                'address' => $request->address,
            ]);

            Log::info('Configurações básicas atualizadas', [
                'tenant_id' => $tenant->id,
                'updated_by' => $request->user()->id,
                'changes' => $request->all()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Configurações atualizadas com sucesso',
                'data' => [
                    'name' => $tenant->name,
                    'description' => $tenant->description,
                    'contact_email' => $tenant->contact_email,
                    'contact_phone' => $tenant->contact_phone,
                    'address' => $tenant->address,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar configurações básicas', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/site-config/theme",
     *     summary="Atualizar configurações de tema",
     *     description="Atualiza a cor do tema e configurações de branding do site",
     *     tags={"2. Admin Cliente"},
     *     security={{"tenant_auth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="theme_color", type="string", pattern="^#[0-9A-Fa-f]{6}$", example="#007bff", description="Cor principal do tema em formato hexadecimal")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tema atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tema atualizado com sucesso"),
     *             @OA\Property(property="data", type="object")
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
            $validator = Validator::make($request->all(), [
                'theme_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dados inválidos',
                    'messages' => $validator->errors()
                ], 400);
            }

            $tenant = $request->user()->tenant;

            $tenant->update([
                'theme_color' => $request->theme_color,
            ]);

            Log::info('Tema atualizado', [
                'tenant_id' => $tenant->id,
                'updated_by' => $request->user()->id,
                'theme_color' => $request->theme_color
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tema atualizado com sucesso',
                'data' => [
                    'theme_color' => $tenant->getThemeColor(),
                    'branding' => [
                        'primary_color' => $tenant->getThemeColor(),
                        'secondary_color' => $this->generateSecondaryColor($tenant->getThemeColor()),
                        'accent_color' => $this->generateAccentColor($tenant->getThemeColor()),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar tema', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/site-config/logo",
     *     summary="Upload de logo",
     *     description="Faz upload de uma nova logo para o site",
     *     tags={"2. Admin Cliente"},
     *     security={{"tenant_auth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="logo", type="string", format="binary", description="Arquivo de imagem da logo")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logo atualizada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logo atualizada com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Dados inválidos"),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function uploadLogo(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dados inválidos',
                    'messages' => $validator->errors()
                ], 400);
            }

            $tenant = $request->user()->tenant;

            // Deletar logo anterior se existir
            if ($tenant->logo_url) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $tenant->logo_url));
            }

            // Upload da nova logo
            $logoPath = $request->file('logo')->store('tenants/' . $tenant->id . '/logos', 'public');
            $logoUrl = asset('storage/' . $logoPath);

            $tenant->update([
                'logo_url' => $logoUrl,
            ]);

            Log::info('Logo atualizada', [
                'tenant_id' => $tenant->id,
                'updated_by' => $request->user()->id,
                'logo_url' => $logoUrl
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logo atualizada com sucesso',
                'data' => [
                    'logo_url' => $logoUrl,
                    'logo_path' => $logoPath
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao fazer upload da logo', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/site-config/favicon",
     *     summary="Upload do favicon",
     *     description="Faz upload do favicon do site",
     *     tags={"2. Admin Cliente"},
     *     security={{"tenant_auth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="favicon", type="string", format="binary", description="Arquivo do favicon")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Favicon atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Favicon atualizado com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Dados inválidos"),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function uploadFavicon(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'favicon' => 'required|image|mimes:ico,png,jpg,jpeg,gif|max:512',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dados inválidos',
                    'messages' => $validator->errors()
                ], 400);
            }

            $tenant = $request->user()->tenant;

            // Deletar favicon anterior se existir
            if ($tenant->favicon_url) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $tenant->favicon_url));
            }

            // Upload do novo favicon
            $faviconPath = $request->file('favicon')->store('tenants/' . $tenant->id . '/favicons', 'public');
            $faviconUrl = asset('storage/' . $faviconPath);

            $tenant->update([
                'favicon_url' => $faviconUrl,
            ]);

            Log::info('Favicon atualizado', [
                'tenant_id' => $tenant->id,
                'updated_by' => $request->user()->id,
                'favicon_url' => $faviconUrl
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Favicon atualizado com sucesso',
                'data' => [
                    'favicon_url' => $faviconUrl,
                    'favicon_path' => $faviconPath
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao fazer upload do favicon', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/site-config/banner",
     *     summary="Upload do banner",
     *     description="Faz upload do banner do site",
     *     tags={"2. Admin Cliente"},
     *     security={{"tenant_auth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="banner", type="string", format="binary", description="Arquivo do banner")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Banner atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Banner atualizado com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Dados inválidos"),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function uploadBanner(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'banner' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dados inválidos',
                    'messages' => $validator->errors()
                ], 400);
            }

            $tenant = $request->user()->tenant;

            // Deletar banner anterior se existir
            if ($tenant->banner_url) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $tenant->banner_url));
            }

            // Upload do novo banner
            $bannerPath = $request->file('banner')->store('tenants/' . $tenant->id . '/banners', 'public');
            $bannerUrl = asset('storage/' . $bannerPath);

            $tenant->update([
                'banner_url' => $bannerUrl,
            ]);

            Log::info('Banner atualizado', [
                'tenant_id' => $tenant->id,
                'updated_by' => $request->user()->id,
                'banner_url' => $bannerUrl
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Banner atualizado com sucesso',
                'data' => [
                    'banner_url' => $bannerUrl,
                    'banner_path' => $bannerPath
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao fazer upload do banner', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/site-config/social-media",
     *     summary="Atualizar redes sociais",
     *     description="Atualiza as configurações de redes sociais do site",
     *     tags={"2. Admin Cliente"},
     *     security={{"tenant_auth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="social_media", type="object", description="Configurações de redes sociais")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Redes sociais atualizadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Redes sociais atualizadas com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Dados inválidos"),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function updateSocialMedia(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'social_media' => 'required|array',
                'social_media.facebook' => 'nullable|url',
                'social_media.instagram' => 'nullable|url',
                'social_media.twitter' => 'nullable|url',
                'social_media.linkedin' => 'nullable|url',
                'social_media.youtube' => 'nullable|url',
                'social_media.whatsapp' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dados inválidos',
                    'messages' => $validator->errors()
                ], 400);
            }

            $tenant = $request->user()->tenant;

            $tenant->update([
                'social_media' => $request->social_media,
            ]);

            Log::info('Redes sociais atualizadas', [
                'tenant_id' => $tenant->id,
                'updated_by' => $request->user()->id,
                'social_media' => $request->social_media
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Redes sociais atualizadas com sucesso',
                'data' => [
                    'social_media' => $tenant->getSocialMedia()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar redes sociais', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/site-config/business-hours",
     *     summary="Atualizar horário de funcionamento",
     *     description="Atualiza o horário de funcionamento do negócio",
     *     tags={"2. Admin Cliente"},
     *     security={{"tenant_auth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="business_hours", type="object", description="Horários de funcionamento por dia da semana")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Horário de funcionamento atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Horário de funcionamento atualizado com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Dados inválidos"),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function updateBusinessHours(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'business_hours' => 'required|array',
                'business_hours.monday' => 'nullable|array|size:2',
                'business_hours.tuesday' => 'nullable|array|size:2',
                'business_hours.wednesday' => 'nullable|array|size:2',
                'business_hours.thursday' => 'nullable|array|size:2',
                'business_hours.friday' => 'nullable|array|size:2',
                'business_hours.saturday' => 'nullable|array|size:2',
                'business_hours.sunday' => 'nullable|array|size:2',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dados inválidos',
                    'messages' => $validator->errors()
                ], 400);
            }

            $tenant = $request->user()->tenant;

            $tenant->update([
                'business_hours' => $request->business_hours,
            ]);

            Log::info('Horário de funcionamento atualizado', [
                'tenant_id' => $tenant->id,
                'updated_by' => $request->user()->id,
                'business_hours' => $request->business_hours
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Horário de funcionamento atualizado com sucesso',
                'data' => [
                    'business_hours' => $tenant->getBusinessHours()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar horário de funcionamento', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/site-config/portal-settings",
     *     summary="Atualizar configurações do portal",
     *     description="Atualiza as configurações gerais do portal (registro, aprovação)",
     *     tags={"2. Admin Cliente"},
     *     security={{"tenant_auth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="allow_registration", type="boolean", example=true, description="Permitir registro de usuários"),
     *             @OA\Property(property="require_approval", type="boolean", example=false, description="Requerer aprovação para novos usuários")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configurações do portal atualizadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Configurações do portal atualizadas com sucesso"),
     *             @OA\Property(property="data", type="object")
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
            $validator = Validator::make($request->all(), [
                'allow_registration' => 'required|boolean',
                'require_approval' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dados inválidos',
                    'messages' => $validator->errors()
                ], 400);
            }

            $tenant = $request->user()->tenant;

            $tenant->update([
                'allow_registration' => $request->allow_registration,
                'require_approval' => $request->require_approval,
            ]);

            Log::info('Configurações do portal atualizadas', [
                'tenant_id' => $tenant->id,
                'updated_by' => $request->user()->id,
                'allow_registration' => $request->allow_registration,
                'require_approval' => $request->require_approval
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Configurações do portal atualizadas com sucesso',
                'data' => [
                    'allow_registration' => $tenant->allowsRegistration(),
                    'require_approval' => $tenant->requiresApproval(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar configurações do portal', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Gera cor secundária baseada na cor primária
     */
    private function generateSecondaryColor(string $primaryColor): string
    {
        $hex = ltrim($primaryColor, '#');
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max == $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            switch ($max) {
                case $r: $h = ($g - $b) / $d + ($g < $b ? 6 : 0); break;
                case $g: $h = ($b - $r) / $d + 2; break;
                case $b: $h = ($r - $g) / $d + 4; break;
            }
            $h /= 6;
        }

        $l = max(0.3, min(0.7, $l + 0.1));

        return $this->hslToHex($h, $s, $l);
    }

    /**
     * Gera cor de destaque baseada na cor primária
     */
    private function generateAccentColor(string $primaryColor): string
    {
        $hex = ltrim($primaryColor, '#');
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max == $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            switch ($max) {
                case $r: $h = ($g - $b) / $d + ($g < $b ? 6 : 0); break;
                case $g: $h = ($b - $r) / $d + 2; break;
                case $b: $h = ($r - $g) / $d + 4; break;
            }
            $h /= 6;
        }

        $h = fmod($h + 0.5, 1.0);
        $s = min(1.0, $s + 0.2);
        $l = max(0.4, min(0.8, $l));

        return $this->hslToHex($h, $s, $l);
    }

    /**
     * Converte HSL para Hex
     */
    private function hslToHex(float $h, float $s, float $l): string
    {
        if ($s == 0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = $this->hue2rgb($p, $q, $h + 1/3);
            $g = $this->hue2rgb($p, $q, $h);
            $b = $this->hue2rgb($p, $q, $h - 1/3);
        }

        return sprintf("#%02x%02x%02x", round($r * 255), round($g * 255), round($b * 255));
    }

    /**
     * Converte matiz para RGB
     */
    private function hue2rgb(float $p, float $q, float $t): float
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1/2) return $q;
        if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
        return $p;
    }
}
