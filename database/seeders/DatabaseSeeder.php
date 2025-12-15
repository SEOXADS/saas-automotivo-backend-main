<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Verificar ambiente
        if (app()->environment('production')) {
            // Em produção, usar seeder específico
            $this->call([
                ProductionSeeder::class,
            ]);
        } else {
            // Em desenvolvimento, usar seeders de teste
            $this->call([
                PermissionSeeder::class,
                CountrySeeder::class,
                StateSeeder::class,
                CitySeeder::class,
                NeighborhoodSeeder::class,
                TenantSeeder::class,
                SystemMessageSeeder::class,
                DefaultTenantSeeder::class,
            ]);
        }
    }
}
