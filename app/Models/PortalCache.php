<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache as LaravelCache;

class PortalCache extends Model
{
    use HasFactory;

    protected $table = 'portal_cache';

    protected $fillable = [
        'tenant_id',
        'cache_key',
        'cache_value',
        'cache_type',
        'expires_at',
        'tags',
        'hit_count',
        'last_accessed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Relacionamento com o tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope para filtrar por tenant
     */
    public function scopeByTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope para cache por tipo
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('cache_type', $type);
    }

    /**
     * Scope para cache válido (não expirado)
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope para cache expirado
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope para cache por tags
     */
    public function scopeByTags(Builder $query, array $tags): Builder
    {
        return $query->where(function ($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhere('tags', 'LIKE', "%{$tag}%");
            }
        });
    }

    /**
     * Verificar se o cache está expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Verificar se o cache é válido
     */
    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Incrementar contador de hits
     */
    public function incrementHits(): bool
    {
        return $this->update([
            'hit_count' => $this->hit_count + 1,
            'last_accessed_at' => now()
        ]);
    }

    /**
     * Obter valor do cache decodificado
     */
    public function getDecodedValue()
    {
        if ($this->cache_type === 'json') {
            return json_decode($this->cache_value, true);
        }

        return $this->cache_value;
    }

    /**
     * Definir valor do cache com codificação automática
     */
    public function setValue($value, string $type = 'data'): bool
    {
        if ($type === 'json' && is_array($value)) {
            $value = json_encode($value);
        }

        return $this->update([
            'cache_value' => $value,
            'cache_type' => $type
        ]);
    }

    /**
     * Método para obter cache do portal
     */
    public static function get(
        int $tenantId,
        string $key,
        $default = null
    ) {
        $cache = self::byTenant($tenantId)
            ->where('cache_key', $key)
            ->valid()
            ->first();

        if (!$cache) {
            return $default;
        }

        // Incrementar hits
        $cache->incrementHits();

        return $cache->getDecodedValue();
    }

    /**
     * Método para definir cache do portal
     */
    public static function set(
        int $tenantId,
        string $key,
        $value,
        string $type = 'data',
        int $ttl = 3600,
        array $tags = []
    ): bool {
        $expiresAt = $ttl > 0 ? now()->addSeconds($ttl) : null;

        return self::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'cache_key' => $key
            ],
            [
                'cache_value' => is_array($value) ? json_encode($value) : $value,
                'cache_type' => $type,
                'expires_at' => $expiresAt,
                'tags' => implode(',', $tags),
                'hit_count' => 0,
                'last_accessed_at' => now()
            ]
        )->exists;
    }

    /**
     * Método para remover cache do portal
     */
    public static function forget(int $tenantId, string $key): bool
    {
        return self::byTenant($tenantId)
            ->where('cache_key', $key)
            ->delete() > 0;
    }

    /**
     * Método para limpar cache por tags
     */
    public static function flushByTags(int $tenantId, array $tags): int
    {
        return self::byTenant($tenantId)
            ->byTags($tags)
            ->delete();
    }

    /**
     * Método para limpar todo o cache de um tenant
     */
    public static function flushTenant(int $tenantId): int
    {
        return self::byTenant($tenantId)->delete();
    }

    /**
     * Método para limpar cache expirado
     */
    public static function cleanExpired(): int
    {
        return self::expired()->delete();
    }

    /**
     * Método para cache de filtros do portal
     */
    public static function cacheFilters(
        int $tenantId,
        array $filters,
        int $ttl = 3600
    ): bool {
        return self::set(
            $tenantId,
            'portal_filters',
            $filters,
            'json',
            $ttl,
            ['filters', 'portal']
        );
    }

    /**
     * Método para cache de estatísticas do portal
     */
    public static function cacheStats(
        int $tenantId,
        array $stats,
        int $ttl = 1800
    ): bool {
        return self::set(
            $tenantId,
            'portal_stats',
            $stats,
            'json',
            $ttl,
            ['stats', 'portal']
        );
    }

    /**
     * Método para cache de configurações do tenant
     */
    public static function cacheTenantConfig(
        int $tenantId,
        array $config,
        int $ttl = 7200
    ): bool {
        return self::set(
            $tenantId,
            'tenant_config',
            $config,
            'json',
            $ttl,
            ['config', 'tenant']
        );
    }

    /**
     * Método para cache de veículos em destaque
     */
    public static function cacheFeaturedVehicles(
        int $tenantId,
        array $vehicles,
        int $ttl = 1800
    ): bool {
        return self::set(
            $tenantId,
            'featured_vehicles',
            $vehicles,
            'json',
            $ttl,
            ['vehicles', 'featured', 'portal']
        );
    }

    /**
     * Método para cache de marcas populares
     */
    public static function cachePopularBrands(
        int $tenantId,
        array $brands,
        int $ttl = 3600
    ): bool {
        return self::set(
            $tenantId,
            'popular_brands',
            $brands,
            'json',
            $ttl,
            ['brands', 'popular', 'portal']
        );
    }

    /**
     * Método para obter estatísticas de cache
     */
    public static function getCacheStats(int $tenantId): array
    {
        $total = self::byTenant($tenantId)->count();
        $valid = self::byTenant($tenantId)->valid()->count();
        $expired = self::byTenant($tenantId)->expired()->count();

        $totalHits = self::byTenant($tenantId)->sum('hit_count');
        $avgHits = $total > 0 ? round($totalHits / $total, 2) : 0;

        return [
            'total' => $total,
            'valid' => $valid,
            'expired' => $expired,
            'total_hits' => $totalHits,
            'average_hits' => $avgHits,
            'hit_rate' => $total > 0 ? round(($valid / $total) * 100, 2) : 0
        ];
    }

    /**
     * Método para sincronizar com cache do Laravel
     */
    public static function syncWithLaravelCache(int $tenantId, string $key): bool
    {
        $laravelCacheKey = "portal_{$tenantId}_{$key}";
        $value = LaravelCache::get($laravelCacheKey);

        if ($value !== null) {
            return self::set($tenantId, $key, $value, 'data', 3600);
        }

        return false;
    }
}
