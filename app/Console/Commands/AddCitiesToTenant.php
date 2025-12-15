<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\City;
use App\Models\TenantCity;

class AddCitiesToTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:add-cities {tenant_id} {--state=25}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adicionar cidades específicas de São Paulo para um tenant';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->argument('tenant_id');
        $stateId = $this->option('state');

        $cities = [
            'Votorantim',
            'Araçoiaba da Serra',
            'Iperó',
            'Boituva',
            'Itu',
            'Salto',
            'Porto Feliz',
            'Mairinque',
            'São Roque',
            'Alumínio',
            'Piedade',
            'Salto de Pirapora',
            'Alambari',
            'Araçariguama',
            'Capela do Alto',
            'Cerquilho',
            'Cesário Lange',
            'Ibiúna',
            'Pilar do Sul',
            'São Miguel Arcanjo',
            'Sarapuí',
            'Tapiraí',
            'Tatuí',
            'Tietê',
            'Itapetininga',
            'Campinas',
            'Itapevi',
            'Osasco',
            'Valinhos',
            'Indaiatuba'
        ];

        $this->info("Adicionando cidades para o tenant ID: {$tenantId}");
        $this->info("Estado: São Paulo (ID: {$stateId})");
        $this->newLine();

        $added = 0;
        $existing = 0;
        $notFound = 0;

        foreach ($cities as $cityName) {
            $city = City::where('name', 'like', '%' . $cityName . '%')
                       ->where('state_id', $stateId)
                       ->first();

            if (!$city) {
                $this->error("❌ Cidade não encontrada: {$cityName}");
                $notFound++;
                continue;
            }

            $exists = TenantCity::where('tenant_id', $tenantId)
                               ->where('city_id', $city->id)
                               ->exists();

            if (!$exists) {
                TenantCity::create([
                    'tenant_id' => $tenantId,
                    'city_id' => $city->id,
                    'is_active' => true
                ]);
                $this->info("✅ Adicionada: {$cityName} (ID: {$city->id})");
                $added++;
            } else {
                $this->warn("⚠️  Já existe: {$cityName} (ID: {$city->id})");
                $existing++;
            }
        }

        $this->newLine();
        $this->info("=== RESUMO ===");
        $this->info("Cidades adicionadas: {$added}");
        $this->info("Cidades já existentes: {$existing}");
        $this->info("Cidades não encontradas: {$notFound}");
        $this->info("Total processadas: " . count($cities));

        return 0;
    }
}
