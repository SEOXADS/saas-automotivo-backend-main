<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\Vehicle;
use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Support\Str;

class ChevroletVehiclesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar a marca Chevrolet
        $chevrolet = VehicleBrand::where('name', 'like', '%Chevrolet%')->first();

        if (!$chevrolet) {
            $this->command->error('Marca Chevrolet não encontrada!');
            return;
        }

        // Buscar um tenant para associar os veículos
        $tenant = Tenant::first();
        if (!$tenant) {
            $this->command->error('Nenhum tenant encontrado!');
            return;
        }

        // Buscar um usuário do tenant para ser o criador
        $user = TenantUser::where('tenant_id', $tenant->id)->first();
        if (!$user) {
            $this->command->error('Nenhum usuário do tenant encontrado!');
            return;
        }

        // Modelos da Chevrolet
        $models = [
            [
                'name' => 'Onix',
                'category' => 'hatch',
                'description' => 'Compacto hatchback da Chevrolet, ideal para cidade'
            ],
            [
                'name' => 'Onix Plus',
                'category' => 'sedan',
                'description' => 'Versão sedan do Onix, com mais espaço interno'
            ],
            [
                'name' => 'Tracker',
                'category' => 'suv',
                'description' => 'SUV compacto da Chevrolet, versátil para cidade e estrada'
            ],
            [
                'name' => 'Cruze',
                'category' => 'sedan',
                'description' => 'Sedan médio da Chevrolet, com design moderno e conforto'
            ],
            [
                'name' => 'Spin',
                'category' => 'van',
                'description' => 'Van da Chevrolet, ideal para família e trabalho'
            ],
            [
                'name' => 'S10',
                'category' => 'pickup',
                'description' => 'Pickup média da Chevrolet, robusta e versátil'
            ],
            [
                'name' => 'Cobalt',
                'category' => 'sedan',
                'description' => 'Sedan compacto da Chevrolet, econômico e confiável'
            ],
            [
                'name' => 'Prisma',
                'category' => 'sedan',
                'description' => 'Sedan compacto da Chevrolet, com boa relação custo-benefício'
            ]
        ];

        // Criar modelos
        foreach ($models as $modelData) {
            $model = VehicleModel::create([
                'brand_id' => $chevrolet->id,
                'name' => $modelData['name'],
                'slug' => Str::slug($modelData['name']),
                'description' => $modelData['description'],
                'category' => $modelData['category'],
                'is_active' => true,
                'sort_order' => 0
            ]);

            $this->command->info("Modelo {$model->name} criado com sucesso!");
        }

        // Veículos de teste da Chevrolet
        $vehicles = [
            [
                'title' => 'Chevrolet Onix 1.0 Turbo Flex LTZ 2023',
                'version' => 'LTZ',
                'year' => 2023,
                'model_year' => 2023,
                'color' => 'Branco',
                'fuel_type' => 'flex',
                'transmission' => 'manual',
                'doors' => 4,
                'mileage' => 15000,
                'price' => 75000.00,
                'fipe_price' => 72000.00,
                'engine' => '1.0 Turbo Flex',
                'power' => '116 cv',
                'torque' => '16.3 kgfm',
                'consumption_city' => '12.5 km/l',
                'consumption_highway' => '16.8 km/l',
                'description' => 'Chevrolet Onix LTZ 2023 em excelente estado, único dono, revisões em dia. Carro muito econômico e ágil na cidade.',
                'plate' => 'ABC1234',
                'chassi' => '9BWZZZ377VT004321',
                'renavam' => '12345678901',
                'owner_name' => 'João Silva',
                'owner_phone' => '(11) 99999-9999',
                'owner_email' => 'joao@email.com',
                'status' => 'available',
                'is_featured' => true,
                'is_active' => true,
                'views' => 45,
                'published_at' => now(),
                'model_name' => 'Onix'
            ],
            [
                'title' => 'Chevrolet Tracker 1.0 Turbo Flex Premier 2022',
                'version' => 'Premier',
                'year' => 2022,
                'model_year' => 2022,
                'color' => 'Prata',
                'fuel_type' => 'flex',
                'transmission' => 'automatica',
                'doors' => 5,
                'mileage' => 25000,
                'price' => 95000.00,
                'fipe_price' => 92000.00,
                'engine' => '1.0 Turbo Flex',
                'power' => '116 cv',
                'torque' => '16.3 kgfm',
                'consumption_city' => '11.8 km/l',
                'consumption_highway' => '15.2 km/l',
                'description' => 'Chevrolet Tracker Premier 2022, automático, muito bem conservado. SUV compacto ideal para família, com excelente posição de dirigir.',
                'plate' => 'XYZ5678',
                'chassi' => '9BWZZZ377VT004322',
                'renavam' => '12345678902',
                'owner_name' => 'Maria Santos',
                'owner_phone' => '(11) 88888-8888',
                'owner_email' => 'maria@email.com',
                'status' => 'available',
                'is_featured' => true,
                'is_active' => true,
                'views' => 67,
                'published_at' => now(),
                'model_name' => 'Tracker'
            ],
            [
                'title' => 'Chevrolet Cruze 1.8 LTZ 2021',
                'version' => 'LTZ',
                'year' => 2021,
                'model_year' => 2021,
                'color' => 'Preto',
                'fuel_type' => 'flex',
                'transmission' => 'automatica',
                'doors' => 4,
                'mileage' => 35000,
                'price' => 85000.00,
                'fipe_price' => 82000.00,
                'engine' => '1.8 Flex',
                'power' => '104 cv',
                'torque' => '17.3 kgfm',
                'consumption_city' => '10.5 km/l',
                'consumption_highway' => '14.2 km/l',
                'description' => 'Chevrolet Cruze LTZ 2021, automático, muito bem conservado. Sedan médio com excelente conforto e dirigibilidade.',
                'plate' => 'DEF9012',
                'chassi' => '9BWZZZ377VT004323',
                'renavam' => '12345678903',
                'owner_name' => 'Pedro Costa',
                'owner_phone' => '(11) 77777-7777',
                'owner_email' => 'pedro@email.com',
                'status' => 'available',
                'is_featured' => false,
                'is_active' => true,
                'views' => 23,
                'published_at' => now(),
                'model_name' => 'Cruze'
            ],
            [
                'title' => 'Chevrolet Spin 1.8 LTZ 2020',
                'version' => 'LTZ',
                'year' => 2020,
                'model_year' => 2020,
                'color' => 'Azul',
                'fuel_type' => 'flex',
                'transmission' => 'automatica',
                'doors' => 5,
                'mileage' => 45000,
                'price' => 65000.00,
                'fipe_price' => 62000.00,
                'engine' => '1.8 Flex',
                'power' => '104 cv',
                'torque' => '17.3 kgfm',
                'consumption_city' => '10.2 km/l',
                'consumption_highway' => '13.8 km/l',
                'description' => 'Chevrolet Spin LTZ 2020, automático, muito bem conservado. Van ideal para família e trabalho, com excelente espaço interno.',
                'plate' => 'GHI3456',
                'chassi' => '9BWZZZ377VT004324',
                'renavam' => '12345678904',
                'owner_name' => 'Ana Oliveira',
                'owner_phone' => '(11) 66666-6666',
                'owner_email' => 'ana@email.com',
                'status' => 'available',
                'is_featured' => false,
                'is_active' => true,
                'views' => 18,
                'published_at' => now(),
                'model_name' => 'Spin'
            ],
            [
                'title' => 'Chevrolet S10 2.8 LTZ 4x4 2019',
                'version' => 'LTZ 4x4',
                'year' => 2019,
                'model_year' => 2019,
                'color' => 'Branco',
                'fuel_type' => 'diesel',
                'transmission' => 'manual',
                'doors' => 4,
                'mileage' => 55000,
                'price' => 120000.00,
                'fipe_price' => 118000.00,
                'engine' => '2.8 Diesel',
                'power' => '200 cv',
                'torque' => '45.9 kgfm',
                'consumption_city' => '8.5 km/l',
                'consumption_highway' => '12.2 km/l',
                'description' => 'Chevrolet S10 LTZ 4x4 2019, diesel, muito bem conservada. Pickup robusta ideal para trabalho e lazer, com tração 4x4.',
                'plate' => 'JKL7890',
                'chassi' => '9BWZZZ377VT004325',
                'renavam' => '12345678905',
                'owner_name' => 'Carlos Ferreira',
                'owner_phone' => '(11) 55555-5555',
                'owner_email' => 'carlos@email.com',
                'status' => 'available',
                'is_featured' => true,
                'is_active' => true,
                'views' => 89,
                'published_at' => now(),
                'model_name' => 'S10'
            ]
        ];

        // Criar veículos
        foreach ($vehicles as $vehicleData) {
            $modelName = $vehicleData['model_name'];
            unset($vehicleData['model_name']);

            $model = VehicleModel::where('name', $modelName)->first();

            if ($model) {
                $vehicle = Vehicle::create([
                    'tenant_id' => $tenant->id,
                    'brand_id' => $chevrolet->id,
                    'model_id' => $model->id,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    ...$vehicleData
                ]);

                $this->command->info("Veículo {$vehicle->title} criado com sucesso!");
            }
        }

        $this->command->info('Seeder ChevroletVehiclesSeeder executado com sucesso!');
        $this->command->info('Modelos criados: ' . VehicleModel::where('brand_id', $chevrolet->id)->count());
        $this->command->info('Veículos criados: ' . Vehicle::where('brand_id', $chevrolet->id)->count());
    }
}
