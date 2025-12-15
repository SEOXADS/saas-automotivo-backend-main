<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TenantIdentificationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // 1. Tentar identificar tenant por subdomínio
            $tenant = $this->identifyBySubdomain($request);

            // 2. Se não encontrou por subdomínio, tentar por domínio próprio
            if (!$tenant) {
                $tenant = $this->identifyByCustomDomain($request);
            }

            // 3. Se ainda não encontrou, tentar por header
            if (!$tenant) {
                $tenant = $this->identifyByHeader($request);
            }

            // 4. Se ainda não encontrou, usar tenant padrão
            if (!$tenant) {
                $tenant = $this->getDefaultTenant();
            }

            // 5. Se não há tenant padrão, criar um erro
            if (!$tenant) {
                Log::error('Nenhum tenant encontrado ou configurado', [
                    'host' => $request->getHost(),
                    'subdomain' => $this->extractSubdomain($request->getHost()),
                    'header_subdomain' => $request->header('X-Tenant-Subdomain')
                ]);

                return response()->json([
                    'error' => 'Configuração inválida',
                    'message' => 'Nenhum tenant configurado no sistema'
                ], 503);
            }

            // Verificar se o tenant está ativo
            if ($tenant && $tenant->status !== 'active') {
                return response()->json([
                    'error' => 'Tenant inativo',
                    'message' => 'Este tenant está temporariamente indisponível'
                ], 503);
            }

            // Armazenar tenant no request para uso posterior
            $request->attributes->set('current_tenant', $tenant);

            // Definir configurações do tenant
            $this->setTenantConfiguration($tenant);

            // Log para debug
            Log::info('Tenant identificado', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'subdomain' => $tenant->subdomain,
                'custom_domain' => $tenant->custom_domain,
                'request_url' => $request->fullUrl(),
                'user_agent' => $request->userAgent()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao identificar tenant', [
                'error' => $e->getMessage(),
                'request_url' => $request->fullUrl(),
                'trace' => $e->getTraceAsString()
            ]);

            // Para rotas públicas, usar tenant padrão
            if ($this->isPublicRoute($request)) {
                $tenant = $this->getDefaultTenant();
                $request->attributes->set('current_tenant', $tenant);
                $this->setTenantConfiguration($tenant);
            } else {
                return response()->json([
                    'error' => 'Erro interno',
                    'message' => 'Problema ao identificar tenant'
                ], 500);
            }
        }

        return $next($request);
    }

    /**
     * Identifica tenant por subdomínio
     */
    private function identifyBySubdomain(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $subdomain = $this->extractSubdomain($host);

        // Log para debug
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

            // Log para debug
            Log::info('Tenant encontrado por subdomínio', [
                'subdomain' => $subdomain,
                'tenant_found' => $tenant ? $tenant->id : null
            ]);

            return $tenant;
        }

        // Log para debug
        Log::info('Subdomínio não válido para identificação', [
            'host' => $host,
            'subdomain' => $subdomain
        ]);

        return null;
    }

    /**
     * Identifica tenant por domínio próprio
     */
    private function identifyByCustomDomain(Request $request): ?Tenant
    {
        $host = $request->getHost();

        // Normalizar o domínio (remover www se existir)
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

    /**
     * Verifica se é uma rota pública
     */
    private function isPublicRoute(Request $request): bool
    {
        $publicRoutes = [
            'api/public/*',
            'api/vehicles/*',
            'api/vehicle-brands/*',
            'api/vehicle-models/*',
            'api/portal/*',
            'api/health',
            'api/status'
        ];

        foreach ($publicRoutes as $route) {
            if ($request->is($route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtém tenant padrão
     */
    private function getDefaultTenant(): ?Tenant
    {
        return Cache::remember('default_tenant', 300, function () {
            return Tenant::where('is_default', true)
                ->where('status', 'active')
                ->first();
        });
    }

    /**
     * Define configurações do tenant
     */
    private function setTenantConfiguration(Tenant $tenant): void
    {
        // Configurar timezone
        if ($tenant->config && isset($tenant->config['timezone'])) {
            config(['app.timezone' => $tenant->config['timezone']]);
        }

        // Configurar locale
        if ($tenant->config && isset($tenant->config['locale'])) {
            app()->setLocale($tenant->config['locale']);
        }

        // Configurar outras configurações específicas do tenant
        if ($tenant->config) {
            config(['tenant' => $tenant->config]);
        }
    }
}
