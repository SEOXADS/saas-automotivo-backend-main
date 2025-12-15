<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\TenantProfile;
use App\Models\TenantTheme;
use App\Models\TenantSeo;
use App\Models\TenantPortalSettings;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CreateTenantOnly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create-only
                            {--name= : Nome da empresa/tenant}
                            {--subdomain= : Subdomínio do tenant}
                            {--email= : Email de contato}
                            {--phone= : Telefone de contato}
                            {--plan= : Plano (basic, premium, enterprise)}
                            {--trial-days= : Dias de trial (padrão: 30)}
                            {--features= : Features separadas por vírgula}
                            {--theme-color= : Cor principal do tema}
                            {--logo-url= : URL do logo}
                            {--address= : Endereço completo}
                            {--description= : Descrição da empresa}
                            {--website= : Website da empresa}
                            {--cnpj= : CNPJ da empresa}
                            {--country= : País (padrão: Brasil)}
                            {--timezone= : Timezone (padrão: America/Sao_Paulo)}
                            {--locale= : Locale (padrão: pt_BR)}
                            {--currency= : Moeda (padrão: BRL}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Criar apenas um tenant com todas as configurações (sem usuário administrador)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Criando novo tenant (sem usuário administrador)...');
        $this->newLine();

        try {
            DB::beginTransaction();

            // Obter dados via opções ou prompts interativos
            $tenantData = $this->getTenantData();

            // Validar dados
            $this->validateData($tenantData);

            // Verificar se subdomínio já existe
            if (Tenant::where('subdomain', $tenantData['subdomain'])->exists()) {
                $this->error("❌ Subdomínio '{$tenantData['subdomain']}' já existe!");
                return 1;
            }

            // Criar tenant
            $tenant = $this->createTenant($tenantData);
            $this->info("✅ Tenant '{$tenant->name}' criado com sucesso!");

            // Criar configurações relacionadas
            $this->createTenantProfile($tenant, $tenantData);
            $this->createTenantTheme($tenant, $tenantData);
            $this->createTenantSeo($tenant, $tenantData);
            $this->createTenantPortalSettings($tenant, $tenantData);

            // Commit da transação
            DB::commit();

            // Exibir resumo
            $this->displaySummary($tenant, $tenantData);

            $this->info('🎉 Tenant criado com sucesso com todas as configurações!');
            $this->info('💡 Use o comando "admin:create" para adicionar um usuário administrador.');
            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Erro ao criar tenant: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Obter dados do tenant via opções ou prompts
     */
    private function getTenantData(): array
    {
        return [
            'name' => $this->option('name') ?: $this->ask('Nome da empresa/tenant'),
            'subdomain' => $this->option('subdomain') ?: $this->ask('Subdomínio do tenant'),
            'email' => $this->option('email') ?: $this->ask('Email de contato'),
            'phone' => $this->option('phone') ?: $this->ask('Telefone de contato', '(11) 99999-9999'),
            'plan' => $this->option('plan') ?: $this->choice('Plano', ['basic', 'premium', 'enterprise'], 'premium'),
            'trial_days' => (int)($this->option('trial-days') ?: 30),
            'features' => $this->option('features') ? explode(',', $this->option('features')) : ['basic_filters', 'multiple_users', 'analytics', 'crm'],
            'theme_color' => $this->option('theme-color') ?: '#007bff',
            'logo_url' => $this->option('logo-url') ?: null,
            'address' => $this->option('address') ?: 'Endereço não informado',
            'description' => $this->option('description') ?: null,
            'website' => $this->option('website') ?: null,
            'cnpj' => $this->option('cnpj') ?: null,
            'country' => $this->option('country') ?: 'Brasil',
            'timezone' => $this->option('timezone') ?: 'America/Sao_Paulo',
            'locale' => $this->option('locale') ?: 'pt_BR',
            'currency' => $this->option('currency') ?: 'BRL',
        ];
    }

    /**
     * Validar dados
     */
    private function validateData(array $tenantData): void
    {
        $validator = Validator::make($tenantData, [
            'name' => 'required|string|max:255',
            'subdomain' => 'required|string|max:50|regex:/^[a-z0-9-]+$/',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'plan' => 'required|in:basic,premium,enterprise',
        ]);

        if ($validator->fails()) {
            $this->error('❌ Dados inválidos:');
            foreach ($validator->errors()->all() as $error) {
                $this->error("  • {$error}");
            }
            throw new \Exception('Validação falhou');
        }
    }

    /**
     * Criar tenant
     */
    private function createTenant(array $data): Tenant
    {
        return Tenant::create([
            'name' => $data['name'],
            'subdomain' => $data['subdomain'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'status' => 'active',
            'plan' => $data['plan'],
            'trial_ends_at' => now()->addDays($data['trial_days']),
            'subscription_ends_at' => now()->addYear(),
            'features' => $data['features'],
            'config' => [
                'theme_color' => $data['theme_color'],
                'logo_url' => $data['logo_url'],
                'contact_email' => $data['email'],
                'contact_phone' => $data['phone'],
                'address' => $data['address'],
                'timezone' => $data['timezone'],
                'locale' => $data['locale'],
                'currency' => $data['currency'],
            ]
        ]);
    }

    /**
     * Criar perfil do tenant
     */
    private function createTenantProfile(Tenant $tenant, array $data): void
    {
        TenantProfile::create([
            'tenant_id' => $tenant->id,
            'company_name' => $data['name'],
            'company_description' => $data['description'] ?: "Portal de veículos da {$data['name']}",
            'company_cnpj' => $data['cnpj'],
            'company_phone' => $data['phone'],
            'company_email' => $data['email'],
            'company_website' => $data['website'],
            'address_street' => $data['address'],
            'address_city' => 'São Paulo',
            'address_state' => 'SP',
            'address_country' => $data['country'],
            'business_hours' => $this->getDefaultBusinessHours(),
            'social_media' => $this->getDefaultSocialMedia(),
            'logo_url' => $data['logo_url'],
            'favicon_url' => null,
            'banner_url' => null,
        ]);

        $this->info('✅ Perfil do tenant criado');
    }

    /**
     * Criar tema do tenant
     */
    private function createTenantTheme(Tenant $tenant, array $data): void
    {
        TenantTheme::create([
            'tenant_id' => $tenant->id,
            'theme_name' => 'default',
            'theme_version' => '1.0.0',
            'primary_color' => $data['theme_color'],
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
            'enable_animations' => true,
        ]);

        $this->info('✅ Tema do tenant criado');
    }

    /**
     * Criar SEO do tenant
     */
    private function createTenantSeo(Tenant $tenant, array $data): void
    {
        TenantSeo::create([
            'tenant_id' => $tenant->id,
            'meta_title' => "{$data['name']} - Portal de Veículos",
            'meta_description' => "Encontre o veículo ideal para você na {$data['name']}. Veículos novos e usados com as melhores condições.",
            'meta_keywords' => "veículos, carros, motos, {$data['name']}",
            'meta_author' => $data['name'],
            'meta_robots' => 'index, follow',
            'og_title' => "{$data['name']} - Portal de Veículos",
            'og_description' => "Encontre o veículo ideal para você na {$data['name']}. Veículos novos e usados com as melhores condições.",
            'og_image' => $data['logo_url'],
            'og_type' => 'website',
            'og_site_name' => $data['name'],
            'og_locale' => $data['locale'],
            'twitter_card' => 'summary_large_image',
            'twitter_site' => null,
            'twitter_creator' => null,
            'twitter_title' => "{$data['name']} - Portal de Veículos",
            'twitter_description' => "Encontre o veículo ideal para você na {$data['name']}. Veículos novos e usados com as melhores condições.",
            'twitter_image' => $data['logo_url'],
            'schema_organization' => null,
            'schema_website' => null,
            'schema_automotive' => null,
            'canonical_url' => null,
            'hreflang' => null,
            'structured_data' => null,
            'enable_amp' => false,
            'enable_sitemap' => true,
        ]);

        $this->info('✅ SEO do tenant criado');
    }

    /**
     * Criar configurações do portal
     */
    private function createTenantPortalSettings(Tenant $tenant, array $data): void
    {
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
            'enable_image_optimization' => true,
        ]);

        $this->info('✅ Configurações do portal criadas');
    }

    /**
     * Horários de funcionamento padrão
     */
    private function getDefaultBusinessHours(): array
    {
        return [
            'monday' => ['open' => '08:00', 'close' => '18:00', 'closed' => false],
            'tuesday' => ['open' => '08:00', 'close' => '18:00', 'closed' => false],
            'wednesday' => ['open' => '08:00', 'close' => '18:00', 'closed' => false],
            'thursday' => ['open' => '08:00', 'close' => '18:00', 'closed' => false],
            'friday' => ['open' => '08:00', 'close' => '18:00', 'closed' => false],
            'saturday' => ['open' => '08:00', 'close' => '12:00', 'closed' => false],
            'sunday' => ['closed' => true],
        ];
    }

    /**
     * Redes sociais padrão
     */
    private function getDefaultSocialMedia(): array
    {
        return [
            'facebook' => null,
            'instagram' => null,
            'twitter' => null,
            'linkedin' => null,
            'youtube' => null,
            'whatsapp' => null,
        ];
    }

    /**
     * Exibir resumo da criação
     */
    private function displaySummary(Tenant $tenant, array $tenantData): void
    {
        $this->newLine();
        $this->info('📋 RESUMO DO TENANT CRIADO:');
        $this->newLine();

        $this->table(
            ['Campo', 'Valor'],
            [
                ['ID do Tenant', $tenant->id],
                ['Nome', $tenant->name],
                ['Subdomínio', $tenant->subdomain],
                ['Email', $tenant->email],
                ['Telefone', $tenant->phone],
                ['Plano', $tenant->plan],
                ['Status', $tenant->status],
                ['Trial até', $tenant->trial_ends_at->format('d/m/Y')],
                ['Features', implode(', ', $tenant->features)],
            ]
        );

        $this->newLine();
        $this->info('🌐 ACESSO:');
        $this->line("🏢 Tenant: {$tenant->subdomain}");
        $this->line("🌐 URL: http://{$tenant->subdomain}.localhost:8000");
        $this->newLine();

        $this->info('📊 CONFIGURAÇÕES CRIADAS:');
        $this->line("✅ Perfil da empresa");
        $this->line("✅ Tema e cores");
        $this->line("✅ Configurações SEO");
        $this->line("✅ Configurações do portal");
        $this->newLine();
    }
}
