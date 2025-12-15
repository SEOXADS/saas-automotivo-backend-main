<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\TenantIntegration;
use App\Models\TenantAnalytics;
use App\Models\TenantNotification;
use App\Models\PortalCache;
use Illuminate\Support\Facades\DB;

class TenantPortalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌐 Iniciando seed das funcionalidades do portal...');

        // 1. Atualizar tenants existentes com configurações do portal
        $this->updateExistingTenants();

        // 2. Criar integrações de exemplo
        $this->createSampleIntegrations();

        // 3. Criar analytics de exemplo
        $this->createSampleAnalytics();

        // 4. Criar notificações de exemplo
        $this->createSampleNotifications();

        // 5. Criar cache de exemplo
        $this->createSampleCache();

        $this->command->info('✅ Seed das funcionalidades do portal concluído!');
    }

    /**
     * Atualizar tenants existentes
     */
    private function updateExistingTenants(): void
    {
        $this->command->info('📝 Atualizando tenants existentes...');

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $tenant->update([
                'description' => "Portal de anúncios da {$tenant->name}",
                'contact_email' => "contato@{$tenant->subdomain}.com",
                'contact_phone' => '(11) 99999-9999',
                'address' => 'Rua das Flores, 123 - São Paulo/SP',
                'theme_color' => $this->getRandomThemeColor(),
                'logo_url' => "https://via.placeholder.com/200x80/007bff/ffffff?text={$tenant->name}",
                'social_media' => [
                    'facebook' => "https://facebook.com/{$tenant->subdomain}",
                    'instagram' => "https://instagram.com/{$tenant->subdomain}",
                    'whatsapp' => "https://wa.me/5511999999999"
                ],
                'business_hours' => [
                    'monday' => ['09:00', '18:00'],
                    'tuesday' => ['09:00', '18:00'],
                    'wednesday' => ['09:00', '18:00'],
                    'thursday' => ['09:00', '18:00'],
                    'friday' => ['09:00', '18:00'],
                    'saturday' => ['09:00', '12:00'],
                    'sunday' => []
                ],
                'allow_registration' => true,
                'require_approval' => true,
                'is_default' => $tenant->id === 1 // Primeiro tenant como padrão
            ]);

            $this->command->info("✅ Tenant {$tenant->name} atualizado");
        }
    }

    /**
     * Criar integrações de exemplo
     */
    private function createSampleIntegrations(): void
    {
        $this->command->info('🔗 Criando integrações de exemplo...');

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Google Analytics
            TenantIntegration::createGoogleAnalytics(
                $tenant->id,
                'GA-' . strtoupper(substr($tenant->subdomain, 0, 8)) . '01'
            );

            // Facebook Pixel
            TenantIntegration::createFacebookPixel(
                $tenant->id,
                '123456789' . $tenant->id
            );

            // WhatsApp
            TenantIntegration::createWhatsApp(
                $tenant->id,
                '5511999999999',
                "Olá! Gostaria de saber mais sobre os veículos da {$tenant->name}"
            );

            $this->command->info("✅ Integrações criadas para {$tenant->name}");
        }
    }

    /**
     * Criar analytics de exemplo
     */
    private function createSampleAnalytics(): void
    {
        $this->command->info('📊 Criando analytics de exemplo...');

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Simular dados dos últimos 7 dias
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);

                // Visualizações de página
                TenantAnalytics::recordPageView(
                    $tenant->id,
                    'home_page',
                    ['date' => $date->toDateString()]
                );

                TenantAnalytics::recordPageView(
                    $tenant->id,
                    'vehicle_list',
                    ['date' => $date->toDateString()]
                );

                // Buscas realizadas
                TenantAnalytics::recordSearchPerformed(
                    $tenant->id,
                    'honda civic',
                    ['brand_id' => 25, 'min_price' => 50000],
                    ['date' => $date->toDateString()]
                );

                TenantAnalytics::recordSearchPerformed(
                    $tenant->id,
                    'toyota corolla',
                    ['brand_id' => 56, 'max_price' => 80000],
                    ['date' => $date->toDateString()]
                );
            }

            $this->command->info("✅ Analytics criados para {$tenant->name}");
        }
    }

    /**
     * Criar notificações de exemplo
     */
    private function createSampleNotifications(): void
    {
        $this->command->info('🔔 Criando notificações de exemplo...');

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Notificação de lead
            TenantNotification::createLeadNotification(
                $tenant->id,
                1, // lead_id
                'João Silva'
            );

            // Notificação de veículo visualizado
            TenantNotification::createVehicleViewedNotification(
                $tenant->id,
                1, // vehicle_id
                'Honda Civic 2020'
            );

            // Notificação de sistema
            TenantNotification::createSystemNotification(
                $tenant->id,
                'Portal Atualizado',
                'Seu portal foi atualizado com sucesso!',
                'normal'
            );

            $this->command->info("✅ Notificações criadas para {$tenant->name}");
        }
    }

    /**
     * Criar cache de exemplo
     */
    private function createSampleCache(): void
    {
        $this->command->info('💾 Criando cache de exemplo...');

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Cache de filtros
            PortalCache::cacheFilters(
                $tenant->id,
                [
                    'brands' => [
                        ['id' => 25, 'name' => 'Honda'],
                        ['id' => 56, 'name' => 'Toyota']
                    ],
                    'fuel_types' => ['flex', 'gasolina', 'diesel'],
                    'transmissions' => ['manual', 'automatica']
                ]
            );

            // Cache de estatísticas
            PortalCache::cacheStats(
                $tenant->id,
                [
                    'total_vehicles' => 150,
                    'total_leads' => 25,
                    'total_views' => 1250
                ]
            );

            // Cache de configurações
            PortalCache::cacheTenantConfig(
                $tenant->id,
                $tenant->getPortalConfig()
            );

            $this->command->info("✅ Cache criado para {$tenant->name}");
        }
    }

    /**
     * Obter cor de tema aleatória
     */
    private function getRandomThemeColor(): string
    {
        $colors = [
            '#007bff', // Azul
            '#28a745', // Verde
            '#dc3545', // Vermelho
            '#ffc107', // Amarelo
            '#6f42c1', // Roxo
            '#fd7e14', // Laranja
            '#20c997', // Teal
            '#e83e8c'  // Rosa
        ];

        return $colors[array_rand($colors)];
    }
}
