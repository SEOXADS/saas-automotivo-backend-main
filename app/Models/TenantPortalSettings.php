<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantPortalSettings extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'enable_search',
        'enable_filters',
        'enable_comparison',
        'enable_wishlist',
        'enable_reviews',
        'enable_newsletter',
        'enable_chat_widget',
        'enable_whatsapp_button',
        'vehicles_per_page',
        'max_vehicles_comparison',
        'show_featured_vehicles',
        'show_recent_vehicles',
        'show_vehicle_count',
        'show_price_range',
        'require_phone_in_lead',
        'require_email_in_lead',
        'enable_captcha',
        'enable_gdpr_compliance',
        'google_analytics_id',
        'facebook_pixel_id',
        'whatsapp_number',
        'google_maps_api_key',
        'recaptcha_site_key',
        'recaptcha_secret_key',
        'cache_ttl',
        'enable_page_cache',
        'enable_image_optimization'
    ];

    protected $casts = [
        'enable_search' => 'boolean',
        'enable_filters' => 'boolean',
        'enable_comparison' => 'boolean',
        'enable_wishlist' => 'boolean',
        'enable_reviews' => 'boolean',
        'enable_newsletter' => 'boolean',
        'enable_chat_widget' => 'boolean',
        'enable_whatsapp_button' => 'boolean',
        'show_featured_vehicles' => 'boolean',
        'show_recent_vehicles' => 'boolean',
        'show_vehicle_count' => 'boolean',
        'show_price_range' => 'boolean',
        'require_phone_in_lead' => 'boolean',
        'require_email_in_lead' => 'boolean',
        'enable_captcha' => 'boolean',
        'enable_gdpr_compliance' => 'boolean',
        'enable_page_cache' => 'boolean',
        'enable_image_optimization' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Relacionamento com o tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Obter funcionalidades habilitadas
     */
    public function getEnabledFeatures(): array
    {
        $features = [];

        if ($this->enable_search) $features[] = 'search';
        if ($this->enable_filters) $features[] = 'filters';
        if ($this->enable_comparison) $features[] = 'comparison';
        if ($this->enable_wishlist) $features[] = 'wishlist';
        if ($this->enable_reviews) $features[] = 'reviews';
        if ($this->enable_newsletter) $features[] = 'newsletter';
        if ($this->enable_chat_widget) $features[] = 'chat_widget';
        if ($this->enable_whatsapp_button) $features[] = 'whatsapp_button';

        return $features;
    }

    /**
     * Verificar se uma funcionalidade está habilitada
     */
    public function isFeatureEnabled(string $feature): bool
    {
        $property = 'enable_' . $feature;
        return property_exists($this, $property) ? $this->$property : false;
    }

    /**
     * Obter configurações de exibição
     */
    public function getDisplaySettings(): array
    {
        return [
            'vehicles_per_page' => $this->vehicles_per_page,
            'max_vehicles_comparison' => $this->max_vehicles_comparison,
            'show_featured_vehicles' => $this->show_featured_vehicles,
            'show_recent_vehicles' => $this->show_recent_vehicles,
            'show_vehicle_count' => $this->show_vehicle_count,
            'show_price_range' => $this->show_price_range
        ];
    }

    /**
     * Obter configurações de formulários
     */
    public function getFormSettings(): array
    {
        return [
            'require_phone_in_lead' => $this->require_phone_in_lead,
            'require_email_in_lead' => $this->require_email_in_lead,
            'enable_captcha' => $this->enable_captcha,
            'enable_gdpr_compliance' => $this->enable_gdpr_compliance
        ];
    }

    /**
     * Obter integrações configuradas
     */
    public function getIntegrations(): array
    {
        $integrations = [];

        if ($this->google_analytics_id) {
            $integrations['google_analytics'] = [
                'id' => $this->google_analytics_id,
                'enabled' => true
            ];
        }

        if ($this->facebook_pixel_id) {
            $integrations['facebook_pixel'] = [
                'id' => $this->facebook_pixel_id,
                'enabled' => true
            ];
        }

        if ($this->whatsapp_number) {
            $integrations['whatsapp'] = [
                'number' => $this->whatsapp_number,
                'enabled' => true
            ];
        }

        if ($this->google_maps_api_key) {
            $integrations['google_maps'] = [
                'api_key' => $this->google_maps_api_key,
                'enabled' => true
            ];
        }

        if ($this->recaptcha_site_key && $this->recaptcha_secret_key) {
            $integrations['recaptcha'] = [
                'site_key' => $this->recaptcha_site_key,
                'enabled' => true
            ];
        }

        return $integrations;
    }

    /**
     * Obter configurações de cache
     */
    public function getCacheSettings(): array
    {
        return [
            'ttl' => $this->cache_ttl,
            'page_cache' => $this->enable_page_cache,
            'image_optimization' => $this->enable_image_optimization
        ];
    }

    /**
     * Obter configurações para o frontend
     */
    public function getFrontendConfig(): array
    {
        return [
            'features' => $this->getEnabledFeatures(),
            'display' => $this->getDisplaySettings(),
            'forms' => $this->getFormSettings(),
            'integrations' => $this->getIntegrations(),
            'cache' => $this->getCacheSettings(),
            'whatsapp' => [
                'enabled' => $this->enable_whatsapp_button,
                'number' => $this->whatsapp_number
            ],
            'chat' => [
                'enabled' => $this->enable_chat_widget
            ]
        ];
    }

    /**
     * Obter configurações para o admin
     */
    public function getAdminConfig(): array
    {
        return [
            'portal' => [
                'search' => $this->enable_search,
                'filters' => $this->enable_filters,
                'comparison' => $this->enable_comparison,
                'wishlist' => $this->enable_wishlist,
                'reviews' => $this->enable_reviews,
                'newsletter' => $this->enable_newsletter,
                'chat_widget' => $this->enable_chat_widget,
                'whatsapp_button' => $this->enable_whatsapp_button
            ],
            'display' => $this->getDisplaySettings(),
            'forms' => $this->getFormSettings(),
            'integrations' => $this->getIntegrations(),
            'performance' => $this->getCacheSettings()
        ];
    }

    /**
     * Validar configurações
     */
    public function validateSettings(): array
    {
        $errors = [];

        if ($this->enable_captcha && !$this->recaptcha_site_key) {
            $errors[] = 'reCAPTCHA habilitado mas chave do site não configurada';
        }

        if ($this->enable_chat_widget && !$this->whatsapp_number) {
            $errors[] = 'Chat widget habilitado mas número do WhatsApp não configurado';
        }

        if ($this->vehicles_per_page < 1 || $this->vehicles_per_page > 100) {
            $errors[] = 'Veículos por página deve estar entre 1 e 100';
        }

        if ($this->max_vehicles_comparison < 2 || $this->max_vehicles_comparison > 10) {
            $errors[] = 'Máximo de veículos para comparação deve estar entre 2 e 10';
        }

        return $errors;
    }
}
