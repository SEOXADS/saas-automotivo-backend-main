<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSitemapConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'url',
        'is_active',
        'priority',
        'change_frequency',
        'config_data'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'decimal:1',
        'config_data' => 'array'
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
     * Scope para filtrar por tipo
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para ordenar por prioridade
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('created_at', 'desc');
    }

    /**
     * Obter configuração específica por tipo
     */
    public function getConfigForType(): array
    {
        $config = $this->config_data ?? [];

        switch ($this->type) {
            case 'vehicles':
                return array_merge($config, [
                    'include_images' => $config['include_images'] ?? true,
                    'include_videos' => $config['include_videos'] ?? false,
                    'max_vehicles' => $config['max_vehicles'] ?? 1000,
                    'lastmod_field' => $config['lastmod_field'] ?? 'updated_at'
                ]);

            case 'images':
                return array_merge($config, [
                    'image_types' => $config['image_types'] ?? ['jpg', 'jpeg', 'png', 'webp'],
                    'max_images' => $config['max_images'] ?? 500,
                    'include_captions' => $config['include_captions'] ?? true
                ]);

            case 'videos':
                return array_merge($config, [
                    'video_types' => $config['video_types'] ?? ['mp4', 'webm'],
                    'max_videos' => $config['max_videos'] ?? 100,
                    'include_duration' => $config['include_duration'] ?? true
                ]);

            case 'articles':
                return array_merge($config, [
                    'include_content' => $config['include_content'] ?? false,
                    'max_articles' => $config['max_articles'] ?? 500,
                    'exclude_drafts' => $config['exclude_drafts'] ?? true
                ]);

            default:
                return $config;
        }
    }

    /**
     * Validar prioridade
     */
    public function setPriorityAttribute($value)
    {
        $this->attributes['priority'] = max(0.0, min(1.0, (float) $value));
    }

    /**
     * Obter dados para geração de sitemap
     */
    public function getSitemapData(): array
    {
        return [
            'loc' => $this->url,
            'lastmod' => $this->updated_at->toISOString(),
            'changefreq' => $this->change_frequency,
            'priority' => (string) $this->priority,
            'config' => $this->getConfigForType()
        ];
    }
}
