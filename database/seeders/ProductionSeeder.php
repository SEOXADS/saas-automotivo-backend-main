<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Configurações via environment ou prompt
        $tenantName = env('PROD_TENANT_NAME', 'Portal Veículos');
        $tenantSubdomain = env('PROD_TENANT_SUBDOMAIN', 'portal');
        $tenantEmail = env('PROD_TENANT_EMAIL', 'contato@portalveiculos.com');
        $tenantPhone = env('PROD_TENANT_PHONE', '(11) 99999-9999');

        $adminName = env('PROD_ADMIN_NAME', 'Administrador');
        $adminEmail = env('PROD_ADMIN_EMAIL', 'admin@portalveiculos.com');
        $adminPassword = env('PROD_ADMIN_PASSWORD', Str::random(12));
        $adminPhone = env('PROD_ADMIN_PHONE', '(11) 99999-9999');

        // Verificar se tenant já existe
        $tenant = Tenant::where('subdomain', $tenantSubdomain)->first();

        if (!$tenant) {
            // Criar tenant de produção
            $tenant = Tenant::create([
                'name' => $tenantName,
                'subdomain' => $tenantSubdomain,
                'email' => $tenantEmail,
                'phone' => $tenantPhone,
                'status' => 'active',
                'plan' => 'enterprise',
                'trial_ends_at' => null, // Sem trial
                'subscription_ends_at' => now()->addYear(),
                'features' => [
                    'advanced_filters',
                    'multiple_users',
                    'analytics',
                    'crm',
                    'api_access',
                    'custom_branding',
                    'priority_support'
                ],
                'config' => [
                    'theme_color' => '#007bff',
                    'logo_url' => null,
                    'contact_email' => $tenantEmail,
                    'contact_phone' => $tenantPhone,
                    'address' => 'Endereço da empresa',
                    'allow_registration' => false,
                    'require_approval' => true,
                ]
            ]);

            $this->command->info("✅ Tenant '{$tenantName}' criado!");
        } else {
            $this->command->info("ℹ️  Tenant '{$tenantName}' já existe.");
        }

        // Verificar se admin já existe
        $existingAdmin = TenantUser::where('email', $adminEmail)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$existingAdmin) {
            // Criar usuário admin
            $admin = TenantUser::create([
                'tenant_id' => $tenant->id,
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'phone' => $adminPhone,
                'role' => 'admin',
                'is_active' => true,
                'permissions' => [
                    'manage_users',
                    'manage_vehicles',
                    'manage_leads',
                    'manage_settings',
                    'view_analytics',
                    'manage_billing',
                    'manage_tenants',
                    'system_admin'
                ],
                'email_verified_at' => now(),
            ]);

            $this->command->info('✅ Usuário administrador criado!');
            $this->command->newLine();
            $this->command->info('🔐 CREDENCIAIS DE ACESSO:');
            $this->command->info("📧 Email: {$adminEmail}");
            $this->command->info("🔑 Senha: {$adminPassword}");
            $this->command->info("🏢 Tenant: {$tenantSubdomain}");
            $this->command->info("🌐 URL: " . env('APP_URL'));
            $this->command->newLine();
            $this->command->warn('⚠️  IMPORTANTE: Salve essas credenciais em local seguro!');
        } else {
            $this->command->info("ℹ️  Admin '{$adminEmail}' já existe no tenant.");
        }

        // Criar marcas básicas se não existirem
        $this->createBasicBrands();
    }

    private function createBasicBrands()
    {
        $brands = [
            ['name' => 'Toyota', 'slug' => 'toyota'],
            ['name' => 'Honda', 'slug' => 'honda'],
            ['name' => 'Ford', 'slug' => 'ford'],
            ['name' => 'Chevrolet', 'slug' => 'chevrolet'],
            ['name' => 'Volkswagen', 'slug' => 'volkswagen'],
            ['name' => 'Fiat', 'slug' => 'fiat'],
            ['name' => 'Hyundai', 'slug' => 'hyundai'],
            ['name' => 'Nissan', 'slug' => 'nissan'],
        ];

        foreach ($brands as $index => $brandData) {
            \App\Models\VehicleBrand::firstOrCreate(
                ['slug' => $brandData['slug']],
                [
                    'name' => $brandData['name'],
                    'description' => "Veículos da marca {$brandData['name']}",
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }

        $this->command->info('✅ Marcas básicas criadas/verificadas.');
    }
}
