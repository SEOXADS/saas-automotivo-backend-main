<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar tenant de exemplo
        $tenant = Tenant::create([
            'name' => 'Empresa Demo',
            'subdomain' => 'demo',
            'email' => 'contato@demo.com',
            'phone' => '(11) 99999-9999',
            'status' => 'active',
            'plan' => 'premium',
            'trial_ends_at' => now()->addDays(30),
            'subscription_ends_at' => now()->addYear(),
            'features' => ['advanced_filters', 'multiple_users', 'analytics', 'crm'],
            'config' => [
                'theme_color' => '#007bff',
                'logo_url' => null,
                'contact_email' => 'vendas@demo.com',
                'contact_phone' => '(11) 99999-9999',
                'address' => 'Rua Demo, 123 - São Paulo/SP'
            ]
        ]);

        // Criar usuário admin
        $adminUser = TenantUser::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Demo',
            'email' => 'admin@demo.com',
            'password' => Hash::make('123456'),
            'phone' => '(11) 99999-9999',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $adminUser->assignRole('admin');

        // Criar usuário manager
        $managerUser = TenantUser::create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager Demo',
            'email' => 'manager@demo.com',
            'password' => Hash::make('123456'),
            'phone' => '(11) 99999-8888',
            'role' => 'manager',
            'is_active' => true,
        ]);
        $managerUser->assignRole('manager');

        // Criar usuário vendedor
        $salespersonUser = TenantUser::create([
            'tenant_id' => $tenant->id,
            'name' => 'Vendedor Demo',
            'email' => 'vendedor@demo.com',
            'password' => Hash::make('123456'),
            'phone' => '(11) 99999-7777',
            'role' => 'salesperson',
            'is_active' => true,
        ]);
        $salespersonUser->assignRole('salesperson');

        // Criar marcas de veículos
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
            $brand = VehicleBrand::create([
                'name' => $brandData['name'],
                'slug' => $brandData['slug'],
                'description' => "Veículos da marca {$brandData['name']}",
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);

            // Criar alguns modelos para cada marca
            $this->createModelsForBrand($brand);
        }

        $this->command->info('✅ Tenant demo criado com sucesso!');
        $this->command->info('📧 Login: admin@demo.com | Senha: 123456');
        $this->command->info('🏢 Tenant: demo');
    }

    private function createModelsForBrand($brand)
    {
        $models = match($brand->slug) {
            'toyota' => ['Corolla', 'Camry', 'RAV4', 'Hilux', 'Etios'],
            'honda' => ['Civic', 'Accord', 'CR-V', 'Fit', 'HR-V'],
            'ford' => ['Focus', 'Fiesta', 'Fusion', 'EcoSport', 'Ranger'],
            'chevrolet' => ['Onix', 'Cruze', 'Tracker', 'S10', 'Spin'],
            'volkswagen' => ['Gol', 'Polo', 'Jetta', 'Tiguan', 'Amarok'],
            'fiat' => ['Uno', 'Palio', 'Siena', 'Toro', 'Argo'],
            'hyundai' => ['HB20', 'Elantra', 'Tucson', 'Creta', 'Santa Fe'],
            'nissan' => ['March', 'Versa', 'Sentra', 'Kicks', 'Frontier'],
            default => ['Modelo 1', 'Modelo 2', 'Modelo 3']
        };

        foreach ($models as $index => $modelName) {
            VehicleModel::create([
                'brand_id' => $brand->id,
                'name' => $modelName,
                'slug' => Str::slug($modelName),
                'description' => "Modelo {$modelName} da {$brand->name}",
                'category' => $this->getRandomCategory(),
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function getRandomCategory()
    {
        $categories = ['hatch', 'sedan', 'suv', 'pickup', 'van'];
        return $categories[array_rand($categories)];
    }
}
