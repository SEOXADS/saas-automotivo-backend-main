<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class SuperAdminTenantConfigController extends Controller
{
    /**
     * Exibir todas as configurações de um tenant
     */
    public function show($tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);
            $config = TenantConfiguration::where('tenant_id', $tenantId)->first();

            if (!$config) {
                $config = new TenantConfiguration();
                $config->tenant_id = $tenantId;
                $config->save();
            }

            return response()->json([
                'success' => true,
                'data' => $config,
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'subdomain' => $tenant->subdomain
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar configurações: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar configurações gerais do tenant
     */
    public function update(Request $request, $tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);

            $validator = Validator::make($request->all(), [
                'company_name' => 'sometimes|string|max:255',
                'company_description' => 'nullable|string',
                'company_address' => 'nullable|string',
                'company_phone' => 'nullable|string|max:20',
                'company_email' => 'nullable|email',
                'company_website' => 'nullable|url',
                'company_cnpj' => 'nullable|string|max:18',
                'business_hours' => 'nullable|array',
                'social_media' => 'nullable|array',
                'theme_settings' => 'nullable|array',
                'portal_settings' => 'nullable|array',
                'seo_settings' => 'nullable|array',
                'ai_settings' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $config = TenantConfiguration::updateOrCreate(
                ['tenant_id' => $tenantId],
                $validator->validated()
            );

            // Limpar cache das configurações
            Cache::forget("tenant_config_{$tenantId}");

            return response()->json([
                'success' => true,
                'message' => 'Configurações atualizadas com sucesso',
                'data' => $config
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar configurações: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter configurações de tema
     */
    public function getTheme($tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);
            $config = TenantConfiguration::where('tenant_id', $tenantId)->first();

            $themeSettings = $config ? $config->theme_settings : [];

            return response()->json([
                'success' => true,
                'data' => $themeSettings,
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'subdomain' => $tenant->subdomain
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar configurações de tema: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar configurações de tema
     */
    public function updateTheme(Request $request, $tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);

            $validator = Validator::make($request->all(), [
                'primary_color' => 'sometimes|string|regex:/^#[0-9A-F]{6}$/i',
                'secondary_color' => 'sometimes|string|regex:/^#[0-9A-F]{6}$/i',
                'accent_color' => 'sometimes|string|regex:/^#[0-9A-F]{6}$/i',
                'text_color' => 'sometimes|string|regex:/^#[0-9A-F]{6}$/i',
                'background_color' => 'sometimes|string|regex:/^#[0-9A-F]{6}$/i',
                'font_family' => 'sometimes|string|max:100',
                'font_size' => 'sometimes|string|in:small,medium,large',
                'border_radius' => 'sometimes|string|in:small,medium,large,none',
                'button_style' => 'sometimes|string|in:rounded,squared,pill',
                'layout_style' => 'sometimes|string|in:modern,classic,minimal'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $config = TenantConfiguration::where('tenant_id', $tenantId)->first();

            if (!$config) {
                $config = new TenantConfiguration();
                $config->tenant_id = $tenantId;
            }

            $themeSettings = $config->theme_settings ?? [];
            $themeSettings = array_merge($themeSettings, $validator->validated());

            $config->theme_settings = $themeSettings;
            $config->save();

            // Limpar cache das configurações
            Cache::forget("tenant_config_{$tenantId}");

            return response()->json([
                'success' => true,
                'message' => 'Configurações de tema atualizadas com sucesso',
                'data' => $themeSettings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar configurações de tema: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter configurações de redes sociais
     */
    public function getSocialMedia($tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);
            $config = TenantConfiguration::where('tenant_id', $tenantId)->first();

            $socialMedia = $config ? $config->social_media : [];

            return response()->json([
                'success' => true,
                'data' => $socialMedia,
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'subdomain' => $tenant->subdomain
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar configurações de redes sociais: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar configurações de redes sociais
     */
    public function updateSocialMedia(Request $request, $tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);

            $validator = Validator::make($request->all(), [
                'facebook' => 'nullable|url',
                'instagram' => 'nullable|url',
                'twitter' => 'nullable|url',
                'linkedin' => 'nullable|url',
                'youtube' => 'nullable|url',
                'whatsapp' => 'nullable|string|max:20',
                'telegram' => 'nullable|string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $config = TenantConfiguration::where('tenant_id', $tenantId)->first();

            if (!$config) {
                $config = new TenantConfiguration();
                $config->tenant_id = $tenantId;
            }

            $socialMedia = $config->social_media ?? [];
            $socialMedia = array_merge($socialMedia, $validator->validated());

            $config->social_media = $socialMedia;
            $config->save();

            // Limpar cache das configurações
            Cache::forget("tenant_config_{$tenantId}");

            return response()->json([
                'success' => true,
                'message' => 'Configurações de redes sociais atualizadas com sucesso',
                'data' => $socialMedia
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar configurações de redes sociais: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter horários de funcionamento
     */
    public function getBusinessHours($tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);
            $config = TenantConfiguration::where('tenant_id', $tenantId)->first();

            $businessHours = $config ? $config->business_hours : [];

            return response()->json([
                'success' => true,
                'data' => $businessHours,
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'subdomain' => $tenant->subdomain
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar horários de funcionamento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar horários de funcionamento
     */
    public function updateBusinessHours(Request $request, $tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);

            $validator = Validator::make($request->all(), [
                'monday' => 'nullable|array',
                'monday.open' => 'nullable|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                'monday.close' => 'nullable|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                'monday.closed' => 'nullable|boolean',
                'tuesday' => 'nullable|array',
                'tuesday.open' => 'nullable|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                'tuesday.close' => 'nullable|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                'tuesday.closed' => 'nullable|boolean',
                'wednesday' => 'nullable|array',
                'wednesday.open' => 'nullable|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                'wednesday.close' => 'nullable|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                'wednesday.closed' => 'nullable|boolean',
                'thursday' => 'nullable|array',
                'thursday.open' => 'nullable|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                'thursday.close' => 'nullable|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                'thursday.closed' => 'nullable|boolean',
                'friday' => 'nullable|array',
                'friday.open' => 'nullable|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                'friday.close' => 'nullable|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                'friday.closed' => 'nullable|boolean',
                'saturday' => 'nullable|array',
                'saturday.open' => 'nullable|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                'saturday.close' => 'nullable|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                'saturday.closed' => 'nullable|boolean',
                'sunday' => 'nullable|array',
                'sunday.open' => 'nullable|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                'sunday.close' => 'nullable|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                'sunday.closed' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $config = TenantConfiguration::where('tenant_id', $tenantId)->first();

            if (!$config) {
                $config = new TenantConfiguration();
                $config->tenant_id = $tenantId;
            }

            $businessHours = $config->business_hours ?? [];
            $businessHours = array_merge($businessHours, $validator->validated());

            $config->business_hours = $businessHours;
            $config->save();

            // Limpar cache das configurações
            Cache::forget("tenant_config_{$tenantId}");

            return response()->json([
                'success' => true,
                'message' => 'Horários de funcionamento atualizados com sucesso',
                'data' => $businessHours
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar horários de funcionamento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter informações de contato
     */
    public function getContact($tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);
            $config = TenantConfiguration::where('tenant_id', $tenantId)->first();

            $contactInfo = [
                'company_name' => $config->company_name ?? null,
                'company_address' => $config->company_address ?? null,
                'company_phone' => $config->company_phone ?? null,
                'company_email' => $config->company_email ?? null,
                'company_website' => $config->company_website ?? null,
                'company_cnpj' => $config->company_cnpj ?? null
            ];

            return response()->json([
                'success' => true,
                'data' => $contactInfo,
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'subdomain' => $tenant->subdomain
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar informações de contato: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar informações de contato
     */
    public function updateContact(Request $request, $tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);

            $validator = Validator::make($request->all(), [
                'company_name' => 'sometimes|string|max:255',
                'company_address' => 'nullable|string',
                'company_phone' => 'nullable|string|max:20',
                'company_email' => 'nullable|email',
                'company_website' => 'nullable|url',
                'company_cnpj' => 'nullable|string|max:18'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $config = TenantConfiguration::where('tenant_id', $tenantId)->first();

            if (!$config) {
                $config = new TenantConfiguration();
                $config->tenant_id = $tenantId;
            }

            $config->fill($validator->validated());
            $config->save();

            // Limpar cache das configurações
            Cache::forget("tenant_config_{$tenantId}");

            return response()->json([
                'success' => true,
                'message' => 'Informações de contato atualizadas com sucesso',
                'data' => $config->only([
                    'company_name', 'company_address', 'company_phone',
                    'company_email', 'company_website', 'company_cnpj'
                ])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar informações de contato: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter configurações do portal
     */
    public function getPortalConfig($tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);
            $config = TenantConfiguration::where('tenant_id', $tenantId)->first();

            $portalSettings = $config ? $config->portal_settings : [];

            return response()->json([
                'success' => true,
                'data' => $portalSettings,
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'subdomain' => $tenant->subdomain
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar configurações do portal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar configurações do portal
     */
    public function updatePortalConfig(Request $request, $tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);

            $validator = Validator::make($request->all(), [
                'show_featured_vehicles' => 'nullable|boolean',
                'max_vehicles_per_page' => 'nullable|integer|min:1|max:100',
                'enable_search_filters' => 'nullable|boolean',
                'enable_vehicle_comparison' => 'nullable|boolean',
                'enable_wishlist' => 'nullable|boolean',
                'enable_reviews' => 'nullable|boolean',
                'enable_newsletter' => 'nullable|boolean',
                'enable_chat_widget' => 'nullable|boolean',
                'chat_widget_config' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $config = TenantConfiguration::where('tenant_id', $tenantId)->first();

            if (!$config) {
                $config = new TenantConfiguration();
                $config->tenant_id = $tenantId;
            }

            $portalSettings = $config->portal_settings ?? [];
            $portalSettings = array_merge($portalSettings, $validator->validated());

            $config->portal_settings = $portalSettings;
            $config->save();

            // Limpar cache das configurações
            Cache::forget("tenant_config_{$tenantId}");

            return response()->json([
                'success' => true,
                'message' => 'Configurações do portal atualizadas com sucesso',
                'data' => $portalSettings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar configurações do portal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter configurações de SEO
     */
    public function getSeoConfig($tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);
            $config = TenantConfiguration::where('tenant_id', $tenantId)->first();

            $seoSettings = $config ? $config->seo_settings : [];

            return response()->json([
                'success' => true,
                'data' => $seoSettings,
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'subdomain' => $tenant->subdomain
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar configurações de SEO: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar configurações de SEO
     */
    public function updateSeoConfig(Request $request, $tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);

            $validator = Validator::make($request->all(), [
                'meta_title' => 'nullable|string|max:60',
                'meta_description' => 'nullable|string|max:160',
                'meta_keywords' => 'nullable|string|max:255',
                'og_title' => 'nullable|string|max:60',
                'og_description' => 'nullable|string|max:160',
                'og_image' => 'nullable|url',
                'twitter_card' => 'nullable|string|in:summary,summary_large_image,app,player',
                'twitter_title' => 'nullable|string|max:60',
                'twitter_description' => 'nullable|string|max:160',
                'twitter_image' => 'nullable|url',
                'google_analytics_id' => 'nullable|string|max:20',
                'google_tag_manager_id' => 'nullable|string|max:20',
                'schema_markup' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $config = TenantConfiguration::where('tenant_id', $tenantId)->first();

            if (!$config) {
                $config = new TenantConfiguration();
                $config->tenant_id = $tenantId;
            }

            $seoSettings = $config->seo_settings ?? [];
            $seoSettings = array_merge($seoSettings, $validator->validated());

            $config->seo_settings = $seoSettings;
            $config->save();

            // Limpar cache das configurações
            Cache::forget("tenant_config_{$tenantId}");

            return response()->json([
                'success' => true,
                'message' => 'Configurações de SEO atualizadas com sucesso',
                'data' => $seoSettings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar configurações de SEO: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter configurações de IA
     */
    public function getAiConfig($tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);
            $config = TenantConfiguration::where('tenant_id', $tenantId)->first();

            $aiSettings = $config ? $config->ai_settings : [];

            return response()->json([
                'success' => true,
                'data' => $aiSettings,
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'subdomain' => $tenant->subdomain
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar configurações de IA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar configurações de IA
     */
    public function updateAiConfig(Request $request, $tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);

            $validator = Validator::make($request->all(), [
                'enable_ai_chat' => 'nullable|boolean',
                'ai_chat_model' => 'nullable|string|in:gpt-3.5-turbo,gpt-4,claude-3,gemini-pro',
                'ai_chat_temperature' => 'nullable|numeric|min:0|max:2',
                'ai_chat_max_tokens' => 'nullable|integer|min:1|max:4000',
                'enable_ai_vehicle_recommendations' => 'nullable|boolean',
                'enable_ai_price_analysis' => 'nullable|boolean',
                'enable_ai_content_generation' => 'nullable|boolean',
                'ai_api_key' => 'nullable|string',
                'ai_organization_id' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $config = TenantConfiguration::where('tenant_id', $tenantId)->first();

            if (!$config) {
                $config = new TenantConfiguration();
                $config->tenant_id = $tenantId;
            }

            $aiSettings = $config->ai_settings ?? [];
            $aiSettings = array_merge($aiSettings, $validator->validated());

            $config->ai_settings = $aiSettings;
            $config->save();

            // Limpar cache das configurações
            Cache::forget("tenant_config_{$tenantId}");

            return response()->json([
                'success' => true,
                'message' => 'Configurações de IA atualizadas com sucesso',
                'data' => $aiSettings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar configurações de IA: ' . $e->getMessage()
            ], 500);
        }
    }
}
