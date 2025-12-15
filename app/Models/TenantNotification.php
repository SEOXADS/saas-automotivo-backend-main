<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class TenantNotification extends Model
{
    use HasFactory;

    protected $table = 'tenant_notifications';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'priority',
        'status',
        'read_at',
        'sent_at',
        'delivery_channels',
    ];

    protected $casts = [
        'data' => 'array',
        'delivery_channels' => 'array',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Relacionamento com o tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relacionamento com o usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'user_id');
    }

    /**
     * Scope para filtrar por tenant
     */
    public function scopeByTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope para filtrar por usuário
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para notificações não lidas
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('status', 'unread');
    }

    /**
     * Scope para notificações lidas
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->where('status', 'read');
    }

    /**
     * Scope para notificações por tipo
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para notificações por prioridade
     */
    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope para notificações recentes
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Marcar como lida
     */
    public function markAsRead(): bool
    {
        return $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
    }

    /**
     * Marcar como arquivada
     */
    public function markAsArchived(): bool
    {
        return $this->update(['status' => 'archived']);
    }

    /**
     * Verificar se foi lida
     */
    public function isRead(): bool
    {
        return $this->status === 'read';
    }

    /**
     * Verificar se foi arquivada
     */
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    /**
     * Verificar se é de alta prioridade
     */
    public function isHighPriority(): bool
    {
        return in_array($this->priority, ['high', 'urgent']);
    }

    /**
     * Método para criar notificação de lead
     */
    public static function createLeadNotification(
        int $tenantId,
        int $leadId,
        string $leadName,
        int $userId = null
    ): self {
        return self::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => 'lead_created',
            'title' => 'Novo Lead Recebido',
            'message' => "Novo lead recebido de {$leadName}",
            'data' => [
                'lead_id' => $leadId,
                'lead_name' => $leadName,
                'action_url' => "/leads/{$leadId}"
            ],
            'priority' => 'high',
            'status' => 'unread',
            'delivery_channels' => ['email', 'in_app']
        ]);
    }

    /**
     * Método para criar notificação de veículo visualizado
     */
    public static function createVehicleViewedNotification(
        int $tenantId,
        int $vehicleId,
        string $vehicleTitle,
        int $userId = null
    ): self {
        return self::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => 'vehicle_viewed',
            'title' => 'Veículo Visualizado',
            'message' => "O veículo '{$vehicleTitle}' foi visualizado",
            'data' => [
                'vehicle_id' => $vehicleId,
                'vehicle_title' => $vehicleTitle,
                'action_url' => "/vehicles/{$vehicleId}"
            ],
            'priority' => 'normal',
            'status' => 'unread',
            'delivery_channels' => ['in_app']
        ]);
    }

    /**
     * Método para criar notificação de sistema
     */
    public static function createSystemNotification(
        int $tenantId,
        string $title,
        string $message,
        string $priority = 'normal',
        array $data = [],
        int $userId = null
    ): self {
        return self::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => 'system_alert',
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'priority' => $priority,
            'status' => 'unread',
            'delivery_channels' => ['email', 'in_app']
        ]);
    }

    /**
     * Método para criar notificação de assinatura
     */
    public static function createSubscriptionNotification(
        int $tenantId,
        string $type, // expiring, expired, renewed
        string $message,
        int $userId = null
    ): self {
        $priority = $type === 'expired' ? 'urgent' : 'high';

        return self::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => 'subscription',
            'title' => 'Notificação de Assinatura',
            'message' => $message,
            'data' => [
                'subscription_type' => $type,
                'action_url' => '/subscription'
            ],
            'priority' => $priority,
            'status' => 'unread',
            'delivery_channels' => ['email', 'in_app']
        ]);
    }

    /**
     * Método para obter contagem de notificações não lidas
     */
    public static function getUnreadCount(int $tenantId, int $userId = null): int
    {
        $query = self::byTenant($tenantId)->unread();

        if ($userId) {
            $query->byUser($userId);
        }

        return $query->count();
    }

    /**
     * Método para limpar notificações antigas
     */
    public static function cleanOldNotifications(int $daysToKeep = 90): int
    {
        return self::where('created_at', '<', now()->subDays($daysToKeep))
            ->where('status', '!=', 'unread')
            ->delete();
    }
}
