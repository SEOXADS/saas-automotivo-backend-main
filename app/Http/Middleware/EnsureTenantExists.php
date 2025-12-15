<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;

class EnsureTenantExists
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pegar tenant do header ou da request
        $tenant = $request->get('current_tenant');

        // Se não tem tenant na request, tentar pelo header
        if (!$tenant) {
            $subdomain = $request->header('X-Tenant-Subdomain');

            if (!$subdomain) {
                return response()->json([
                    'error' => 'Tenant não especificado',
                    'message' => 'Forneça o header X-Tenant-Subdomain ou faça login'
                ], 400);
            }

            $tenant = Tenant::bySubdomain($subdomain)->first();
        }

        // Verificar se tenant existe
        if (!$tenant) {
            return response()->json([
                'error' => 'Tenant não encontrado',
                'message' => 'O tenant especificado não existe'
            ], 404);
        }

        // Verificar se tenant está ativo
        if (!$tenant->isActive()) {
            return response()->json([
                'error' => 'Tenant inativo',
                'message' => 'A conta desta empresa está inativa'
            ], 403);
        }

        // Verificar se o tenant não está em trial vencido
        if ($tenant->isOnTrial() && $tenant->trial_ends_at->isPast()) {
            return response()->json([
                'error' => 'Trial expirado',
                'message' => 'O período de teste desta conta expirou'
            ], 402); // Payment Required
        }

        // Verificar se a assinatura não está vencida
        if ($tenant->subscription_ends_at && $tenant->subscription_ends_at->isPast()) {
            return response()->json([
                'error' => 'Assinatura vencida',
                'message' => 'A assinatura desta conta expirou'
            ], 402); // Payment Required
        }

        // Adicionar tenant na request se não existir
        if (!$request->has('current_tenant')) {
            $request->merge(['current_tenant' => $tenant]);
        }

        return $next($request);
    }
}
