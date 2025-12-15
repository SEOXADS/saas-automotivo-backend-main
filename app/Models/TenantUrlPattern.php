<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TenantUrlPattern extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'pattern',
        'urlable_type',
        'urlable_id',
        'generated_url',
        'is_primary',
        'is_active',
        'priority',
        'context_data'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'context_data' => 'array'
    ];

    /**
     * Relacionamento com o tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relacionamento polimórfico com o modelo associado
     */
    public function urlable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope para URLs ativas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para URLs primárias
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope para ordenar por prioridade
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('created_at', 'desc');
    }

    /**
     * Scope para filtrar por tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope para buscar por URL gerada
     */
    public function scopeByGeneratedUrl($query, string $url)
    {
        return $query->where('generated_url', $url);
    }

    /**
     * Scope para buscar por pattern
     */
    public function scopeByPattern($query, string $pattern)
    {
        return $query->where('pattern', $pattern);
    }
}
