<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantRobotsConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'locale',
        'is_active',
        'user_agent_rules',
        'disallow_rules',
        'allow_rules',
        'crawl_delay',
        'sitemap_urls',
        'custom_rules',
        'host_directive',
        'include_sitemap_index',
        'include_sitemap_files',
        'notes',
        'last_generated_at',
        'last_generated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'user_agent_rules' => 'array',
        'disallow_rules' => 'array',
        'allow_rules' => 'array',
        'crawl_delay' => 'array',
        'sitemap_urls' => 'array',
        'include_sitemap_index' => 'boolean',
        'include_sitemap_files' => 'boolean',
        'last_generated_at' => 'datetime',
    ];

    /**
     * Relacionamento com o tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope para configurações ativas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para filtrar por tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope para filtrar por locale
     */
    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Gerar conteúdo do robots.txt
     */
    public function generateRobotsContent(): string
    {
        $content = '';

        // Host directive
        if ($this->host_directive) {
            $content .= "Host: {$this->host_directive}\n\n";
        }

        // User-agent rules
        $userAgentRules = $this->user_agent_rules ?? ['*' => []];

        foreach ($userAgentRules as $userAgent => $rules) {
            $content .= "User-agent: {$userAgent}\n";

            // Allow rules
            if (!empty($rules['allow'])) {
                foreach ($rules['allow'] as $path) {
                    $content .= "Allow: {$path}\n";
                }
            }

            // Disallow rules
            if (!empty($rules['disallow'])) {
                foreach ($rules['disallow'] as $path) {
                    $content .= "Disallow: {$path}\n";
                }
            }

            // Crawl delay
            if (!empty($rules['crawl_delay'])) {
                $content .= "Crawl-delay: {$rules['crawl_delay']}\n";
            }

            $content .= "\n";
        }

        // Custom rules
        if ($this->custom_rules) {
            $content .= $this->custom_rules . "\n\n";
        }

        // Sitemap URLs
        $sitemapUrls = $this->sitemap_urls ?? [];

        if ($this->include_sitemap_index) {
            $sitemapUrls[] = "https://{$this->tenant->subdomain}.localhost/sitemap-index.xml";
        }

        if ($this->include_sitemap_files) {
            $sitemapTypes = ['vehicle_detail', 'collection', 'blog_post', 'faq', 'static'];
            foreach ($sitemapTypes as $type) {
                $sitemapUrls[] = "https://{$this->tenant->subdomain}.localhost/sitemap-{$type}.xml";
            }
        }

        foreach ($sitemapUrls as $sitemapUrl) {
            $content .= "Sitemap: {$sitemapUrl}\n";
        }

        return $content;
    }

    /**
     * Validar configuração
     */
    public function validateConfig(): array
    {
        $errors = [];

        if (empty($this->sitemap)) {
            $errors[] = 'Pelo menos um sitemap deve ser especificado';
        }

        if ($this->crawl_delay && $this->crawl_delay < 0) {
            $errors[] = 'Crawl delay deve ser um número positivo';
        }

        // Validar URLs de sitemap
        if (!empty($this->sitemap)) {
            foreach ($this->sitemap as $url) {
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    $errors[] = "URL de sitemap inválida: {$url}";
                }
            }
        }

        return $errors;
    }

    /**
     * Obter configuração padrão para tenant
     */
    public static function getDefaultConfig(int $tenantId, string $locale = 'pt-BR'): array
    {
        return [
            'tenant_id' => $tenantId,
            'locale' => $locale,
            'is_active' => true,
            'user_agent_rules' => [
                '*' => [
                    'allow' => ['/'],
                    'disallow' => ['/admin/', '/private/', '/temp/', '/api/'],
                    'crawl_delay' => 1
                ]
            ],
            'sitemap_urls' => [],
            'include_sitemap_index' => true,
            'include_sitemap_files' => true,
            'custom_rules' => null,
            'host_directive' => null,
            'notes' => 'Configuração padrão gerada automaticamente'
        ];
    }
}
