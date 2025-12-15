<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\HierarchicalUrlService;
use Illuminate\Console\Command;

class TestHierarchicalUrlsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:hierarchical-urls
                            {--tenant= : ID do tenant específico}
                            {--dry-run : Apenas mostrar o que seria gerado}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa o sistema de URLs hierárquicas';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $dryRun = $this->option('dry-run');

        $this->info('Testando sistema de URLs hierárquicas...');

        if ($dryRun) {
            $this->warn('MODO DRY-RUN: Nenhuma URL será criada');
        }

        try {
            if ($tenantId) {
                $this->testSingleTenant($tenantId, $dryRun);
            } else {
                $this->testAllTenants($dryRun);
            }

            $this->info('Teste concluído com sucesso!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Erro durante o teste: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Testar um tenant específico
     */
    private function testSingleTenant(int $tenantId, bool $dryRun): void
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant {$tenantId} não encontrado");
            return;
        }

        $this->info("Testando tenant: {$tenant->name} ({$tenant->subdomain})");

        // Verificar dados necessários
        $this->checkTenantData($tenant);

        if (!$dryRun) {
            $urlService = new HierarchicalUrlService();
            $results = $urlService->generateAllUrlsForTenant($tenantId);

            $this->info('URLs geradas:');
            $this->table(['Tipo', 'Quantidade'], [
                ['Marcas', $results['brands']],
                ['Veículos', $results['vehicles']],
                ['URLs com Cidades', $results['city_urls']],
                ['URLs com Bairros', $results['neighborhood_urls']],
                ['Total', $results['total_urls']]
            ]);
        } else {
            $this->showDryRunResults($tenant);
        }
    }

    /**
     * Testar todos os tenants
     */
    private function testAllTenants(bool $dryRun): void
    {
        $tenants = Tenant::where('status', 'active')->get();

        $this->info("Testando {$tenants->count()} tenants ativos");

        foreach ($tenants as $tenant) {
            $this->newLine();
            $this->info("--- Tenant: {$tenant->name} ---");
            $this->testSingleTenant($tenant->id, $dryRun);
        }
    }

    /**
     * Verificar dados do tenant
     */
    private function checkTenantData(Tenant $tenant): void
    {
        $this->info('Verificando dados do tenant...');

        // Verificar veículos
        $vehiclesCount = $tenant->vehicles()->count();
        $this->line("Veículos: {$vehiclesCount}");

        // Verificar marcas
        $brandsCount = $tenant->vehicles()->distinct('brand_id')->count();
        $this->line("Marcas: {$brandsCount}");

        // Verificar cidades
        $citiesCount = $tenant->cities()->count();
        $this->line("Cidades: {$citiesCount}");

        // Verificar bairros
        $neighborhoodsCount = $tenant->neighborhoods()->count();
        $this->line("Bairros: {$neighborhoodsCount}");

        if ($vehiclesCount === 0) {
            $this->warn('⚠️  Tenant não possui veículos');
        }

        if ($citiesCount === 0) {
            $this->warn('⚠️  Tenant não possui cidades configuradas');
        }

        if ($neighborhoodsCount === 0) {
            $this->warn('⚠️  Tenant não possui bairros configurados');
        }
    }

    /**
     * Mostrar resultados do dry-run
     */
    private function showDryRunResults(Tenant $tenant): void
    {
        $this->info('Simulando geração de URLs...');

        // Simular contagem de URLs que seriam geradas
        $vehiclesCount = $tenant->vehicles()->count();
        $brandsCount = $tenant->vehicles()->distinct('brand_id')->count();
        $citiesCount = $tenant->cities()->count();
        $neighborhoodsCount = $tenant->neighborhoods()->count();

        $brandUrls = $brandsCount * 2; // Marca + categoria
        $vehicleUrls = $vehiclesCount;
        $cityUrls = $brandsCount * $citiesCount + $vehiclesCount * $citiesCount;
        $neighborhoodUrls = $brandsCount * $neighborhoodsCount + $vehiclesCount * $neighborhoodsCount;

        $totalUrls = $brandUrls + $vehicleUrls + $cityUrls + $neighborhoodUrls;

        $this->table(['Tipo de URL', 'Quantidade Estimada'], [
            ['URLs de Marcas', $brandUrls],
            ['URLs de Veículos', $vehicleUrls],
            ['URLs com Cidades', $cityUrls],
            ['URLs com Bairros', $neighborhoodUrls],
            ['TOTAL', $totalUrls]
        ]);

        $this->info("Total estimado de URLs: {$totalUrls}");

        if ($totalUrls > 10000) {
            $this->warn('⚠️  Muitas URLs serão geradas. Considere usar processamento em background.');
        }
    }
}
