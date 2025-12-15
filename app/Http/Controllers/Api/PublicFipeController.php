<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FipeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="1. Portal Público",
 *     description="Endpoints públicos do portal de anúncios (sem autenticação)"
 * )
 */
class PublicFipeController extends Controller
{
    protected $fipeService;

    public function __construct(FipeService $fipeService)
    {
        $this->fipeService = $fipeService;
    }

    /**
     * @OA\Get(
     *     path="/api/public/fipe/references",
     *     summary="Obter referências de meses da FIPE (público)",
     *     description="Retorna as referências de meses e códigos da tabela FIPE para acesso público",
     *     tags={"1. Portal Público"},
     *     @OA\Response(
     *         response=200,
     *         description="Referências obtidas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="code", type="string", example="324"),
     *                 @OA\Property(property="month", type="string", example="agosto de 2025")
     *             )),
     *             @OA\Property(property="note", type="string", example="Dados da tabela FIPE oficial")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getReferences(): JsonResponse
    {
        $references = $this->fipeService->getReferences();

        if (!$references) {
            return response()->json(['error' => 'Erro ao obter referências da FIPE'], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $references,
            'note' => 'Dados da tabela FIPE oficial'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/public/fipe/brands/{vehicleType}",
     *     summary="Obter marcas por tipo de veículo (público)",
     *     description="Retorna as marcas disponíveis para o tipo de veículo especificado para acesso público",
     *     tags={"1. Portal Público"},
     *     @OA\Parameter(
     *         name="vehicleType",
     *         in="path",
     *         required=true,
     *         description="Tipo de veículo",
     *         @OA\Schema(type="string", enum={"cars", "motorcycles", "trucks"})
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
     *             @OA\Property(property="note", type="string", example="Dados da tabela FIPE oficial")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Dados inválidos"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getBrands(string $vehicleType): JsonResponse
    {
        $validator = Validator::make(['vehicle_type' => $vehicleType], [
            'vehicle_type' => 'required|in:cars,motorcycles,trucks'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $brands = $this->fipeService->getBrands($vehicleType);

        if (!$brands) {
            return response()->json(['error' => 'Erro ao obter marcas da FIPE'], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $brands,
            'vehicle_type' => $vehicleType,
            'note' => 'Dados da tabela FIPE oficial'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/public/fipe/brands/{vehicleType}/{brandId}/models",
     *     summary="Obter modelos por marca (público)",
     *     description="Retorna os modelos disponíveis para a marca especificada para acesso público",
     *     tags={"1. Portal Público"},
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
     *             @OA\Property(property="note", type="string", example="Dados da tabela FIPE oficial")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Dados inválidos"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getModels(string $vehicleType, int $brandId): JsonResponse
    {
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

        $models = $this->fipeService->getModels($vehicleType, $brandId);

        if (!$models) {
            return response()->json(['error' => 'Erro ao obter modelos da FIPE'], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $models,
            'vehicle_type' => $vehicleType,
            'brand_id' => $brandId,
            'note' => 'Dados da tabela FIPE oficial'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/public/fipe/brands/{vehicleType}/{brandId}/models/{modelId}/years",
     *     summary="Obter anos por modelo (público)",
     *     description="Retorna os anos disponíveis para o modelo especificado para acesso público",
     *     tags={"1. Portal Público"},
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
     *             @OA\Property(property="note", type="string", example="Dados da tabela FIPE oficial")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Dados inválidos"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getYears(string $vehicleType, int $brandId, int $modelId): JsonResponse
    {
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

        $years = $this->fipeService->getYears($vehicleType, $brandId, $modelId);

        if (!$years) {
            return response()->json(['error' => 'Erro ao obter anos da FIPE'], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $years,
            'vehicle_type' => $vehicleType,
            'brand_id' => $brandId,
            'model_id' => $modelId,
            'note' => 'Dados da tabela FIPE oficial'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/public/fipe/search/code/{codeFipe}",
     *     summary="Buscar veículo por código FIPE (público)",
     *     description="Retorna as informações do veículo pelo código FIPE para acesso público",
     *     tags={"1. Portal Público"},
     *     @OA\Parameter(
     *         name="codeFipe",
     *         in="path",
     *         required=true,
     *         description="Código FIPE do veículo",
     *         @OA\Schema(type="string")
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
     *             @OA\Property(property="note", type="string", example="Dados da tabela FIPE oficial")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Dados inválidos"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function searchVehicleByCode(string $codeFipe): JsonResponse
    {
        $validator = Validator::make(['code_fipe' => $codeFipe], [
            'code_fipe' => 'required|string|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vehicleInfo = $this->fipeService->searchVehicleByCode($codeFipe);

        if (!$vehicleInfo) {
            return response()->json(['error' => 'Erro ao buscar veículo por código FIPE'], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $vehicleInfo,
            'code_fipe' => $codeFipe,
            'note' => 'Dados da tabela FIPE oficial'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/public/fipe/search",
     *     summary="Busca avançada de veículos (público)",
     *     description="Realiza busca avançada de veículos na tabela FIPE para acesso público",
     *     tags={"1. Portal Público"},
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
     *             @OA\Property(property="search_params", type="object"),
     *             @OA\Property(property="note", type="string", example="Dados da tabela FIPE oficial")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Dados inválidos"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function searchVehicles(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'vehicle_type' => 'required|in:cars,motorcycles,trucks',
            'brand_id' => 'required|integer|min:1',
            'model_id' => 'required|integer|min:1',
            'year_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vehicleInfo = $this->fipeService->getVehicleInfo(
            $request->vehicle_type,
            $request->brand_id,
            $request->model_id,
            $request->year_id
        );

        if (!$vehicleInfo) {
            return response()->json(['error' => 'Erro ao buscar veículo na FIPE'], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $vehicleInfo,
            'search_params' => $request->only(['vehicle_type', 'brand_id', 'model_id', 'year_id']),
            'note' => 'Dados da tabela FIPE oficial'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/public/fipe/status",
     *     summary="Verificar status da API FIPE (público)",
     *     description="Retorna o status atual da API FIPE para acesso público",
     *     tags={"1. Portal Público"},
     *     @OA\Response(
     *         response=200,
     *         description="Status obtido com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="api_status", type="string", example="online"),
     *                 @OA\Property(property="has_available_calls", type="boolean", example=true),
     *                 @OA\Property(property="last_reference", type="object"),
     *                 @OA\Property(property="timestamp", type="string", example="2025-08-25T07:20:02.808918Z"),
     *                 @OA\Property(property="note", type="string", example="API FIPE Online - Dados oficiais da tabela FIPE")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function getStatus(): JsonResponse
    {
        $hasAvailableCalls = $this->fipeService->hasAvailableCalls();
        $references = $this->fipeService->getReferences();

        return response()->json([
            'success' => true,
            'data' => [
                'api_status' => $references ? 'online' : 'offline',
                'has_available_calls' => $hasAvailableCalls,
                'last_reference' => $references ? $references[0] ?? null : null,
                'timestamp' => now()->toISOString(),
                'note' => 'API FIPE Online - Dados oficiais da tabela FIPE'
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/public/fipe/calculate-price",
     *     summary="Calculadora de preços FIPE (público)",
     *     description="Calcula o preço estimado de um veículo baseado na tabela FIPE e condição",
     *     tags={"1. Portal Público"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"vehicle_type", "brand_id", "model_id", "year_id", "condition"},
     *             @OA\Property(property="vehicle_type", type="string", enum={"cars", "motorcycles", "trucks"}, example="cars"),
     *             @OA\Property(property="brand_id", type="integer", minimum=1, example=59),
     *             @OA\Property(property="model_id", type="integer", minimum=1, example=8070),
     *             @OA\Property(property="year_id", type="string", example="2022-5"),
     *             @OA\Property(property="condition", type="string", enum={"excellent", "good", "regular", "poor"}, example="good")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Preço calculado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="vehicle_info", type="object"),
     *                 @OA\Property(property="price_calculation", type="object",
     *                     @OA\Property(property="base_price_fipe", type="string", example="R$ 70.283,00"),
     *                     @OA\Property(property="condition", type="string", example="good"),
     *                     @OA\Property(property="condition_factor", type="number", example=1.05),
     *                     @OA\Property(property="estimated_price", type="string", example="R$ 73.797,15")
     *                 ),
     *                 @OA\Property(property="note", type="string", example="Preço estimado baseado na tabela FIPE e condição do veículo")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Dados inválidos"),
     *     @OA\Response(response=500, description="Erro interno do servidor")
     * )
     */
    public function calculatePrice(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'vehicle_type' => 'required|in:cars,motorcycles,trucks',
            'brand_id' => 'required|integer|min:1',
            'model_id' => 'required|integer|min:1',
            'year_id' => 'required|string',
            'condition' => 'required|in:excellent,good,regular,poor'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vehicleInfo = $this->fipeService->getVehicleInfo(
            $request->vehicle_type,
            $request->brand_id,
            $request->model_id,
            $request->year_id
        );

        if (!$vehicleInfo) {
            return response()->json(['error' => 'Erro ao buscar veículo na FIPE'], 500);
        }

        // Fatores de condição (baseados no mercado)
        $conditionFactors = [
            'excellent' => 1.15,  // 15% acima da FIPE
            'good' => 1.05,       // 5% acima da FIPE
            'regular' => 0.95,    // 5% abaixo da FIPE
            'poor' => 0.80        // 20% abaixo da FIPE
        ];

        $basePrice = $this->extractPriceFromFipe($vehicleInfo['price']);
        $conditionFactor = $conditionFactors[$request->condition];
        $estimatedPrice = $basePrice * $conditionFactor;

        return response()->json([
            'success' => true,
            'data' => [
                'vehicle_info' => $vehicleInfo,
                'price_calculation' => [
                    'base_price_fipe' => $vehicleInfo['price'],
                    'base_price_numeric' => $basePrice,
                    'condition' => $request->condition,
                    'condition_factor' => $conditionFactor,
                    'estimated_price' => 'R$ ' . number_format($estimatedPrice, 2, ',', '.'),
                    'estimated_price_numeric' => $estimatedPrice
                ],
                'note' => 'Preço estimado baseado na tabela FIPE e condição do veículo'
            ]
        ]);
    }

    /**
     * Extrair preço numérico da string da FIPE
     */
    private function extractPriceFromFipe(string $priceString): float
    {
        // Remove "R$ " e converte para float
        $cleanPrice = str_replace(['R$ ', '.', ','], ['', '', '.'], $priceString);
        return (float) $cleanPrice;
    }
}
