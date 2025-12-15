<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'vehicle_id',
        'name',
        'email',
        'phone',
        'message',
        'source',
        'status',
        'utm_data',
        'ip_address',
        'user_agent',
        'contacted_at',
        'qualified_at',
        'closed_at',
        'assigned_to',
        'notes',
    ];

    protected $casts = [
        'utm_data' => 'array',
        'contacted_at' => 'datetime',
        'qualified_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Relacionamentos
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'assigned_to');
    }

    /**
     * Scopes
     */
    public function scopeByTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', 'new');
    }

    public function scopeContacted(Builder $query): Builder
    {
        return $query->where('status', 'contacted');
    }

    public function scopeQualified(Builder $query): Builder
    {
        return $query->where('status', 'qualified');
    }

    public function scopeClosedWon(Builder $query): Builder
    {
        return $query->where('status', 'closed_won');
    }

    public function scopeClosedLost(Builder $query): Builder
    {
        return $query->where('status', 'closed_lost');
    }

    /**
     * Métodos auxiliares
     */
    public function isNew(): bool
    {
        return $this->status === 'new';
    }

    public function isContacted(): bool
    {
        return $this->status === 'contacted';
    }

    public function isQualified(): bool
    {
        return $this->status === 'qualified';
    }

    public function isClosedWon(): bool
    {
        return $this->status === 'closed_won';
    }

    public function isClosedLost(): bool
    {
        return $this->status === 'closed_lost';
    }

    public function markAsContacted(): void
    {
        $this->update([
            'status' => 'contacted',
            'contacted_at' => now(),
        ]);
    }

    public function markAsQualified(): void
    {
        $this->update([
            'status' => 'qualified',
            'qualified_at' => now(),
        ]);
    }

    public function markAsClosedWon(): void
    {
        $this->update([
            'status' => 'closed_won',
            'closed_at' => now(),
        ]);
    }

    public function markAsClosedLost(): void
    {
        $this->update([
            'status' => 'closed_lost',
            'closed_at' => now(),
        ]);
    }

    public function getFormattedPhone(): string
    {
        $phone = preg_replace('/\D/', '', $this->phone);

        if (strlen($phone) === 11) {
            return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 5) . '-' . substr($phone, 7);
        }

        return $this->phone;
    }
}
