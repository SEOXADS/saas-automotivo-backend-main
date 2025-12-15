<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'phone',
        'avatar',
        'role',
        'permissions',
        'is_active',
        'last_login_at',
        'settings',
        'preferences',
        'password_reset_token',
        'password_reset_expires_at',
        'password_reset_requested_at',
        'tenant_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'settings' => 'array',
            'preferences' => 'array',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password_reset_expires_at' => 'datetime',
            'password_reset_requested_at' => 'datetime',
        ];
    }



    /**
     * Relacionamentos
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'created_by');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeTenantUsers($query)
    {
        return $query->whereNotNull('tenant_id');
    }



    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeSuperAdmins($query)
    {
        return $query->where('role', 'super_admin')->whereNull('tenant_id');
    }

    /**
     * Métodos utilitários
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin' && is_null($this->tenant_id);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin']) && is_null($this->tenant_id);
    }

    public function isTenantUser(): bool
    {
        return !is_null($this->tenant_id);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Permissões do Super Admin
     */
    public static function getSuperAdminPermissions(): array
    {
        return [
            'manage_all_tenants',
            'create_tenants',
            'delete_tenants',
            'manage_tenant_users',
            'view_system_analytics',
            'manage_system_settings',
            'manage_billing',
            'manage_subscriptions',
            'view_logs',
            'manage_super_admins',
            'system_maintenance',
            'api_management',
        ];
    }

    /**
     * Métodos para Reset de Senha
     */
    public function generatePasswordResetToken(): string
    {
        $token = \Illuminate\Support\Str::random(64);
        $this->update([
            'password_reset_token' => $token,
            'password_reset_expires_at' => now()->addHours(24),
            'password_reset_requested_at' => now(),
        ]);
        return $token;
    }

    public function clearPasswordResetToken(): void
    {
        $this->update([
            'password_reset_token' => null,
            'password_reset_expires_at' => null,
            'password_reset_requested_at' => null,
        ]);
    }

    public function isPasswordResetTokenValid(): bool
    {
        return $this->password_reset_token &&
               $this->password_reset_expires_at &&
               $this->password_reset_expires_at->isFuture();
    }

    public function scopeByPasswordResetToken($query, $token)
    {
        return $query->where('password_reset_token', $token);
    }
}
