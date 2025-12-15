<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Services\FipeService;

class FipeRateLimitMiddleware
{
    protected $fipeService;

    public function __construct(FipeService $fipeService)
    {
        $this->fipeService = $fipeService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Verificar se ainda há chamadas disponíveis
        if (!$this->fipeService->hasAvailableCalls()) {
            return response()->json([
                'error' => 'Limite diário de consultas à API FIPE atingido',
                'message' => 'O limite de 500 consultas por dia foi atingido. Tente novamente amanhã.',
                'rate_limit' => config('services.fipe.rate_limit_per_day'),
                'reset_time' => now()->addDay()->startOfDay()->toISOString()
            ], 429);
        }

        // Verificar rate limit por IP (máximo 100 consultas por hora por IP)
        $ip = $request->ip();
        $hourlyKey = "fipe_rate_limit_ip_{$ip}_" . now()->format('Y-m-d-H');
        $hourlyCalls = Cache::get($hourlyKey, 0);

        if ($hourlyCalls >= 100) {
            return response()->json([
                'error' => 'Limite horário de consultas por IP atingido',
                'message' => 'Máximo de 100 consultas por hora por IP. Tente novamente em breve.',
                'hourly_limit' => 100,
                'reset_time' => now()->addHour()->startOfDay()->toISOString()
            ], 429);
        }

        // Incrementar contador horário
        Cache::put($hourlyKey, $hourlyCalls + 1, 3600); // 1 hora

        // Verificar rate limit por usuário autenticado (máximo 50 consultas por hora)
        if (Auth::check()) {
            $userId = Auth::id();
            $userHourlyKey = "fipe_rate_limit_user_{$userId}_" . now()->format('Y-m-d-H');
            $userHourlyCalls = Cache::get($userHourlyKey, 0);

            if ($userHourlyCalls >= 50) {
                return response()->json([
                    'error' => 'Limite horário de consultas por usuário atingido',
                    'message' => 'Máximo de 50 consultas por hora por usuário. Tente novamente em breve.',
                    'hourly_limit' => 50,
                    'reset_time' => now()->addHour()->startOfDay()->toISOString()
                ], 429);
            }

            // Incrementar contador horário do usuário
            Cache::put($userHourlyKey, $userHourlyCalls + 1, 3600); // 1 hora
        }

        return $next($request);
    }
}
