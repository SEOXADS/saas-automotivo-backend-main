<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Neighborhood;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="4. Localizações",
 *     description="Endpoints para gestão de bairros"
 * )
 */
class NeighborhoodController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/locations/neighborhoods",
     *     summary="Listar bairros",
     *     description="Retorna uma lista paginada de bairros com filtros opcionais. Rota pública - não requer autenticação.",
     *     tags={"4. Localizações"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Buscar por nome",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="city_id",
     *         in="query",
     *         description="Filtrar por cidade",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="state_id",
     *         in="query",
     *         description="Filtrar por estado",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="country_id",
     *         in="query",
     *         description="Filtrar por país",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filtrar por status ativo",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de bairros retornada com sucesso"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Neighborhood::with(['city', 'state', 'country']);

            // Filtros
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('zip_code', 'like', "%{$search}%");
                });
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->filled('city_id')) {
                $query->where('city_id', $request->city_id);
            }

            if ($request->filled('state_id')) {
                $query->where('state_id', $request->state_id);
            }

            if ($request->filled('country_id')) {
                $query->where('country_id', $request->country_id);
            }

            // Ordenação
            $sort = $request->get('sort', 'name');
            $order = $request->get('order', 'asc');
            $query->orderBy($sort, $order);

            // Paginação
            $perPage = $request->get('per_page', 15);
            $neighborhoods = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $neighborhoods->items(),
                'current_page' => $neighborhoods->currentPage(),
                'last_page' => $neighborhoods->lastPage(),
                'per_page' => $neighborhoods->perPage(),
                'total' => $neighborhoods->total(),
                'from' => $neighborhoods->firstItem(),
                'to' => $neighborhoods->lastItem(),
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar bairros', [
                'error' => $e->getMessage(),
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
     *     path="/api/locations/neighborhoods",
     *     summary="Criar bairro",
     *     description="Cria um novo bairro. Requer autenticação de Super Admin.",
     *     tags={"4. Localizações"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "city_id", "state_id", "country_id"},
     *             @OA\Property(property="name", type="string", example="Centro"),
     *             @OA\Property(property="city_id", type="integer", example=1),
     *             @OA\Property(property="state_id", type="integer", example=25),
     *             @OA\Property(property="country_id", type="integer", example=1),
     *             @OA\Property(property="zip_code", type="string", example="01000-000"),
     *             @OA\Property(property="latitude", type="number", format="float", example=-23.532905),
     *             @OA\Property(property="longitude", type="number", format="float", example=-46.639520),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Bairro criado com sucesso"
     *     ),
     *     @OA\Response(response=422, description="Dados de validação inválidos")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'city_id' => 'required|exists:cities,id',
                'state_id' => 'required|exists:states,id',
                'country_id' => 'required|exists:countries,id',
                'zip_code' => 'nullable|string|max:20',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $neighborhood = Neighborhood::create($request->all());

            return response()->json([
                'success' => true,
                'data' => $neighborhood->load(['city', 'state', 'country'])
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao criar bairro', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
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
     *     path="/api/locations/neighborhoods/{id}",
     *     summary="Visualizar bairro",
     *     description="Retorna os dados de um bairro específico. Rota pública - não requer autenticação.",
     *     tags={"4. Localizações"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bairro retornado com sucesso"
     *     ),
     *     @OA\Response(response=404, description="Bairro não encontrado")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $neighborhood = Neighborhood::with(['city', 'state', 'country'])->find($id);

            if (!$neighborhood) {
                return response()->json([
                    'success' => false,
                    'error' => 'Bairro não encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $neighborhood
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao visualizar bairro', [
                'error' => $e->getMessage(),
                'neighborhood_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/locations/neighborhoods/{id}",
     *     summary="Atualizar bairro",
     *     description="Atualiza os dados de um bairro. Requer autenticação de Super Admin.",
     *     tags={"4. Localizações"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Centro"),
     *             @OA\Property(property="city_id", type="integer", example=1),
     *             @OA\Property(property="state_id", type="integer", example=25),
     *             @OA\Property(property="country_id", type="integer", example=1),
     *             @OA\Property(property="zip_code", type="string", example="01000-000"),
     *             @OA\Property(property="latitude", type="number", format="float", example=-23.532905),
     *             @OA\Property(property="longitude", type="number", format="float", example=-46.639520),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bairro atualizado com sucesso"
     *     ),
     *     @OA\Response(response=404, description="Bairro não encontrado"),
     *     @OA\Response(response=422, description="Dados de validação inválidos")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $neighborhood = Neighborhood::find($id);

            if (!$neighborhood) {
                return response()->json([
                    'success' => false,
                    'error' => 'Bairro não encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'city_id' => 'sometimes|required|exists:cities,id',
                'state_id' => 'sometimes|required|exists:states,id',
                'country_id' => 'sometimes|required|exists:countries,id',
                'zip_code' => 'nullable|string|max:20',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $neighborhood->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $neighborhood->load(['city', 'state', 'country'])
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar bairro', [
                'error' => $e->getMessage(),
                'neighborhood_id' => $id,
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/locations/neighborhoods/{id}",
     *     summary="Deletar bairro",
     *     description="Remove um bairro do sistema. Requer autenticação de Super Admin.",
     *     tags={"4. Localizações"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bairro deletado com sucesso"
     *     ),
     *     @OA\Response(response=404, description="Bairro não encontrado")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $neighborhood = Neighborhood::find($id);

            if (!$neighborhood) {
                return response()->json([
                    'success' => false,
                    'error' => 'Bairro não encontrado'
                ], 404);
            }

            $neighborhood->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bairro deletado com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao deletar bairro', [
                'error' => $e->getMessage(),
                'neighborhood_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }
}
