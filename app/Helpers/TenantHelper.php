<?php

namespace App\Helpers;

use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class TenantHelper
{
    /**
     * Obter o usuário autenticado atual
     */
    public static function currentUser(): ?TenantUser
    {
        try {
            return JWTAuth::user();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obter o tenant atual
     */
    public static function currentTenant(): ?Tenant
    {
        $user = self::currentUser();

        if ($user) {
            return $user->tenant;
        }

        // Tentar obter tenant da request
        $request = request();

        if ($request->has('current_tenant')) {
            return $request->get('current_tenant');
        }

        // Tentar obter pelo header
        $subdomain = $request->header('X-Tenant-Subdomain');

        if ($subdomain) {
            return Tenant::bySubdomain($subdomain)->active()->first();
        }

        // Tentar obter pelo parâmetro da URL
        $subdomain = $request->get('tenant_subdomain');

        if ($subdomain) {
            return Tenant::bySubdomain($subdomain)->active()->first();
        }

        return null;
    }

    /**
     * Obter ID do tenant atual
     */
    public static function currentTenantId(): ?int
    {
        $tenant = self::currentTenant();
        return $tenant ? $tenant->id : null;
    }

    /**
     * Verificar se usuário tem role específico
     */
    public static function hasRole(string $role): bool
    {
        $user = self::currentUser();
        return $user && $user->role === $role;
    }

    /**
     * Verificar se usuário tem algum dos roles
     */
    public static function hasAnyRole(array $roles): bool
    {
        $user = self::currentUser();
        return $user && in_array($user->role, $roles);
    }

    /**
     * Verificar se usuário é admin
     */
    public static function isAdmin(): bool
    {
        return self::hasRole('admin');
    }

    /**
     * Verificar se usuário é manager
     */
    public static function isManager(): bool
    {
        return self::hasRole('manager');
    }

    /**
     * Verificar se usuário é salesperson
     */
    public static function isSalesperson(): bool
    {
        return self::hasRole('salesperson');
    }

    /**
     * Verificar se usuário é admin ou manager
     */
    public static function canManage(): bool
    {
        return self::hasAnyRole(['admin', 'manager']);
    }

    /**
     * Verificar se tenant está ativo
     */
    public static function isTenantActive(): bool
    {
        $tenant = self::currentTenant();
        return $tenant && $tenant->isActive();
    }

    /**
     * Verificar se tenant tem uma feature específica
     */
    public static function hasFeature(string $feature): bool
    {
        $tenant = self::currentTenant();
        return $tenant && $tenant->hasFeature($feature);
    }

    /**
     * Obter configuração do tenant
     */
    public static function getTenantConfig(string $key, $default = null)
    {
        $tenant = self::currentTenant();

        if (!$tenant || !$tenant->config) {
            return $default;
        }

        return data_get($tenant->config, $key, $default);
    }

    /**
     * Definir configuração do tenant
     */
    public static function setTenantConfig(string $key, $value): bool
    {
        $tenant = self::currentTenant();

        if (!$tenant) {
            return false;
        }

        $config = $tenant->config ?? [];
        data_set($config, $key, $value);

        return $tenant->update(['config' => $config]);
    }
}
