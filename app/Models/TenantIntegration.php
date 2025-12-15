<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class TenantIntegration extends Model
{
    use HasFactory;

    protected $table = 'tenant_integrations';

    protected $fillable = [
        'tenant_id',
        'integration_type',
        'name',
        'description',
        'config',
        'is_active',
        'is_required',
        'webhook_urls',
        'last_sync_at',
        'sync_status',
        'error_message',
    ];

    protected $casts = [
        'config' => 'array',
        'webhook_urls' => 'array',
        'sync_status' => 'array',
        'last_sync_at' => 'datetime',
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
     * Scope para integrações ativas
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para integrações por tipo
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('integration_type', $type);
    }

    /**
     * Scope para integrações obrigatórias
     */
    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_required', true);
    }

    /**
     * Verificar se a integração está ativa
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Verificar se a integração é obrigatória
     */
    public function isRequired(): bool
    {
        return $this->is_required;
    }

    /**
     * Verificar se a integração está sincronizada
     */
    public function isSynced(): bool
    {
        return $this->last_sync_at &&
               $this->last_sync_at->isAfter(now()->subHours(24));
    }

    /**
     * Verificar se há erro na integração
     */
    public function hasError(): bool
    {
        return !empty($this->error_message);
    }

    /**
     * Obter configuração específica
     */
    public function getConfig(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Definir configuração
     */
    public function setConfig(string $key, $value): bool
    {
        $config = $this->config ?? [];
        $config[$key] = $value;

        return $this->update(['config' => $config]);
    }

    /**
     * Obter URL de webhook específica
     */
    public function getWebhookUrl(string $type): ?string
    {
        return data_get($this->webhook_urls, $type);
    }

    /**
     * Definir URL de webhook
     */
    public function setWebhookUrl(string $type, string $url): bool
    {
        $webhooks = $this->webhook_urls ?? [];
        $webhooks[$type] = $url;

        return $this->update(['webhook_urls' => $webhooks]);
    }

    /**
     * Atualizar status de sincronização
     */
    public function updateSyncStatus(string $status, array $data = []): bool
    {
        $syncStatus = $this->sync_status ?? [];
        $syncStatus[$status] = array_merge(
            $data,
            ['updated_at' => now()->toISOString()]
        );

        return $this->update([
            'sync_status' => $syncStatus,
            'last_sync_at' => now(),
            'error_message' => null
        ]);
    }

    /**
     * Registrar erro na integração
     */
    public function recordError(string $error): bool
    {
        return $this->update([
            'error_message' => $error,
            'last_sync_at' => now()
        ]);
    }

    /**
     * Método para criar integração do Google Analytics
     */
    public static function createGoogleAnalytics(
        int $tenantId,
        string $trackingId,
        string $measurementId = null
    ): self {
        return self::create([
            'tenant_id' => $tenantId,
            'integration_type' => 'google_analytics',
            'name' => 'Google Analytics',
            'description' => 'Integração com Google Analytics para rastreamento de visitantes',
            'config' => [
                'tracking_id' => $trackingId,
                'measurement_id' => $measurementId,
                'enable_enhanced_ecommerce' => true,
                'enable_user_id_tracking' => false
            ],
            'is_active' => true,
            'is_required' => false
        ]);
    }

    /**
     * Método para criar integração do Facebook Pixel
     */
    public static function createFacebookPixel(
        int $tenantId,
        string $pixelId
    ): self {
        return self::create([
            'tenant_id' => $tenantId,
            'integration_type' => 'facebook_pixel',
            'name' => 'Facebook Pixel',
            'description' => 'Integração com Facebook Pixel para rastreamento de conversões',
            'config' => [
                'pixel_id' => $pixelId,
                'enable_standard_events' => true,
                'enable_custom_events' => true
            ],
            'is_active' => true,
            'is_required' => false
        ]);
    }

    /**
     * Método para criar integração do WhatsApp
     */
    public static function createWhatsApp(
        int $tenantId,
        string $phoneNumber,
        string $message = null
    ): self {
        return self::create([
            'tenant_id' => $tenantId,
            'integration_type' => 'whatsapp',
            'name' => 'WhatsApp Business',
            'description' => 'Integração com WhatsApp Business para atendimento ao cliente',
            'config' => [
                'phone_number' => $phoneNumber,
                'default_message' => $message ?? 'Olá! Gostaria de saber mais sobre este veículo.',
                'enable_auto_reply' => true,
                'business_hours' => [
                    'monday' => ['09:00', '18:00'],
                    'tuesday' => ['09:00', '18:00'],
                    'wednesday' => ['09:00', '18:00'],
                    'thursday' => ['09:00', '18:00'],
                    'friday' => ['09:00', '18:00'],
                    'saturday' => ['09:00', '12:00'],
                    'sunday' => []
                ]
            ],
            'is_active' => true,
            'is_required' => false
        ]);
    }

    /**
     * Método para criar integração de webhook
     */
    public static function createWebhook(
        int $tenantId,
        string $name,
        array $webhookUrls,
        array $config = []
    ): self {
        return self::create([
            'tenant_id' => $tenantId,
            'integration_type' => 'webhook',
            'name' => $name,
            'description' => 'Integração via webhook para sincronização de dados',
            'config' => array_merge([
                'retry_attempts' => 3,
                'timeout' => 30,
                'verify_ssl' => true
            ], $config),
            'webhook_urls' => $webhookUrls,
            'is_active' => true,
            'is_required' => false
        ]);
    }

    /**
     * Método para obter integrações ativas de um tenant
     */
    public static function getActiveIntegrations(int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return self::byTenant($tenantId)
            ->active()
            ->get();
    }

    /**
     * Método para verificar se um tenant tem uma integração específica
     */
    public static function hasIntegration(int $tenantId, string $type): bool
    {
        return self::byTenant($tenantId)
            ->byType($type)
            ->active()
            ->exists();
    }

    /**
     * Método para obter configuração de uma integração
     */
    public static function getIntegrationConfig(int $tenantId, string $type): ?array
    {
        $integration = self::byTenant($tenantId)
            ->byType($type)
            ->active()
            ->first();

        return $integration ? $integration->config : null;
    }
}
