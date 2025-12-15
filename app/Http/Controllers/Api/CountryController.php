<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="4. Localizações",
 *     description="Endpoints para gestão de países"
 * )
 *
 * @OA\Schema(
 *     schema="Country",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Brasil"),
 *     @OA\Property(property="code", type="string", example="BR"),
 *     @OA\Property(property="phone_code", type="string", example="+55"),
 *     @OA\Property(property="currency", type="string", example="BRL"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class CountryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/locations/countries",
     *     summary="Listar países",
     *     description="Retorna uma lista paginada de países com filtros opcionais. Rota pública - não requer autenticação.",
     *     tags={"4. Localizações"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Buscar por nome ou código",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filtrar por status ativo",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número da página",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Itens por página",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de países retornada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="from", type="integer"),
     *             @OA\Property(property="to", type="integer")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Country::query();

            // Filtros
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Ordenação
            $sort = $request->get('sort', 'name');
            $order = $request->get('order', 'asc');
            $query->orderBy($sort, $order);

            // Paginação
            $perPage = $request->get('per_page', 15);
            $countries = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $countries->items(),
                'current_page' => $countries->currentPage(),
                'last_page' => $countries->lastPage(),
                'per_page' => $countries->perPage(),
                'total' => $countries->total(),
                'from' => $countries->firstItem(),
                'to' => $countries->lastItem(),
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar países', [
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
     *     path="/api/locations/countries",
     *     summary="Criar país",
     *     description="Cria um novo país. Requer autenticação de Super Admin.",
     *     tags={"4. Localizações"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "code"},
     *             @OA\Property(property="name", type="string", example="Brasil"),
     *             @OA\Property(property="code", type="string", example="BR"),
     *             @OA\Property(property="phone_code", type="string", example="+55"),
     *             @OA\Property(property="currency", type="string", example="BRL"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="País criado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/Country")
     *     ),
     *     @OA\Response(response=422, description="Dados de validação inválidos")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:2|unique:countries,code',
                'phone_code' => 'nullable|string|max:10',
                'currency' => 'nullable|string|max:3',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $country = Country::create($request->all());

            return response()->json([
                'success' => true,
                'data' => $country
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao criar país', [
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
     *     path="/api/locations/countries/{id}",
     *     summary="Visualizar país",
     *     description="Retorna os dados de um país específico. Rota pública - não requer autenticação.",
     *     tags={"4. Localizações"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="País retornado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/Country")
     *     ),
     *     @OA\Response(response=404, description="País não encontrado")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $country = Country::find($id);

            if (!$country) {
                return response()->json([
                    'success' => false,
                    'error' => 'País não encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $country
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao visualizar país', [
                'error' => $e->getMessage(),
                'country_id' => $id,
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
     *     path="/api/locations/countries/{id}",
     *     summary="Atualizar país",
     *     description="Atualiza os dados de um país. Requer autenticação de Super Admin.",
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
     *             @OA\Property(property="name", type="string", example="Brasil"),
     *             @OA\Property(property="code", type="string", example="BR"),
     *             @OA\Property(property="phone_code", type="string", example="+55"),
     *             @OA\Property(property="currency", type="string", example="BRL"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="País atualizado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/Country")
     *     ),
     *     @OA\Response(response=404, description="País não encontrado"),
     *     @OA\Response(response=422, description="Dados de validação inválidos")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $country = Country::find($id);

            if (!$country) {
                return response()->json([
                    'success' => false,
                    'error' => 'País não encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'code' => 'sometimes|required|string|max:2|unique:countries,code,' . $id,
                'phone_code' => 'nullable|string|max:10',
                'currency' => 'nullable|string|max:3',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $country->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $country
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar país', [
                'error' => $e->getMessage(),
                'country_id' => $id,
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
     *     path="/api/locations/countries/{id}",
     *     summary="Deletar país",
     *     description="Remove um país do sistema. Requer autenticação de Super Admin.",
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
     *         description="País deletado com sucesso"
     *     ),
     *     @OA\Response(response=404, description="País não encontrado"),
     *     @OA\Response(response=409, description="País possui dependências")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $country = Country::find($id);

            if (!$country) {
                return response()->json([
                    'success' => false,
                    'error' => 'País não encontrado'
                ], 404);
            }

            // Verificar se possui dependências
            if ($country->states()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Não é possível deletar o país pois possui estados vinculados'
                ], 409);
            }

            $country->delete();

            return response()->json([
                'success' => true,
                'message' => 'País deletado com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao deletar país', [
                'error' => $e->getMessage(),
                'country_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }
}
