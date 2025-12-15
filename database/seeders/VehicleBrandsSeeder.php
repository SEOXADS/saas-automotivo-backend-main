<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class VehicleBrandsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar se a tabela existe
        if (!Schema::hasTable('vehicle_brands')) {
            $this->command->error('Tabela vehicle_brands não encontrada!');
            return;
        }

        // Verificar se a coluna code existe
        if (!Schema::hasColumn('vehicle_brands', 'code')) {
            $this->command->error('Coluna code não encontrada na tabela vehicle_brands!');
            $this->command->info('Execute primeiro: php artisan migrate');
            return;
        }

        $brands = [
            ['code' => '1', 'name' => 'Acura'],
            ['code' => '2', 'name' => 'Agrale'],
            ['code' => '3', 'name' => 'Alfa Romeo'],
            ['code' => '4', 'name' => 'AM Gen'],
            ['code' => '5', 'name' => 'Asia Motors'],
            ['code' => '189', 'name' => 'ASTON MARTIN'],
            ['code' => '6', 'name' => 'Audi'],
            ['code' => '207', 'name' => 'Baby'],
            ['code' => '7', 'name' => 'BMW'],
            ['code' => '8', 'name' => 'BRM'],
            ['code' => '123', 'name' => 'Bugre'],
            ['code' => '238', 'name' => 'BYD'],
            ['code' => '236', 'name' => 'CAB Motors'],
            ['code' => '10', 'name' => 'Cadillac'],
            ['code' => '245', 'name' => 'Caoa Chery'],
            ['code' => '161', 'name' => 'Caoa Chery/Chery'],
            ['code' => '11', 'name' => 'CBT Jipe'],
            ['code' => '136', 'name' => 'CHANA'],
            ['code' => '182', 'name' => 'CHANGAN'],
            ['code' => '12', 'name' => 'Chrysler'],
            ['code' => '13', 'name' => 'Citroën'],
            ['code' => '14', 'name' => 'Cross Lander'],
            ['code' => '241', 'name' => 'D2D Motors'],
            ['code' => '15', 'name' => 'Daewoo'],
            ['code' => '16', 'name' => 'Daihatsu'],
            ['code' => '246', 'name' => 'DFSK'],
            ['code' => '17', 'name' => 'Dodge'],
            ['code' => '147', 'name' => 'EFFA'],
            ['code' => '18', 'name' => 'Engesa'],
            ['code' => '19', 'name' => 'Envemo'],
            ['code' => '20', 'name' => 'Ferrari'],
            ['code' => '249', 'name' => 'FEVER'],
            ['code' => '21', 'name' => 'Fiat'],
            ['code' => '149', 'name' => 'Fibravan'],
            ['code' => '22', 'name' => 'Ford'],
            ['code' => '190', 'name' => 'FOTON'],
            ['code' => '170', 'name' => 'Fyber'],
            ['code' => '254', 'name' => 'GAC'],
            ['code' => '199', 'name' => 'GEELY'],
            ['code' => '23', 'name' => 'GM - Chevrolet'],
            ['code' => '153', 'name' => 'GREAT WALL'],
            ['code' => '24', 'name' => 'Gurgel'],
            ['code' => '240', 'name' => 'GWM'],
            ['code' => '152', 'name' => 'HAFEI'],
            ['code' => '214', 'name' => 'HITECH ELECTRIC'],
            ['code' => '25', 'name' => 'Honda'],
            ['code' => '26', 'name' => 'Hyundai'],
            ['code' => '27', 'name' => 'Isuzu'],
            ['code' => '208', 'name' => 'IVECO'],
            ['code' => '177', 'name' => 'JAC'],
            ['code' => '251', 'name' => 'Jaecoo'],
            ['code' => '28', 'name' => 'Jaguar'],
            ['code' => '29', 'name' => 'Jeep'],
            ['code' => '154', 'name' => 'JINBEI'],
            ['code' => '30', 'name' => 'JPX'],
            ['code' => '31', 'name' => 'Kia Motors'],
            ['code' => '32', 'name' => 'Lada'],
            ['code' => '171', 'name' => 'LAMBORGHINI'],
            ['code' => '33', 'name' => 'Land Rover'],
            ['code' => '34', 'name' => 'Lexus'],
            ['code' => '168', 'name' => 'LIFAN'],
            ['code' => '127', 'name' => 'LOBINI'],
            ['code' => '35', 'name' => 'Lotus'],
            ['code' => '140', 'name' => 'Mahindra'],
            ['code' => '36', 'name' => 'Maserati'],
            ['code' => '37', 'name' => 'Matra'],
            ['code' => '38', 'name' => 'Mazda'],
            ['code' => '211', 'name' => 'Mclaren'],
            ['code' => '39', 'name' => 'Mercedes-Benz'],
            ['code' => '40', 'name' => 'Mercury'],
            ['code' => '167', 'name' => 'MG'],
            ['code' => '156', 'name' => 'MINI'],
            ['code' => '41', 'name' => 'Mitsubishi'],
            ['code' => '42', 'name' => 'Miura'],
            ['code' => '250', 'name' => 'NETA'],
            ['code' => '43', 'name' => 'Nissan'],
            ['code' => '252', 'name' => 'Omoda'],
            ['code' => '44', 'name' => 'Peugeot'],
            ['code' => '45', 'name' => 'Plymouth'],
            ['code' => '46', 'name' => 'Pontiac'],
            ['code' => '47', 'name' => 'Porsche'],
            ['code' => '185', 'name' => 'RAM'],
            ['code' => '186', 'name' => 'RELY'],
            ['code' => '48', 'name' => 'Renault'],
            ['code' => '195', 'name' => 'Rolls-Royce'],
            ['code' => '49', 'name' => 'Rover'],
            ['code' => '50', 'name' => 'Saab'],
            ['code' => '51', 'name' => 'Saturn'],
            ['code' => '52', 'name' => 'Seat'],
            ['code' => '247', 'name' => 'SERES'],
            ['code' => '183', 'name' => 'SHINERAY'],
            ['code' => '157', 'name' => 'smart'],
            ['code' => '125', 'name' => 'SSANGYONG'],
            ['code' => '54', 'name' => 'Subaru'],
            ['code' => '55', 'name' => 'Suzuki'],
            ['code' => '165', 'name' => 'TAC'],
            ['code' => '56', 'name' => 'Toyota'],
            ['code' => '57', 'name' => 'Troller'],
            ['code' => '58', 'name' => 'Volvo'],
            ['code' => '59', 'name' => 'VW - VolksWagen'],
            ['code' => '163', 'name' => 'Wake'],
            ['code' => '120', 'name' => 'Walk'],
            ['code' => '253', 'name' => 'ZEEKR'],
        ];

        $this->command->info('Iniciando atualização das marcas de veículos...');
        $this->command->info('Total de marcas para processar: ' . count($brands));

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($brands as $brandData) {
            $existingBrand = DB::table('vehicle_brands')
                ->where('name', $brandData['name'])
                ->orWhere('code', $brandData['code'])
                ->first();

            if ($existingBrand) {
                // Atualizar marca existente
                $updateData = [];

                // Se não tem code, adicionar
                if (empty($existingBrand->code)) {
                    $updateData['code'] = $brandData['code'];
                }

                // Se não tem name ou é diferente, atualizar
                if (empty($existingBrand->name) || $existingBrand->name !== $brandData['name']) {
                    $updateData['name'] = $brandData['name'];
                }

                if (!empty($updateData)) {
                    DB::table('vehicle_brands')
                        ->where('id', $existingBrand->id)
                        ->update($updateData);
                    $updated++;
                    $this->command->info("✓ Atualizada: {$brandData['name']} (ID: {$existingBrand->id})");
                } else {
                    $skipped++;
                    $this->command->info("- Pulada: {$brandData['name']} (já está atualizada)");
                }
            } else {
                // Criar nova marca
                DB::table('vehicle_brands')->insert([
                    'code' => $brandData['code'],
                    'name' => $brandData['name'],
                    'slug' => Str::slug($brandData['name']),
                    'is_active' => true,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $created++;
                $this->command->info("+ Criada: {$brandData['name']} (Code: {$brandData['code']})");
            }
        }

        $this->command->info('');
        $this->command->info('🎯 RESUMO DA EXECUÇÃO:');
        $this->command->info("✓ Marcas criadas: {$created}");
        $this->command->info("✓ Marcas atualizadas: {$updated}");
        $this->command->info("- Marcas puladas: {$skipped}");
        $this->command->info("✓ Total processado: " . ($created + $updated + $skipped));

        $this->command->info('');
        $this->command->info('✅ Seeder executado com sucesso!');
    }
};
