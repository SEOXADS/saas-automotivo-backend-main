<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class VehicleBrand extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'slug',
        'logo',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Relacionamentos
     */
    public function models(): HasMany
    {
        return $this->hasMany(VehicleModel::class, 'brand_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'brand_id');
    }

    /**
     * Relacionamento com tenants através dos veículos
     */
    public function tenants()
    {
        return $this->hasManyThrough(Tenant::class, Vehicle::class, 'brand_id', 'id', 'id', 'tenant_id')
                    ->distinct();
    }

    /**
     * Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    /**
     * Métodos auxiliares
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getActiveModels()
    {
        return $this->models()->active()->ordered()->get();
    }

    public function getVehiclesCount(): int
    {
        return $this->vehicles()->active()->available()->count();
    }
}
