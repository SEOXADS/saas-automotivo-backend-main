<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseFallbackMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Testar conexão com o banco
            DB::connection()->getPdo();

            // Se chegou aqui, o banco está funcionando
            return $next($request);

        } catch (\Exception $e) {
            Log::error('Banco de dados indisponível', [
                'error' => $e->getMessage(),
                'url' => $request->fullUrl(),
                'method' => $request->method()
            ]);

            // Para rotas de API, retornar erro estruturado
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Serviço temporariamente indisponível',
                    'message' => 'O banco de dados está temporariamente indisponível. Tente novamente em alguns minutos.',
                    'code' => 'DATABASE_UNAVAILABLE'
                ], 503);
            }

            // Para outras rotas, retornar página de erro
            return response()->view('errors.503', [], 503);
        }
    }
}
