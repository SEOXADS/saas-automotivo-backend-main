<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantUrlRedirect extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'old_path',
        'new_path',
        'status_code',
        'is_active',
        'redirected_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'redirected_at' => 'datetime'
    ];

    /**
     * Relacionamento com o tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope para redirects ativos
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
     * Scope para buscar por path antigo
     */
    public function scopeByOldPath($query, string $oldPath)
    {
        return $query->where('old_path', $oldPath);
    }

    /**
     * Scope para buscar por path novo
     */
    public function scopeByNewPath($query, string $newPath)
    {
        return $query->where('new_path', $newPath);
    }

    /**
     * Scope para filtrar por código de status
     */
    public function scopeByStatusCode($query, int $statusCode)
    {
        return $query->where('status_code', $statusCode);
    }

    /**
     * Scope para ordenar por data de criação
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Marcar redirect como executado
     */
    public function markAsRedirected(): void
    {
        $this->update(['redirected_at' => now()]);
    }
}
