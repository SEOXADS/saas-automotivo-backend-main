<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="4. Localizações",
 *     description="Endpoints para gestão de estados"
 * )
 */
class StateController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/locations/states",
     *     summary="Listar estados",
     *     description="Retorna uma lista paginada de estados com filtros opcionais. Rota pública - não requer autenticação.",
     *     tags={"4. Localizações"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Buscar por nome ou código",
     *         required=false,
     *         @OA\Schema(type="string")
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
     *         description="Lista de estados retornada com sucesso"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = State::with('country');

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

            if ($request->filled('country_id')) {
                $query->where('country_id', $request->country_id);
            }

            // Ordenação
            $sort = $request->get('sort', 'name');
            $order = $request->get('order', 'asc');
            $query->orderBy($sort, $order);

            // Paginação
            $perPage = $request->get('per_page', 15);
            $states = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $states->items(),
                'current_page' => $states->currentPage(),
                'last_page' => $states->lastPage(),
                'per_page' => $states->perPage(),
                'total' => $states->total(),
                'from' => $states->firstItem(),
                'to' => $states->lastItem(),
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar estados', [
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
     *     path="/api/locations/states",
     *     summary="Criar estado",
     *     description="Cria um novo estado. Requer autenticação de Super Admin.",
     *     tags={"4. Localizações"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "code", "country_id"},
     *             @OA\Property(property="name", type="string", example="São Paulo"),
     *             @OA\Property(property="code", type="string", example="SP"),
     *             @OA\Property(property="country_id", type="integer", example=1),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Estado criado com sucesso"
     *     ),
     *     @OA\Response(response=422, description="Dados de validação inválidos")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:10',
                'country_id' => 'required|exists:countries,id',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $state = State::create($request->all());

            return response()->json([
                'success' => true,
                'data' => $state->load('country')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao criar estado', [
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
     *     path="/api/locations/states/{id}",
     *     summary="Visualizar estado",
     *     description="Retorna os dados de um estado específico. Rota pública - não requer autenticação.",
     *     tags={"4. Localizações"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estado retornado com sucesso"
     *     ),
     *     @OA\Response(response=404, description="Estado não encontrado")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $state = State::with('country')->find($id);

            if (!$state) {
                return response()->json([
                    'success' => false,
                    'error' => 'Estado não encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $state
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao visualizar estado', [
                'error' => $e->getMessage(),
                'state_id' => $id,
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
     *     path="/api/locations/states/{id}",
     *     summary="Atualizar estado",
     *     description="Atualiza os dados de um estado. Requer autenticação de Super Admin.",
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
     *             @OA\Property(property="code", type="string", example="SP"),
     *             @OA\Property(property="country_id", type="integer", example=1),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estado atualizado com sucesso"
     *     ),
     *     @OA\Response(response=404, description="Estado não encontrado"),
     *     @OA\Response(response=422, description="Dados de validação inválidos")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $state = State::find($id);

            if (!$state) {
                return response()->json([
                    'success' => false,
                    'error' => 'Estado não encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'code' => 'sometimes|required|string|max:10',
                'country_id' => 'sometimes|required|exists:countries,id',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $state->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $state->load('country')
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar estado', [
                'error' => $e->getMessage(),
                'state_id' => $id,
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
     *     path="/api/locations/states/{id}",
     *     summary="Deletar estado",
     *     description="Remove um estado do sistema. Requer autenticação de Super Admin.",
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
     *         description="Estado deletado com sucesso"
     *     ),
     *     @OA\Response(response=404, description="Estado não encontrado"),
     *     @OA\Response(response=409, description="Estado possui dependências")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $state = State::find($id);

            if (!$state) {
                return response()->json([
                    'success' => false,
                    'error' => 'Estado não encontrado'
                ], 404);
            }

            // Verificar se possui dependências
            if ($state->cities()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Não é possível deletar o estado pois possui cidades vinculadas'
                ], 409);
            }

            $state->delete();

            return response()->json([
                'success' => true,
                'message' => 'Estado deletado com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao deletar estado', [
                'error' => $e->getMessage(),
                'state_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }
}
