<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\TenantSitemapConfig;
use App\Http\Controllers\Api\TenantSitemapController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SitemapMaintenanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tenantId;
    protected $action; // 'generate', 'update', 'regenerate_all'

    /**
     * Create a new job instance.
     */
    public function __construct(?int $tenantId = null, string $action = 'generate')
    {
        $this->tenantId = $tenantId;
        $this->action = $action;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            switch ($this->action) {
                case 'generate':
                    $this->handleGenerate();
                    break;
                case 'update':
                    $this->handleUpdate();
                    break;
                case 'regenerate_all':
                    $this->handleRegenerateAll();
                    break;
            }

            Log::info('SitemapMaintenanceJob executado com sucesso', [
                'tenant_id' => $this->tenantId,
                'action' => $this->action
            ]);

        } catch (\Exception $e) {
            Log::error('Erro no SitemapMaintenanceJob', [
                'tenant_id' => $this->tenantId,
                'action' => $this->action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle sitemap generation for specific tenant
     */
    private function handleGenerate(): void
    {
        if (!$this->tenantId) {
            Log::warning('SitemapMaintenanceJob: tenant_id não fornecido para geração');
            return;
        }

        $tenant = Tenant::find($this->tenantId);
        if (!$tenant) {
            Log::warning('SitemapMaintenanceJob: Tenant não encontrado', ['tenant_id' => $this->tenantId]);
            return;
        }

        // Verificar se existe configuração de sitemap
        $config = TenantSitemapConfig::where('tenant_id', $this->tenantId)->first();
        if (!$config) {
            Log::info('SitemapMaintenanceJob: Criando configuração padrão de sitemap', ['tenant_id' => $this->tenantId]);
            $config = TenantSitemapConfig::create([
                'tenant_id' => $this->tenantId,
                'is_active' => true,
                'include_vehicles' => true,
                'include_images' => true,
                'include_pages' => true,
                'vehicle_priority' => 0.8,
                'vehicle_changefreq' => 'daily',
                'image_priority' => 0.6,
                'image_changefreq' => 'weekly',
                'page_priority' => 0.5,
                'page_changefreq' => 'monthly',
                'max_urls_per_sitemap' => 50000,
                'last_generated_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Gerar sitemap usando o controller
        $controller = new TenantSitemapController();
        $request = new \Illuminate\Http\Request();
        $request->merge(['tenant_id' => $this->tenantId]);

        $controller->generateSitemap($request);

        Log::info('Sitemap gerado com sucesso', [
            'tenant_id' => $this->tenantId,
            'tenant_subdomain' => $tenant->subdomain
        ]);
    }

    /**
     * Handle sitemap update for specific tenant
     */
    private function handleUpdate(): void
    {
        if (!$this->tenantId) {
            Log::warning('SitemapMaintenanceJob: tenant_id não fornecido para atualização');
            return;
        }

        $tenant = Tenant::find($this->tenantId);
        if (!$tenant) {
            Log::warning('SitemapMaintenanceJob: Tenant não encontrado', ['tenant_id' => $this->tenantId]);
            return;
        }

        // Atualizar timestamp da última geração
        TenantSitemapConfig::where('tenant_id', $this->tenantId)
            ->update(['last_generated_at' => now()]);

        // Regenerar sitemap
        $this->handleGenerate();

        Log::info('Sitemap atualizado com sucesso', [
            'tenant_id' => $this->tenantId,
            'tenant_subdomain' => $tenant->subdomain
        ]);
    }

    /**
     * Handle regeneration of all sitemaps
     */
    private function handleRegenerateAll(): void
    {
        $tenants = Tenant::where('is_active', true)->get();

        Log::info('Iniciando regeneração de todos os sitemaps', ['total_tenants' => $tenants->count()]);

        foreach ($tenants as $tenant) {
            try {
                $this->tenantId = $tenant->id;
                $this->handleGenerate();

                Log::info('Sitemap regenerado para tenant', [
                    'tenant_id' => $tenant->id,
                    'tenant_subdomain' => $tenant->subdomain
                ]);

            } catch (\Exception $e) {
                Log::error('Erro ao regenerar sitemap para tenant', [
                    'tenant_id' => $tenant->id,
                    'tenant_subdomain' => $tenant->subdomain,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Regeneração de todos os sitemaps concluída', ['total_tenants' => $tenants->count()]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        $tags = ['sitemap-maintenance'];

        if ($this->tenantId) {
            $tags[] = 'tenant-' . $this->tenantId;
        }

        return $tags;
    }
}
