<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Lista de origens permitidas
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://localhost:5173',
            'http://localhost:8080',
            'https://saas-automotivo-client.vercel.app',
            'https://saas-automotivo-admin.vercel.app',
            'https://*.vercel.app',
        ];

        $origin = $request->headers->get('Origin');

        // Verificar se a origem está na lista de permitidas
        $isAllowedOrigin = false;
        foreach ($allowedOrigins as $allowedOrigin) {
            if ($allowedOrigin === '*' || $origin === $allowedOrigin) {
                $isAllowedOrigin = true;
                break;
            }
            // Verificar padrões wildcard
            if (str_contains($allowedOrigin, '*')) {
                $pattern = str_replace('*', '.*', $allowedOrigin);
                if (preg_match('/^' . preg_quote($pattern, '/') . '$/', $origin)) {
                    $isAllowedOrigin = true;
                    break;
                }
            }
        }

        $finalOrigin = $isAllowedOrigin ? $origin : '*';

        // Pré-flight CORS
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 204)
                ->header('Access-Control-Allow-Origin', $finalOrigin)
                ->header('Vary', 'Origin')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Requested-With, Accept, Origin, X-Tenant-Subdomain, X-CSRF-TOKEN, X-XSRF-TOKEN')
                ->header('Access-Control-Max-Age', '86400')
                ->header('Access-Control-Expose-Headers', 'X-Tenant-Subdomain, X-API-Version, X-Request-ID');
        }

        $response = $next($request);

        // Adicionar headers CORS para todas as respostas
        $response->headers->set('Access-Control-Allow-Origin', $finalOrigin);
        $response->headers->set('Vary', 'Origin');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Requested-With, Accept, Origin, X-Tenant-Subdomain, X-CSRF-TOKEN, X-XSRF-TOKEN');
        $response->headers->set('Access-Control-Max-Age', '86400');
        $response->headers->set('Access-Control-Expose-Headers', 'X-Tenant-Subdomain, X-API-Version, X-Request-ID');

        return $response;
    }
}
