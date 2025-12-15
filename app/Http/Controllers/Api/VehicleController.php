<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use App\Helpers\UrlHelper;
use App\Models\TenantUser;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Helpers\TokenHelper;

/**
 * @OA\Tag(
 *     name="2. Admin Cliente",
 *     description="Endpoints para administradores do tenant (autenticação e gestão)"
 * )
 *
 * @OA\Schema(
 *     schema="Vehicle",
 *     title="Veículo",
 *     description="Modelo de dados para veículos",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Honda Civic 2023"),
 *     @OA\Property(property="url", type="string", example="honda-civic-2023", description="Slug do título para URLs amigáveis"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Veículo em excelente estado"),
 *     @OA\Property(property="brand_id", type="integer", example=1),
 *     @OA\Property(property="model_id", type="integer", example=1),
 *     @OA\Property(property="year", type="integer", example=2023),
 *     @OA\Property(property="price", type="number", format="float", example=85000.00),
 *     @OA\Property(property="mileage", type="integer", example=15000),
 *     @OA\Property(property="fuel_type", type="string", enum={"flex", "gasolina", "diesel", "eletrico", "hibrido", "gnv"}, example="flex"),
 *     @OA\Property(property="transmission", type="string", enum={"manual", "automatica", "cvt", "automatizada"}, example="automatica"),
 *     @OA\Property(property="color", type="string", example="Preto"),
 *     @OA\Property(property="doors", type="integer", example=4),
 *     @OA\Property(property="seats", type="integer", example=5),
 *     @OA\Property(property="engine_size", type="string", example="2.0"),
 *     @OA\Property(property="power", type="string", example="150cv"),
 *     @OA\Property(property="consumption_city", type="string", nullable=true, example="12.5 km/l"),
 *     @OA\Property(property="consumption_highway", type="string", nullable=true, example="16.2 km/l"),
 *     @OA\Property(property="status", type="string", enum={"available", "sold", "reserved", "maintenance"}, example="available"),
 *     @OA\Property(property="is_featured", type="boolean", nullable=true, example=true),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="tenant_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="brand", ref="#/components/schemas/VehicleBrand"),
 *     @OA\Property(property="model", ref="#/components/schemas/VehicleModel"),
     @OA\Property(property="main_image", type="object", nullable=true, description="Imagem principal do veículo"),
 *     @OA\Property(property="images", type="array", @OA\Items(ref="#/components/schemas/VehicleImage"))
 * )
 *
 * @OA\Schema(
 *     schema="VehicleImage",
 *     title="Imagem do Veículo",
 *     description="Modelo de dados para imagens de veículos",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="vehicle_id", type="integer", example=1),
 *     @OA\Property(property="filename", type="string", example="1234567890_abc123.jpg"),
 *     @OA\Property(property="original_name", type="string", example="civic_front.jpg"),
 *     @OA\Property(property="path", type="string", example="tenants/1/vehicles/1/1234567890_abc123.jpg"),
 *     @OA\Property(property="url", type="string", example="honda-civic-2023", description="Slug do título do veículo para URLs amigáveis"),
     @OA\Property(property="image_url", type="string", example="http://localhost:8000/storage/tenants/1/vehicles/1/1234567890_abc123.jpg", description="URL completa da imagem"),
 *     @OA\Property(property="size", type="integer", example=1024000),
 *     @OA\Property(property="mime_type", type="string", example="image/jpeg"),
 *     @OA\Property(property="width", type="integer", nullable=true, example=1920),
 *     @OA\Property(property="height", type="integer", nullable=true, example=1080),
 *     @OA\Property(property="is_primary", type="boolean", example=true),
 *     @OA\Property(property="sort_order", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="VehicleBrand",
 *     title="Marca de Veículo",
 *     description="Modelo de dados para marcas de veículos",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Honda"),
 *     @OA\Property(property="slug", type="string", example="honda"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Marca japonesa de veículos"),
 *     @OA\Property(property="logo_url", type="string", nullable=true, example="https://example.com/honda-logo.png"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="sort_order", type="integer", example=0),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="VehicleModel",
 *     title="Modelo de Veículo",
 *     description="Modelo de dados para modelos de veículos",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="brand_id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Civic"),
 *     @OA\Property(property="slug", type="string", example="civic"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Sedan compacto"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="sort_order", type="integer", example=0),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="brand", ref="#/components/schemas/VehicleBrand"))
 * )
 */
class VehicleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/vehicles",
     *     summary="Listar veículos",
     *     description="Retorna uma lista paginada de veículos do tenant",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Número de itens por página",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Termo de busca",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="brand_id",
     *         in="query",
     *         required=false,
     *         description="ID da marca",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="model_id",
     *         in="query",
     *         required=false,
     *         description="ID do modelo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Status do veículo",
     *         @OA\Schema(type="string", enum={"available", "sold", "reserved", "maintenance"})
     *     ),
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         required=false,
     *         description="Preço mínimo",
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         required=false,
     *         description="Preço máximo",
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="min_year",
     *         in="query",
     *         required=false,
     *         description="Ano mínimo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="max_year",
     *         in="query",
     *         required=false,
     *         description="Ano máximo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="fuel_type",
     *         in="query",
     *         required=false,
     *         description="Tipo de combustível",
     *         @OA\Schema(type="string", enum={"flex", "gasolina", "diesel", "eletrico", "hibrido", "gnv"})
     *     ),
     *     @OA\Parameter(
     *         name="transmission",
     *         in="query",
     *         required=false,
     *         description="Tipo de transmissão",
     *         @OA\Schema(type="string", enum={"manual", "automatica", "cvt", "automatizada"})
     *     ),
     *     @OA\Parameter(
     *         name="featured",
     *         in="query",
     *         required=false,
     *         description="Apenas veículos em destaque",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         required=false,
     *         description="Campo para ordenação",
     *         @OA\Schema(type="string", default="created_at")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         required=false,
     *         description="Ordem da ordenação",
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de veículos retornada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Vehicle")),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="per_page", type="integer", example=15),
     *             @OA\Property(property="total", type="integer", example=100),
     *             @OA\Property(property="last_page", type="integer", example=7)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Usuário não autenticado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = TokenHelper::getAuthenticatedUser($request);

            if (!$user) {
                return response()->json(['error' => 'Usuário não autenticado'], 401);
            }

        $perPage = $request->get('per_page', 15);

        $query = Vehicle::byTenant($user->tenant_id)
            ->with(['brand', 'model', 'primaryImage', 'createdBy'])
            ->active();

        // Filtros
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('brand_id')) {
            $query->byBrand($request->brand_id);
        }

        if ($request->filled('model_id')) {
            $query->byModel($request->model_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

            // Excluir status específicos
            if ($request->filled('exclude_status')) {
                $excludeStatuses = explode(',', $request->exclude_status);
                $query->whereNotIn('status', $excludeStatuses);
        }

        if ($request->filled('min_price') && $request->filled('max_price')) {
            $query->byPriceRange($request->min_price, $request->max_price);
        }

        if ($request->filled('min_year') && $request->filled('max_year')) {
            $query->byYearRange($request->min_year, $request->max_year);
        }

        if ($request->filled('fuel_type')) {
            $query->byFuelType($request->fuel_type);
        }

        if ($request->filled('transmission')) {
            $query->byTransmission($request->transmission);
        }

        if ($request->filled('featured')) {
            $query->featured();
        }

        // Ordenação
            $sortBy = $request->get('sort_by', $request->get('sort', 'created_at'));
            $sortOrder = $request->get('sort_order', $request->get('order', 'desc'));
        $query->orderBy($sortBy, $sortOrder);

        $vehicles = $query->paginate($perPage);

        // Processar cada veículo para incluir a imagem principal
        $vehiclesData = $vehicles->items();
        foreach ($vehiclesData as $vehicle) {
                // Converter para array para evitar atualizações acidentais no banco
                $vehicleArray = $vehicle->toArray();

            // Adicionar a imagem principal se existir
            if ($vehicle->primaryImage) {
                    $vehicleArray['main_image'] = [
                    'id' => $vehicle->primaryImage->id,
                    'filename' => $vehicle->primaryImage->filename,
                    'url' => $vehicle->primaryImage->url,
                    'image_url' => url("/api/public/images/{$vehicle->tenant_id}/{$vehicle->id}/{$vehicle->primaryImage->filename}"),
                    'is_primary' => true,
                    'size' => $vehicle->primaryImage->size,
                    'mime_type' => $vehicle->primaryImage->mime_type,
                ];
            } else {
                    $vehicleArray['main_image'] = null;
                }

                // Adicionar relacionamentos se solicitados
                if ($request->filled('include')) {
                    $includes = explode(',', $request->include);

                    if (in_array('images', $includes)) {
                        $vehicleArray['images'] = $vehicle->images()->orderBy('sort_order')->get()->map(function ($image) use ($vehicle) {
                            return [
                                'id' => $image->id,
                                'filename' => $image->filename,
                                'url' => $image->url,
                                'image_url' => url("/api/public/images/{$vehicle->tenant_id}/{$vehicle->id}/{$image->filename}"),
                                'is_primary' => $image->is_primary,
                                'size' => $image->size,
                                'mime_type' => $image->mime_type,
                                'sort_order' => $image->sort_order,
                            ];
                        });
                    }
                }

                // Substituir o item original pelo array processado
                $vehiclesData[array_search($vehicle, $vehiclesData)] = $vehicleArray;
        }

        return response()->json([
            'data' => $vehiclesData,
            'current_page' => $vehicles->currentPage(),
            'per_page' => $vehicles->perPage(),
            'total' => $vehicles->total(),
            'last_page' => $vehicles->lastPage(),
        ]);
        } catch (\Exception $e) {
            Log::error('Erro ao listar veículos: ' . $e->getMessage(), [
                'user_id' => $user->id ?? null,
                'tenant_id' => $user->tenant_id ?? null,
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Erro interno do servidor ao listar veículos',
                'message' => config('app.debug') ? $e->getMessage() : 'Tente novamente mais tarde'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/vehicles/{id}",
     *     summary="Exibir veículo específico",
     *     tags={"Vehicles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do veículo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dados do veículo",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Veículo não encontrado")
     * )
     */
    public function show(Request $request, $id)
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $vehicle = Vehicle::byTenant($user->tenant_id)
            ->with(['brand', 'model', 'images', 'features', 'createdBy', 'updatedBy'])
            ->find($id);

        if (!$vehicle) {
            return response()->json(['error' => 'Veículo não encontrado'], 404);
        }

        // Incrementar visualizações
        $vehicle->incrementViews();

        return response()->json([
            'data' => $vehicle
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/vehicles",
     *     summary="Criar novo veículo",
     *     tags={"Vehicles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"brand_id", "model_id"},
     *             @OA\Property(property="brand_id", type="integer", description="ID da marca do veículo"),
     *             @OA\Property(property="model_id", type="integer", description="ID do modelo do veículo"),
     *             @OA\Property(property="vehicle_type", type="string", enum={"car", "motorcycle", "truck", "suv", "pickup", "van", "bus", "other"}, description="Tipo de veículo"),
     *             @OA\Property(property="condition", type="string", enum={"new", "used"}, description="Condição do veículo"),
     *             @OA\Property(property="title", type="string", maxLength=255, description="Título do anúncio"),
     *             @OA\Property(property="version", type="string", maxLength=255, nullable=true, description="Versão do veículo"),
     *             @OA\Property(property="year", type="integer", minimum=1900, description="Ano de fabricação"),
     *             @OA\Property(property="model_year", type="integer", minimum=1900, description="Ano do modelo"),
     *             @OA\Property(property="color", type="string", maxLength=100, description="Cor do veículo"),
     *             @OA\Property(property="fuel_type", type="string", enum={"flex", "gasolina", "diesel", "eletrico", "hibrido", "gnv"}, description="Tipo de combustível"),
     *             @OA\Property(property="transmission", type="string", enum={"manual", "automatica", "cvt", "automatizada"}, description="Tipo de transmissão"),
     *             @OA\Property(property="doors", type="integer", minimum=2, maximum=5, description="Número de portas"),
     *             @OA\Property(property="mileage", type="integer", minimum=0, description="Quilometragem"),
     *             @OA\Property(property="hide_mileage", type="boolean", nullable=true, description="Não exibir quilometragem"),
     *             @OA\Property(property="price", type="number", minimum=0, description="Preço de venda"),
     *             @OA\Property(property="classified_price", type="number", minimum=0, nullable=true, description="Preço do classificado"),
     *             @OA\Property(property="cost_type", type="string", maxLength=100, nullable=true, description="Tipo de custo"),
     *             @OA\Property(property="fipe_price", type="number", minimum=0, nullable=true, description="Preço FIPE"),
     *             @OA\Property(property="accept_financing", type="boolean", nullable=true, description="Aceita financiamento"),
     *             @OA\Property(property="accept_exchange", type="boolean", nullable=true, description="Aceita troca"),
     *             @OA\Property(property="engine", type="string", maxLength=255, nullable=true, description="Motor"),
     *             @OA\Property(property="power", type="string", maxLength=255, nullable=true, description="Potência"),
     *             @OA\Property(property="torque", type="string", maxLength=255, nullable=true, description="Torque"),
     *             @OA\Property(property="consumption_city", type="string", maxLength=255, nullable=true, description="Consumo na cidade"),
     *             @OA\Property(property="consumption_highway", type="string", maxLength=255, nullable=true, description="Consumo na estrada"),
     *             @OA\Property(property="description", type="string", nullable=true, description="Descrição geral"),
     *             @OA\Property(property="use_same_observation", type="boolean", nullable=true, description="Usar mesma observação do site"),
     *             @OA\Property(property="custom_observation", type="string", nullable=true, description="Observação personalizada"),
     *             @OA\Property(property="classified_observations", type="array", nullable=true, description="Observações por classificado", @OA\Items(type="string")),
     *             @OA\Property(property="standard_features", type="array", nullable=true, description="Características padrão selecionadas", @OA\Items(type="string")),
     *             @OA\Property(property="optional_features", type="array", nullable=true, description="Opcionais selecionados", @OA\Items(type="string")),
     *             @OA\Property(property="plate", type="string", maxLength=10, nullable=true, description="Placa do veículo"),
     *             @OA\Property(property="chassi", type="string", maxLength=17, nullable=true, description="Chassi"),
     *             @OA\Property(property="renavam", type="string", maxLength=11, nullable=true, description="RENAVAM"),
     *             @OA\Property(property="video_link", type="string", maxLength=500, nullable=true, description="Link do vídeo"),
     *             @OA\Property(property="owner_name", type="string", maxLength=255, nullable=true, description="Nome do proprietário"),
     *             @OA\Property(property="owner_phone", type="string", maxLength=20, nullable=true, description="Telefone do proprietário"),
     *             @OA\Property(property="owner_email", type="string", maxLength=255, nullable=true, description="Email do proprietário"),
     *             @OA\Property(property="is_featured", type="boolean", nullable=true, description="Veículo em destaque"),
     *             @OA\Property(property="is_licensed", type="boolean", nullable=true, description="Veículo licenciado"),
     *             @OA\Property(property="has_warranty", type="boolean", nullable=true, description="Tem garantia"),
     *             @OA\Property(property="is_adapted", type="boolean", nullable=true, description="Adaptado para deficientes"),
     *             @OA\Property(property="is_armored", type="boolean", nullable=true, description="Veículo blindado"),
     *             @OA\Property(property="has_spare_key", type="boolean", nullable=true, description="Tem chave reserva"),
     *             @OA\Property(property="ipva_paid", type="boolean", nullable=true, description="IPVA pago"),
     *             @OA\Property(property="has_manual", type="boolean", nullable=true, description="Tem manual"),
     *             @OA\Property(property="auction_history", type="boolean", nullable=true, description="Passou por leilão"),
     *             @OA\Property(property="dealer_serviced", type="boolean", nullable=true, description="Revisado em concessionária"),
     *             @OA\Property(property="single_owner", type="boolean", nullable=true, description="Único dono")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Veículo criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function store(Request $request)
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $validator = Validator::make($request->all(), [
            'brand_id' => 'required|integer|exists:vehicle_brands,id',
            'model_id' => 'required|integer|exists:vehicle_models,id',
            'vehicle_type' => 'required|string|in:car,motorcycle,truck,suv,pickup,van,bus,other',
            'condition' => 'required|string|in:new,used',
            'title' => 'required|string|max:255',
            'version' => 'nullable|string|max:255',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'model_year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'required|string|max:100',
            'fuel_type' => 'required|string|in:flex,gasolina,diesel,eletrico,hibrido,gnv',
            'transmission' => 'required|string|in:manual,automatica,cvt,automatizada',
            'doors' => 'required|integer|min:2|max:5',
            'mileage' => 'required|integer|min:0',
            'hide_mileage' => 'boolean',
            'price' => 'required|numeric|min:0',
            'classified_price' => 'nullable|numeric|min:0',
            'cost_type' => 'nullable|string|max:100',
            'fipe_price' => 'nullable|numeric|min:0',
            'accept_financing' => 'boolean',
            'accept_exchange' => 'boolean',
            'engine' => 'nullable|string|max:255',
            'power' => 'nullable|string|max:255',
            'torque' => 'nullable|string|max:255',
            'consumption_city' => 'nullable|string|max:255',
            'consumption_highway' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'use_same_observation' => 'boolean',
            'custom_observation' => 'nullable|string',
            'classified_observations' => 'nullable|array',
            'standard_features' => 'nullable|array',
            'optional_features' => 'nullable|array',
            'plate' => 'nullable|string|max:10',
            'chassi' => 'nullable|string|max:17',
            'renavam' => 'nullable|string|max:11',
            'video_link' => 'nullable|url|max:500',
            'owner_name' => 'nullable|string|max:255',
            'owner_phone' => 'nullable|string|max:20',
            'owner_email' => 'nullable|email|max:255',
            'is_featured' => 'boolean',
            'is_licensed' => 'boolean',
            'has_warranty' => 'boolean',
            'is_adapted' => 'boolean',
            'is_armored' => 'boolean',
            'has_spare_key' => 'boolean',
            'ipva_paid' => 'boolean',
            'has_manual' => 'boolean',
            'auction_history' => 'boolean',
            'dealer_serviced' => 'boolean',
            'single_owner' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        // Verificar se o modelo pertence à marca
        $model = VehicleModel::where('id', $request->model_id)
            ->where('brand_id', $request->brand_id)
            ->first();

        if (!$model) {
            return response()->json(['error' => 'Modelo não pertence à marca selecionada'], 422);
        }

        $vehicleData = $request->all();
        $vehicleData['tenant_id'] = $user->tenant_id;
        $vehicleData['created_by'] = $user->id;
        $vehicleData['published_at'] = now();

        // URL será gerada automaticamente pelo VehicleObserver
        // Removido: $vehicleData['url'] = UrlHelper::generateUniqueUrl($request->title, $user->tenant_id);

        $vehicle = Vehicle::create($vehicleData);

        return response()->json([
            'message' => 'Veículo criado com sucesso',
            'data' => $vehicle->load(['brand', 'model', 'createdBy'])
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/vehicles/{id}",
     *     summary="Atualizar veículo",
     *     description="Atualiza os dados de um veículo existente. Todos os campos são opcionais - apenas os campos enviados serão atualizados.",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do veículo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="brand_id", type="integer", description="ID da marca do veículo"),
     *             @OA\Property(property="model_id", type="integer", description="ID do modelo do veículo"),
     *             @OA\Property(property="vehicle_type", type="string", enum={"car", "motorcycle", "truck", "suv", "pickup", "van", "bus", "other"}, description="Tipo de veículo"),
     *             @OA\Property(property="condition", type="string", enum={"new", "used"}, description="Condição do veículo"),
     *             @OA\Property(property="title", type="string", maxLength=255, description="Título do anúncio"),
     *             @OA\Property(property="version", type="string", maxLength=255, nullable=true, description="Versão do veículo"),
     *             @OA\Property(property="year", type="integer", minimum=1900, description="Ano de fabricação"),
     *             @OA\Property(property="model_year", type="integer", minimum=1900, description="Ano do modelo"),
     *             @OA\Property(property="color", type="string", maxLength=100, description="Cor do veículo"),
     *             @OA\Property(property="fuel_type", type="string", enum={"flex", "gasolina", "diesel", "eletrico", "hibrido", "gnv"}, description="Tipo de combustível"),
     *             @OA\Property(property="transmission", type="string", enum={"manual", "automatica", "cvt", "automatizada"}, description="Tipo de transmissão"),
     *             @OA\Property(property="doors", type="integer", minimum=2, maximum=5, description="Número de portas"),
     *             @OA\Property(property="mileage", type="integer", minimum=0, description="Quilometragem"),
     *             @OA\Property(property="hide_mileage", type="boolean", nullable=true, description="Não exibir quilometragem"),
     *             @OA\Property(property="price", type="number", minimum=0, description="Preço de venda"),
     *             @OA\Property(property="classified_price", type="number", minimum=0, nullable=true, description="Preço do classificado"),
     *             @OA\Property(property="cost_type", type="string", maxLength=100, nullable=true, description="Tipo de custo"),
     *             @OA\Property(property="fipe_price", type="number", minimum=0, nullable=true, description="Preço FIPE"),
     *             @OA\Property(property="accept_financing", type="boolean", nullable=true, description="Aceita financiamento"),
     *             @OA\Property(property="accept_exchange", type="boolean", nullable=true, description="Aceita troca"),
     *             @OA\Property(property="engine", type="string", maxLength=255, nullable=true, description="Motor"),
     *             @OA\Property(property="power", type="string", maxLength=255, nullable=true, description="Potência"),
     *             @OA\Property(property="torque", type="string", maxLength=255, nullable=true, description="Torque"),
     *             @OA\Property(property="consumption_city", type="string", maxLength=255, nullable=true, description="Consumo na cidade"),
     *             @OA\Property(property="consumption_highway", type="string", maxLength=255, nullable=true, description="Consumo na estrada"),
     *             @OA\Property(property="description", type="string", nullable=true, description="Descrição geral"),
     *             @OA\Property(property="use_same_observation", type="boolean", nullable=true, description="Usar mesma observação do site"),
     *             @OA\Property(property="custom_observation", type="string", nullable=true, description="Observação personalizada"),
     *             @OA\Property(property="classified_observations", type="array", nullable=true, description="Observações por classificado", @OA\Items(type="string")),
     *             @OA\Property(property="standard_features", type="array", nullable=true, description="Características padrão selecionadas", @OA\Items(type="string")),
     *             @OA\Property(property="optional_features", type="array", nullable=true, description="Opcionais selecionados", @OA\Items(type="string")),
     *             @OA\Property(property="plate", type="string", maxLength=10, nullable=true, description="Placa do veículo"),
     *             @OA\Property(property="chassi", type="string", maxLength=17, nullable=true, description="Chassi"),
     *             @OA\Property(property="renavam", type="string", maxLength=11, nullable=true, description="RENAVAM"),
     *             @OA\Property(property="video_link", type="string", maxLength=500, nullable=true, description="Link do vídeo"),
     *             @OA\Property(property="owner_name", type="string", maxLength=255, nullable=true, description="Nome do proprietário"),
     *             @OA\Property(property="owner_phone", type="string", maxLength=20, nullable=true, description="Telefone do proprietário"),
     *             @OA\Property(property="owner_email", type="string", maxLength=255, nullable=true, description="Email do proprietário"),
     *             @OA\Property(property="status", type="string", enum={"available", "sold", "reserved", "maintenance"}, description="Status do veículo"),
     *             @OA\Property(property="is_featured", type="boolean", nullable=true, description="Veículo em destaque"),
     *             @OA\Property(property="is_licensed", type="boolean", nullable=true, description="Veículo licenciado"),
     *             @OA\Property(property="has_warranty", type="boolean", nullable=true, description="Tem garantia"),
     *             @OA\Property(property="is_adapted", type="boolean", nullable=true, description="Adaptado para deficientes"),
     *             @OA\Property(property="is_armored", type="boolean", nullable=true, description="Veículo blindado"),
     *             @OA\Property(property="has_spare_key", type="boolean", nullable=true, description="Tem chave reserva"),
     *             @OA\Property(property="ipva_paid", type="boolean", nullable=true, description="IPVA pago"),
     *             @OA\Property(property="has_manual", type="boolean", nullable=true, description="Tem manual"),
     *             @OA\Property(property="auction_history", type="boolean", nullable=true, description="Passou por leilão"),
     *             @OA\Property(property="dealer_serviced", type="boolean", nullable=true, description="Revisado em concessionária"),
     *             @OA\Property(property="single_owner", type="boolean", nullable=true, description="Único dono"),
     *             @OA\Property(property="is_active", type="boolean", nullable=true, description="Veículo ativo")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Veículo atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Veículo atualizado com sucesso"),
     *             @OA\Property(property="data", ref="#/components/schemas/Vehicle")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Veículo não encontrado"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function update(Request $request, $id)
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $vehicle = Vehicle::byTenant($user->tenant_id)->find($id);

        if (!$vehicle) {
            return response()->json(['error' => 'Veículo não encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'brand_id' => 'sometimes|required|integer|exists:vehicle_brands,id',
            'model_id' => 'sometimes|required|integer|exists:vehicle_models,id',
            'vehicle_type' => 'sometimes|required|string|in:car,motorcycle,truck,suv,pickup,van,bus,other',
            'condition' => 'sometimes|required|string|in:new,used',
            'title' => 'sometimes|required|string|max:255',
            'version' => 'nullable|string|max:255',
            'year' => 'sometimes|required|integer|min:1900|max:' . (date('Y') + 1),
            'model_year' => 'sometimes|required|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'sometimes|required|string|max:100',
            'fuel_type' => 'sometimes|required|string|in:flex,gasolina,diesel,eletrico,hibrido,gnv',
            'transmission' => 'sometimes|required|string|in:manual,automatica,cvt,automatizada',
            'doors' => 'sometimes|required|integer|min:2|max:5',
            'mileage' => 'sometimes|required|integer|min:0',
            'hide_mileage' => 'boolean',
            'price' => 'sometimes|required|numeric|min:0',
            'classified_price' => 'nullable|numeric|min:0',
            'cost_type' => 'nullable|string|max:100',
            'fipe_price' => 'nullable|numeric|min:0',
            'accept_financing' => 'boolean',
            'accept_exchange' => 'boolean',
            'engine' => 'nullable|string|max:255',
            'power' => 'nullable|string|max:255',
            'torque' => 'nullable|string|max:255',
            'consumption_city' => 'nullable|string|max:255',
            'consumption_highway' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'use_same_observation' => 'boolean',
            'custom_observation' => 'nullable|string',
            'classified_observations' => 'nullable|array',
            'standard_features' => 'nullable|array',
            'optional_features' => 'nullable|array',
            'plate' => 'nullable|string|max:10',
            'chassi' => 'nullable|string|max:17',
            'renavam' => 'nullable|string|max:11',
            'video_link' => 'nullable|url|max:500',
            'owner_name' => 'nullable|string|max:255',
            'owner_phone' => 'nullable|string|max:20',
            'owner_email' => 'nullable|email|max:255',
            'status' => 'sometimes|required|string|in:available,sold,reserved,maintenance',
            'is_featured' => 'boolean',
            'is_licensed' => 'boolean',
            'has_warranty' => 'boolean',
            'is_adapted' => 'boolean',
            'is_armored' => 'boolean',
            'has_spare_key' => 'boolean',
            'ipva_paid' => 'boolean',
            'has_manual' => 'boolean',
            'auction_history' => 'boolean',
            'dealer_serviced' => 'boolean',
            'single_owner' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        $vehicleData = $request->all();
        $vehicleData['updated_by'] = $user->id;

        $vehicle->update($vehicleData);

        return response()->json([
            'message' => 'Veículo atualizado com sucesso',
            'data' => $vehicle->load(['brand', 'model', 'createdBy', 'updatedBy'])
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/vehicles/{id}",
     *     summary="Excluir veículo",
     *     tags={"Vehicles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do veículo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Veículo excluído com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Veículo não encontrado")
     * )
     */
    public function destroy(Request $request, $id)
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $vehicle = Vehicle::byTenant($user->tenant_id)->find($id);

        if (!$vehicle) {
            return response()->json(['error' => 'Veículo não encontrado'], 404);
        }

        // Remover imagens do storage
        foreach ($vehicle->images as $image) {
            if (Storage::exists($image->path)) {
                Storage::delete($image->path);
            }
        }

        $vehicle->delete();

        return response()->json(['message' => 'Veículo excluído com sucesso']);
    }

    /**
     * @OA\Get(
     *     path="/api/vehicles/filters",
     *     summary="Obter filtros disponíveis",
     *     tags={"Vehicles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Filtros disponíveis",
     *         @OA\JsonContent(
     *             @OA\Property(property="brands", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="models", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="years", type="array", @OA\Items(type="integer")),
     *             @OA\Property(property="fuel_types", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="transmissions", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="price_range", type="object")
     *         )
     *     )
     * )
     */
    public function filters(Request $request): JsonResponse
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $brands = VehicleBrand::active()->ordered()->get(['id', 'name']);
        $models = VehicleModel::active()->ordered()->get(['id', 'name', 'brand_id']);

        $vehicleStats = Vehicle::byTenant($user->tenant_id)
            ->active()
            ->selectRaw('
                MIN(year) as min_year,
                MAX(year) as max_year,
                MIN(price) as min_price,
                MAX(price) as max_price
            ')
            ->first();

        $years = [];
        if ($vehicleStats) {
            for ($year = $vehicleStats->max_year; $year >= $vehicleStats->min_year; $year--) {
                $years[] = $year;
            }
        }

        return response()->json([
            'brands' => $brands,
            'models' => $models,
            'years' => $years,
            'fuel_types' => ['flex', 'gasolina', 'diesel', 'eletrico', 'hibrido', 'gnv'],
            'transmissions' => ['manual', 'automatica', 'cvt', 'automatizada'],
            'price_range' => [
                'min' => $vehicleStats->min_price ?? 0,
                'max' => $vehicleStats->max_price ?? 0
            ]
        ]);
    }

    /**
     * Obter características disponíveis para veículos
     *
     * @OA\Get(
     *     path="/api/vehicles/features",
     *     summary="Obter características disponíveis",
     *     description="Retorna as características padrão e opcionais disponíveis para veículos",
     *     operationId="getVehicleFeatures",
     *     tags={"Vehicles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Características obtidas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="standard_features", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="optional_features", type="array", @OA\Items(type="string"))
     *             ),
     *             @OA\Property(property="message", type="string", example="Características obtidas com sucesso")
     *         )
     *     )
     * )
     */
    public function getFeatures(Request $request)
    {
        try {
            $user = TokenHelper::getAuthenticatedUser($request);

            if (!$user) {
                return response()->json(['error' => 'Usuário não autenticado'], 401);
            }

            $features = Cache::get('vehicle_features');

            if (!$features) {
                // Se não estiver em cache, executar o seeder
                Artisan::call('db:seed', ['--class' => 'VehicleFeaturesSeeder']);
                $features = Cache::get('vehicle_features');
            }

            return response()->json([
                'success' => true,
                'data' => $features,
                'message' => 'Características obtidas com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter características: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload de imagens para um veículo
     *
     * @OA\Post(
     *     path="/api/vehicles/{id}/images",
     *     summary="Upload de imagens para veículo",
     *     description="Faz upload de uma ou mais imagens para um veículo específico",
     *     operationId="uploadVehicleImages",
     *     tags={"Vehicles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do veículo",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="images[]",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Array de imagens (máximo 10)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Imagens enviadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Imagens enviadas com sucesso"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=404, description="Veículo não encontrado"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function uploadImages(Request $request, $id)
    {
        try {
            $user = TokenHelper::getAuthenticatedUser($request);

            if (!$user) {
                return response()->json(['error' => 'Usuário não autenticado'], 401);
            }

            $vehicle = Vehicle::byTenant($user->tenant_id)->find($id);

            if (!$vehicle) {
                return response()->json(['error' => 'Veículo não encontrado'], 404);
            }

            $validator = Validator::make($request->all(), [
                'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            ], [
                'images.*.required' => 'É necessário selecionar pelo menos uma imagem.',
                'images.*.image' => 'O arquivo deve ser uma imagem.',
                'images.*.mimes' => 'A imagem deve ser do tipo: jpeg, png, jpg, gif, webp.',
                'images.*.max' => 'A imagem não pode ter mais que 5MB.',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
            }

            if (!$request->hasFile('images')) {
                return response()->json(['error' => 'Nenhuma imagem foi enviada'], 422);
            }

            $images = $request->file('images');
            if (count($images) > 10) {
                return response()->json(['error' => 'Máximo de 10 imagens permitidas'], 422);
            }

            $uploadedImages = [];
            $tenant = $user->tenant;
            $basePath = "tenants/{$tenant->id}/vehicles/{$vehicle->id}";

            foreach ($images as $image) {
                $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs($basePath, $filename, 'public');

                if ($path) {
                                        // Gerar slug baseado no título do veículo
                    $vehicleSlug = Str::slug($vehicle->title);

                    // Criar registro na tabela vehicle_images
                    $vehicleImage = $vehicle->images()->create([
                        'filename' => $filename,
                        'path' => $path,
                        'url' => $vehicleSlug,
                        'original_name' => $image->getClientOriginalName(),
                        'size' => $image->getSize(),
                        'mime_type' => $image->getMimeType(),
                        'is_primary' => $vehicle->images()->count() === 0, // Primeira imagem como primária
                    ]);

                    $uploadedImages[] = [
                        'id' => $vehicleImage->id,
                        'filename' => $filename,
                        'path' => $path,
                        'url' => $vehicleSlug,
                        'image_url' => asset('storage/' . $path),
                        'is_primary' => $vehicleImage->is_primary,
                        'size' => $vehicleImage->size,
                        'mime_type' => $vehicleImage->mime_type,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Imagens enviadas com sucesso',
                'data' => $uploadedImages
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer upload das imagens: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deletar uma imagem específica
     *
     * @OA\Delete(
     *     path="/api/vehicles/{id}/images/{imageId}",
     *     summary="Deletar imagem de veículo",
     *     description="Remove uma imagem específica de um veículo",
     *     operationId="deleteVehicleImage",
     *     tags={"Vehicles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do veículo",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="imageId",
     *         in="path",
     *         description="ID da imagem",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Imagem deletada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Imagem deletada com sucesso")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Veículo ou imagem não encontrada")
     * )
     */
    public function deleteImage(Request $request, $id, $imageId)
    {
        try {
            $user = TokenHelper::getAuthenticatedUser($request);

            if (!$user) {
                return response()->json(['error' => 'Usuário não autenticado'], 401);
            }

            $vehicle = Vehicle::byTenant($user->tenant_id)->find($id);

            if (!$vehicle) {
                return response()->json(['error' => 'Veículo não encontrado'], 404);
            }

            $image = $vehicle->images()->find($imageId);
            if (!$image) {
                return response()->json(['error' => 'Imagem não encontrada'], 404);
            }

            // Se for a imagem primária, não permitir deletar
            if ($image->is_primary) {
                return response()->json(['error' => 'Não é possível deletar a imagem primária'], 422);
            }

            // Deletar arquivo físico
            if (Storage::disk('public')->exists($image->path)) {
                Storage::disk('public')->delete($image->path);
            }

            // Deletar registro do banco
            $image->delete();

            return response()->json([
                'success' => true,
                'message' => 'Imagem deletada com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar imagem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Definir imagem como primária
     *
     * @OA\Post(
     *     path="/api/vehicles/{id}/images/{imageId}/primary",
     *     summary="Definir imagem como primária",
     *     description="Define uma imagem específica como primária para o veículo",
     *     operationId="setPrimaryImage",
     *     tags={"Vehicles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do veículo",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="imageId",
     *         in="path",
     *         description="ID da imagem",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Imagem definida como primária com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Imagem definida como primária com sucesso")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Veículo ou imagem não encontrada")
     * )
     */
    public function setPrimaryImage(Request $request, $id, $imageId)
    {
        try {
            $user = TokenHelper::getAuthenticatedUser($request);

            if (!$user) {
                return response()->json(['error' => 'Usuário não autenticado'], 401);
            }

            $vehicle = Vehicle::byTenant($user->tenant_id)->find($id);

            if (!$vehicle) {
                return response()->json(['error' => 'Veículo não encontrado'], 404);
            }

            $image = $vehicle->images()->find($imageId);
            if (!$image) {
                return response()->json(['error' => 'Imagem não encontrada'], 404);
            }

            // Remover primária atual
            $vehicle->images()->update(['is_primary' => false]);

            // Definir nova primária
            $image->update(['is_primary' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Imagem definida como primária com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao definir imagem primária: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reordenar imagens do veículo
     *
     * @OA\Post(
     *     path="/api/vehicles/{id}/images/reorder",
     *     summary="Reordenar imagens do veículo",
     *     description="Reordena as imagens de um veículo específico",
     *     operationId="reorderVehicleImages",
     *     tags={"Vehicles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do veículo",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="image_order",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 description="Array com IDs das imagens na ordem desejada"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Imagens reordenadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Imagens reordenadas com sucesso")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Veículo não encontrado"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function reorderImages(Request $request, $id)
    {
        try {
            $user = TokenHelper::getAuthenticatedUser($request);

            if (!$user) {
                return response()->json(['error' => 'Usuário não autenticado'], 401);
            }

            $vehicle = Vehicle::byTenant($user->tenant_id)->find($id);

            if (!$vehicle) {
                return response()->json(['error' => 'Veículo não encontrado'], 404);
            }

            $validator = Validator::make($request->all(), [
                'image_order' => 'required|array',
                'image_order.*' => 'integer|exists:vehicle_images,id'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
            }

            $order = $request->input('image_order');

            foreach ($order as $position => $imageId) {
                $vehicle->images()->where('id', $imageId)->update(['sort_order' => $position + 1]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Imagens reordenadas com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao reordenar imagens: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lista todas as imagens de um veículo
     */
    public function getImages(Request $request, $id)
    {
        try {
            $user = TokenHelper::getAuthenticatedUser($request);

            if (!$user) {
                return response()->json(['error' => 'Usuário não autenticado'], 401);
            }

            $vehicle = Vehicle::byTenant($user->tenant_id)
                ->with(['images' => function ($query) {
                    $query->orderBy('sort_order', 'asc');
                }])
                ->find($id);

            if (!$vehicle) {
                return response()->json(['error' => 'Veículo não encontrado'], 404);
            }

            $images = $vehicle->images->map(function ($image) use ($vehicle) {
                return [
                    'id' => $image->id,
                    'filename' => $image->filename,
                    'url' => $image->url,
                    'image_url' => url("/api/public/images/{$vehicle->tenant_id}/{$vehicle->id}/{$image->filename}"),
                    'is_primary' => $image->is_primary,
                    'size' => $image->size,
                    'mime_type' => $image->mime_type,
                    'sort_order' => $image->sort_order,
                    'created_at' => $image->created_at,
                    'updated_at' => $image->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'vehicle_id' => $vehicle->id,
                    'total_images' => $images->count(),
                    'images' => $images
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar imagens: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lista veículos para o portal público (sem autenticação)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function indexPublic(Request $request): JsonResponse
    {
        try {
            // Buscar tenant pelo subdomínio
            $subdomain = $request->header('X-Tenant-Subdomain');

            if (!$subdomain) {
                return response()->json(['error' => 'Tenant não especificado'], 400);
            }

            $tenant = \App\Models\Tenant::bySubdomain($subdomain)->active()->first();

            if (!$tenant) {
                return response()->json(['error' => 'Tenant não encontrado'], 404);
            }

            // Log para debug
            Log::info('Debug indexPublic', [
                'subdomain' => $subdomain,
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name
            ]);

            $query = Vehicle::byTenant($tenant->id)
                ->with(['brand', 'model', 'primaryImage'])
                ->active()
                ->available();

            // Log para debug - verificar query
            Log::info('Query SQL', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);

            // Aplicar filtros básicos
            if ($request->filled('brand_id')) {
                $query->where('brand_id', $request->brand_id);
            }

            if ($request->filled('model_id')) {
                $query->where('model_id', $request->model_id);
            }

            if ($request->filled('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->filled('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            if ($request->filled('min_year')) {
                $query->where('year', '>=', $request->min_year);
            }

            if ($request->filled('max_year')) {
                $query->where('year', '<=', $request->max_year);
            }

            if ($request->filled('fuel_type')) {
                $query->where('fuel_type', $request->fuel_type);
            }

            if ($request->filled('transmission')) {
                $query->where('transmission', $request->transmission);
            }

            // Busca por texto
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('brand', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('model', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Ordenação
            $query->orderBy('is_featured', 'desc')
                  ->orderBy('created_at', 'desc');

            // Paginação
            $perPage = min($request->get('per_page', 12), 100);
            $vehicles = $query->paginate($perPage);

            // Log para debug - verificar resultado
            Log::info('Resultado da query', [
                'total_vehicles' => $vehicles->total(),
                'current_page' => $vehicles->currentPage(),
                'per_page' => $vehicles->perPage(),
                'first_vehicle' => $vehicles->first() ? [
                    'id' => $vehicles->first()->id,
                    'title' => $vehicles->first()->title,
                    'brand_id' => $vehicles->first()->brand_id,
                    'model_id' => $vehicles->first()->model_id,
                    'tenant_id' => $vehicles->first()->tenant_id,
                    'is_active' => $vehicles->first()->is_active,
                    'status' => $vehicles->first()->status
                ] : null
            ]);

            // Formatar dados para resposta pública
            $vehicles->getCollection()->transform(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'title' => $vehicle->title,
                    'url' => $vehicle->url,
                    'description' => $vehicle->description,
                    'year' => $vehicle->year,
                    'price' => $vehicle->price,
                    'mileage' => $vehicle->mileage,
                    'fuel_type' => $vehicle->fuel_type,
                    'transmission' => $vehicle->transmission,
                    'color' => $vehicle->color,
                    'doors' => $vehicle->doors,
                    'seats' => $vehicle->seats,
                    'engine_size' => $vehicle->engine_size,
                    'power' => $vehicle->power,
                    'is_featured' => $vehicle->is_featured,
                    'created_at' => $vehicle->created_at,
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
                    'primary_image' => $vehicle->primaryImage ? [
                        'id' => $vehicle->primaryImage->id,
                        'url' => $vehicle->primaryImage->image_url,
                        'alt' => $vehicle->title
                    ] : null
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $vehicles->items(),
                'pagination' => [
                    'current_page' => $vehicles->currentPage(),
                    'last_page' => $vehicles->lastPage(),
                    'per_page' => $vehicles->perPage(),
                    'total' => $vehicles->total(),
                    'from' => $vehicles->firstItem(),
                    'to' => $vehicles->lastItem()
                ],
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'subdomain' => $tenant->subdomain
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar veículos públicos: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Exibe um veículo específico para o portal público
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function showPublic(Request $request, int $id): JsonResponse
    {
        try {
            // Buscar tenant pelo subdomínio
            $subdomain = $request->header('X-Tenant-Subdomain');

            if (!$subdomain) {
                return response()->json(['error' => 'Tenant não especificado'], 400);
            }

            $tenant = \App\Models\Tenant::bySubdomain($subdomain)->active()->first();

            if (!$tenant) {
                return response()->json(['error' => 'Tenant não encontrado'], 404);
            }

            $vehicle = Vehicle::byTenant($tenant->id)
                ->with(['brand', 'model', 'images', 'features'])
                ->active()
                ->available()
                ->find($id);

            if (!$vehicle) {
                return response()->json(['error' => 'Veículo não encontrado'], 404);
            }

            // Formatar dados para resposta pública
            $vehicleData = [
                'id' => $vehicle->id,
                'title' => $vehicle->title,
                'url' => $vehicle->url,
                'description' => $vehicle->description,
                'year' => $vehicle->year,
                'price' => $vehicle->price,
                'mileage' => $vehicle->mileage,
                'fuel_type' => $vehicle->fuel_type,
                'transmission' => $vehicle->transmission,
                'color' => $vehicle->color,
                'doors' => $vehicle->doors,
                'seats' => $vehicle->seats,
                'engine_size' => $vehicle->engine_size,
                'power' => $vehicle->power,
                'consumption_city' => $vehicle->consumption_city,
                'consumption_highway' => $vehicle->consumption_highway,
                'is_featured' => $vehicle->is_featured,
                'created_at' => $vehicle->created_at,
                'brand' => $vehicle->brand ? [
                    'id' => $vehicle->brand->id,
                    'name' => $vehicle->brand->name,
                    'slug' => $vehicle->brand->slug,
                    'logo_url' => $vehicle->brand->logo_url
                ] : null,
                'model' => $vehicle->model ? [
                    'id' => $vehicle->model->id,
                    'name' => $vehicle->model->name,
                    'slug' => $vehicle->model->slug
                ] : null,
                'images' => $vehicle->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'url' => $image->image_url,
                        'alt' => $image->original_name,
                        'is_primary' => $image->is_primary,
                        'sort_order' => $image->sort_order
                    ];
                })->sortBy('sort_order')->values(),
                'features' => $vehicle->features->map(function ($feature) {
                    return [
                        'id' => $feature->id,
                        'name' => $feature->name,
                        'description' => $feature->description,
                        'icon' => $feature->icon
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $vehicleData,
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'subdomain' => $tenant->subdomain
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao exibir veículo público: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }
}
