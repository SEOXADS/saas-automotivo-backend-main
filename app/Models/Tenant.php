<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Models\Concerns\ImplementsTenant;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;

class Tenant extends Model implements IsTenant
{
    use HasFactory, ImplementsTenant, UsesMultitenancyConfig;

    protected $fillable = [
        'name',
        'subdomain',
        'custom_domain',
        'domain',
        'email',
        'phone',
        'logo',
        'description',
        'contact_email',
        'contact_phone',
        'address',
        'theme_color',
        'logo_url',
        'favicon_url',
        'banner_url',
        'social_media',
        'business_hours',
        'allow_registration',
        'require_approval',
        'is_default',
        'config',
        'status',
        'plan',
        'trial_ends_at',
        'subscription_ends_at',
        'features',
        'created_by',
    ];

    protected $casts = [
        'config' => 'array',
        'features' => 'array',
        'social_media' => 'array',
        'business_hours' => 'array',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    /**
     * Relacionamentos
     */
    public function users(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(TenantAnalytics::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(TenantNotification::class);
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(TenantIntegration::class);
    }

    public function portalCache(): HasMany
    {
        return $this->hasMany(PortalCache::class);
    }

    /**
     * Novos relacionamentos para configurações organizadas
     */
    public function profile()
    {
        return $this->hasOne(TenantProfile::class);
    }

    public function theme()
    {
        return $this->hasOne(TenantTheme::class);
    }

    public function seo()
    {
        return $this->hasOne(TenantSeo::class);
    }

    public function portalSettings()
    {
        return $this->hasOne(TenantPortalSettings::class);
    }

    public function configuration()
    {
        return $this->hasOne(TenantConfiguration::class);
    }

    /**
     * Usuário (super admin) que criou o tenant
     */
    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBySubdomain($query, $subdomain)
    {
        return $query->where('subdomain', $subdomain);
    }

    public function scopeByDomain($query, $domain)
    {
        return $query->where('domain', $domain);
    }

    /**
     * Métodos auxiliares
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    /**
     * Verificar se é o tenant padrão
     */
    public function isDefault(): bool
    {
        return $this->is_default === true;
    }

    /**
     * Verificar se permite registro de usuários
     */
    public function allowsRegistration(): bool
    {
        return $this->allow_registration === true;
    }

    /**
     * Verificar se requer aprovação para novos usuários
     */
    public function requiresApproval(): bool
    {
        return $this->require_approval === true;
    }

    /**
     * Obter cor do tema
     */
    public function getThemeColor(): string
    {
        return $this->theme?->primary_color ?? '#007bff';
    }

    /**
     * Obter configurações de redes sociais
     */
    public function getSocialMedia(): array
    {
        return $this->profile?->social_media ?? [];
    }

    /**
     * Obter horário de funcionamento
     */
    public function getBusinessHours(): array
    {
        return $this->profile?->business_hours ?? [];
    }

    /**
     * Obter configurações de contato
     */
    public function getContactInfo(): array
    {
        if ($this->profile) {
            return [
                'email' => $this->profile->company_email,
                'phone' => $this->profile->company_phone,
                'website' => $this->profile->company_website,
                'address' => $this->profile->getFullAddressAttribute(),
            ];
        }

        return [
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => null,
            'address' => null,
        ];
    }

    /**
     * Obter configurações do portal
     */
    public function getPortalConfig(): array
    {
        return [
            'theme' => $this->theme?->getFrontendConfig() ?? [],
            'profile' => $this->profile?->getContactInfo() ?? [],
            'seo' => $this->seo?->getFrontendConfig() ?? [],
            'portal_settings' => $this->portalSettings?->getFrontendConfig() ?? [],
            'social_media' => $this->getSocialMedia(),
            'business_hours' => $this->getBusinessHours(),
        ];
    }

    /**
     * Relacionamento com cidades através da tabela pivot
     */
    public function cities()
    {
        return $this->belongsToMany(City::class, 'tenant_cities', 'tenant_id', 'city_id')
                    ->withPivot(['is_active', 'created_at', 'updated_at'])
                    ->wherePivot('is_active', true);
    }

    /**
     * Relacionamento com bairros através da tabela pivot
     */
    public function neighborhoods()
    {
        return $this->belongsToMany(Neighborhood::class, 'tenant_neighborhoods', 'tenant_id', 'neighborhood_id')
                    ->withPivot(['is_active', 'created_at', 'updated_at'])
                    ->wherePivot('is_active', true);
    }

    public function getFullDomain(): string
    {
        return $this->domain ?: $this->subdomain . '.localhost';
    }

    /**
     * Sobrescrever o método do trait para retornar o banco atual
     */
    public function getDatabaseName(): string
    {
        return config('database.connections.' . config('database.default') . '.database');
    }
}
