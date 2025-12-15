<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class StatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = [
            ['name' => 'Acre', 'code' => 'AC', 'country_id' => 1],
            ['name' => 'Alagoas', 'code' => 'AL', 'country_id' => 1],
            ['name' => 'Amapá', 'code' => 'AP', 'country_id' => 1],
            ['name' => 'Amazonas', 'code' => 'AM', 'country_id' => 1],
            ['name' => 'Bahia', 'code' => 'BA', 'country_id' => 1],
            ['name' => 'Ceará', 'code' => 'CE', 'country_id' => 1],
            ['name' => 'Distrito Federal', 'code' => 'DF', 'country_id' => 1],
            ['name' => 'Espírito Santo', 'code' => 'ES', 'country_id' => 1],
            ['name' => 'Goiás', 'code' => 'GO', 'country_id' => 1],
            ['name' => 'Maranhão', 'code' => 'MA', 'country_id' => 1],
            ['name' => 'Mato Grosso', 'code' => 'MT', 'country_id' => 1],
            ['name' => 'Mato Grosso do Sul', 'code' => 'MS', 'country_id' => 1],
            ['name' => 'Minas Gerais', 'code' => 'MG', 'country_id' => 1],
            ['name' => 'Pará', 'code' => 'PA', 'country_id' => 1],
            ['name' => 'Paraíba', 'code' => 'PB', 'country_id' => 1],
            ['name' => 'Paraná', 'code' => 'PR', 'country_id' => 1],
            ['name' => 'Pernambuco', 'code' => 'PE', 'country_id' => 1],
            ['name' => 'Piauí', 'code' => 'PI', 'country_id' => 1],
            ['name' => 'Rio de Janeiro', 'code' => 'RJ', 'country_id' => 1],
            ['name' => 'Rio Grande do Norte', 'code' => 'RN', 'country_id' => 1],
            ['name' => 'Rio Grande do Sul', 'code' => 'RS', 'country_id' => 1],
            ['name' => 'Rondônia', 'code' => 'RO', 'country_id' => 1],
            ['name' => 'Roraima', 'code' => 'RR', 'country_id' => 1],
            ['name' => 'Santa Catarina', 'code' => 'SC', 'country_id' => 1],
            ['name' => 'São Paulo', 'code' => 'SP', 'country_id' => 1],
            ['name' => 'Sergipe', 'code' => 'SE', 'country_id' => 1],
            ['name' => 'Tocantins', 'code' => 'TO', 'country_id' => 1],
        ];

        foreach ($states as $state) {
            DB::table('states')->insert([
                'name' => $state['name'],
                'code' => $state['code'],
                'country_id' => $state['country_id'],
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        $this->command->info('27 Brazilian states seeded successfully!');
    }
}