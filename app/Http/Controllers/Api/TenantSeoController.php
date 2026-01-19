<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantSeoUrl;
use App\Models\Tenant;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="SEO URLs",
 *     description="Endpoints públicos para gestão de URLs SEO e sitemaps"
 * )
 *
 * @OA\Schema(
 *     schema="SeoUrlResponse",
 *     type="object",
 *     @OA\Property(property="tenant", type="string", example="omegaveiculos"),
 *     @OA\Property(property="locale", type="string", example="pt-BR"),
 *     @OA\Property(property="path", type="string", example="/comprar-carro/vw-volkswagen-polo-2023-49"),
 *     @OA\Property(property="type", type="string", enum={"vehicle_detail","collection","blog_post","faq","static"}),
 *     @OA\Property(property="status", type="string", enum={"active","redirect_301","redirect_302","redirect_canonical"}, example="active"),
 *     @OA\Property(property="canonical_url", type="string", example="https://omegaveiculos.com.br/comprar-carro/vw-volkswagen-polo-2023-49"),
 *     @OA\Property(property="is_indexable", type="boolean", example=true),
 *     @OA\Property(property="include_in_sitemap", type="boolean", example=true),
 *     @OA\Property(property="sitemap", type="object",
 *         @OA\Property(property="priority", type="number", format="float", example=0.8),
 *         @OA\Property(property="changefreq", type="string", example="daily"),
 *         @OA\Property(property="lastmod", type="string", format="date-time", example="2025-10-15T12:34:56Z")
 *     ),
 *     @OA\Property(property="meta", type="object",
 *         @OA\Property(property="title", type="string", example="Volkswagen Polo 2023 - Oferta Especial"),
 *         @OA\Property(property="description", type="string", example="Compre seu Volkswagen Polo 2023 com condições especiais"),
 *         @OA\Property(property="og_image", type="string", example="https://omegaveiculos.com.br/images/polo-2023.jpg")
 *     ),
 *     @OA\Property(property="breadcrumbs", type="array", @OA\Items(type="object",
 *         @OA\Property(property="name", type="string", example="Início"),
 *         @OA\Property(property="item", type="string", example="https://omegaveiculos.com.br")
 *     )),
 *     @OA\Property(property="structured_data_type", type="string", example="Vehicle"),
 *     @OA\Property(property="structured_data_payload", type="object"),
 *     @OA\Property(property="content_templates", type="object"),
 *     @OA\Property(property="content_data", type="object"),
 *     @OA\Property(property="route_params", type="object"),
 *     @OA\Property(property="aggregated_data", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="SitemapResponse",
 *     type="object",
 *     @OA\Property(property="tenant", type="string", example="omegaveiculos"),
 *     @OA\Property(property="generated_at", type="string", format="date-time", example="2025-10-15T12:34:56Z"),
 *     @OA\Property(property="total_urls", type="integer", example=150),
 *     @OA\Property(property="urls", type="array", @OA\Items(type="object",
 *         @OA\Property(property="loc", type="string", example="https://omegaveiculos.com.br/comprar-carro/vw-polo"),
 *         @OA\Property(property="lastmod", type="string", format="date-time", example="2025-10-15T12:34:56Z"),
 *         @OA\Property(property="changefreq", type="string", example="daily"),
 *         @OA\Property(property="priority", type="number", format="float", example=0.8)
 *     ))
 * )
 *
 * @OA\Schema(
 *     schema="SeoUrlRequest",
 *     type="object",
 *     required={"path","type","canonical_url"},
 *     @OA\Property(property="path", type="string", example="/comprar-carro/vw-volkswagen-polo-2023-49"),
 *     @OA\Property(property="type", type="string", enum={"vehicle_detail","collection","blog_post","faq","static"}),
 *     @OA\Property(property="canonical_url", type="string", example="https://omegaveiculos.com.br/comprar-carro/vw-volkswagen-polo-2023-49"),
 *     @OA\Property(property="locale", type="string", example="pt-BR"),
 *     @OA\Property(property="title", type="string", maxLength=160, example="Volkswagen Polo 2023 - Oferta Especial"),
 *     @OA\Property(property="meta_description", type="string", maxLength=300, example="Compre seu Volkswagen Polo 2023 com condições especiais"),
 *     @OA\Property(property="og_image", type="string", maxLength=512, example="https://omegaveiculos.com.br/images/polo-2023.jpg"),
 *     @OA\Property(property="is_indexable", type="boolean", example=true),
 *     @OA\Property(property="include_in_sitemap", type="boolean", example=true),
 *     @OA\Property(property="sitemap_priority", type="number", format="float", minimum=0, maximum=1, example=0.8),
 *     @OA\Property(property="sitemap_changefreq", type="string", enum={"always","hourly","daily","weekly","monthly","yearly","never"}, example="daily"),
 *     @OA\Property(property="breadcrumbs", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="structured_data_type", type="string", enum={"Vehicle","Product","Offer","CollectionPage","ItemList","Article","FAQPage","Organization","LocalBusiness"}),
 *     @OA\Property(property="structured_data_payload", type="object"),
 *     @OA\Property(property="content_data", type="object"),
 *     @OA\Property(property="content_templates", type="object"),
 *     @OA\Property(property="route_params", type="object"),
 *     @OA\Property(property="extra_meta", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="CanonicalRedirectResponse",
 *     type="object",
 *     @OA\Property(property="canonical_url", type="string", example="https://omegaveiculos.com.br/comprar-carro/vw-polo"),
 *     @OA\Property(property="status_code", type="integer", example=301)
 * )
 *
 * @OA\Schema(
 *     schema="SeoPreviewResponse",
 *     type="object",
 *     @OA\Property(property="preview", type="object",
 *         @OA\Property(property="title", type="string", example="Volkswagen Polo 2023 - Oferta Especial"),
 *         @OA\Property(property="description", type="string", example="Compre seu Volkswagen Polo 2023 com condições especiais"),
 *         @OA\Property(property="canonical_url", type="string", example="https://omegaveiculos.com.br/comprar-carro/vw-polo"),
 *         @OA\Property(property="structured_data", type="object"),
 *         @OA\Property(property="breadcrumbs", type="array", @OA\Items(type="object"))
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SeoTemplatesResponse",
 *     type="object",
 *     @OA\Property(property="templates", type="object",
 *         @OA\Property(property="vehicle_detail", type="object",
 *             @OA\Property(property="title", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="description", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="content", type="array", @OA\Items(type="string"))
 *         ),
 *         @OA\Property(property="collection", type="object"),
 *         @OA\Property(property="static", type="object")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="OrganizationResponse",
 *     type="object",
 *     @OA\Property(property="@context", type="string", example="https://schema.org"),
 *     @OA\Property(property="@type", type="string", example="LocalBusiness"),
 *     @OA\Property(property="name", type="string", example="Omega Veículos"),
 *     @OA\Property(property="description", type="string", example="Loja de veículos Omega Veículos"),
 *     @OA\Property(property="url", type="string", example="https://omegaveiculos.com.br"),
 *     @OA\Property(property="logo", type="string", example="https://omegaveiculos.com.br/logo.png"),
 *     @OA\Property(property="telephone", type="string", example="+55 11 99999-9999"),
 *     @OA\Property(property="email", type="string", example="contato@omegaveiculos.com.br"),
 *     @OA\Property(property="address", type="object",
 *         @OA\Property(property="@type", type="string", example="PostalAddress"),
 *         @OA\Property(property="streetAddress", type="string", example="Rua das Flores, 123"),
 *         @OA\Property(property="addressLocality", type="string", example="São Paulo"),
 *         @OA\Property(property="addressRegion", type="string", example="SP"),
 *         @OA\Property(property="postalCode", type="string", example="01234-567"),
 *         @OA\Property(property="addressCountry", type="string", example="BR")
 *     ),
 *     @OA\Property(property="openingHours", type="string", example="Mo-Fr 08:00-18:00"),
 *     @OA\Property(property="priceRange", type="string", example="$$"),
 *     @OA\Property(property="paymentAccepted", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="currenciesAccepted", type="string", example="BRL")
 * )
 */
class TenantSeoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/seo/resolve-path",
     *     summary="Resolver path SEO",
     *     description="Resolve um path SEO e retorna dados completos para SSR/ISR",
     *     operationId="resolveSeoPath",
     *     tags={"SEO URLs"},
     *     @OA\Parameter(
     *         name="path",
     *         in="query",
     *         description="Path da URL",
     *         required=true,
     *         @OA\Schema(type="string", example="/comprar-carro/vw-volkswagen-polo-2023-49")
     *     ),
     *     @OA\Parameter(
     *         name="tenant",
     *         in="query",
     *         description="Subdomínio do tenant",
     *         required=true,
     *         @OA\Schema(type="string", example="omegaveiculos")
     *     ),
     *     @OA\Parameter(
     *         name="locale",
     *         in="query",
     *         description="Locale da URL",
     *         required=false,
     *         @OA\Schema(type="string", example="pt-BR", default="pt-BR")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dados SEO resolvidos com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/SeoUrlResponse")
     *     ),
     *     @OA\Response(response=404, description="Path não encontrado"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function resolvePath(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string|max:512',
            'tenant' => 'required|string|max:255',
            'locale' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'messages' => $validator->errors()
            ], 422);
        }

        $path = $request->path;
        $tenantSubdomain = $request->tenant;
        $locale = $request->locale ?? 'pt-BR';

        try {
            // Buscar tenant
            $tenant = Tenant::where('subdomain', $tenantSubdomain)
                           ->where('status', 'active')
                           ->first();

            if (!$tenant) {
                return response()->json([
                    'error' => 'Tenant não encontrado'
                ], 404);
            }

            // Buscar URL SEO
            $seoUrl = TenantSeoUrl::resolvePath($tenant->id, $locale, $path)
                                 ->with('tenant')
                                 ->first();

            if (!$seoUrl) {
                return response()->json([
                    'error' => 'Path não encontrado'
                ], 404);
            }

            // Verificar se tem redirect
            if ($seoUrl->hasRedirect()) {
                $redirectData = $seoUrl->resolveRedirect();

                return response()->json([
                    'tenant' => $tenant->subdomain,
                    'path' => $seoUrl->path, // URL antiga
                    'type' => $seoUrl->type,
                    'status' => $seoUrl->getStatus(),
                    'redirect_type' => $redirectData['redirect_type'],
                    'redirect_target' => $redirectData['redirect_target'],
                    'redirect_reason' => $redirectData['redirect_reason'],
                    'previous_slug' => $redirectData['previous_slug'],
                    'redirect_date' => $redirectData['redirect_date']?->toISOString(),

                    // Campos normais (para casos sem redirect)
                    'canonical_url' => $seoUrl->canonical_url,
                    'is_indexable' => $seoUrl->is_indexable, // URLs com redirect não são indexáveis
                    'include_in_sitemap' => $seoUrl->include_in_sitemap, // URLs com redirect não entram no sitemap
                    'meta' => [
                        'title' => $seoUrl->title,
                        'description' => $seoUrl->meta_description,
                        'og_image' => $seoUrl->og_image
                    ]
                ]);
            }

            // Buscar dados agregados baseado no tipo (para URLs sem redirect)
            $aggregatedData = $this->getAggregatedData($seoUrl);

            return response()->json([
                'tenant' => $tenant->subdomain,
                'locale' => $seoUrl->locale,
                'path' => $seoUrl->path,
                'type' => $seoUrl->type,
                'status' => $seoUrl->getStatus(),
                'canonical_url' => $seoUrl->canonical_url,
                'is_indexable' => $seoUrl->is_indexable,
                'include_in_sitemap' => $seoUrl->include_in_sitemap,
                'sitemap' => [
                    'priority' => $seoUrl->sitemap_priority,
                    'changefreq' => $seoUrl->sitemap_changefreq,
                    'lastmod' => $seoUrl->lastmod?->toISOString()
                ],
                'meta' => [
                    'title' => $seoUrl->title,
                    'description' => $seoUrl->meta_description,
                    'og_image' => $seoUrl->og_image
                ],
                'breadcrumbs' => $seoUrl->breadcrumbs,
                'structured_data_type' => $seoUrl->structured_data_type,
                'structured_data_payload' => $seoUrl->structured_data_payload,
                'content_templates' => $seoUrl->content_templates,
                'content_data' => $seoUrl->content_data,
                'route_params' => $seoUrl->route_params,
                'aggregated_data' => $aggregatedData
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao resolver path SEO', [
                'path' => $path,
                'tenant' => $tenantSubdomain,
                'locale' => $locale,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/seo/urls",
     *     summary="Criar/atualizar URL SEO",
     *     description="Cria ou atualiza um registro de URL SEO",
     *     operationId="createSeoUrl",
     *     tags={"SEO URLs"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/SeoUrlRequest")
     *     ),
     *     @OA\Response(response=201, description="URL SEO criada com sucesso"),
     *     @OA\Response(response=200, description="URL SEO atualizada com sucesso"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function createOrUpdateUrl(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string|max:512',
            'type' => 'required|in:vehicle_detail,collection,blog_post,faq,static',
            'canonical_url' => 'required|string|max:512',
            'locale' => 'nullable|string|max:10',
            'title' => 'nullable|string|max:160',
            'meta_description' => 'nullable|string|max:300',
            'og_image' => 'nullable|string|max:512',
            'is_indexable' => 'nullable|boolean',
            'include_in_sitemap' => 'nullable|boolean',
            'sitemap_priority' => 'nullable|numeric|between:0,1',
            'sitemap_changefreq' => 'nullable|in:always,hourly,daily,weekly,monthly,yearly,never',
            'breadcrumbs' => 'nullable|array',
            'structured_data_type' => 'nullable|in:Vehicle,Product,Offer,CollectionPage,ItemList,Article,FAQPage,Organization,LocalBusiness',
            'structured_data_payload' => 'nullable|array',
            'content_data' => 'nullable|array',
            'content_templates' => 'nullable|array',
            'route_params' => 'nullable|array',
            'extra_meta' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $tenantId = $user->tenant_id ?? $user->id; // Para usuários da tabela users

            $data = $request->all();
            $data['tenant_id'] = $tenantId;
            $data['locale'] = $data['locale'] ?? 'pt-BR';

            // Buscar ou criar URL SEO
            $seoUrl = TenantSeoUrl::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'locale' => $data['locale'],
                    'path' => $data['path']
                ],
                $data
            );

            $seoUrl->updateLastmod();

            return response()->json([
                'message' => $seoUrl->wasRecentlyCreated ? 'URL SEO criada com sucesso' : 'URL SEO atualizada com sucesso',
                'data' => $seoUrl
            ], $seoUrl->wasRecentlyCreated ? 201 : 200);

        } catch (\Exception $e) {
            Log::error('Erro ao criar/atualizar URL SEO', [
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/super-admin/seo/sitemap",
     *     summary="Gerar sitemap (Super Admin)",
     *     description="Gera sitemap XML/JSON para um tenant específico - Acesso exclusivo Super Admin",
     *     operationId="generateSitemapSuperAdmin",
     *     tags={"Super Admin - SEO"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="tenant",
     *         in="query",
     *         description="Subdomínio do tenant",
     *         required=true,
     *         @OA\Schema(type="string", example="omegaveiculos")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Tipo de sitemap",
     *         required=false,
     *         @OA\Schema(type="string", example="vehicle_detail")
     *     ),
     *     @OA\Parameter(
     *         name="format",
     *         in="query",
     *         description="Formato de retorno",
     *         required=false,
     *         @OA\Schema(type="string", enum={"xml","json"}, default="xml")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sitemap gerado com sucesso",
     *         @OA\MediaType(
     *             mediaType="application/xml",
     *             @OA\Schema(type="string", example="&lt;?xml version=&quot;1.0&quot; encoding=&quot;UTF-8&quot;?&gt;&lt;urlset xmlns=&quot;http://www.sitemaps.org/schemas/sitemap/0.9&quot;&gt;...&lt;/urlset&gt;")
     *         ),
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/SitemapResponse")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Não autorizado"),
     *     @OA\Response(response=404, description="Tenant não encontrado")
     * )
     */
    public function generateSitemapSuperAdmin(Request $request)
    {
        return $this->generateSitemap($request);
    }

    /**
     * Gerar sitemap (método privado)
     */
    private function generateSitemap(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tenant' => 'required|string|max:255',
            'type' => 'nullable|in:vehicle_detail,collection,blog_post,faq,static',
            'format' => 'nullable|in:xml,json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $tenantSubdomain = $request->tenant;
            $type = $request->type;
            $format = $request->format ?? 'xml';

            // Buscar tenant
            $tenant = Tenant::where('subdomain', $tenantSubdomain)
                           ->where('status', 'active')
                           ->first();

            if (!$tenant) {
                return response()->json([
                    'error' => 'Tenant não encontrado'
                ], 404);
            }

            // Buscar URLs para sitemap
            $query = TenantSeoUrl::byTenant($tenant->id)
                                ->forSitemap();

            if ($type) {
                $query->byType($type);
            }

            $seoUrls = $query->orderBy('lastmod', 'desc')
                            ->get();

            if ($format === 'json') {
                return response()->json([
                    'tenant' => $tenant->subdomain,
                    'generated_at' => Carbon::now()->toISOString(),
                    'total_urls' => $seoUrls->count(),
                    'urls' => $seoUrls->map(function ($url) {
                        return [
                            'loc' => $url->canonical_url,
                            'lastmod' => $url->lastmod?->toISOString(),
                            'changefreq' => $url->sitemap_changefreq,
                            'priority' => $url->sitemap_priority
                        ];
                    })
                ]);
            }

            // Gerar XML
            $xml = $this->generateSitemapXml($seoUrls, $tenant);

            // Salvar sitemap em pasta específica do tenant
            $this->saveSitemapToFile($xml, $tenant, $type);

            return response($xml, 200, [
                'Content-Type' => 'application/xml; charset=utf-8'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao gerar sitemap', [
                'tenant' => $request->tenant,
                'type' => $request->type,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/seo/sitemap-index",
     *     summary="Gerar sitemap index",
     *     description="Gera o sitemap index com todos os sitemaps do tenant",
     *     operationId="getSitemapIndex",
     *     tags={"SEO URLs"},
     *     @OA\Parameter(
     *         name="tenant",
     *         in="query",
     *         description="Subdomínio do tenant",
     *         required=true,
     *         @OA\Schema(type="string", example="omegaveiculos")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sitemap index gerado com sucesso",
     *         @OA\MediaType(
     *             mediaType="application/xml",
     *             @OA\Schema(type="string", example="&lt;?xml version=&quot;1.0&quot; encoding=&quot;UTF-8&quot;?&gt;&lt;sitemapindex xmlns=&quot;http://www.sitemaps.org/schemas/sitemap/0.9&quot;&gt;...&lt;/sitemapindex&gt;")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Tenant não encontrado")
     * )
     */
    public function getSitemapIndex(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tenant' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $tenantSubdomain = $request->tenant;

            // Buscar tenant
            $tenant = Tenant::where('subdomain', $tenantSubdomain)
                           ->where('status', 'active')
                           ->first();

            if (!$tenant) {
                return response()->json([
                    'error' => 'Tenant não encontrado'
                ], 404);
            }

            // Gerar sitemap index
            $xml = $this->buildSitemapIndex($tenant);

            return response($xml, 200, [
                'Content-Type' => 'application/xml; charset=utf-8'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao gerar sitemap index', [
                'tenant' => $request->tenant,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/seo/canonical-redirect",
     *     summary="Obter URL canônica",
     *     description="Retorna a URL canônica para uma rota técnica",
     *     operationId="getCanonicalRedirect",
     *     tags={"SEO URLs"},
     *     @OA\Parameter(
     *         name="path",
     *         in="query",
     *         description="Path da URL",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="tenant",
     *         in="query",
     *         description="Subdomínio do tenant",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="URL canônica encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/CanonicalRedirectResponse")
     *     ),
     *     @OA\Response(response=404, description="URL não encontrada")
     * )
     */
    public function getCanonicalRedirect(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string|max:512',
            'tenant' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $tenant = Tenant::where('subdomain', $request->tenant)
                           ->where('status', 'active')
                           ->first();

            if (!$tenant) {
                return response()->json([
                    'error' => 'Tenant não encontrado'
                ], 404);
            }

            $seoUrl = TenantSeoUrl::byTenant($tenant->id)
                                 ->byPath($request->path)
                                 ->first();

            if (!$seoUrl) {
                return response()->json([
                    'error' => 'URL não encontrada'
                ], 404);
            }

            return response()->json([
                'canonical_url' => $seoUrl->canonical_url,
                'status_code' => 301
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar URL canônica', [
                'path' => $request->path,
                'tenant' => $request->tenant,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/seo/preview",
     *     summary="Preview de URL SEO",
     *     description="Gera preview de uma URL SEO com template e dados",
     *     operationId="previewSeoUrl",
     *     tags={"SEO URLs"},
     *     @OA\Parameter(
     *         name="path",
     *         in="query",
     *         description="Path da URL",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="tenant",
     *         in="query",
     *         description="Subdomínio do tenant",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Preview gerado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/SeoPreviewResponse")
     *     ),
     *     @OA\Response(response=404, description="URL não encontrada")
     * )
     */
    public function preview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string|max:512',
            'tenant' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $tenant = Tenant::where('subdomain', $request->tenant)
                           ->where('status', 'active')
                           ->first();

            if (!$tenant) {
                return response()->json([
                    'error' => 'Tenant não encontrado'
                ], 404);
            }

            $seoUrl = TenantSeoUrl::byTenant($tenant->id)
                                 ->byPath($request->path)
                                 ->first();

            if (!$seoUrl) {
                return response()->json([
                    'error' => 'URL não encontrada'
                ], 404);
            }

            // Processar spintax
            $processedTitle = $seoUrl->processSpintax($seoUrl->title);
            $processedDescription = $seoUrl->processSpintax($seoUrl->meta_description);

            return response()->json([
                'preview' => [
                    'title' => $processedTitle,
                    'description' => $processedDescription,
                    'canonical_url' => $seoUrl->canonical_url,
                    'structured_data' => $seoUrl->generateStructuredData(),
                    'breadcrumbs' => $seoUrl->generateBreadcrumbs()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao gerar preview', [
                'path' => $request->path,
                'tenant' => $request->tenant,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/seo/templates",
     *     summary="Listar templates de spintax",
     *     description="Lista templates de spintax disponíveis por tipo/bloco",
     *     operationId="getSeoTemplates",
     *     tags={"SEO URLs"},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Tipo de template",
     *         required=false,
     *         @OA\Schema(type="string", example="vehicle_detail")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Templates listados com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/SeoTemplatesResponse")
     *     )
     * )
     */
    public function getTemplates(Request $request): JsonResponse
    {
        $type = $request->type;

        $templates = [
            'vehicle_detail' => [
                'title' => [
                    '{Compre|Garanta|Adquira} seu {carro|veículo} {novo|seminovo} {Volkswagen Polo|Honda Civic|Toyota Corolla} {2023|2024}',
                    '{Oferta|Condição} {especial|imperdível} em {carros|veículos} {usados|seminovos}',
                    '{Melhor|Maior} {oferta|condição} de {carros|veículos} {na região|da cidade}'
                ],
                'description' => [
                    '{Condição|Oferta} {imperdível|especial} em {MyTitle}',
                    '{Financiamento|Pagamento} {sem entrada|com entrada baixa}',
                    '{Garantia|Proteção} {estendida|completa} {incluída|disponível}'
                ],
                'content' => [
                    '<p>{Texto A|Texto B|Texto C}</p>',
                    '<h2>{Título A|Título B|Título C}</h2>',
                    '<p>{Parágrafo A|Parágrafo B|Parágrafo C}</p>'
                ]
            ],
            'collection' => [
                'title' => [
                    '{Carros|Veículos} {usados|seminovos} {na região|da cidade}',
                    '{Melhor|Maior} {seleção|variedade} de {carros|veículos}'
                ],
                'description' => [
                    '{Encontre|Descubra} {os melhores|as melhores} {carros|veículos} {usados|seminovos}',
                    '{Condições|Ofertas} {especiais|imperdíveis} {todos os dias|sempre}'
                ]
            ],
            'static' => [
                'title' => [
                    '{Sobre|Conheça} {nossa empresa|nossa loja}',
                    '{Contato|Fale conosco} {hoje|agora}'
                ],
                'description' => [
                    '{Saiba mais|Descubra} {sobre|acerca de} {nossos serviços|nossa empresa}',
                    '{Entre em contato|Fale conosco} {conosco|hoje}'
                ]
            ]
        ];

        if ($type && isset($templates[$type])) {
            return response()->json([
                'type' => $type,
                'templates' => $templates[$type]
            ]);
        }

        return response()->json([
            'templates' => $templates
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/seo/tenants/{tenant}/organization",
     *     summary="Dados da organização do tenant",
     *     description="Retorna dados para JSON-LD de Organization/LocalBusiness",
     *     operationId="getTenantOrganization",
     *     tags={"SEO URLs"},
     *     @OA\Parameter(
     *         name="tenant",
     *         in="path",
     *         description="Subdomínio do tenant",
     *         required=true,
     *         @OA\Schema(type="string", example="omegaveiculos")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dados da organização retornados",
     *         @OA\JsonContent(ref="#/components/schemas/OrganizationResponse")
     *     ),
     *     @OA\Response(response=404, description="Tenant não encontrado")
     * )
     */
    public function getTenantOrganization(string $tenant): JsonResponse
    {
        try {
            $tenantModel = Tenant::where('subdomain', $tenant)
                                ->where('status', 'active')
                                ->first();

            if (!$tenantModel) {
                return response()->json([
                    'error' => 'Tenant não encontrado'
                ], 404);
            }

            $organizationData = [
                '@context' => 'https://schema.org',
                '@type' => 'LocalBusiness',
                'name' => $tenantModel->name,
                'description' => $tenantModel->description ?? "Loja de veículos {$tenantModel->name}",
                'url' => "https://{$tenantModel->subdomain}.com.br",
                'logo' => $tenantModel->logo_url ?? null,
                'image' => $tenantModel->logo_url ?? null,
                'telephone' => $tenantModel->phone ?? null,
                'email' => $tenantModel->email ?? null,
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $tenantModel->address ?? null,
                    'addressLocality' => $tenantModel->city ?? null,
                    'addressRegion' => $tenantModel->state ?? null,
                    'postalCode' => $tenantModel->zip_code ?? null,
                    'addressCountry' => 'BR'
                ],
                'openingHours' => $tenantModel->business_hours ?? null,
                'priceRange' => '$$',
                'paymentAccepted' => ['Cash', 'Credit Card', 'Debit Card'],
                'currenciesAccepted' => 'BRL'
            ];

            return response()->json($organizationData);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar dados da organização', [
                'tenant' => $tenant,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Buscar dados agregados baseado no tipo de URL
     */
    private function getAggregatedData(TenantSeoUrl $seoUrl): ?array
    {
        if (!$seoUrl->route_params) {
            return null;
        }

        switch ($seoUrl->type) {
            case 'vehicle_detail':
                if (isset($seoUrl->route_params['vehicle_id'])) {
                    $vehicle = Vehicle::with(['brand', 'model', 'images'])
                                     ->find($seoUrl->route_params['vehicle_id']);

                    if ($vehicle) {
                        return [
                            'vehicle' => [
                                'id' => $vehicle->id,
                                'brand' => $vehicle->brand?->name,
                                'model' => $vehicle->model?->name,
                                'year' => $vehicle->year,
                                'price' => $vehicle->price,
                                'images' => $vehicle->images->pluck('url')->toArray()
                            ]
                        ];
                    }
                }
                break;

            case 'collection':
                // Implementar lógica para coleções
                break;

            case 'blog_post':
                // Implementar lógica para posts de blog
                break;

            case 'faq':
                // Implementar lógica para FAQ
                break;

            case 'static':
                // Implementar lógica para páginas estáticas
                break;
        }

        return null;
    }

    /**
     * Gerar XML do sitemap
     */
    private function generateSitemapXml($seoUrls, $tenant): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($seoUrls as $url) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($url->canonical_url) . '</loc>' . "\n";

            if ($url->lastmod) {
                $xml .= '    <lastmod>' . $url->lastmod->toISOString() . '</lastmod>' . "\n";
            }

            $xml .= '    <changefreq>' . $url->sitemap_changefreq . '</changefreq>' . "\n";
            $xml .= '    <priority>' . $url->sitemap_priority . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Salvar sitemap em arquivo específico do tenant
     */
    private function saveSitemapToFile(string $xml, Tenant $tenant, ?string $type = null): void
    {
        try {
            // Criar diretório específico do tenant no storage
            $tenantDir = storage_path("app/sitemaps/{$tenant->subdomain}");

            if (!file_exists($tenantDir)) {
                mkdir($tenantDir, 0755, true);
            }

            // Definir nome do arquivo baseado no tipo
            $filename = $type ? "sitemap-{$type}.xml" : "sitemap.xml";
            $filepath = "{$tenantDir}/{$filename}";

            // Salvar arquivo
            file_put_contents($filepath, $xml);

            Log::info('Sitemap salvo com sucesso', [
                'tenant' => $tenant->subdomain,
                'type' => $type,
                'filepath' => $filepath,
                'urls_count' => substr_count($xml, '<url>')
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao salvar sitemap', [
                'tenant' => $tenant->subdomain,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Gerar sitemap index para múltiplos sitemaps
     */
    private function buildSitemapIndex(Tenant $tenant): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $sitemapTypes = ['vehicle_detail', 'collection', 'blog_post', 'faq', 'static'];
        $baseUrl = "https://{$tenant->subdomain}.localhost"; // Ajustar conforme domínio real

        foreach ($sitemapTypes as $type) {
            $filepath = storage_path("app/sitemaps/{$tenant->subdomain}/sitemap-{$type}.xml");

            if (file_exists($filepath)) {
                $xml .= '  <sitemap>' . "\n";
                $xml .= '    <loc>' . htmlspecialchars("{$baseUrl}/storage/sitemaps/{$tenant->subdomain}/sitemap-{$type}.xml") . '</loc>' . "\n";
                $xml .= '    <lastmod>' . date('c', filemtime($filepath)) . '</lastmod>' . "\n";
                $xml .= '  </sitemap>' . "\n";
            }
        }

        $xml .= '</sitemapindex>';

        return $xml;
    }

    /**
     * @OA\Get(
     *     path="/api/seo/sitemap-file",
     *     summary="Servir arquivo de sitemap",
     *     description="Serve um arquivo de sitemap específico do storage",
     *     operationId="serveSitemapFile",
     *     tags={"SEO URLs"},
     *     @OA\Parameter(
     *         name="tenant",
     *         in="query",
     *         description="Subdomínio do tenant",
     *         required=true,
     *         @OA\Schema(type="string", example="omegaveiculos")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Tipo de sitemap",
     *         required=false,
     *         @OA\Schema(type="string", example="vehicle_detail")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Arquivo de sitemap servido com sucesso",
     *         @OA\MediaType(
     *             mediaType="application/xml",
     *             @OA\Schema(type="string")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Arquivo não encontrado")
     * )
     */
    public function serveSitemapFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tenant' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $tenantSubdomain = $request->tenant;
            $type = $request->type;

            // Buscar tenant
            $tenant = Tenant::where('subdomain', $tenantSubdomain)
                           ->where('status', 'active')
                           ->first();

            if (!$tenant) {
                return response()->json([
                    'error' => 'Tenant não encontrado'
                ], 404);
            }

            // Definir caminho do arquivo
            $filename = $type ? "sitemap-{$type}.xml" : "sitemap.xml";
            $filepath = storage_path("app/sitemaps/{$tenant->subdomain}/{$filename}");

            if (!file_exists($filepath)) {
                return response()->json([
                    'error' => 'Arquivo de sitemap não encontrado'
                ], 404);
            }

            // Servir arquivo
            return response()->file($filepath, [
                'Content-Type' => 'application/xml; charset=utf-8',
                'Cache-Control' => 'public, max-age=3600', // Cache por 1 hora
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao servir arquivo de sitemap', [
                'tenant' => $request->tenant,
                'type' => $request->type,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

}
