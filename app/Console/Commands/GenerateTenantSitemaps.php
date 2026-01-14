<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TenantSitemapConfig;
use App\Http\Controllers\Api\TenantSitemapController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class GenerateTenantSitemaps extends Command
{
    protected $signature = 'sitemap:generate-tenants';
    protected $description = 'Checks all tenant sitemap configs and regenerates them if due';

    public function handle()
    {
        $this->info('Starting Sitemap Generation Check...');

        // 1. Check for Tenants without config and create default (Hourly)
        $this->ensureDefaultConfigsExist();

        // 2. Get all active configs
        $configs = TenantSitemapConfig::where('is_active', true)->get();

        foreach ($configs as $config) {
            if ($this->isDue($config)) {
                $this->info("Generating sitemap for Tenant {$config->tenant_id} ({$config->type})...");
                
                try {
                    // Instantiate Controller
                    $controller = app(TenantSitemapController::class);
                    
                    // Create a mock request
                    $request = new Request([
                        'type' => $config->type,
                        'force' => true // Force overwrite since it is due
                    ]);
                    
                    // Mock User/Tenant Context
                    $request->setUserResolver(function () use ($config) {
                        $user = new \stdClass();
                        $user->tenant_id = $config->tenant_id;
                        return $user;
                    });

                    // Call generation
                    $controller->generateSitemap($request);
                    
                    $this->info("Done.");
                } catch (\Exception $e) {
                    $this->error("Error generating for Tenant {$config->tenant_id}: " . $e->getMessage());
                }
            }
        }
        
        $this->info('Sitemap Check Complete.');
    }

    /**
     * Logic to check if sitemap needs regeneration based on frequency
     */
    protected function isDue($config)
    {
        // If set to 'always', run every time the script runs
        if ($config->change_frequency === 'always') return true;
        if ($config->change_frequency === 'never') return false;

        // Construct file path to check last modification
        $filename = $config->type === 'index' ? 'sitemap.xml' : "sitemap-{$config->type}.xml";
        $filePath = "sitemaps/tenant_{$config->tenant_id}/{$filename}";

        // If file doesn't exist, it is definitely due
        if (!Storage::disk('public')->exists($filePath)) {
            return true;
        }

        // Get last modified time
        $lastModifiedTimestamp = Storage::disk('public')->lastModified($filePath);
        $lastModified = Carbon::createFromTimestamp($lastModifiedTimestamp);
        $now = Carbon::now();

        switch ($config->change_frequency) {
            case 'hourly':
                return $now->diffInHours($lastModified) >= 1;
            case 'daily':
                return $now->diffInDays($lastModified) >= 1;
            case 'weekly':
                return $now->diffInWeeks($lastModified) >= 1;
            case 'monthly':
                return $now->diffInMonths($lastModified) >= 1;
            case 'yearly':
                return $now->diffInYears($lastModified) >= 1;
            default:
                return false;
        }
    }

    /**
     * Checks if tenants have a config, if not, creates a default hourly one.
     */
    protected function ensureDefaultConfigsExist()
    {
        $activeTenantIds = \App\Models\Vehicle::distinct()->pluck('tenant_id');

        foreach ($activeTenantIds as $tenantId) {
            $hasConfig = TenantSitemapConfig::where('tenant_id', $tenantId)->exists();

            if (!$hasConfig) {
                $this->info("Creating default hourly config for Tenant {$tenantId}...");
                
                TenantSitemapConfig::create([
                    'tenant_id' => $tenantId,
                    'name' => 'Sitemap Automático (Veículos)',
                    'type' => 'vehicles', // Default to vehicles
                    'url' => config('app.url') . "/sitemap-vehicles.xml", // Adjust URL logic as needed
                    'is_active' => true,
                    'priority' => 0.8,
                    'change_frequency' => 'hourly', // Default to Hourly as requested
                    'config_data' => ['include_images' => true]
                ]);
            }
        }
    }
}
