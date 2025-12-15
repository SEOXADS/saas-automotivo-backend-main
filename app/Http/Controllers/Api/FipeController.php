<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FipeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Helpers\TokenHelper;

/**
 * @OA\Tag(
 *     name="2. Admin Cliente",
 *     description="Endpoints para administradores do tenant (autenticação e gestão)"
 * )
 */
class FipeController extends Controller
{
    protected $fipeService;

    public function __construct(FipeService $fipeService)
    {
        $this->fipeService = $fipeService;
    }

    /**
     * @OA\Get(
     *     path="/api/fipe/references",
     *     summary="Obter referências de meses da FIPE",
     *     description="Retorna as referências de meses e códigos da tabela FIPE",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Referências obtidas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="code", type="string", example="324"),
     *                 @OA\Property(property="month", type="string", example="agosto de 2025")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Usuário não autenticado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getReferences(Request $request): JsonResponse
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $references = $this->fipeService->getReferences();

        if (!$references) {
            return response()->json(['error' => 'Erro ao obter referências da FIPE'], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $references
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/fipe/brands/{vehicleType}",
     *     summary="Obter marcas por tipo de veículo",
     *     description="Retorna as marcas disponíveis para o tipo de veículo especificado",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="vehicleType",
     *         in="path",
     *         required=true,
     *         description="Tipo de veículo",
     *         @OA\Schema(type="string", enum={"cars", "motorcycles", "trucks"})
     *     ),
     *     @OA\Parameter(
     *         name="reference",
     *         in="query",
     *         required=false,
     *         description="Código de referência do mês",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Marcas obtidas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="code", type="string", example="59"),
     *                 @OA\Property(property="name", type="string", example="VW - VolksWagen")
     *             )),
     *             @OA\Property(property="vehicle_type", type="string", example="cars"),
     *             @OA\Property(property="reference", type="integer", example=324)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Usuário não autenticado"),
     *     @OA\Response(response=422, description="Dados inválidos"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getBrands(Request $request, string $vehicleType): JsonResponse
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $validator = Validator::make(['vehicle_type' => $vehicleType], [
            'vehicle_type' => 'required|in:cars,motorcycles,trucks'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $reference = $request->get('reference');
        $brands = $this->fipeService->getBrands($vehicleType, $reference);

        if (!$brands) {
            return response()->json(['error' => 'Erro ao obter marcas da FIPE'], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $brands,
            'vehicle_type' => $vehicleType,
            'reference' => $reference
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/fipe/brands/{vehicleType}/{brandId}/models",
     *     summary="Obter modelos por marca",
     *     description="Retorna os modelos disponíveis para a marca especificada",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="vehicleType",
     *         in="path",
     *         required=true,
     *         description="Tipo de veículo",
     *         @OA\Schema(type="string", enum={"cars", "motorcycles", "trucks"})
     *     ),
     *     @OA\Parameter(
     *         name="brandId",
     *         in="path",
     *         required=true,
     *         description="ID da marca",
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="reference",
     *         in="query",
     *         required=false,
     *         description="Código de referência do mês",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Modelos obtidos com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="code", type="string", example="8070"),
     *                 @OA\Property(property="name", type="string", example="Polo 1.0 Flex 12V 5p")
     *             )),
     *             @OA\Property(property="vehicle_type", type="string", example="cars"),
     *             @OA\Property(property="brand_id", type="integer", example=59),
     *             @OA\Property(property="reference", type="integer", example=324)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Usuário não autenticado"),
     *     @OA\Response(response=422, description="Dados inválidos"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getModels(Request $request, string $vehicleType, int $brandId): JsonResponse
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $validator = Validator::make([
            'vehicle_type' => $vehicleType,
            'brand_id' => $brandId
        ], [
            'vehicle_type' => 'required|in:cars,motorcycles,trucks',
            'brand_id' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $reference = $request->get('reference');
        $models = $this->fipeService->getModels($vehicleType, $brandId, $reference);

        if (!$models) {
            return response()->json(['error' => 'Erro ao obter modelos da FIPE'], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $models,
            'vehicle_type' => $vehicleType,
            'brand_id' => $brandId,
            'reference' => $reference
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/fipe/brands/{vehicleType}/{brandId}/models/{modelId}/years",
     *     summary="Obter anos por modelo",
     *     description="Retorna os anos disponíveis para o modelo especificado",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="vehicleType",
     *         in="path",
     *         required=true,
     *         description="Tipo de veículo",
     *         @OA\Schema(type="string", enum={"cars", "motorcycles", "trucks"})
     *     ),
     *     @OA\Parameter(
     *         name="brandId",
     *         in="path",
     *         required=true,
     *         description="ID da marca",
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="modelId",
     *         in="path",
     *         required=true,
     *         description="ID do modelo",
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="reference",
     *         in="query",
     *         required=false,
     *         description="Código de referência do mês",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Anos obtidos com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="code", type="string", example="2022-5"),
     *                 @OA\Property(property="name", type="string", example="2022 Flex")
     *             )),
     *             @OA\Property(property="vehicle_type", type="string", example="cars"),
     *             @OA\Property(property="brand_id", type="integer", example=59),
     *             @OA\Property(property="model_id", type="integer", example=8070),
     *             @OA\Property(property="reference", type="integer", example=324)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Usuário não autenticado"),
     *     @OA\Response(response=422, description="Dados inválidos"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getYears(Request $request, string $vehicleType, int $brandId, int $modelId): JsonResponse
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $validator = Validator::make([
            'vehicle_type' => $vehicleType,
            'brand_id' => $brandId,
            'model_id' => $modelId
        ], [
            'vehicle_type' => 'required|in:cars,motorcycles,trucks',
            'brand_id' => 'required|integer|min:1',
            'model_id' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $reference = $request->get('reference');
        $years = $this->fipeService->getYears($vehicleType, $brandId, $modelId, $reference);

        if (!$years) {
            return response()->json(['error' => 'Erro ao obter anos da FIPE'], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $years,
            'vehicle_type' => $vehicleType,
            'brand_id' => $brandId,
            'model_id' => $modelId,
            'reference' => $reference
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/fipe/vehicle/{vehicleType}/{brandId}/{modelId}/{yearId}",
     *     summary="Obter informações completas do veículo",
     *     description="Retorna as informações completas do veículo da tabela FIPE",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="vehicleType",
     *         in="path",
     *         required=true,
     *         description="Tipo de veículo",
     *         @OA\Schema(type="string", enum={"cars", "motorcycles", "trucks"})
     *     ),
     *     @OA\Parameter(
     *         name="brandId",
     *         in="path",
     *         required=true,
     *         description="ID da marca",
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="modelId",
     *         in="path",
     *         required=true,
     *         description="ID do modelo",
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="yearId",
     *         in="path",
     *         required=true,
     *         description="ID do ano",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="reference",
     *         in="query",
     *         required=false,
     *         description="Código de referência do mês",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Informações do veículo obtidas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="brand", type="string", example="VW - VolksWagen"),
     *                 @OA\Property(property="model", type="string", example="Polo 1.0 Flex 12V 5p"),
     *                 @OA\Property(property="modelYear", type="integer", example=2022),
     *                 @OA\Property(property="fuel", type="string", example="Flex"),
     *                 @OA\Property(property="price", type="string", example="R$ 70.283,00"),
     *                 @OA\Property(property="codeFipe", type="string", example="005479-8")
     *             ),
     *             @OA\Property(property="vehicle_type", type="string", example="cars"),
     *             @OA\Property(property="brand_id", type="integer", example=59),
     *             @OA\Property(property="model_id", type="integer", example=8070),
     *             @OA\Property(property="year_id", type="string", example="2022-5"),
     *             @OA\Property(property="reference", type="integer", example=324)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Usuário não autenticado"),
     *     @OA\Response(response=422, description="Dados inválidos"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getVehicleInfo(Request $request, string $vehicleType, int $brandId, int $modelId, string $yearId): JsonResponse
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $validator = Validator::make([
            'vehicle_type' => $vehicleType,
            'brand_id' => $brandId,
            'model_id' => $modelId,
            'year_id' => $yearId
        ], [
            'vehicle_type' => 'required|in:cars,motorcycles,trucks',
            'brand_id' => 'required|integer|min:1',
            'model_id' => 'required|integer|min:1',
            'year_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $reference = $request->get('reference');
        $vehicleInfo = $this->fipeService->getVehicleInfo($vehicleType, $brandId, $modelId, $yearId, $reference);

        if (!$vehicleInfo) {
            return response()->json(['error' => 'Erro ao obter informações do veículo da FIPE'], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $vehicleInfo,
            'vehicle_type' => $vehicleType,
            'brand_id' => $brandId,
            'model_id' => $modelId,
            'year_id' => $yearId,
            'reference' => $reference
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/fipe/search/code/{codeFipe}",
     *     summary="Buscar veículo por código FIPE",
     *     description="Retorna as informações do veículo pelo código FIPE",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="codeFipe",
     *         in="path",
     *         required=true,
     *         description="Código FIPE do veículo",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="reference",
     *         in="query",
     *         required=false,
     *         description="Código de referência do mês",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Veículo encontrado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="brand", type="string", example="VW - VolksWagen"),
     *                 @OA\Property(property="model", type="string", example="Polo 1.0 Flex 12V 5p"),
     *                 @OA\Property(property="price", type="string", example="R$ 70.283,00")
     *             ),
     *             @OA\Property(property="code_fipe", type="string", example="005479-8"),
     *             @OA\Property(property="reference", type="integer", example=324)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Usuário não autenticado"),
     *     @OA\Response(response=422, description="Dados inválidos"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function searchVehicleByCode(Request $request, string $codeFipe): JsonResponse
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $validator = Validator::make(['code_fipe' => $codeFipe], [
            'code_fipe' => 'required|string|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $reference = $request->get('reference');
        $vehicleInfo = $this->fipeService->searchVehicleByCode($codeFipe, $reference);

        if (!$vehicleInfo) {
            return response()->json(['error' => 'Erro ao buscar veículo por código FIPE'], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $vehicleInfo,
            'code_fipe' => $codeFipe,
            'reference' => $reference
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/fipe/search",
     *     summary="Busca avançada de veículos",
     *     description="Realiza busca avançada de veículos na tabela FIPE",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="vehicle_type",
     *         in="query",
     *         required=true,
     *         description="Tipo de veículo",
     *         @OA\Schema(type="string", enum={"cars", "motorcycles", "trucks"})
     *     ),
     *     @OA\Parameter(
     *         name="brand_id",
     *         in="query",
     *         required=true,
     *         description="ID da marca",
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="model_id",
     *         in="query",
     *         required=true,
     *         description="ID do modelo",
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="year_id",
     *         in="query",
     *         required=true,
     *         description="ID do ano",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="reference",
     *         in="query",
     *         required=false,
     *         description="Código de referência do mês",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Veículo encontrado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="brand", type="string", example="VW - VolksWagen"),
     *                 @OA\Property(property="model", type="string", example="Polo 1.0 Flex 12V 5p"),
     *                 @OA\Property(property="price", type="string", example="R$ 70.283,00")
     *             ),
     *             @OA\Property(property="search_params", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Usuário não autenticado"),
     *     @OA\Response(response=422, description="Dados inválidos"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function searchVehicles(Request $request): JsonResponse
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $validator = Validator::make($request->all(), [
            'vehicle_type' => 'required|in:cars,motorcycles,trucks',
            'brand_id' => 'required|integer|min:1',
            'model_id' => 'required|integer|min:1',
            'year_id' => 'required|string',
            'reference' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vehicleInfo = $this->fipeService->getVehicleInfo(
            $request->vehicle_type,
            $request->brand_id,
            $request->model_id,
            $request->year_id,
            $request->reference
        );

        if (!$vehicleInfo) {
            return response()->json(['error' => 'Erro ao buscar veículo na FIPE'], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $vehicleInfo,
            'search_params' => $request->only(['vehicle_type', 'brand_id', 'model_id', 'year_id', 'reference'])
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/fipe/usage-stats",
     *     summary="Obter estatísticas de uso da API FIPE",
     *     description="Retorna estatísticas de uso da API FIPE (apenas Super Admin)",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Estatísticas obtidas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="date", type="string", example="2025-08-25"),
     *                 @OA\Property(property="total_calls", type="integer", example=45),
     *                 @OA\Property(property="remaining_calls", type="integer", example=455),
     *                 @OA\Property(property="rate_limit", type="integer", example=500)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Usuário não autenticado"),
     *     @OA\Response(response=403, description="Acesso negado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getUsageStats(Request $request): JsonResponse
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        if ($user->role !== 'super_admin') {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $stats = $this->fipeService->getUsageStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/fipe/cache/clear",
     *     summary="Limpar cache da FIPE",
     *     description="Limpa todo o cache da API FIPE (apenas Super Admin)",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cache limpo com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message", type="string", example="Cache da FIPE limpo com sucesso")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Usuário não autenticado"),
     *     @OA\Response(response=403, description="Acesso negado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function clearCache(Request $request): JsonResponse
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        if ($user->role !== 'super_admin') {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $result = $this->fipeService->clearCache();

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/fipe/status",
     *     summary="Verificar status da API FIPE",
     *     description="Retorna o status atual da API FIPE",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Status obtido com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="api_status", type="string", example="online"),
     *                 @OA\Property(property="has_available_calls", type="boolean", example=true),
     *                 @OA\Property(property="last_reference", type="object"),
     *                 @OA\Property(property="timestamp", type="string", example="2025-08-25T07:20:02.808918Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Usuário não autenticado"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getStatus(Request $request): JsonResponse
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $hasAvailableCalls = $this->fipeService->hasAvailableCalls();
        $references = $this->fipeService->getReferences();

        return response()->json([
            'success' => true,
            'data' => [
                'api_status' => $references ? 'online' : 'offline',
                'has_available_calls' => $hasAvailableCalls,
                'last_reference' => $references ? $references[0] ?? null : null,
                'timestamp' => now()->toISOString()
            ]
        ]);
    }
}
