<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\TenantSeoUrl;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GenerateSitemapsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sitemap:generate
                            {--tenant= : ID do tenant específico (opcional)}
                            {--type=all : Tipo de sitemap (index, vehicles, images, pages, all)}
                            {--limit=1000 : Limite de URLs por sitemap}';

    /**
     * The console command description.
     */
    protected $description = 'Gera sitemaps XML lendo URLs existentes da tabela tenant_seo_urls';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->option('tenant');
        $type = $this->option('type');
        $limit = (int) $this->option('limit');

        $this->info('🚀 Iniciando geração de sitemaps...');

        try {
            if ($tenantId) {
                $tenant = Tenant::find($tenantId);
                if (!$tenant) {
                    $this->error("❌ Tenant ID {$tenantId} não encontrado");
                    return 1;
                }
                $this->generateSitemapsForTenant($tenant, $type, $limit);
            } else {
                $tenants = Tenant::where('status', 'active')->get();
                foreach ($tenants as $tenant) {
                    $this->info("📋 Processando tenant: {$tenant->name}");
                    $this->generateSitemapsForTenant($tenant, $type, $limit);
                }
            }

            $this->info('✅ Geração de sitemaps concluída com sucesso!');
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erro ao gerar sitemaps: {$e->getMessage()}");
            Log::error('Erro ao gerar sitemaps', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Gera sitemaps para um tenant específico
     */
    private function generateSitemapsForTenant(Tenant $tenant, string $type, int $limit)
    {
        $baseUrl = $tenant->custom_domain ?: "https://{$tenant->subdomain}.localhost";
        $sitemapDir = "sitemaps/{$tenant->id}";

        // Criar diretório se não existir
        if (!Storage::exists($sitemapDir)) {
            Storage::makeDirectory($sitemapDir);
        }

        $sitemaps = [];

        if ($type === 'all' || $type === 'index') {
            $this->info("  📋 Gerando sitemap index...");
        }

        if ($type === 'all' || $type === 'vehicles') {
            $this->info("  🚗 Gerando sitemap de veículos...");
            $sitemaps[] = $this->generateVehicleSitemaps($tenant, $baseUrl, $sitemapDir, $limit);
        }

        if ($type === 'all' || $type === 'images') {
            $this->info("  🖼️ Gerando sitemap de imagens...");
            $sitemaps[] = $this->generateImageSitemaps($tenant, $baseUrl, $sitemapDir, $limit);
        }

        if ($type === 'all' || $type === 'pages') {
            $this->info("  📄 Gerando sitemap de páginas...");
            $sitemaps[] = $this->generatePageSitemaps($tenant, $baseUrl, $sitemapDir);
        }

        // Gerar sitemap index final se não foi gerado
        if ($type !== 'index') {
            $this->generateSitemapIndex($tenant, $baseUrl, $sitemapDir, $sitemaps);
        }

        $this->info("  ✅ Sitemaps gerados para tenant: {$tenant->name}");
    }

    /**
     * Gera sitemap index
     */
    private function generateSitemapIndex(Tenant $tenant, string $baseUrl, string $sitemapDir, array &$sitemaps = [])
    {
        if (empty($sitemaps)) {
            // Buscar sitemaps existentes
            $files = Storage::files($sitemapDir);
            $sitemaps = array_filter($files, function($file) {
                return str_contains($file, 'sitemap-') && str_ends_with($file, '.xml');
            });
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($sitemaps as $sitemap) {
            // Se $sitemap é um array, usar o primeiro elemento
            if (is_array($sitemap)) {
                $sitemap = $sitemap[0] ?? null;
            }

            if (!$sitemap) {
                continue;
            }

            $filename = basename($sitemap);
            $lastmod = date('Y-m-d\TH:i:s\Z');

            $xml .= '  <sitemap>' . "\n";
            $xml .= "    <loc>{$baseUrl}/storage/{$sitemap}</loc>\n";
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            $xml .= '  </sitemap>' . "\n";
        }

        $xml .= '</sitemapindex>';

        $filePath = "{$sitemapDir}/sitemap-index.xml";
        Storage::put($filePath, $xml);

        return $filePath;
    }

    /**
     * Gera sitemaps de veículos lendo URLs existentes
     */
    private function generateVehicleSitemaps(Tenant $tenant, string $baseUrl, string $sitemapDir, int $limit)
    {
        $urls = [];
        $sitemapCount = 1;

        // Buscar URLs de veículos existentes
        $vehicleUrls = TenantSeoUrl::where('tenant_id', $tenant->id)
            ->where('include_in_sitemap', true)
            ->where('type', 'vehicle_detail')
            ->orderBy('lastmod', 'desc')
            ->get();

        foreach ($vehicleUrls as $seoUrl) {
            $urls[] = $this->createUrlEntry(
                $baseUrl . '/' . $seoUrl->path,
                $seoUrl->lastmod,
                [
                    'priority' => $seoUrl->sitemap_priority,
                    'changefreq' => $seoUrl->sitemap_changefreq
                ]
            );

            // Se atingiu o limite, salvar e criar novo sitemap
            if (count($urls) >= $limit) {
                $this->saveSitemapFile($sitemapDir, "sitemap-vehicles-{$sitemapCount}.xml", $urls);
                $this->info("    💾 Salvo: sitemap-vehicles-{$sitemapCount}.xml (" . count($urls) . " URLs)");
                $urls = [];
                $sitemapCount++;
            }
        }

        // Salvar URLs restantes
        if (!empty($urls)) {
            $this->saveSitemapFile($sitemapDir, "sitemap-vehicles-{$sitemapCount}.xml", $urls);
            $this->info("    💾 Salvo: sitemap-vehicles-{$sitemapCount}.xml (" . count($urls) . " URLs)");
        }

        return ["sitemap-vehicles-{$sitemapCount}.xml"];
    }

    /**
     * Gera sitemaps de imagens lendo URLs existentes
     */
    private function generateImageSitemaps(Tenant $tenant, string $baseUrl, string $sitemapDir, int $limit)
    {
        $urls = [];
        $sitemapCount = 1;

        // Buscar URLs que podem ter imagens (veículos)
        $imageUrls = TenantSeoUrl::where('tenant_id', $tenant->id)
            ->where('include_in_sitemap', true)
            ->where('type', 'vehicle_detail')
            ->whereNotNull('og_image')
            ->orderBy('lastmod', 'desc')
            ->get();

        foreach ($imageUrls as $seoUrl) {
            if ($seoUrl->og_image) {
                $urls[] = $this->createImageUrlEntry(
                    $baseUrl . '/' . $seoUrl->path,
                    $seoUrl->og_image,
                    $seoUrl->lastmod
                );
            }

            // Se atingiu o limite, salvar e criar novo sitemap
            if (count($urls) >= $limit) {
                $this->saveSitemapFile($sitemapDir, "sitemap-images-{$sitemapCount}.xml", $urls);
                $this->info("    💾 Salvo: sitemap-images-{$sitemapCount}.xml (" . count($urls) . " URLs)");
                $urls = [];
                $sitemapCount++;
            }
        }

        // Salvar URLs restantes
        if (!empty($urls)) {
            $this->saveSitemapFile($sitemapDir, "sitemap-images-{$sitemapCount}.xml", $urls);
            $this->info("    💾 Salvo: sitemap-images-{$sitemapCount}.xml (" . count($urls) . " URLs)");
        }

        return ["sitemap-images-{$sitemapCount}.xml"];
    }

    /**
     * Gera sitemaps de páginas lendo URLs existentes
     */
    private function generatePageSitemaps(Tenant $tenant, string $baseUrl, string $sitemapDir)
    {
        $urls = [];

        // Buscar URLs de páginas (collection, blog_post, faq, static)
        $pageUrls = TenantSeoUrl::where('tenant_id', $tenant->id)
            ->where('include_in_sitemap', true)
            ->whereIn('type', ['collection', 'blog_post', 'faq', 'static'])
            ->orderBy('lastmod', 'desc')
            ->get();

        foreach ($pageUrls as $seoUrl) {
            $urls[] = $this->createUrlEntry(
                $baseUrl . '/' . $seoUrl->path,
                $seoUrl->lastmod,
                [
                    'priority' => $seoUrl->sitemap_priority,
                    'changefreq' => $seoUrl->sitemap_changefreq
                ]
            );
        }

        $this->saveSitemapFile($sitemapDir, "sitemap-pages.xml", $urls);
        $this->info("    💾 Salvo: sitemap-pages.xml (" . count($urls) . " URLs)");

        return ["sitemap-pages.xml"];
    }

    /**
     * Salva arquivo de sitemap
     */
    private function saveSitemapFile(string $sitemapDir, string $filename, array $urls)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= '  <url>' . "\n";
            $xml .= "    <loc>{$url['loc']}</loc>\n";
            $xml .= "    <lastmod>{$url['lastmod']}</lastmod>\n";

            if (isset($url['priority'])) {
                $xml .= "    <priority>{$url['priority']}</priority>\n";
            }

            if (isset($url['changefreq'])) {
                $xml .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
            }

            if (isset($url['image'])) {
                $xml .= "    <image:image>\n";
                $xml .= "      <image:loc>{$url['image']['loc']}</image:loc>\n";
                $xml .= "    </image:image>\n";
            }

            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';

        $filePath = "{$sitemapDir}/{$filename}";
        Storage::put($filePath, $xml);
    }

    /**
     * Cria entrada de URL para sitemap
     */
    private function createUrlEntry(string $url, $lastmod, array $options = [])
    {
        return [
            'loc' => $url,
            'lastmod' => $lastmod ? $lastmod->format('Y-m-d\TH:i:s\Z') : date('Y-m-d\TH:i:s\Z'),
            'priority' => $options['priority'] ?? '0.5',
            'changefreq' => $options['changefreq'] ?? 'weekly'
        ];
    }

    /**
     * Cria entrada de URL com imagem para sitemap
     */
    private function createImageUrlEntry(string $url, string $imageUrl, $lastmod)
    {
        return [
            'loc' => $url,
            'lastmod' => $lastmod ? $lastmod->format('Y-m-d\TH:i:s\Z') : date('Y-m-d\TH:i:s\Z'),
            'image' => [
                'loc' => $imageUrl
            ]
        ];
    }
}
