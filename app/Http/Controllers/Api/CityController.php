<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="4. Localizações",
 *     description="Endpoints para gestão de cidades"
 * )
 */
class CityController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/locations/cities",
     *     summary="Listar cidades",
     *     description="Retorna uma lista paginada de cidades com filtros opcionais. Rota pública - não requer autenticação.",
     *     tags={"4. Localizações"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Buscar por nome ou código IBGE",
     *         required=false,
     *         @OA\Schema(type="string")
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
     *         description="Lista de cidades retornada com sucesso"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = City::with(['state', 'country']);

            // Filtros
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('ibge_code', 'like', "%{$search}%");
                });
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
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
            $cities = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $cities->items(),
                'current_page' => $cities->currentPage(),
                'last_page' => $cities->lastPage(),
                'per_page' => $cities->perPage(),
                'total' => $cities->total(),
                'from' => $cities->firstItem(),
                'to' => $cities->lastItem(),
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar cidades', [
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
     *     path="/api/locations/cities",
     *     summary="Criar cidade",
     *     description="Cria uma nova cidade. Requer autenticação de Super Admin.",
     *     tags={"4. Localizações"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "state_id", "country_id"},
     *             @OA\Property(property="name", type="string", example="São Paulo"),
     *             @OA\Property(property="state_id", type="integer", example=25),
     *             @OA\Property(property="country_id", type="integer", example=1),
     *             @OA\Property(property="ibge_code", type="string", example="3550308"),
     *             @OA\Property(property="latitude", type="number", format="float", example=-23.532905),
     *             @OA\Property(property="longitude", type="number", format="float", example=-46.639520),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Cidade criada com sucesso"
     *     ),
     *     @OA\Response(response=422, description="Dados de validação inválidos")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'state_id' => 'required|exists:states,id',
                'country_id' => 'required|exists:countries,id',
                'ibge_code' => 'nullable|string|max:20',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $city = City::create($request->all());

            return response()->json([
                'success' => true,
                'data' => $city->load(['state', 'country'])
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao criar cidade', [
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
     *     path="/api/locations/cities/{id}",
     *     summary="Visualizar cidade",
     *     description="Retorna os dados de uma cidade específica. Rota pública - não requer autenticação.",
     *     tags={"4. Localizações"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cidade retornada com sucesso"
     *     ),
     *     @OA\Response(response=404, description="Cidade não encontrada")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $city = City::with(['state', 'country'])->find($id);

            if (!$city) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cidade não encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $city
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao visualizar cidade', [
                'error' => $e->getMessage(),
                'city_id' => $id,
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
     *     path="/api/locations/cities/{id}",
     *     summary="Atualizar cidade",
     *     description="Atualiza os dados de uma cidade. Requer autenticação de Super Admin.",
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
     *             @OA\Property(property="name", type="string", example="São Paulo"),
     *             @OA\Property(property="state_id", type="integer", example=25),
     *             @OA\Property(property="country_id", type="integer", example=1),
     *             @OA\Property(property="ibge_code", type="string", example="3550308"),
     *             @OA\Property(property="latitude", type="number", format="float", example=-23.532905),
     *             @OA\Property(property="longitude", type="number", format="float", example=-46.639520),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cidade atualizada com sucesso"
     *     ),
     *     @OA\Response(response=404, description="Cidade não encontrada"),
     *     @OA\Response(response=422, description="Dados de validação inválidos")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $city = City::find($id);

            if (!$city) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cidade não encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'state_id' => 'sometimes|required|exists:states,id',
                'country_id' => 'sometimes|required|exists:countries,id',
                'ibge_code' => 'nullable|string|max:20',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $city->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $city->load(['state', 'country'])
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar cidade', [
                'error' => $e->getMessage(),
                'city_id' => $id,
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
     *     path="/api/locations/cities/{id}",
     *     summary="Deletar cidade",
     *     description="Remove uma cidade do sistema. Requer autenticação de Super Admin.",
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
     *         description="Cidade deletada com sucesso"
     *     ),
     *     @OA\Response(response=404, description="Cidade não encontrada"),
     *     @OA\Response(response=409, description="Cidade possui dependências")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $city = City::find($id);

            if (!$city) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cidade não encontrada'
                ], 404);
            }

            // Verificar se possui dependências
            if ($city->neighborhoods()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Não é possível deletar a cidade pois possui bairros vinculados'
                ], 409);
            }

            $city->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cidade deletada com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao deletar cidade', [
                'error' => $e->getMessage(),
                'city_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }
}
