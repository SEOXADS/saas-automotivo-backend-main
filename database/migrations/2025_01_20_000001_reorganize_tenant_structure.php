<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Criar tabela tenant_profiles para dados da empresa
        Schema::create('tenant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            // Dados da empresa
            $table->string('company_name');
            $table->text('company_description')->nullable();
            $table->string('company_cnpj', 18)->nullable();
            $table->string('company_phone', 20)->nullable();
            $table->string('company_email');
            $table->string('company_website')->nullable();

            // Endereço
            $table->string('address_street')->nullable();
            $table->string('address_number')->nullable();
            $table->string('address_complement')->nullable();
            $table->string('address_district')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_state', 2)->nullable();
            $table->string('address_zipcode', 9)->nullable();
            $table->string('address_country')->default('Brasil');

            // Horário de funcionamento
            $table->json('business_hours')->nullable();

            // Redes sociais
            $table->json('social_media')->nullable();

            // Logo e imagens
            $table->string('logo_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->string('banner_url')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->unique('tenant_id');
            $table->index('company_cnpj');
        });

        // 2. Criar tabela tenant_themes para configurações de tema
        Schema::create('tenant_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            // Tema selecionado
            $table->string('theme_name')->default('default');
            $table->string('theme_version')->default('1.0.0');

            // Cores principais
            $table->string('primary_color')->default('#007bff');
            $table->string('secondary_color')->default('#6c757d');
            $table->string('accent_color')->default('#28a745');
            $table->string('success_color')->default('#28a745');
            $table->string('warning_color')->default('#ffc107');
            $table->string('danger_color')->default('#dc3545');
            $table->string('info_color')->default('#17a2b8');

            // Cores de texto e fundo
            $table->string('text_color')->default('#212529');
            $table->string('text_muted_color')->default('#6c757d');
            $table->string('background_color')->default('#ffffff');
            $table->string('background_secondary_color')->default('#f8f9fa');
            $table->string('border_color')->default('#dee2e6');

            // Tipografia
            $table->string('font_family')->default('Inter, sans-serif');
            $table->string('font_size_base')->default('16px');
            $table->string('font_size_small')->default('14px');
            $table->string('font_size_large')->default('18px');
            $table->string('font_weight_normal')->default('400');
            $table->string('font_weight_bold')->default('700');

            // Layout e espaçamento
            $table->string('border_radius')->default('0.375rem');
            $table->string('border_radius_large')->default('0.5rem');
            $table->string('border_radius_small')->default('0.25rem');
            $table->string('spacing_unit')->default('1rem');
            $table->string('container_max_width')->default('1200px');

            // Componentes
            $table->string('button_style')->default('rounded');
            $table->string('card_style')->default('shadow');
            $table->string('form_style')->default('modern');

            // Configurações avançadas
            $table->json('custom_css')->nullable();
            $table->json('custom_js')->nullable();
            $table->boolean('enable_dark_mode')->default(false);
            $table->boolean('enable_animations')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->unique('tenant_id');
            $table->index('theme_name');
        });

        // 3. Criar tabela tenant_seo para configurações de SEO
        Schema::create('tenant_seo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            // Meta tags básicas
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('meta_author')->nullable();
            $table->string('meta_robots')->default('index, follow');

            // Open Graph (Facebook)
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('og_type')->default('website');
            $table->string('og_site_name')->nullable();
            $table->string('og_locale')->default('pt_BR');

            // Twitter Card
            $table->string('twitter_card')->default('summary_large_image');
            $table->string('twitter_site')->nullable();
            $table->string('twitter_creator')->nullable();
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image')->nullable();

            // Schema.org
            $table->json('schema_organization')->nullable();
            $table->json('schema_website')->nullable();
            $table->json('schema_automotive')->nullable();

            // Configurações avançadas
            $table->string('canonical_url')->nullable();
            $table->string('hreflang')->nullable();
            $table->json('structured_data')->nullable();
            $table->boolean('enable_amp')->default(false);
            $table->boolean('enable_sitemap')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->unique('tenant_id');
        });

        // 4. Criar tabela tenant_portal_settings para configurações do portal
        Schema::create('tenant_portal_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            // Funcionalidades do portal
            $table->boolean('enable_search')->default(true);
            $table->boolean('enable_filters')->default(true);
            $table->boolean('enable_comparison')->default(true);
            $table->boolean('enable_wishlist')->default(true);
            $table->boolean('enable_reviews')->default(true);
            $table->boolean('enable_newsletter')->default(true);
            $table->boolean('enable_chat_widget')->default(false);
            $table->boolean('enable_whatsapp_button')->default(true);

            // Configurações de exibição
            $table->integer('vehicles_per_page')->default(12);
            $table->integer('max_vehicles_comparison')->default(4);
            $table->boolean('show_featured_vehicles')->default(true);
            $table->boolean('show_recent_vehicles')->default(true);
            $table->boolean('show_vehicle_count')->default(true);
            $table->boolean('show_price_range')->default(true);

            // Configurações de formulários
            $table->boolean('require_phone_in_lead')->default(true);
            $table->boolean('require_email_in_lead')->default(true);
            $table->boolean('enable_captcha')->default(false);
            $table->boolean('enable_gdpr_compliance')->default(false);

            // Integrações
            $table->string('google_analytics_id')->nullable();
            $table->string('facebook_pixel_id')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('google_maps_api_key')->nullable();
            $table->string('recaptcha_site_key')->nullable();
            $table->string('recaptcha_secret_key')->nullable();

            // Configurações de cache
            $table->integer('cache_ttl')->default(3600);
            $table->boolean('enable_page_cache')->default(true);
            $table->boolean('enable_image_optimization')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->unique('tenant_id');
        });

        // 5. Migrar dados existentes da tabela tenants para as novas tabelas
        $this->migrateExistingData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_portal_settings');
        Schema::dropIfExists('tenant_seo');
        Schema::dropIfExists('tenant_themes');
        Schema::dropIfExists('tenant_profiles');
    }

    /**
     * Migrar dados existentes
     */
    private function migrateExistingData(): void
    {
        // Migrar dados para tenant_profiles
        DB::statement("
            INSERT INTO tenant_profiles (
                tenant_id, company_name, company_description, company_phone,
                company_email, logo_url, created_at, updated_at
            )
            SELECT
                id, name, NULL, phone, email, logo,
                created_at, updated_at
            FROM tenants
            WHERE id NOT IN (SELECT tenant_id FROM tenant_profiles)
        ");

        // Migrar dados para tenant_themes
        DB::statement("
            INSERT INTO tenant_themes (
                tenant_id, created_at, updated_at
            )
            SELECT
                id, created_at, updated_at
            FROM tenants
            WHERE id NOT IN (SELECT tenant_id FROM tenant_themes)
        ");

        // Migrar dados para tenant_portal_settings
        DB::statement("
            INSERT INTO tenant_portal_settings (
                tenant_id, created_at, updated_at
            )
            SELECT
                id, created_at, updated_at
            FROM tenants
            WHERE id NOT IN (SELECT tenant_id FROM tenant_portal_settings)
        ");

        // Migrar dados para tenant_seo
        DB::statement("
            INSERT INTO tenant_seo (
                tenant_id, created_at, updated_at
            )
            SELECT
                id, created_at, updated_at
            FROM tenants
            WHERE id NOT IN (SELECT tenant_id FROM tenant_seo)
        ");
    }
};
