<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Models\Tenant;
use App\Jobs\UrlMaintenanceJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RegenerateVehicleUrlsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vehicles:regenerate-urls
                            {--tenant= : ID do tenant específico}
                            {--dry-run : Apenas mostrar o que seria alterado}
                            {--batch-size=100 : Tamanho do lote para processamento}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenera URLs de veículos existentes aplicando as novas regras de slug';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');

        $this->info('Iniciando regeneração de URLs de veículos...');

        if ($dryRun) {
            $this->warn('MODO DRY-RUN: Nenhuma alteração será feita');
        }

        if ($tenantId) {
            $this->info("Tenant específico: {$tenantId}");
        }

        try {
            $query = Vehicle::query();

            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }

            $totalVehicles = $query->count();
            $this->info("Total de veículos encontrados: {$totalVehicles}");

            if ($totalVehicles === 0) {
                $this->info('Nenhum veículo encontrado para processar');
                return Command::SUCCESS;
            }

            $processed = 0;
            $updated = 0;
            $errors = 0;

            $query->chunk($batchSize, function ($vehicles) use ($dryRun, &$processed, &$updated, &$errors) {
                foreach ($vehicles as $vehicle) {
                    try {
                        $processed++;

                        // Gerar nova URL usando as regras atualizadas
                        $newUrl = \App\Helpers\UrlHelper::generateBasicUrl($vehicle->title);
                        $currentUrl = $vehicle->url;

                        if ($currentUrl !== $newUrl) {
                            $updated++;

                            if ($dryRun) {
                                $this->line("Veículo {$vehicle->id}: '{$currentUrl}' → '{$newUrl}'");
                            } else {
                                // Disparar job para atualizar URL e criar redirecionamento
                                UrlMaintenanceJob::dispatch($vehicle->id, 'update', $currentUrl);

                                $this->line("Job disparado para veículo {$vehicle->id}: '{$currentUrl}' → '{$newUrl}'");
                            }
                        }

                    } catch (\Exception $e) {
                        $errors++;
                        $this->error("Erro ao processar veículo {$vehicle->id}: " . $e->getMessage());
                        Log::error('Erro ao regenerar URL do veículo', [
                            'vehicle_id' => $vehicle->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });

            $this->newLine();
            $this->info("Processamento concluído:");
            $this->info("- Total processados: {$processed}");
            $this->info("- URLs que serão alteradas: {$updated}");
            $this->info("- Erros: {$errors}");

            if ($dryRun) {
                $this->warn('MODO DRY-RUN: Nenhuma alteração foi feita');
                $this->info('Execute sem --dry-run para aplicar as alterações');
            } else {
                $this->info('Jobs disparados com sucesso!');
                $this->info('As URLs serão atualizadas em background pelos workers');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Erro durante a regeneração: ' . $e->getMessage());
            Log::error('Erro no comando de regeneração de URLs', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}
