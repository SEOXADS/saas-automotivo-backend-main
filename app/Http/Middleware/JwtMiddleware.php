<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Support\Facades\Auth;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $guard = null): Response
    {
        // Pré-flight CORS
        if ($request->getMethod() === 'OPTIONS') {
            return $this->withCorsHeaders($request, response('', 204));
        }

        try {
            // Verificar se o token existe
            $token = $request->bearerToken();

            if (!$token) {
                return $this->withCorsHeaders($request, response()->json([
                    'error' => 'Token não fornecido',
                    'message' => 'Token de autenticação é obrigatório'
                ], 401));
            }

            // Se um guard específico foi especificado, usar ele
            if ($guard) {
                $user = JWTAuth::setToken($token)->authenticate($guard);
            } else {
                // Usar o guard padrão da configuração
                $user = JWTAuth::setToken($token)->authenticate();
            }

            if (!$user) {
                return $this->withCorsHeaders($request, response()->json([
                    'error' => 'Usuário não autenticado',
                    'message' => 'Token inválido ou expirado'
                ], 401));
            }

            // Adicionar o usuário à request
            $request->merge(['user' => $user]);

            return $next($request);

        } catch (TokenExpiredException $e) {
            return $this->withCorsHeaders($request, response()->json([
                'error' => 'Token expirado',
                'message' => 'Token de autenticação expirou'
            ], 401));
        } catch (TokenInvalidException $e) {
            return $this->withCorsHeaders($request, response()->json([
                'error' => 'Token inválido',
                'message' => 'Token de autenticação é inválido'
            ], 401));
        } catch (JWTException $e) {
            return $this->withCorsHeaders($request, response()->json([
                'error' => 'Erro no token',
                'message' => 'Erro ao processar token de autenticação'
            ], 401));
        } catch (\Exception $e) {
            return $this->withCorsHeaders($request, response()->json([
                'error' => 'Erro interno',
                'message' => 'Erro interno do servidor'
            ], 500));
        }
    }

    /**
     * Adiciona headers CORS à resposta
     */
    private function withCorsHeaders(Request $request, Response $response): Response
    {
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-Tenant');

        return $response;
    }
}
