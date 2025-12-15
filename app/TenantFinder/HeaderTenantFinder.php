<?php

namespace App\TenantFinder;

use App\Models\Tenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;
use Spatie\Multitenancy\Contracts\IsTenant;
use Illuminate\Http\Request;

class HeaderTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        try {
            // Tentar obter pelo header
            $subdomain = $request->header('X-Tenant-Subdomain');

            if ($subdomain) {
                return Tenant::where('subdomain', $subdomain)->active()->first();
            }

            // Tentar obter pelo parâmetro da URL
            $subdomain = $request->get('tenant_subdomain');

            if ($subdomain) {
                return Tenant::where('subdomain', $subdomain)->active()->first();
            }

            return null;
        } catch (\Exception $e) {
            // Log do erro mas não quebra a aplicação
            \Log::error('Erro ao buscar tenant no HeaderTenantFinder', [
                'error' => $e->getMessage(),
                'subdomain' => $subdomain ?? null
            ]);

            return null;
        }
    }
}
