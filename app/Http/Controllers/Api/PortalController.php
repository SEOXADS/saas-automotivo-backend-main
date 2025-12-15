<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\Lead;
use App\Models\Tenant;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="1. Portal Público",
 *     description="Endpoints públicos do portal de anúncios (sem autenticação)"
 * )
 */
class PortalController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/portal/vehicles",
     *     summary="Lista veículos para o portal de anúncios",
     *     description="Retorna uma lista paginada de veículos disponíveis para o portal de anúncios com filtros avançados",
     *     tags={"1. Portal Público"},
     *     @OA\Parameter(name="brand_id", in="query", description="ID da marca", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="model_id", in="query", description="ID do modelo", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="min_price", in="query", description="Preço mínimo", @OA\Schema(type="number", format="float")),
     *     @OA\Parameter(name="max_price", in="query", description="Preço máximo", @OA\Schema(type="number", format="float")),
     *     @OA\Parameter(name="min_year", in="query", description="Ano mínimo", @OA\Schema(type="integer", minimum=1900, maximum=2030)),
     *     @OA\Parameter(name="max_year", in="query", description="Ano máximo", @OA\Schema(type="integer", minimum=1900, maximum=2030)),
     *     @OA\Parameter(name="fuel_type", in="query", description="Tipo de combustível", @OA\Schema(type="string", enum={"flex", "gasolina", "diesel", "eletrico", "hibrido", "gnv"})),
     *     @OA\Parameter(name="transmission", in="query", description="Transmissão", @OA\Schema(type="string", enum={"manual", "automatica", "cvt", "automatizada"})),
     *     @OA\Parameter(name="search", in="query", description="Termo de busca", @OA\Schema(type="string", maxLength=100)),
     *     @OA\Parameter(name="page", in="query", description="Página", @OA\Schema(type="integer", minimum=1, default=1)),
     *     @OA\Parameter(name="per_page", in="query", description="Itens por página", @OA\Schema(type="integer", minimum=1, maximum=100, default=12)),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de veículos retornada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
      *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
 *             @OA\Property(property="pagination", type="object"),
 *             @OA\Property(property="tenant", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Tenant não identificado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getVehicles(Request $request)
    {
        try {
            $tenant = $this->getCurrentTenant($request);

            if (!$tenant) {
                return response()->json(['error' => 'Tenant não identificado'], 400);
            }

            $query = Vehicle::byTenant($tenant->id)
                ->with(['brand', 'model', 'primaryImage'])
                ->active()
                ->available();

            // Aplicar filtros
            $this->applyFilters($query, $request);

            // Aplicar busca
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Ordenação
            $query->orderBy('created_at', 'desc');

            // Paginação
            $perPage = $request->get('per_page', 12);
            $vehicles = $query->paginate($perPage);

            // Transformar dados para o portal
            $vehicles->getCollection()->transform(function ($vehicle) {
                return $this->transformVehicleForPortal($vehicle);
            });

            return response()->json([
                'success' => true,
                'data' => $vehicles->items(),
                'pagination' => [
                    'current_page' => $vehicles->currentPage(),
                    'per_page' => $vehicles->perPage(),
                    'total' => $vehicles->total(),
                    'last_page' => $vehicles->lastPage(),
                    'from' => $vehicles->firstItem(),
                    'to' => $vehicles->lastItem(),
                ],
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'subdomain' => $tenant->subdomain,
                    'custom_domain' => $tenant->custom_domain,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar veículos do portal', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/portal/vehicles/{id}",
     *     summary="Detalhes de um veículo para o portal",
     *     description="Retorna detalhes completos de um veículo específico, incluindo imagens, características e incrementa visualizações",
     *     tags={"1. Portal Público"},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID do veículo", @OA\Schema(type="integer", minimum=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes do veículo retornados com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
      *             @OA\Property(property="data", type="object"),
 *             @OA\Property(property="tenant", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Tenant não identificado"),
     *     @OA\Response(response=404, description="Veículo não encontrado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getVehicle($id, Request $request)
    {
        try {
            $tenant = $this->getCurrentTenant($request);

            if (!$tenant) {
                return response()->json(['error' => 'Tenant não identificado'], 400);
            }

            $vehicle = Vehicle::byTenant($tenant->id)
                ->with(['brand', 'model', 'images', 'features'])
                ->active()
                ->available()
                ->find($id);

            if (!$vehicle) {
                return response()->json([
                    'success' => false,
                    'error' => 'Veículo não encontrado'
                ], 404);
            }

            // Incrementar visualizações
            $vehicle->incrementViews();

            // Teste simples - retornar dados básicos com video_link
            $vehicleData = [
                'id' => $vehicle->id,
                'title' => $vehicle->title,
                'brand' => $vehicle->brand ? [
                    'id' => $vehicle->brand->id,
                    'name' => $vehicle->brand->name,
                    'slug' => $vehicle->brand->slug
                ] : null,
                'model' => $vehicle->model ? [
                    'id' => $vehicle->model->id,
                    'name' => $vehicle->model->name,
                    'slug' => $vehicle->model->slug
                ] : null,
                'year' => $vehicle->year,
                'price' => $vehicle->price,
                'mileage' => $vehicle->mileage,
                'fuel_type' => $vehicle->fuel_type,
                'transmission' => $vehicle->transmission,
                'color' => $vehicle->color,
                'status' => $vehicle->status,
                'description' => $vehicle->description,
                'video_link' => $vehicle->video_link,
                'views' => $vehicle->views,
                'images' => $vehicle->images->map(function ($image) use ($vehicle) {
                    return [
                        'id' => $image->id,
                        'url' => url("/api/public/images/{$vehicle->tenant_id}/{$vehicle->id}/{$image->filename}"),
                        'filename' => $image->filename,
                        'is_primary' => $image->is_primary,
                        'sort_order' => $image->sort_order
                    ];
                }),
                'main_image' => $vehicle->images->where('is_primary', true)->first() ? [
                    'id' => $vehicle->images->where('is_primary', true)->first()->id,
                    'url' => url("/api/public/images/{$vehicle->tenant_id}/{$vehicle->id}/{$vehicle->images->where('is_primary', true)->first()->filename}"),
                    'filename' => $vehicle->images->where('is_primary', true)->first()->filename
                ] : null,
                'features' => $vehicle->features ? $vehicle->features->pluck('name') : [],
                'created_at' => $vehicle->created_at,
                'updated_at' => $vehicle->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $vehicleData,
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'subdomain' => $tenant->subdomain,
                    'custom_domain' => $tenant->custom_domain,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter veículo do portal', [
                'error' => $e->getMessage(),
                'vehicle_id' => $id,
                'tenant_id' => $tenant->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/portal/filters",
     *     summary="Filtros disponíveis para o portal",
     *     description="Retorna todos os filtros disponíveis para o portal, incluindo marcas, modelos, faixas de preço e ano",
     *     tags={"1. Portal Público"},
     *     @OA\Response(
     *         response=200,
     *         description="Filtros retornados com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Tenant não identificado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getFilters(Request $request)
    {
        try {
            $tenant = $this->getCurrentTenant($request);

            if (!$tenant) {
                return response()->json(['error' => 'Tenant não identificado'], 400);
            }

            $cacheKey = "portal_filters_{$tenant->id}";

            return Cache::remember($cacheKey, 3600, function () use ($tenant) {
                $brands = VehicleBrand::active()->ordered()->get(['id', 'name']);
                $models = VehicleModel::active()->ordered()->get(['id', 'name', 'brand_id']);

                $vehicleStats = Vehicle::byTenant($tenant->id)
                    ->active()
                    ->available()
                    ->selectRaw('
                        MIN(year) as min_year,
                        MAX(year) as max_year,
                        MIN(price) as min_price,
                        MAX(price) as max_price
                    ')
                    ->first();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'brands' => $brands,
                        'models' => $models,
                        'fuel_types' => ['flex', 'gasolina', 'diesel', 'eletrico', 'hibrido', 'gnv'],
                        'transmissions' => ['manual', 'automatica', 'cvt', 'automatizada'],
                        'price_range' => [
                            'min' => $vehicleStats->min_price ?? 0,
                            'max' => $vehicleStats->max_price ?? 0
                        ],
                        'year_range' => [
                            'min' => $vehicleStats->min_year ?? date('Y'),
                            'max' => $vehicleStats->max_year ?? date('Y')
                        ]
                    ]
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Erro ao obter filtros do portal', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/portal/leads",
     *     summary="Criar lead do portal",
     *     description="Cria um novo lead a partir do portal de anúncios com validação completa dos dados",
     *     tags={"1. Portal Público"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "phone", "source"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="João Silva", description="Nome completo do interessado"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255, example="joao@email.com", description="Email válido do interessado"),
     *             @OA\Property(property="phone", type="string", maxLength=20, example="(11) 99999-9999", description="Telefone para contato"),
     *             @OA\Property(property="message", type="string", maxLength=1000, example="Gostaria de saber mais sobre este veículo", description="Mensagem opcional"),
     *             @OA\Property(property="vehicle_id", type="integer", example=1, description="ID do veículo de interesse (opcional)"),
     *             @OA\Property(property="source", type="string", enum={"site", "whatsapp", "facebook", "instagram", "google", "outro"}, example="site", description="Origem do lead")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Lead criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lead criado com sucesso! Entraremos em contato em breve."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Tenant não identificado"),
     *     @OA\Response(response=422, description="Dados inválidos"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function createLead(Request $request)
    {
        try {
            $tenant = $this->getCurrentTenant($request);

            if (!$tenant) {
                return response()->json(['error' => 'Tenant não identificado'], 400);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',
                'message' => 'nullable|string|max:1000',
                'vehicle_id' => 'nullable|integer|exists:vehicles,id',
                'source' => 'required|string|in:site,whatsapp,facebook,instagram,google,outro',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dados inválidos',
                    'messages' => $validator->errors()
                ], 422);
            }

            $leadData = $request->all();
            $leadData['tenant_id'] = $tenant->id;
            $leadData['ip_address'] = $request->ip();
            $leadData['user_agent'] = $request->userAgent();
            $leadData['status'] = 'new';

            $lead = Lead::create($leadData);

            // Log do lead criado
            Log::info('Lead criado via portal', [
                'lead_id' => $lead->id,
                'tenant_id' => $tenant->id,
                'vehicle_id' => $lead->vehicle_id,
                'source' => $lead->source
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lead criado com sucesso! Entraremos em contato em breve.',
                'data' => [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'created_at' => $lead->created_at
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao criar lead via portal', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/portal/tenant-info",
     *     summary="Informações do tenant para o portal",
     *     description="Retorna informações completas do tenant para personalização do portal, incluindo cores, logo, redes sociais e horários",
     *     tags={"1. Portal Público"},
     *     @OA\Response(
     *         response=200,
     *         description="Informações do tenant retornadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Tenant não identificado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getTenantInfo(Request $request)
    {
        try {
            $tenant = $this->getCurrentTenant($request);

            if (!$tenant) {
                return response()->json(['error' => 'Tenant não identificado'], 400);
            }

            // Carregar relacionamentos com tratamento de erro
            try {
                $tenant->load(['profile', 'theme', 'seo', 'portalSettings']);
            } catch (\Exception $e) {
                Log::warning('Erro ao carregar relacionamentos do tenant', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage()
                ]);
                // Continuar sem os relacionamentos se houver erro
            }

            $data = [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'subdomain' => $tenant->subdomain,
                'custom_domain' => $tenant->custom_domain,
                'status' => $tenant->status,
                'plan' => $tenant->plan,

                // Perfil da empresa
                'profile' => $tenant->profile ? [
                    'company_name' => $tenant->profile->company_name,
                    'company_description' => $tenant->profile->company_description,
                    'company_cnpj' => $tenant->profile->company_cnpj,
                    'company_phone' => $tenant->profile->company_phone,
                    'company_email' => $tenant->profile->company_email,
                    'company_website' => $tenant->profile->company_website,
                    'address' => $tenant->profile->getFullAddressAttribute(),
                    'address_details' => [
                        'street' => $tenant->profile->address_street,
                        'number' => $tenant->profile->address_number,
                        'complement' => $tenant->profile->address_complement,
                        'district' => $tenant->profile->address_district,
                        'city' => $tenant->profile->address_city,
                        'state' => $tenant->profile->address_state,
                        'zipcode' => $tenant->profile->address_zipcode,
                        'country' => $tenant->profile->address_country
                    ],
                    'business_hours' => $tenant->profile->business_hours,
                    'social_media' => $tenant->profile->getSocialMediaLinks(),
                    'logo_url' => $tenant->profile->logo_url,
                    'favicon_url' => $tenant->profile->favicon_url,
                    'banner_url' => $tenant->profile->banner_url
                ] : null,

                // Tema
                'theme' => $tenant->theme ? $tenant->theme->getFrontendConfig() : null,

                // SEO
                'seo' => $tenant->seo ? $tenant->seo->getFrontendConfig() : null,

                // Configurações do portal
                'portal_settings' => $tenant->portalSettings ? $tenant->portalSettings->getFrontendConfig() : null,

                // Configurações legadas (para compatibilidade)
                'logo' => $tenant->logo,
                'theme_color' => $tenant->getThemeColor(),
                'contact_email' => $tenant->email,
                'contact_phone' => $tenant->phone,

                'created_at' => $tenant->created_at,
                'updated_at' => $tenant->updated_at
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter informações do tenant', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/portal/tenant/theme",
     *     summary="Configurações de tema do tenant",
     *     description="Retorna configurações específicas de tema e branding do tenant",
     *     tags={"1. Portal Público"},
     *     @OA\Response(
     *         response=200,
     *         description="Configurações de tema retornadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Tenant não identificado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getTenantTheme(Request $request)
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            if (!$tenant) {
                return response()->json(['error' => 'Tenant não identificado'], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'theme_color' => $tenant->getThemeColor(),
                    'logo_url' => $tenant->logo_url ?? $tenant->logo,
                    'company_name' => $tenant->name,
                    'description' => $tenant->description,
                    'branding' => [
                        'primary_color' => $tenant->getThemeColor(),
                        'secondary_color' => $this->generateSecondaryColor($tenant->getThemeColor()),
                        'accent_color' => $this->generateAccentColor($tenant->getThemeColor()),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/portal/tenant/social-media",
     *     summary="Redes sociais do tenant",
     *     description="Retorna configurações de redes sociais do tenant",
     *     tags={"1. Portal Público"},
     *     @OA\Response(
     *         response=200,
     *         description="Redes sociais retornadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Tenant não identificado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getTenantSocialMedia(Request $request)
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            if (!$tenant) {
                return response()->json(['error' => 'Tenant não identificado'], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'social_media' => $tenant->getSocialMedia(),
                    'whatsapp' => $this->extractWhatsAppFromSocialMedia($tenant->getSocialMedia()),
                    'contact_buttons' => $this->generateContactButtons($tenant)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/portal/tenant/business-hours",
     *     summary="Horário de funcionamento do tenant",
     *     description="Retorna horário de funcionamento e disponibilidade do tenant",
     *     tags={"1. Portal Público"},
     *     @OA\Response(
     *         response=200,
     *         description="Horário de funcionamento retornado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Tenant não identificado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getTenantBusinessHours(Request $request)
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            if (!$tenant) {
                return response()->json(['error' => 'Tenant não identificado'], 400);
            }

            $businessHours = $tenant->getBusinessHours();
            $isOpen = $this->isBusinessOpen($businessHours);

            return response()->json([
                'success' => true,
                'data' => [
                    'business_hours' => $businessHours,
                    'is_open' => $isOpen,
                    'current_status' => $isOpen ? 'Aberto' : 'Fechado',
                    'next_opening' => $this->getNextOpeningTime($businessHours),
                    'today_hours' => $this->getTodayHours($businessHours)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/portal/tenant/contact",
     *     summary="Informações de contato do tenant",
     *     description="Retorna informações de contato e localização do tenant",
     *     tags={"1. Portal Público"},
     *     @OA\Response(
     *         response=200,
     *         description="Informações de contato retornadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Tenant não identificado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getTenantContact(Request $request)
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'error' => 'Tenant não identificado'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'contact_info' => $tenant->getContactInfo(),
                    'address_formatted' => $this->formatAddress($tenant->address),
                    'contact_methods' => [
                        'email' => $tenant->contact_email ?? $tenant->email,
                        'phone' => $tenant->contact_phone ?? $tenant->phone,
                        'whatsapp' => $this->extractWhatsAppFromSocialMedia($tenant->getSocialMedia())
                    ],
                    'quick_actions' => [
                        'call' => $tenant->contact_phone ?? $tenant->phone,
                        'whatsapp' => $this->extractWhatsAppFromSocialMedia($tenant->getSocialMedia()),
                        'email' => $tenant->contact_email ?? $tenant->email
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/portal/tenant/portal-config",
     *     summary="Configurações gerais do portal",
     *     description="Retorna todas as configurações do portal para o tenant",
     *     tags={"1. Portal Público"},
     *     @OA\Response(
     *         response=200,
     *         description="Configurações do portal retornadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Tenant não identificado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getTenantPortalConfig(Request $request)
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'error' => 'Tenant não identificado'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'portal_config' => $tenant->getPortalConfig(),
                    'features' => $tenant->features ?? [],
                    'settings' => [
                        'allow_registration' => $tenant->allowsRegistration(),
                        'require_approval' => $tenant->requiresApproval(),
                        'is_default' => $tenant->isDefault()
                    ],
                    'cache_info' => [
                        'cache_enabled' => true,
                        'cache_ttl' => 3600
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Gera cor secundária baseada na cor primária
     */
    private function generateSecondaryColor(string $primaryColor): string
    {
        // Converter hex para HSL e ajustar
        $hex = ltrim($primaryColor, '#');
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max == $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            switch ($max) {
                case $r: $h = ($g - $b) / $d + ($g < $b ? 6 : 0); break;
                case $g: $h = ($b - $r) / $d + 2; break;
                case $b: $h = ($r - $g) / $d + 4; break;
            }
            $h /= 6;
        }

        // Ajustar luminosidade para cor secundária
        $l = max(0.3, min(0.7, $l + 0.1));

        return $this->hslToHex($h, $s, $l);
    }

    /**
     * Gera cor de destaque baseada na cor primária
     */
    private function generateAccentColor(string $primaryColor): string
    {
        // Converter hex para HSL e ajustar
        $hex = ltrim($primaryColor, '#');
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max == $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            switch ($max) {
                case $r: $h = ($g - $b) / $d + ($g < $b ? 6 : 0); break;
                case $g: $h = ($b - $r) / $d + 2; break;
                case $b: $h = ($r - $g) / $d + 4; break;
            }
            $h /= 6;
        }

        // Ajustar matiz para cor de destaque (complementar)
        $h = fmod($h + 0.5, 1.0);
        $s = min(1.0, $s + 0.2);
        $l = max(0.4, min(0.8, $l));

        return $this->hslToHex($h, $s, $l);
    }

    /**
     * Converte HSL para Hex
     */
    private function hslToHex(float $h, float $s, float $l): string
    {
        if ($s == 0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = $this->hue2rgb($p, $q, $h + 1/3);
            $g = $this->hue2rgb($p, $q, $h);
            $b = $this->hue2rgb($p, $q, $h - 1/3);
        }

        return sprintf("#%02x%02x%02x", round($r * 255), round($g * 255), round($b * 255));
    }

    /**
     * Converte matiz para RGB
     */
    private function hue2rgb(float $p, float $q, float $t): float
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1/2) return $q;
        if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
        return $p;
    }

    /**
     * Extrai WhatsApp das redes sociais
     */
    private function extractWhatsAppFromSocialMedia(array $socialMedia): ?string
    {
        if (isset($socialMedia['whatsapp'])) {
            return $socialMedia['whatsapp'];
        }

        // Procurar por URLs que contenham whatsapp
        foreach ($socialMedia as $platform => $url) {
            if (stripos($url, 'whatsapp') !== false || stripos($url, 'wa.me') !== false) {
                return $url;
            }
        }

        return null;
    }

    /**
     * Gera botões de contato
     */
    private function generateContactButtons(Tenant $tenant): array
    {
        $buttons = [];

        if ($tenant->contact_phone || $tenant->phone) {
            $buttons['phone'] = [
                'type' => 'phone',
                'label' => 'Ligar',
                'value' => $tenant->contact_phone ?? $tenant->phone,
                'icon' => 'phone'
            ];
        }

        if ($tenant->contact_email || $tenant->email) {
            $buttons['email'] = [
                'type' => 'email',
                'label' => 'Email',
                'value' => $tenant->contact_email ?? $tenant->email,
                'icon' => 'mail'
            ];
        }

        $whatsapp = $this->extractWhatsAppFromSocialMedia($tenant->getSocialMedia());
        if ($whatsapp) {
            $buttons['whatsapp'] = [
                'type' => 'whatsapp',
                'label' => 'WhatsApp',
                'value' => $whatsapp,
                'icon' => 'whatsapp'
            ];
        }

        return $buttons;
    }

    /**
     * Verifica se o negócio está aberto
     */
    private function isBusinessOpen(array $businessHours): bool
    {
        $today = strtolower(date('l'));
        $currentTime = date('H:i');

        if (!isset($businessHours[$today]) || empty($businessHours[$today])) {
            return false;
        }

        $hours = $businessHours[$today];
        if (count($hours) !== 2) {
            return false;
        }

        $openTime = $hours[0];
        $closeTime = $hours[1];

        return $currentTime >= $openTime && $currentTime <= $closeTime;
    }

    /**
     * Obtém próximo horário de abertura
     */
    private function getNextOpeningTime(array $businessHours): ?string
    {
        $today = strtolower(date('l'));
        $currentTime = date('H:i');

        // Verificar se hoje ainda abre
        if (isset($businessHours[$today]) && !empty($businessHours[$today])) {
            $hours = $businessHours[$today];
            if (count($hours) === 2 && $currentTime < $hours[0]) {
                return $hours[0];
            }
        }

        // Procurar próximo dia
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $currentDayIndex = array_search($today, $days);

        for ($i = 1; $i <= 7; $i++) {
            $nextDayIndex = ($currentDayIndex + $i) % 7;
            $nextDay = $days[$nextDayIndex];

            if (isset($businessHours[$nextDay]) && !empty($businessHours[$nextDay])) {
                return $businessHours[$nextDay][0];
            }
        }

        return null;
    }

    /**
     * Obtém horários de hoje
     */
    private function getTodayHours(array $businessHours): ?array
    {
        $today = strtolower(date('l'));

        if (isset($businessHours[$today]) && !empty($businessHours[$today])) {
            return [
                'day' => ucfirst($today),
                'hours' => $businessHours[$today],
                'is_open' => $this->isBusinessOpen($businessHours)
            ];
        }

        return null;
    }

    /**
     * Formata endereço
     */
    private function formatAddress(?string $address): string
    {
        if (!$address) {
            return 'Endereço não informado';
        }

        // Formatação básica - pode ser melhorada
        return trim($address);
    }

    /**
     * Obtém o tenant atual
     */
    private function getCurrentTenant(Request $request): ?Tenant
    {
        try {
            // 1. Tentar obter do request attributes (setado pelo middleware)
            if ($request->attributes->has('current_tenant')) {
                return $request->attributes->get('current_tenant');
            }

            // 2. Tentar por header
            $subdomain = $request->header('X-Tenant-Subdomain');
            if ($subdomain) {
                return Tenant::where('subdomain', $subdomain)
                    ->where('status', 'active')
                    ->first();
            }

            // 3. Tentar por subdomínio da URL
            $host = $request->getHost();
            $parts = explode('.', $host);

            // Para localhost e desenvolvimento, aceitar 2 partes (demo.localhost)
            if (count($parts) >= 2) {
                $firstPart = $parts[0];
                $secondPart = $parts[1];

                // Se a segunda parte é localhost, 127.0.0.1, ou similar, aceitar
                if (in_array($secondPart, ['localhost', '127.0.0.1', 'local', 'test', 'dev'])) {
                    if ($firstPart !== 'www' && $firstPart !== 'api') {
                        return Tenant::where('subdomain', $firstPart)
                            ->where('status', 'active')
                            ->first();
                    }
                }

                // Para produção, exigir 3 ou mais partes
                if (count($parts) >= 3) {
                    $subdomain = $firstPart;
                    if ($subdomain !== 'www' && $subdomain !== 'api') {
                        return Tenant::where('subdomain', $subdomain)
                            ->where('status', 'active')
                            ->first();
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar tenant no PortalController', [
                'error' => $e->getMessage(),
                'host' => $request->getHost(),
                'headers' => $request->headers->all()
            ]);

            return null;
        }
    }

    /**
     * Aplica filtros na query
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('model_id')) {
            $query->where('model_id', $request->model_id);
        }

        if ($request->filled('min_price') && $request->filled('max_price')) {
            $query->whereBetween('price', [$request->min_price, $request->max_price]);
        }

        if ($request->filled('min_year') && $request->filled('max_year')) {
            $query->whereBetween('year', [$request->min_year, $request->max_year]);
        }

        if ($request->filled('fuel_type')) {
            $query->where('fuel_type', $request->fuel_type);
        }

        if ($request->filled('transmission')) {
            $query->where('transmission', $request->transmission);
        }
    }

    /**
     * Transforma veículo para o formato do portal
     */
    private function transformVehicleForPortal($vehicle, $detailed = false)
    {
        $data = [
            'id' => $vehicle->id,
            'title' => $vehicle->title,
            'brand' => $vehicle->brand ? [
                'id' => $vehicle->brand->id,
                'name' => $vehicle->brand->name,
                'slug' => $vehicle->brand->slug
            ] : null,
            'model' => $vehicle->model ? [
                'id' => $vehicle->model->id,
                'name' => $vehicle->model->name,
                'slug' => $vehicle->model->slug
            ] : null,
            'year' => $vehicle->year,
            'price' => $vehicle->price,
            'mileage' => $vehicle->mileage,
            'fuel_type' => $vehicle->fuel_type,
            'transmission' => $vehicle->transmission,
            'color' => $vehicle->color,
            'status' => $vehicle->status,
            'main_image' => $vehicle->primaryImage ? [
                'id' => $vehicle->primaryImage->id,
                'url' => url("/api/public/images/{$vehicle->tenant_id}/{$vehicle->id}/{$vehicle->primaryImage->filename}"),
                'filename' => $vehicle->primaryImage->filename
            ] : null,
            'video_link' => $vehicle->video_link,
            'created_at' => $vehicle->created_at,
            'updated_at' => $vehicle->updated_at,
        ];

        if ($detailed) {
            $data['description'] = $vehicle->description;
            $data['features'] = $vehicle->features ? $vehicle->features->pluck('name') : [];
            $data['images'] = $vehicle->images->map(function ($image) use ($vehicle) {
                return [
                    'id' => $image->id,
                    'url' => url("/api/public/images/{$vehicle->tenant_id}/{$vehicle->id}/{$image->filename}"),
                    'filename' => $image->filename,
                    'is_primary' => $image->is_primary
                ];
            });
            $data['slug'] = $vehicle->slug;
            $data['views'] = $vehicle->views;
            $data['video_link'] = $vehicle->video_link;

            // Debug log
            Log::info('Video link debug', [
                'vehicle_id' => $vehicle->id,
                'video_link' => $vehicle->video_link,
                'data_video_link' => $data['video_link']
            ]);
        }

        return $data;
    }

    /**
     * @OA\Get(
     *     path="/api/portal/stats",
     *     summary="Estatísticas do portal",
     *     description="Retorna estatísticas gerais do portal para o tenant atual",
     *     tags={"1. Portal Público"},
     *     @OA\Response(
     *         response=200,
     *         description="Estatísticas retornadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Tenant não identificado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getPortalStats(Request $request)
    {
        try {
            $tenant = $this->getCurrentTenant($request);

            if (!$tenant) {
                return response()->json(['error' => 'Tenant não identificado'], 400);
            }

            // Estatísticas de veículos
            $vehicleStats = Vehicle::byTenant($tenant->id)
                ->selectRaw('
                    COUNT(*) as total_vehicles,
                    COUNT(CASE WHEN status = "active" THEN 1 END) as active_vehicles,
                    COUNT(CASE WHEN status = "sold" THEN 1 END) as sold_vehicles,
                    MIN(price) as min_price,
                    MAX(price) as max_price,
                    AVG(price) as avg_price,
                    MIN(year) as min_year,
                    MAX(year) as max_year
                ')
                ->first();

            // Estatísticas de leads
            $leadStats = Lead::where('tenant_id', $tenant->id)
                ->selectRaw('
                    COUNT(*) as total_leads,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as leads_last_30_days,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as leads_last_7_days
                ')
                ->first();

            // Estatísticas de marcas
            $brandStats = Vehicle::byTenant($tenant->id)
                ->join('vehicle_brands', 'vehicles.brand_id', '=', 'vehicle_brands.id')
                ->selectRaw('
                    vehicle_brands.name as brand_name,
                    COUNT(*) as vehicle_count
                ')
                ->groupBy('vehicle_brands.id', 'vehicle_brands.name')
                ->orderBy('vehicle_count', 'desc')
                ->limit(5)
                ->get();

            // Estatísticas de combustível
            $fuelStats = Vehicle::byTenant($tenant->id)
                ->selectRaw('
                    fuel_type,
                    COUNT(*) as count
                ')
                ->groupBy('fuel_type')
                ->get();

            // Estatísticas de transmissão
            $transmissionStats = Vehicle::byTenant($tenant->id)
                ->selectRaw('
                    transmission,
                    COUNT(*) as count
                ')
                ->groupBy('transmission')
                ->get();

            $stats = [
                'vehicles' => [
                    'total' => $vehicleStats->total_vehicles ?? 0,
                    'active' => $vehicleStats->active_vehicles ?? 0,
                    'sold' => $vehicleStats->sold_vehicles ?? 0,
                    'price_range' => [
                        'min' => $vehicleStats->min_price ?? 0,
                        'max' => $vehicleStats->max_price ?? 0,
                        'average' => round($vehicleStats->avg_price ?? 0, 2)
                    ],
                    'year_range' => [
                        'min' => $vehicleStats->min_year ?? date('Y'),
                        'max' => $vehicleStats->max_year ?? date('Y')
                    ]
                ],
                'leads' => [
                    'total' => $leadStats->total_leads ?? 0,
                    'last_30_days' => $leadStats->leads_last_30_days ?? 0,
                    'last_7_days' => $leadStats->leads_last_7_days ?? 0
                ],
                'top_brands' => $brandStats,
                'fuel_distribution' => $fuelStats,
                'transmission_distribution' => $transmissionStats,
                'last_updated' => now()->toISOString()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas do portal', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }
}

/**
 * @OA\Schema(
 *     schema="VehiclePortal",
 *     title="Veículo para Portal",
 *     description="Dados básicos do veículo para o portal de anúncios",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Honda Civic 2020 2.0 EXL"),
 *     @OA\Property(property="brand", ref="#/components/schemas/VehicleBrand"),
 *     @OA\Property(property="model", ref="#/components/schemas/VehicleModel"),
 *     @OA\Property(property="year", type="integer", example=2020),
 *     @OA\Property(property="price", type="number", format="float", example=85000.00),
 *     @OA\Property(property="mileage", type="integer", example=45000),
 *     @OA\Property(property="fuel_type", type="string", example="flex"),
 *     @OA\Property(property="transmission", type="string", example="automatica"),
 *     @OA\Property(property="color", type="string", example="Prata"),
 *     @OA\Property(property="status", type="string", example="available"),
 *     @OA\Property(property="main_image", ref="#/components/schemas/VehicleImage"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="VehiclePortalDetailed",
 *     title="Veículo Detalhado para Portal",
 *     description="Dados completos do veículo para o portal de anúncios",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/VehiclePortal"),
 *         @OA\Schema(
 *             @OA\Property(property="description", type="string", example="Veículo em excelente estado..."),
 *             @OA\Property(property="features", type="array", @OA\Items(type="string"), example={"Ar condicionado", "Direção elétrica"}),
 *             @OA\Property(property="images", type="array", @OA\Items(ref="#/components/schemas/VehicleImage")),
 *             @OA\Property(property="slug", type="string", example="honda-civic-2020-2-0-exl"),
 *             @OA\Property(property="views", type="integer", example=125)
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="VehicleBrand",
 *     title="Marca do Veículo",
 *     @OA\Property(property="id", type="integer", example=25),
 *     @OA\Property(property="name", type="string", example="Honda"),
 *     @OA\Property(property="slug", type="string", example="honda")
 * )
 *
 * @OA\Schema(
 *     schema="VehicleModel",
 *     title="Modelo do Veículo",
 *     @OA\Property(property="id", type="integer", example=150),
 *     @OA\Property(property="name", type="string", example="Civic"),
 *     @OA\Property(property="slug", type="string", example="civic")
 * )
 *
 * @OA\Schema(
 *     schema="VehicleImage",
 *     title="Imagem do Veículo",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="url", type="string", format="uri", example="https://api.exemplo.com/images/1/1/foto.jpg"),
 *     @OA\Property(property="filename", type="string", example="foto.jpg"),
 *     @OA\Property(property="is_primary", type="boolean", example=true)
 * )
 *
 * @OA\Schema(
 *     schema="Pagination",
 *     title="Paginação",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="per_page", type="integer", example=12),
 *     @OA\Property(property="total", type="integer", example=150),
 *     @OA\Property(property="last_page", type="integer", example=13),
 *     @OA\Property(property="from", type="integer", example=1),
 *     @OA\Property(property="to", type="integer", example=12)
 * )
 *
 * @OA\Schema(
 *     schema="TenantPortal",
 *     title="Tenant do Portal",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Empresa Demo"),
 *     @OA\Property(property="subdomain", type="string", example="demo"),
 *     @OA\Property(property="custom_domain", type="string", example="www.empresademo.com")
 * )
 *
 * @OA\Schema(
 *     schema="TenantPortalInfo",
 *     title="Informações Completas do Tenant",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Empresa Demo"),
 *     @OA\Property(property="subdomain", type="string", example="demo"),
 *     @OA\Property(property="custom_domain", type="string", example="www.empresademo.com"),
 *     @OA\Property(property="description", type="string", example="Portal de anúncios da Empresa Demo"),
 *     @OA\Property(property="contact_email", type="string", format="email", example="contato@demo.com"),
 *     @OA\Property(property="contact_phone", type="string", example="(11) 99999-9999"),
 *     @OA\Property(property="address", type="string", example="Rua das Flores, 123 - São Paulo/SP"),
 *     @OA\Property(property="theme_color", type="string", example="#007bff"),
 *     @OA\Property(property="logo_url", type="string", format="uri", example="https://exemplo.com/logo.png"),
 *     @OA\Property(property="social_media", type="object", example={"facebook": "https://facebook.com/demo", "instagram": "https://instagram.com/demo"}),
 *     @OA\Property(property="business_hours", type="object", example={"monday": ["09:00", "18:00"], "tuesday": ["09:00", "18:00"]})
 * )
 *
 * @OA\Schema(
 *     schema="PortalFilters",
 *     title="Filtros do Portal",
 *     @OA\Property(property="brands", type="array", @OA\Items(ref="#/components/schemas/VehicleBrand")),
 *     @OA\Property(property="models", type="array", @OA\Items(ref="#/components/schemas/VehicleModel")),
 *     @OA\Property(property="fuel_types", type="array", @OA\Items(type="string"), example={"flex", "gasolina", "diesel"}),
 *     @OA\Property(property="transmissions", type="array", @OA\Items(type="string"), example={"manual", "automatica"}),
 *     @OA\Property(property="price_range", ref="#/components/schemas/PriceRange"),
 *     @OA\Property(property="year_range", ref="#/components/schemas/YearRange")
 * )
 *
 * @OA\Schema(
 *     schema="PriceRange",
 *     title="Faixa de Preço",
 *     @OA\Property(property="min", type="number", format="float", example=10000.00),
 *     @OA\Property(property="max", type="number", format="float", example=200000.00)
 * )
 *
 * @OA\Schema(
 *     schema="YearRange",
 *     title="Faixa de Ano",
 *     @OA\Property(property="min", type="integer", example=2010),
 *     @OA\Property(property="max", type="integer", example=2024)
 * )
 *
 * @OA\Schema(
 *     schema="LeadCreated",
 *     title="Lead Criado",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="João Silva"),
 *     @OA\Property(property="email", type="string", format="email", example="joao@email.com"),
 *     @OA\Property(property="phone", type="string", example="(11) 99999-9999"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
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
 *
 * @OA\Schema(
 *     schema="PageViewStats",
 *     title="Estatísticas de Visualizações de Página",
 *     @OA\Property(property="period", ref="#/components/schemas/AnalyticsPeriod"),
 *     @OA\Property(property="stats", type="array", @OA\Items(ref="#/components/schemas/DailyPageViewStats")),
 *     @OA\Property(property="summary", ref="#/components/schemas/PageViewSummary")
 * )
 *
 * @OA\Schema(
 *     schema="DailyPageViewStats",
 *     title="Estatísticas Diárias de Visualizações",
 *     @OA\Property(property="metric_name", type="string", example="home_page"),
 *     @OA\Property(property="date", type="string", format="date", example="2024-08-23"),
 *     @OA\Property(property="views", type="integer", example=45),
 *     @OA\Property(property="unique_visitors", type="integer", example=32)
 * )
 *
 * @OA\Schema(
 *     schema="PageViewSummary",
 *     title="Resumo de Visualizações",
 *     @OA\Property(property="total_views", type="integer", example=1250),
 *     @OA\Property(property="total_unique_visitors", type="integer", example=890),
 *     @OA\Property(property="pages_tracked", type="integer", example=5)
 * )
 *
 * @OA\Schema(
 *     schema="LeadStats",
 *     title="Estatísticas de Leads",
 *     @OA\Property(property="period", ref="#/components/schemas/AnalyticsPeriod"),
 *     @OA\Property(property="daily_stats", type="array", @OA\Items(ref="#/components/schemas/DailyLeadStats")),
 *     @OA\Property(property="summary", ref="#/components/schemas/LeadSummary")
 * )
 *
 * @OA\Schema(
 *     schema="DailyLeadStats",
 *     title="Estatísticas Diárias de Leads",
 *     @OA\Property(property="date", type="string", format="date", example="2024-08-23"),
 *     @OA\Property(property="leads_created", type="integer", example=3),
 *     @OA\Property(property="unique_sources", type="integer", example=3)
 * )
 *
 * @OA\Schema(
 *     schema="LeadSummary",
 *     title="Resumo de Leads",
 *     @OA\Property(property="total_leads", type="integer", example=45),
 *     @OA\Property(property="total_days", type="integer", example=30),
 *     @OA\Property(property="average_leads_per_day", type="number", format="float", example=1.5)
 * )
 *
 * @OA\Schema(
 *     schema="SearchStats",
 *     title="Estatísticas de Busca",
 *     @OA\Property(property="period", ref="#/components/schemas/AnalyticsPeriod"),
 *     @OA\Property(property="top_searches", type="array", @OA\Items(ref="#/components/schemas/SearchTermStats")),
 *     @OA\Property(property="summary", ref="#/components/schemas/SearchSummary")
 * )
 *
 * @OA\Schema(
 *     schema="SearchSummary",
 *     title="Resumo de Buscas",
 *     @OA\Property(property="total_searches", type="integer", example=180),
 *     @OA\Property(property="unique_search_terms", type="integer", example=45),
 *     @OA\Property(property="average_searches_per_term", type="number", format="float", example=4.0)
 * )
 */
