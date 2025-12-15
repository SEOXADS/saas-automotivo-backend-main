<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="2. Admin Cliente",
 *     description="Endpoints para administradores do tenant (autenticação e gestão)"
 * )
 *

 */
class VehicleModelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = VehicleModel::with('brand');

            // Filtros
            if ($request->has('brand_id')) {
                $query->byBrand($request->brand_id);
            }

            if ($request->has('category')) {
                $query->byCategory($request->category);
            }

            if ($request->has('active')) {
                $query->active();
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Ordenação
            $sortBy = $request->get('sort_by', 'sort_order');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginação
            $perPage = $request->get('per_page', 15);
            $models = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $models,
                'message' => 'Modelos listados com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar modelos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): JsonResponse
    {
        try {
            $brands = VehicleBrand::active()->ordered()->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => [
                    'brands' => $brands,
                    'categories' => [
                        'car' => 'Carro',
                        'motorcycle' => 'Moto',
                        'truck' => 'Caminhão',
                        'suv' => 'SUV',
                        'pickup' => 'Picape',
                        'van' => 'Van',
                        'bus' => 'Ônibus',
                        'other' => 'Outro'
                    ]
                ],
                'message' => 'Dados para criação carregados'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar dados: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'brand_id' => 'required|exists:vehicle_brands,id',
                'name' => 'required|string|max:100',
                'description' => 'nullable|string|max:500',
                'category' => 'required|in:car,motorcycle,truck,suv,pickup,van,bus,other',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Gerar slug único
            $slug = Str::slug($request->name);
            $originalSlug = $slug;
            $counter = 1;

            while (VehicleModel::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $model = VehicleModel::create([
                'brand_id' => $request->brand_id,
                'name' => $request->name,
                'slug' => $slug,
                'description' => $request->description,
                'category' => $request->category,
                'is_active' => $request->input('is_active', true),
                'sort_order' => $request->input('sort_order', 0)
            ]);

            $model->load('brand');

            return response()->json([
                'success' => true,
                'data' => $model,
                'message' => 'Modelo criado com sucesso'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar modelo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $model = VehicleModel::with(['brand', 'vehicles'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $model,
                'message' => 'Modelo encontrado com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Modelo não encontrado'
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): JsonResponse
    {
        try {
            $model = VehicleModel::findOrFail($id);
            $brands = VehicleBrand::active()->ordered()->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => [
                    'model' => $model,
                    'brands' => $brands,
                    'categories' => [
                        'car' => 'Carro',
                        'motorcycle' => 'Moto',
                        'truck' => 'Caminhão',
                        'suv' => 'SUV',
                        'pickup' => 'Picape',
                        'van' => 'Van',
                        'bus' => 'Ônibus',
                        'other' => 'Outro'
                    ]
                ],
                'message' => 'Dados para edição carregados'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar dados: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $model = VehicleModel::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'brand_id' => 'sometimes|required|exists:vehicle_brands,id',
                'name' => 'sometimes|required|string|max:100',
                'description' => 'nullable|string|max:500',
                'category' => 'sometimes|required|in:car,motorcycle,truck,suv,pickup,van,bus,other',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Se o nome mudou, gerar novo slug
            if ($request->has('name') && $request->name !== $model->name) {
                $slug = Str::slug($request->name);
                $originalSlug = $slug;
                $counter = 1;

                while (VehicleModel::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }

                $request->merge(['slug' => $slug]);
            }

            $model->update($request->only([
                'brand_id', 'name', 'slug', 'description',
                'category', 'is_active', 'sort_order'
            ]));

            $model->load('brand');

            return response()->json([
                'success' => true,
                'data' => $model,
                'message' => 'Modelo atualizado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar modelo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $model = VehicleModel::findOrFail($id);

            // Verificar se há veículos usando este modelo
            if ($model->vehicles()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não é possível excluir um modelo que possui veículos associados'
                ], 400);
            }

            $model->delete();

            return response()->json([
                'success' => true,
                'message' => 'Modelo excluído com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir modelo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar modelos por marca
     *
     * @OA\Get(
     *     path="/api/brands/{id}/models",
     *     summary="Listar modelos de uma marca específica",
     *     description="Retorna todos os modelos ativos de uma marca específica",
     *     operationId="getModelsByBrand",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da marca",
     *         required=true,
     *         @OA\Schema(type="integer", example=4)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Modelos listados com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/VehicleModel")),
     *             @OA\Property(property="message", type="string", example="Modelos da marca listados com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     */
    public function byBrand(string $brandId): JsonResponse
    {
        try {
            $models = VehicleModel::with('brand')
                ->byBrand($brandId)
                ->active()
                ->ordered()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $models,
                'message' => 'Modelos da marca listados com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar modelos da marca: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lista modelos para o portal público (sem autenticação)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexPublic(Request $request)
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

            $query = \App\Models\VehicleModel::query()
                ->with('brand')
                ->whereHas('vehicles', function($q) use ($tenant) {
                    $q->where('tenant_id', $tenant->id)
                      ->where('is_active', true)
                      ->where('status', 'available');
                })
                ->active()
                ->orderBy('name');

            // Filtro por marca
            if ($request->filled('brand_id')) {
                $query->where('brand_id', $request->brand_id);
            }

            // Busca por texto
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $models = $query->get(['id', 'name', 'slug', 'brand_id', 'description']);

            // Formatar dados para resposta pública
            $models->transform(function ($model) {
                return [
                    'id' => $model->id,
                    'name' => $model->name,
                    'slug' => $model->slug,
                    'description' => $model->description,
                    'brand' => $model->brand ? [
                        'id' => $model->brand->id,
                        'name' => $model->brand->name,
                        'slug' => $model->brand->slug
                    ] : null
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $models,
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'subdomain' => $tenant->subdomain
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar modelos públicos: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }
}
