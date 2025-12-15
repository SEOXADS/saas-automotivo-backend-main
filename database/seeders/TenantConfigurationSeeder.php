<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\TenantProfile;
use App\Models\TenantTheme;
use App\Models\TenantSeo;
use App\Models\TenantPortalSettings;

class TenantConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->createTenantProfile($tenant);
            $this->createTenantTheme($tenant);
            $this->createTenantSeo($tenant);
            $this->createTenantPortalSettings($tenant);
        }
    }

    /**
     * Criar perfil do tenant
     */
    private function createTenantProfile(Tenant $tenant): void
    {
        if ($tenant->profile()->exists()) {
            return;
        }

        TenantProfile::create([
            'tenant_id' => $tenant->id,
            'company_name' => $tenant->name,
            'company_description' => 'Concessionária especializada em veículos novos e usados',
            'company_cnpj' => null,
            'company_phone' => $tenant->phone ?? '(11) 99999-9999',
            'company_email' => $tenant->email,
            'company_website' => null,
            'address_street' => 'Rua das Concessionárias',
            'address_number' => '123',
            'address_complement' => 'Sala 1',
            'address_district' => 'Centro',
            'address_city' => 'São Paulo',
            'address_state' => 'SP',
            'address_zipcode' => '01234-567',
            'address_country' => 'Brasil',
            'business_hours' => [
                'monday' => ['open' => '08:00', 'close' => '18:00', 'closed' => false],
                'tuesday' => ['open' => '08:00', 'close' => '18:00', 'closed' => false],
                'wednesday' => ['open' => '08:00', 'close' => '18:00', 'closed' => false],
                'thursday' => ['open' => '08:00', 'close' => '18:00', 'closed' => false],
                'friday' => ['open' => '08:00', 'close' => '18:00', 'closed' => false],
                'saturday' => ['open' => '08:00', 'close' => '12:00', 'closed' => false],
                'sunday' => ['closed' => true]
            ],
            'social_media' => [
                'facebook' => null,
                'instagram' => null,
                'twitter' => null,
                'linkedin' => null,
                'youtube' => null,
                'whatsapp' => null
            ],
            'logo_url' => $tenant->logo,
            'favicon_url' => null,
            'banner_url' => null
        ]);
    }

    /**
     * Criar tema do tenant
     */
    private function createTenantTheme(Tenant $tenant): void
    {
        if ($tenant->theme()->exists()) {
            return;
        }

        TenantTheme::create([
            'tenant_id' => $tenant->id,
            'theme_name' => 'default',
            'theme_version' => '1.0.0',
            'primary_color' => '#007bff',
            'secondary_color' => '#6c757d',
            'accent_color' => '#28a745',
            'success_color' => '#28a745',
            'warning_color' => '#ffc107',
            'danger_color' => '#dc3545',
            'info_color' => '#17a2b8',
            'text_color' => '#212529',
            'text_muted_color' => '#6c757d',
            'background_color' => '#ffffff',
            'background_secondary_color' => '#f8f9fa',
            'border_color' => '#dee2e6',
            'font_family' => 'Inter, sans-serif',
            'font_size_base' => '16px',
            'font_size_small' => '14px',
            'font_size_large' => '18px',
            'font_weight_normal' => '400',
            'font_weight_bold' => '700',
            'border_radius' => '0.375rem',
            'border_radius_large' => '0.5rem',
            'border_radius_small' => '0.25rem',
            'spacing_unit' => '1rem',
            'container_max_width' => '1200px',
            'button_style' => 'rounded',
            'card_style' => 'shadow',
            'form_style' => 'modern',
            'custom_css' => null,
            'custom_js' => null,
            'enable_dark_mode' => false,
            'enable_animations' => true
        ]);
    }

    /**
     * Criar SEO do tenant
     */
    private function createTenantSeo(Tenant $tenant): void
    {
        if ($tenant->seo()->exists()) {
            return;
        }

        TenantSeo::create([
            'tenant_id' => $tenant->id,
            'meta_title' => $tenant->name . ' - Concessionária de Veículos',
            'meta_description' => 'Encontre o veículo ideal para você na ' . $tenant->name . '. Veículos novos e usados com as melhores condições.',
            'meta_keywords' => 'veículos, carros, motos, concessionária, ' . strtolower($tenant->name),
            'meta_author' => $tenant->name,
            'meta_robots' => 'index, follow',
            'og_title' => $tenant->name . ' - Concessionária de Veículos',
            'og_description' => 'Encontre o veículo ideal para você na ' . $tenant->name . '. Veículos novos e usados com as melhores condições.',
            'og_image' => null,
            'og_type' => 'website',
            'og_site_name' => $tenant->name,
            'og_locale' => 'pt_BR',
            'twitter_card' => 'summary_large_image',
            'twitter_site' => null,
            'twitter_creator' => null,
            'twitter_title' => $tenant->name . ' - Concessionária de Veículos',
            'twitter_description' => 'Encontre o veículo ideal para você na ' . $tenant->name . '. Veículos novos e usados com as melhores condições.',
            'twitter_image' => null,
            'schema_organization' => null,
            'schema_website' => null,
            'schema_automotive' => null,
            'canonical_url' => null,
            'hreflang' => null,
            'structured_data' => null,
            'enable_amp' => false,
            'enable_sitemap' => true
        ]);
    }

    /**
     * Criar configurações do portal do tenant
     */
    private function createTenantPortalSettings(Tenant $tenant): void
    {
        if ($tenant->portalSettings()->exists()) {
            return;
        }

        TenantPortalSettings::create([
            'tenant_id' => $tenant->id,
            'enable_search' => true,
            'enable_filters' => true,
            'enable_comparison' => true,
            'enable_wishlist' => true,
            'enable_reviews' => true,
            'enable_newsletter' => true,
            'enable_chat_widget' => false,
            'enable_whatsapp_button' => true,
            'vehicles_per_page' => 12,
            'max_vehicles_comparison' => 4,
            'show_featured_vehicles' => true,
            'show_recent_vehicles' => true,
            'show_vehicle_count' => true,
            'show_price_range' => true,
            'require_phone_in_lead' => true,
            'require_email_in_lead' => true,
            'enable_captcha' => false,
            'enable_gdpr_compliance' => false,
            'google_analytics_id' => null,
            'facebook_pixel_id' => null,
            'whatsapp_number' => null,
            'google_maps_api_key' => null,
            'recaptcha_site_key' => null,
            'recaptcha_secret_key' => null,
            'cache_ttl' => 3600,
            'enable_page_cache' => true,
            'enable_image_optimization' => true
        ]);
    }
}
