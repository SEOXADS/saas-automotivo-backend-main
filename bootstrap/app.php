<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Desabilitar Spark e outros pacotes opcionais
        $middleware->statefulApi(false);

        // Registrar middlewares customizados primeiro
        $middleware->alias([
            'tenant.identification' => \App\Http\Middleware\TenantIdentificationMiddleware::class,
            'tenant.auto' => \App\Http\Middleware\TenantAutoIdentificationMiddleware::class,
            'token.auth' => \App\Http\Middleware\TokenAuthMiddleware::class,
            'token.auth.super_admin' => \App\Http\Middleware\TokenAuthMiddleware::class.':super_admin',
            'token.auth.tenant' => \App\Http\Middleware\TokenAuthMiddleware::class.':admin',
            'token.auth.unified' => \App\Http\Middleware\UnifiedAuthMiddleware::class,
            'tenant' => \App\Http\Middleware\TenantMiddleware::class,
            'tenant.exists' => \App\Http\Middleware\EnsureTenantExists::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'cors' => \App\Http\Middleware\CorsMiddleware::class,
            'debug' => \App\Http\Middleware\DebugMiddleware::class,
            'fipe.rate.limit' => \App\Http\Middleware\FipeRateLimitMiddleware::class,
            'url.redirect' => \App\Http\Middleware\UrlRedirectMiddleware::class,
        ]);

        // Middleware global para rotas API - CORS primeiro
        $middleware->group('api', [
            \App\Http\Middleware\DatabaseFallbackMiddleware::class,
            \App\Http\Middleware\CorsMiddleware::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
