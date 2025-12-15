<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\TenantUrlRedirect;
use App\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

class UrlRedirectMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Obter o tenant do subdomínio ou header
        $tenant = $this->getTenantFromRequest($request);

        if ($tenant) {
            $redirect = $this->findRedirect($request->path(), $tenant->id);

            if ($redirect) {
                // Marcar redirect como executado
                $redirect->markAsRedirected();

                // Retornar redirect
                return redirect($redirect->new_path, $redirect->status_code);
            }
        }

        return $next($request);
    }

    /**
     * Obter tenant da requisição
     */
    private function getTenantFromRequest(Request $request): ?Tenant
    {
        // Tentar obter do header X-Tenant-Subdomain
        $subdomain = $request->header('X-Tenant-Subdomain');

        if ($subdomain) {
            return Tenant::where('subdomain', $subdomain)->first();
        }

        // Tentar obter do subdomínio da URL
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];

        if ($subdomain && $subdomain !== 'www' && $subdomain !== 'localhost') {
            return Tenant::where('subdomain', $subdomain)->first();
        }

        return null;
    }

    /**
     * Encontrar redirect para o path
     */
    private function findRedirect(string $path, int $tenantId): ?TenantUrlRedirect
    {
        return TenantUrlRedirect::forTenant($tenantId)
            ->active()
            ->byOldPath($path)
            ->first();
    }
}
