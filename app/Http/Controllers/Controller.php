<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="v2.0.0",
 *     title="Portal Veículos SaaS API",
 *     description="API completa para o sistema SaaS de portais de veículos com autenticação unificada (JWT + Sanctum)",
 *     @OA\Contact(
 *         email="suporte@portalveiculos.com",
 *         name="Suporte Técnico"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="apiKey",
 *     name="Authorization",
 *     in="header",
 *     description="Token de autenticação Sanctum (Bearer <token>)"
 * )
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8000",
 *     description="Servidor Local"
 * )
 *
 * @OA\Server(
 *     url="https://api.portalveiculos.com",
 *     description="Servidor de Produção"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}

/**
 * @OA\Schema(
 *     schema="AnalyticsDashboard",
 *     title="Dashboard de Analytics",
 *     @OA\Property(property="period", ref="#/components/schemas/AnalyticsPeriod"),
 *     @OA\Property(property="overview", ref="#/components/schemas/AnalyticsOverview"),
 *     @OA\Property(property="conversion_rates", ref="#/components/schemas/ConversionRates"),
 *     @OA\Property(property="daily_stats", type="object"),
 *     @OA\Property(property="top_pages", type="array", @OA\Items(ref="#/components/schemas/PageStats")),
 *     @OA\Property(property="top_searches", type="array", @OA\Items(ref="#/components/schemas/SearchTermStats")),
 *     @OA\Property(property="lead_sources", type="array", @OA\Items(ref="#/components/schemas/LeadSourceStats"))
 * )
 *
 * @OA\Schema(
 *     schema="AnalyticsPeriod",
 *     title="Período de Análise",
 *     @OA\Property(property="days", type="integer", example=30),
 *     @OA\Property(property="start_date", type="string", format="date", example="2024-07-23"),
 *     @OA\Property(property="end_date", type="string", format="date", example="2024-08-23")
 * )
 *
 * @OA\Schema(
 *     schema="AnalyticsOverview",
 *     title="Visão Geral dos Analytics",
 *     @OA\Property(property="total_page_views", type="integer", example=1250),
 *     @OA\Property(property="total_leads", type="integer", example=45),
 *     @OA\Property(property="total_vehicle_views", type="integer", example=320),
 *     @OA\Property(property="total_searches", type="integer", example=180)
 * )
 *
 * @OA\Schema(
 *     schema="ConversionRates",
 *     title="Taxas de Conversão",
 *     @OA\Property(property="leads_per_page_view", type="number", format="float", example=3.6),
 *     @OA\Property(property="leads_per_vehicle_view", type="number", format="float", example=14.1)
 * )
 *
 * @OA\Schema(
 *     schema="PageStats",
 *     title="Estatísticas de Página",
 *     @OA\Property(property="metric_name", type="string", example="home_page"),
 *     @OA\Property(property="views", type="integer", example=450),
 *     @OA\Property(property="unique_visitors", type="integer", example=320)
 * )
 *
 * @OA\Schema(
 *     schema="SearchTermStats",
 *     title="Estatísticas de Termo de Busca",
 *     @OA\Property(property="search_term", type="string", example="honda civic"),
 *     @OA\Property(property="searches", type="integer", example=25)
 * )
 *
 * @OA\Schema(
 *     schema="LeadSourceStats",
 *     title="Estatísticas de Fonte de Lead",
 *     @OA\Property(property="source", type="string", example="site"),
 *     @OA\Property(property="leads", type="integer", example=30)
 * )
 */
