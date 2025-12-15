<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Models\Tenant;
use App\Jobs\UrlMaintenanceJob;
use App\Jobs\SitemapMaintenanceJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MaintenanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:run
                            {--type=all : Tipo de manutenção (urls, sitemaps, all)}
                            {--tenant= : ID do tenant específico}
                            {--force : Forçar execução mesmo se não houver mudanças}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executa manutenção de URLs e sitemaps';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');
        $tenantId = $this->option('tenant');
        $force = $this->option('force');

        $this->info("Iniciando manutenção: {$type}");

        if ($tenantId) {
            $this->info("Tenant específico: {$tenantId}");
        }

        try {
            switch ($type) {
                case 'urls':
                    $this->maintainUrls($tenantId, $force);
                    break;
                case 'sitemaps':
                    $this->maintainSitemaps($tenantId, $force);
                    break;
                case 'all':
                default:
                    $this->maintainUrls($tenantId, $force);
                    $this->maintainSitemaps($tenantId, $force);
                    break;
            }

            $this->info('Manutenção concluída com sucesso!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Erro durante a manutenção: ' . $e->getMessage());
            Log::error('Erro no comando de manutenção', [
                'type' => $type,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Manter URLs
     */
    private function maintainUrls(?int $tenantId, bool $force): void
    {
        $this->info('Iniciando manutenção de URLs...');

        $query = Vehicle::query();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $vehicles = $query->get();

        $this->info("Encontrados {$vehicles->count()} veículos para processar");

        $bar = $this->output->createProgressBar($vehicles->count());
        $bar->start();

        foreach ($vehicles as $vehicle) {
            try {
                // Verificar se a URL precisa ser atualizada
                $currentUrl = $vehicle->url;
                $expectedUrl = \App\Helpers\UrlHelper::generateBasicUrl($vehicle->title);

                if ($force || !$currentUrl || $currentUrl !== $expectedUrl) {
                    UrlMaintenanceJob::dispatch($vehicle->id, 'update', $currentUrl);
                }

            } catch (\Exception $e) {
                $this->warn("Erro ao processar veículo {$vehicle->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Manutenção de URLs concluída');
    }

    /**
     * Manter sitemaps
     */
    private function maintainSitemaps(?int $tenantId, bool $force): void
    {
        $this->info('Iniciando manutenção de sitemaps...');

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                $this->error("Tenant {$tenantId} não encontrado");
                return;
            }

            SitemapMaintenanceJob::dispatch($tenantId, 'generate');
            $this->info("Job de sitemap disparado para tenant {$tenantId}");
        } else {
            SitemapMaintenanceJob::dispatch(null, 'regenerate_all');
            $this->info('Job de regeneração de todos os sitemaps disparado');
        }

        $this->info('Manutenção de sitemaps concluída');
    }
}
