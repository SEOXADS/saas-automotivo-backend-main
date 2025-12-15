<?php

namespace App\Observers;

use App\Models\Vehicle;
use App\Jobs\UrlMaintenanceJob;
use App\Jobs\SitemapMaintenanceJob;
use Illuminate\Support\Facades\Log;

class VehicleObserver
{
    /**
     * Handle the Vehicle "created" event.
     */
    public function created(Vehicle $vehicle): void
    {
        try {
            // Disparar job para gerar URL
            UrlMaintenanceJob::dispatch($vehicle->id, 'create');

            // Disparar job para atualizar sitemap
            SitemapMaintenanceJob::dispatch($vehicle->tenant_id, 'update');

            Log::info('Jobs disparados para novo veículo', [
                'vehicle_id' => $vehicle->id,
                'tenant_id' => $vehicle->tenant_id,
                'title' => $vehicle->title
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao disparar jobs para novo veículo', [
                'vehicle_id' => $vehicle->id,
                'tenant_id' => $vehicle->tenant_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Vehicle "updated" event.
     */
    public function updated(Vehicle $vehicle): void
    {
        try {
            // Verificar se o título mudou (que pode afetar a URL)
            if ($vehicle->wasChanged('title')) {
                $oldUrl = $vehicle->getOriginal('url');

                // Disparar job para atualizar URL e criar redirecionamento se necessário
                UrlMaintenanceJob::dispatch($vehicle->id, 'update', $oldUrl);

                // Disparar job para atualizar sitemap
                SitemapMaintenanceJob::dispatch($vehicle->tenant_id, 'update');

                Log::info('Jobs disparados para veículo atualizado', [
                    'vehicle_id' => $vehicle->id,
                    'tenant_id' => $vehicle->tenant_id,
                    'old_title' => $vehicle->getOriginal('title'),
                    'new_title' => $vehicle->title,
                    'old_url' => $oldUrl
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erro ao disparar jobs para veículo atualizado', [
                'vehicle_id' => $vehicle->id,
                'tenant_id' => $vehicle->tenant_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Vehicle "deleted" event.
     */
    public function deleted(Vehicle $vehicle): void
    {
        try {
            // Disparar job para limpar redirecionamentos
            UrlMaintenanceJob::dispatch($vehicle->id, 'delete');

            // Disparar job para atualizar sitemap
            SitemapMaintenanceJob::dispatch($vehicle->tenant_id, 'update');

            Log::info('Jobs disparados para veículo deletado', [
                'vehicle_id' => $vehicle->id,
                'tenant_id' => $vehicle->tenant_id,
                'title' => $vehicle->title,
                'url' => $vehicle->url
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao disparar jobs para veículo deletado', [
                'vehicle_id' => $vehicle->id,
                'tenant_id' => $vehicle->tenant_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Vehicle "restored" event.
     */
    public function restored(Vehicle $vehicle): void
    {
        try {
            // Disparar job para regenerar URL
            UrlMaintenanceJob::dispatch($vehicle->id, 'create');

            // Disparar job para atualizar sitemap
            SitemapMaintenanceJob::dispatch($vehicle->tenant_id, 'update');

            Log::info('Jobs disparados para veículo restaurado', [
                'vehicle_id' => $vehicle->id,
                'tenant_id' => $vehicle->tenant_id,
                'title' => $vehicle->title
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao disparar jobs para veículo restaurado', [
                'vehicle_id' => $vehicle->id,
                'tenant_id' => $vehicle->tenant_id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
