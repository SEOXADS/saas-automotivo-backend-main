<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TenantUrl extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'path',
        'urlable_type',
        'urlable_id',
        'data',
        'city_id',
        'district_id'
    ];

    protected $casts = [
        'data' => 'array'
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
     * Relacionamento com cidade
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Relacionamento com bairro/distrito
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(Neighborhood::class, 'district_id');
    }

    /**
     * Scope para filtrar por tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope para buscar por path
     */
    public function scopeByPath($query, string $path)
    {
        return $query->where('path', $path);
    }

    /**
     * Scope para filtrar por cidade
     */
    public function scopeByCity($query, int $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    /**
     * Scope para filtrar por bairro
     */
    public function scopeByDistrict($query, int $districtId)
    {
        return $query->where('district_id', $districtId);
    }

    /**
     * Scope para ordenar por data de criação
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
