<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TenantSeoUrl extends Model
{
    use HasFactory;

    protected $table = 'tenant_seo_urls';

    protected $fillable = [
        'tenant_id',
        'locale',
        'path',
        'type',
        'canonical_url',
        'is_indexable',
        'include_in_sitemap',
        'sitemap_priority',
        'sitemap_changefreq',
        'lastmod',
        'title',
        'meta_description',
        'og_image',
        'breadcrumbs',
        'structured_data_type',
        'structured_data_payload',
        'content_data',
        'content_templates',
        'route_params',
        'extra_meta',
        // Campos de redirect
        'redirect_type',
        'redirect_target',
        'redirect_reason',
        'previous_slug',
        'redirect_date',
    ];

    protected $casts = [
        'is_indexable' => 'boolean',
        'include_in_sitemap' => 'boolean',
        'sitemap_priority' => 'decimal:1',
        'lastmod' => 'datetime',
        'breadcrumbs' => 'array',
        'structured_data_payload' => 'array',
        'content_data' => 'array',
        'content_templates' => 'array',
        'route_params' => 'array',
        'extra_meta' => 'array',
        // Campos de redirect
        'redirect_date' => 'datetime',
    ];

    /**
     * Relacionamento com Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope para buscar por tenant
     */
    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope para buscar por locale
     */
    public function scopeByLocale($query, $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope para buscar por tipo
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para URLs indexáveis
     */
    public function scopeIndexable($query)
    {
        return $query->where('is_indexable', true);
    }

    /**
     * Scope para URLs incluídas no sitemap
     */
    public function scopeForSitemap($query)
    {
        return $query->where('include_in_sitemap', true)
                    ->where('is_indexable', true)
                    ->where('redirect_type', 'none'); // URLs com redirect não entram no sitemap
    }

    /**
     * Scope para buscar por path
     */
    public function scopeByPath($query, $path)
    {
        return $query->where('path', $path);
    }

    /**
     * Resolver path completo com tenant e locale
     */
    public function scopeResolvePath($query, $tenantId, $locale, $path)
    {
        return $query->where('tenant_id', $tenantId)
                    ->where('locale', $locale)
                    ->where('path', $path);
    }

    /**
     * Processar spintax no conteúdo
     */
    public function processSpintax($content, $contentData = null)
    {
        if (!$content || !$this->content_templates) {
            return $content;
        }

        $templates = $this->content_templates;
        $data = $contentData ?? $this->content_data ?? [];

        // Processar cada template
        foreach ($templates as $key => $template) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $processed = $this->applySpintaxIndices($template, $data[$key]);
                $content = str_replace('{' . $key . '}', $processed, $content);
            }
        }

        return $content;
    }

    /**
     * Aplicar índices do spintax
     */
    private function applySpintaxIndices($template, $indices)
    {
        $parts = explode('|', $template);
        $result = '';

        foreach ($indices as $index) {
            if (isset($parts[$index])) {
                $result .= $parts[$index];
            }
        }

        return $result;
    }

    /**
     * Gerar dados estruturados JSON-LD
     */
    public function generateStructuredData()
    {
        if (!$this->structured_data_type || !$this->structured_data_payload) {
            return null;
        }

        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => $this->structured_data_type,
        ];

        return array_merge($structuredData, $this->structured_data_payload);
    }

    /**
     * Atualizar lastmod automaticamente
     */
    public function updateLastmod()
    {
        $this->update(['lastmod' => Carbon::now()]);
    }

    /**
     * Verificar se URL está atualizada
     */
    public function isStale($maxAge = 7)
    {
        if (!$this->lastmod) {
            return true;
        }

        return $this->lastmod->diffInDays(Carbon::now()) > $maxAge;
    }

    /**
     * Gerar breadcrumbs estruturados
     */
    public function generateBreadcrumbs()
    {
        if (!$this->breadcrumbs) {
            return [];
        }

        $structuredBreadcrumbs = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];

        foreach ($this->breadcrumbs as $index => $breadcrumb) {
            $structuredBreadcrumbs['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $breadcrumb['name'],
                'item' => $breadcrumb['item']
            ];
        }

        return $structuredBreadcrumbs;
    }

    /**
     * Scope para URLs com redirect
     */
    public function scopeWithRedirect($query)
    {
        return $query->where('redirect_type', '!=', 'none');
    }

    /**
     * Scope para URLs sem redirect
     */
    public function scopeWithoutRedirect($query)
    {
        return $query->where('redirect_type', 'none');
    }

    /**
     * Scope para buscar por tipo de redirect
     */
    public function scopeByRedirectType($query, $redirectType)
    {
        return $query->where('redirect_type', $redirectType);
    }

    /**
     * Verificar se URL tem redirect
     */
    public function hasRedirect()
    {
        return $this->redirect_type !== 'none';
    }

    /**
     * Obter status da URL baseado no redirect
     */
    public function getStatus()
    {
        if ($this->hasRedirect()) {
            return 'redirect_' . $this->redirect_type;
        }
        return 'active';
    }

    /**
     * Criar redirect para nova URL
     */
    public function createRedirect($targetUrl, $reason = 'slug_changed', $redirectType = '301')
    {
        $this->update([
            'redirect_type' => $redirectType,
            'redirect_target' => $targetUrl,
            'redirect_reason' => $reason,
            'previous_slug' => $this->path,
            'redirect_date' => Carbon::now(),
            'is_indexable' => false, // URLs com redirect não são indexáveis
            'include_in_sitemap' => false, // URLs com redirect não entram no sitemap
        ]);
    }

    /**
     * Resolver redirect - encontrar URL de destino
     */
    public function resolveRedirect()
    {
        if (!$this->hasRedirect()) {
            return null;
        }

        return [
            'redirect_type' => $this->redirect_type,
            'redirect_target' => $this->redirect_target,
            'redirect_reason' => $this->redirect_reason,
            'previous_slug' => $this->previous_slug,
            'redirect_date' => $this->redirect_date,
        ];
    }
}
