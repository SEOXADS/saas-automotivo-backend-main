<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Helpers\TokenHelper;

class TenantAutoIdentificationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // 1. Identificar tenant automaticamente pelo subdomínio/custom_domain
            $tenant = $this->identifyTenant($request);

            if (!$tenant) {
                return response()->json([
                    'error' => 'Tenant não encontrado',
                    'message' => 'Não foi possível identificar o tenant pelo subdomínio ou domínio'
                ], 404);
            }

            // 2. Verificar se o tenant está ativo
            if ($tenant->status !== 'active') {
                return response()->json([
                    'error' => 'Tenant inativo',
                    'message' => 'Este tenant está temporariamente indisponível'
                ], 503);
            }

            // 3. Tentar autenticar usuário (opcional)
            $user = null;
            $token = $request->bearerToken();

            if ($token) {
                $user = TokenHelper::getAuthenticatedUser($request);

                // Se encontrou usuário, verificar se pertence ao tenant correto
                if ($user && method_exists($user, 'getTable') && $user->getTable() === 'tenant_users') {
                    if ($user->tenant_id !== $tenant->id) {
                        return response()->json([
                            'error' => 'Acesso negado',
                            'message' => 'Usuário não pertence a este tenant'
                        ], 403);
                    }
                }
            }

            // 4. Armazenar tenant e usuário no request
            $request->attributes->set('current_tenant', $tenant);
            $request->attributes->set('current_user', $user);
            $request->merge([
                'current_tenant' => $tenant,
                'current_user' => $user
            ]);

            // 5. Log para debug
            Log::info('Tenant identificado automaticamente', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'subdomain' => $tenant->subdomain,
                'custom_domain' => $tenant->custom_domain,
                'request_url' => $request->fullUrl(),
                'user_authenticated' => $user ? $user->id : null,
                'user_role' => $user ? $user->role : null
            ]);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('Erro ao identificar tenant automaticamente', [
                'error' => $e->getMessage(),
                'request_url' => $request->fullUrl(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Erro interno',
                'message' => 'Problema ao identificar tenant'
            ], 500);
        }
    }

    /**
     * Identifica tenant por subdomínio ou custom_domain
     */
/*    private function identifyTenant(Request $request): ?Tenant
    {
        // 1. Tentar identificar por subdomínio
        $tenant = $this->identifyBySubdomain($request);

        // 2. Se não encontrou por subdomínio, tentar por domínio próprio
        if (!$tenant) {
            $tenant = $this->identifyByCustomDomain($request);
        }

        // 3. Se ainda não encontrou, tentar por header
        if (!$tenant) {
            $tenant = $this->identifyByHeader($request);
        }

        return $tenant;
    }*/

private function identifyTenant(Request $request): ?Tenant
{
    // 1. FIRST - Try to get tenant from authenticated user's token
    $tenant = $this->identifyByAuthenticatedUser($request);
    
    // 2. If not found, try by subdomain
    if (!$tenant) {
        $tenant = $this->identifyBySubdomain($request);
    }

    // 3. If not found, try by custom domain
    if (!$tenant) {
        $tenant = $this->identifyByCustomDomain($request);
    }

    // 4. If not found, try by header
    if (!$tenant) {
        $tenant = $this->identifyByHeader($request);
    }
    
    // 5. NEW - Try by Origin/Referer header
    if (!$tenant) {
        $tenant = $this->identifyByOrigin($request);
    }

    return $tenant;
}

/**
 * NEW: Identifica tenant pelo usuário autenticado via token
 */
private function identifyByAuthenticatedUser(Request $request): ?Tenant
{
    $token = $request->bearerToken();
    
    if (!$token) {
        return null;
    }
    
    try {
        $user = TokenHelper::getAuthenticatedUser($request);
        
        if ($user && isset($user->tenant_id) && $user->tenant_id) {
            $tenant = Cache::remember("tenant_user_{$user->tenant_id}", 300, function () use ($user) {
                return Tenant::where('id', $user->tenant_id)
                    ->where('status', 'active')
                    ->first();
            });
            
            if ($tenant) {
                Log::info('Tenant identificado via token do usuário', [
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'user_tenant_id' => $user->tenant_id
                ]);
                return $tenant;
            }
        }
    } catch (\Exception $e) {
        Log::debug('Não foi possível identificar tenant via token', [
            'error' => $e->getMessage()
        ]);
    }
    
    return null;
}

/**
 * NEW: Identifica tenant pela Origin ou Referer header
 */
private function identifyByOrigin(Request $request): ?Tenant
{
    $origin = $request->header('Origin') ?? $request->header('Referer');
    
    if (!$origin) {
        return null;
    }
    
    try {
        $parsedUrl = parse_url($origin);
        $host = $parsedUrl['host'] ?? null;
        
        if (!$host) {
            return null;
        }
        
        // Normalize host (remove www if present)
        $normalizedHost = $this->normalizeDomain($host);
        
        // Extract subdomain from origin host
        $parts = explode('.', $normalizedHost);
        
        // Check if first part is a potential subdomain (e.g., "admin" from admin.omegaveiculos.com.br)
        if (count($parts) >= 3) {
            $potentialSubdomain = $parts[0];
            
            // Skip common non-tenant subdomains
            if (!in_array($potentialSubdomain, ['www', 'api', 'mail', 'ftp', 'admin'])) {
                $tenant = Cache::remember("tenant_origin_subdomain_{$potentialSubdomain}", 300, function () use ($potentialSubdomain) {
                    return Tenant::where('subdomain', $potentialSubdomain)
                        ->where('status', 'active')
                        ->first();
                });
                
                if ($tenant) {
                    Log::info('Tenant identificado via subdomain da Origin', [
                        'origin' => $origin,
                        'subdomain' => $potentialSubdomain,
                        'tenant_id' => $tenant->id
                    ]);
                    return $tenant;
                }
            }
        }
        
        // Try to find by base domain (e.g., omegaveiculos.com.br)
        // Build base domain from parts (last 3 parts for .com.br domains, last 2 for others)
        $baseDomain = $normalizedHost;
        if (count($parts) >= 3) {
            // For domains like admin.omegaveiculos.com.br -> omegaveiculos.com.br
            $baseDomain = implode('.', array_slice($parts, -3));
        }
        
        $tenant = Cache::remember("tenant_origin_domain_{$baseDomain}", 300, function () use ($baseDomain, $normalizedHost) {
            // Try exact match first
            $tenant = Tenant::where('custom_domain', 'LIKE', '%' . $baseDomain . '%')
                ->where('status', 'active')
                ->first();
            
            // If not found, try with the full normalized host
            if (!$tenant) {
                $tenant = Tenant::where('custom_domain', 'LIKE', '%' . $normalizedHost . '%')
                    ->where('status', 'active')
                    ->first();
            }
            
            // Also try finding by subdomain extracted from domain
            if (!$tenant) {
                $parts = explode('.', $baseDomain);
                if (count($parts) >= 3) {
                    $possibleSubdomain = $parts[0];
                    $tenant = Tenant::where('subdomain', $possibleSubdomain)
                        ->where('status', 'active')
                        ->first();
                }
            }
            
            return $tenant;
        });
        
        if ($tenant) {
            Log::info('Tenant identificado via custom_domain da Origin', [
                'origin' => $origin,
                'base_domain' => $baseDomain,
                'tenant_id' => $tenant->id
            ]);
            return $tenant;
        }
        
    } catch (\Exception $e) {
        Log::debug('Erro ao identificar tenant via Origin', [
            'origin' => $origin,
            'error' => $e->getMessage()
        ]);
    }
    
    return null;
}


    /**
     * Identifica tenant por subdomínio
     */
    private function identifyBySubdomain(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $subdomain = $this->extractSubdomain($host);

        Log::info('Tentando identificar tenant por subdomínio', [
            'host' => $host,
            'extracted_subdomain' => $subdomain,
            'parts' => explode('.', $host)
        ]);

        if ($subdomain && $subdomain !== 'www' && $subdomain !== 'api') {
            $tenant = Cache::remember("tenant_subdomain_{$subdomain}", 300, function () use ($subdomain) {
                return Tenant::where('subdomain', $subdomain)
                    ->where('status', 'active')
                    ->first();
            });

            Log::info('Tenant encontrado por subdomínio', [
                'subdomain' => $subdomain,
                'tenant_found' => $tenant ? $tenant->id : null
            ]);

            return $tenant;
        }

        return null;
    }

    /**
     * Identifica tenant por domínio próprio
     */
    private function identifyByCustomDomain(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $normalizedHost = $this->normalizeDomain($host);

        return Cache::remember("tenant_domain_{$normalizedHost}", 300, function () use ($normalizedHost) {
            // Tentar encontrar por domínio normalizado primeiro
            $tenant = Tenant::where('custom_domain', $normalizedHost)
                ->where('status', 'active')
                ->first();

            // Se não encontrou, tentar com www
            if (!$tenant) {
                $tenant = Tenant::where('custom_domain', 'www.' . $normalizedHost)
                    ->where('status', 'active')
                    ->first();
            }

            // Se não encontrou, tentar sem www
            if (!$tenant && !str_starts_with($normalizedHost, 'www.')) {
                $tenant = Tenant::where('custom_domain', str_replace('www.', '', $normalizedHost))
                    ->where('status', 'active')
                    ->first();
            }

            return $tenant;
        });
    }

    /**
     * Identifica tenant por header
     */
    private function identifyByHeader(Request $request): ?Tenant
    {
        $subdomain = $request->header('X-Tenant-Subdomain');

        if ($subdomain) {
            return Cache::remember("tenant_header_{$subdomain}", 300, function () use ($subdomain) {
                return Tenant::where('subdomain', $subdomain)
                    ->where('status', 'active')
                    ->first();
            });
        }

        return null;
    }

    /**
     * Extrai subdomínio do host
     */
    private function extractSubdomain(string $host): ?string
    {
        // Normalizar o host removendo www se existir
        $normalizedHost = $this->normalizeDomain($host);
        $parts = explode('.', $normalizedHost);

        // Para localhost e desenvolvimento, aceitar 2 partes (demo.localhost)
        if (count($parts) >= 2) {
            $firstPart = $parts[0];
            $secondPart = $parts[1];

            // Se a segunda parte é localhost, 127.0.0.1, ou similar, aceitar
            if (in_array($secondPart, ['localhost', '127', 'local', 'test', 'dev'])) {
                return $firstPart;
            }

            // Para produção, exigir 3 ou mais partes
            if (count($parts) >= 3) {
                return $firstPart;
            }
        }

        return null;
    }

    /**
     * Normaliza um domínio removendo www se existir
     */
    private function normalizeDomain(string $domain): string
    {
        // Remover www do início se existir
        if (str_starts_with($domain, 'www.')) {
            return substr($domain, 4);
        }

        return $domain;
    }
}
