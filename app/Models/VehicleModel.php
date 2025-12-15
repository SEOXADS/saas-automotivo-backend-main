<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class VehicleModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'fipe_id',
        'fipe_code',
        'fipe_name',
        'name',
        'slug',
        'description',
        'category',
        'is_active',
        'is_fipe_synced',
        'fipe_synced_at',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_fipe_synced' => 'boolean',
        'fipe_synced_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    /**
     * Relacionamentos
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class, 'brand_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'model_id');
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

    public function scopeByBrand(Builder $query, int $brandId): Builder
    {
        return $query->where('brand_id', $brandId);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    public function scopeByFipeId(Builder $query, string $fipeId): Builder
    {
        return $query->where('fipe_id', $fipeId);
    }

    public function scopeByFipeCode(Builder $query, string $fipeCode): Builder
    {
        return $query->where('fipe_code', $fipeCode);
    }

    public function scopeFipeSynced(Builder $query): Builder
    {
        return $query->where('is_fipe_synced', true);
    }

    /**
     * Métodos auxiliares
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getVehiclesCount(): int
    {
        return $this->vehicles()->active()->available()->count();
    }

    public function getFullName(): string
    {
        return $this->brand->name . ' ' . $this->name;
    }
}
