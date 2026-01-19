<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str; 

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'brand_id',
        'model_id',
        'vehicle_type',
        'condition',
        'title',
        'url',
        'version',
        'year',
        'model_year',
        'color',
        'fuel_type',
        'transmission',
        'doors',
        'mileage',
        'hide_mileage',
        'price',
        'classified_price',
        'cost_type',
        'fipe_price',
        'accept_financing',
        'accept_exchange',
        'engine',
        'power',
        'torque',
        'consumption_city',
        'consumption_highway',
        'description',
        'use_same_observation',
        'custom_observation',
        'classified_observations',
        'standard_features',
        'optional_features',
        'plate',
        'chassi',
        'renavam',
        'video_link',
        'owner_name',
        'owner_phone',
        'owner_email',
        'status',
        'is_featured',
        'is_licensed',
        'has_warranty',
        'is_adapted',
        'is_armored',
        'has_spare_key',
        'ipva_paid',
        'has_manual',
        'auction_history',
        'dealer_serviced',
        'single_owner',
        'is_active',
        'views',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'model_year' => 'integer',
        'doors' => 'integer',
        'mileage' => 'integer',
        'price' => 'decimal:2',
        'classified_price' => 'decimal:2',
        'fipe_price' => 'decimal:2',
        'accept_financing' => 'boolean',
        'accept_exchange' => 'boolean',
        'hide_mileage' => 'boolean',
        'use_same_observation' => 'boolean',
        'classified_observations' => 'array',
        'standard_features' => 'array',
        'optional_features' => 'array',
        'is_featured' => 'boolean',
        'is_licensed' => 'boolean',
        'has_warranty' => 'boolean',
        'is_adapted' => 'boolean',
        'is_armored' => 'boolean',
        'has_spare_key' => 'boolean',
        'ipva_paid' => 'boolean',
        'has_manual' => 'boolean',
        'auction_history' => 'boolean',
        'dealer_serviced' => 'boolean',
        'single_owner' => 'boolean',
        'is_active' => 'boolean',
        'views' => 'integer',
        'published_at' => 'datetime',
    ];

    /**
     * Relacionamentos
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class, 'brand_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'updated_by');
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(VehicleImage::class)->where('is_primary', true);
    }

    public function features(): HasMany
    {
        return $this->hasMany(VehicleFeature::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeByTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByBrand(Builder $query, int $brandId): Builder
    {
        return $query->where('brand_id', $brandId);
    }

    public function scopeByModel(Builder $query, int $modelId): Builder
    {
        return $query->where('model_id', $modelId);
    }

    public function scopeByPriceRange(Builder $query, float $minPrice, float $maxPrice): Builder
    {
        return $query->whereBetween('price', [$minPrice, $maxPrice]);
    }

    public function scopeByYearRange(Builder $query, int $minYear, int $maxYear): Builder
    {
        return $query->whereBetween('year', [$minYear, $maxYear]);
    }

    public function scopeByFuelType(Builder $query, string $fuelType): Builder
    {
        return $query->where('fuel_type', $fuelType);
    }

    public function scopeByTransmission(Builder $query, string $transmission): Builder
    {
        return $query->where('transmission', $transmission);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($query) use ($search) {
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('version', 'like', "%{$search}%")
                  ->orWhereHas('brand', function ($query) use ($search) {
                      $query->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('model', function ($query) use ($search) {
                      $query->where('name', 'like', "%{$search}%");
                  });
        });
    }

    /**
     * Métodos auxiliares
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function isFeatured(): bool
    {
        return $this->is_featured;
    }

    public function getFullTitle(): string
    {
        $title = $this->brand->name . ' ' . $this->model->name;
        if ($this->version) {
            $title .= ' ' . $this->version;
        }
        return $title . ' ' . $this->year;
    }

    public function getPrimaryImage(): ?VehicleImage
    {
        return $this->images()->where('is_primary', true)->first();
    }

    public function incrementViews(): void
    {
        $this->increment('views');
    }

    public function getFormattedPrice(): string
    {
        return 'R$ ' . number_format($this->price, 2, ',', '.');
    }

    public function getFormattedMileage(): string
    {
        return number_format($this->mileage, 0, ',', '.') . ' km';
    }

    /**
     * Métodos para gerenciamento de URLs
     */
    public function generateUrl(): string
    {
        return \App\Helpers\UrlHelper::generateUniqueUrl($this->title, $this->tenant_id, $this->id);
    }

    public function updateUrl(): void
    {
        $this->update(['url' => $this->generateUrl()]);
    }

    public function getUrlAttribute($value): string
    {
        if (!$value) {
            $value = $this->generateUrl();
            $this->update(['url' => $value]);
        }
        return $value;
    }

    public function scopeByUrl(Builder $query, string $url): Builder
    {
        return $query->where('url', $url);
    }

    public function scopeByUrlAndTenant(Builder $query, string $url, int $tenantId): Builder
    {
        return $query->where('url', $url)->where('tenant_id', $tenantId);
    }

    public function getSeoUrlAttribute(): string
    {
        $brand = $this->brand ? Str::slug($this->brand->name) : 'sem-marca';
        $model = $this->model ? Str::slug($this->model->name) : 'sem-modelo';
        $fuel = $this->fuel_type ? Str::slug($this->fuel_type) : 'sem-combustivel';
        $year = $this->year ?? 'sem-ano';
        
        return "/comprar/{$brand}/{$model}/{$fuel}-{$year}";
    }


}
