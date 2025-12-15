<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantLocationRequest;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Neighborhood;
use App\Models\TenantCountry;
use App\Models\TenantState;
use App\Models\TenantCity;
use App\Models\TenantNeighborhood;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="2. Admin Cliente - Localizações",
 *     description="Endpoints para gestão de localizações por tenant"
 * )
 */
class TenantLocationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/tenant/locations/countries",
     *     summary="Listar países disponíveis para o tenant",
     *     description="Retorna uma lista de países que o tenant pode utilizar. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - Localizações"},
     *     security={{"sanctum":{}}},
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
     *     @OA\Response(
     *         response=200,
     *         description="Lista de países retornada com sucesso"
     *     )
     * )
     */
    /**
     * Obter tenant atual da requisição
     */
    private function getCurrentTenant(Request $request)
    {
        // Tentar obter do middleware tenant.auto
        $tenant = $request->attributes->get('current_tenant');

        if (!$tenant) {
            // Fallback: tentar obter do request merge
            $tenant = $request->get('current_tenant');
        }

        if (!$tenant) {
            throw new \Exception('Tenant não identificado');
        }

        return $tenant;
    }

    public function getCountries(Request $request): JsonResponse
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $tenantId = $tenant->id;

            $query = Country::whereHas('tenants', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            });

            // Filtros
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }

            if ($request->has('is_active')) {
                $isActive = $request->boolean('is_active');
                $query->whereHas('tenants', function ($q) use ($tenantId, $isActive) {
                    $q->where('tenant_id', $tenantId)
                      ->where('is_active', $isActive);
                });
            }

            $countries = $query->orderBy('name')->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $countries
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar países do tenant', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/tenant/locations/countries",
     *     summary="Adicionar país ao tenant",
     *     description="Adiciona um país existente à lista de países disponíveis para o tenant. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - Localizações"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"country_id"},
     *             @OA\Property(property="country_id", type="integer", example=1),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="País adicionado ao tenant com sucesso"
     *     ),
     *     @OA\Response(response=422, description="Dados de validação inválidos")
     * )
     */
    public function addCountry(Request $request): JsonResponse
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'code' => 'required|string|size:2|unique:countries,code',
                'phone_code' => 'nullable|string|max:10',
                'currency' => 'nullable|string|size:3',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create public country first
            $country = Country::create([
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'phone_code' => $request->phone_code,
                'currency' => $request->currency ? strtoupper($request->currency) : null,
                'is_active' => $request->get('is_active', true)
            ]);

            // Then associate with tenant
            $tenantCountry = TenantCountry::create([
                'tenant_id' => $tenant->id,
                'country_id' => $country->id,
                'is_active' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'País criado e associado ao tenant com sucesso',
                'data' => $country
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao criar país:', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/tenant/locations/countries/{id}",
     *     summary="Atualizar status do país no tenant",
     *     description="Atualiza o status de ativação de um país para o tenant. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - Localizações"},
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
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status do país atualizado com sucesso"
     *     ),
     *     @OA\Response(response=404, description="País não encontrado para este tenant")
     * )
     */
    public function updateCountry(Request $request, int $id): JsonResponse
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $tenantId = $tenant->id;

            $tenantCountry = TenantCountry::where('tenant_id', $tenantId)
                                        ->where('id', $id)
                                        ->first();

            if (!$tenantCountry) {
                return response()->json([
                    'success' => false,
                    'error' => 'País não encontrado para este tenant'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $tenantCountry->update([
                'is_active' => $request->get('is_active', $tenantCountry->is_active)
            ]);

            $country = Country::find($tenantCountry->country_id);

            return response()->json([
                'success' => true,
                'message' => 'Status do país atualizado com sucesso',
                'data' => [
                    'id' => $tenantCountry->id,
                    'country' => $country,
                    'is_active' => $tenantCountry->is_active,
                    'updated_at' => $tenantCountry->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar país do tenant', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
                'tenant_country_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/tenant/locations/countries/{id}",
     *     summary="Remover país do tenant",
     *     description="Remove um país da lista de países disponíveis para o tenant. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - Localizações"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="País removido do tenant com sucesso"
     *     ),
     *     @OA\Response(response=404, description="País não encontrado para este tenant")
     * )
     */
    public function removeCountry(Request $request, int $id): JsonResponse
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $tenantId = $tenant->id;

            $tenantCountry = TenantCountry::where('tenant_id', $tenantId)
                                        ->where('id', $id)
                                        ->first();

            if (!$tenantCountry) {
                return response()->json([
                    'success' => false,
                    'error' => 'País não encontrado para este tenant'
                ], 404);
            }

            $country = Country::find($tenantCountry->country_id);
            $tenantCountry->delete();

            return response()->json([
                'success' => true,
                'message' => 'País removido do tenant com sucesso',
                'data' => [
                    'country' => $country
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao remover país do tenant', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
                'tenant_country_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/tenant/locations/available-countries",
     *     summary="Listar países disponíveis para adicionar",
     *     description="Retorna uma lista de países que ainda não foram adicionados ao tenant. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - Localizações"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Buscar por nome ou código",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de países disponíveis retornada com sucesso"
     *     )
     * )
     */
    public function getAvailableCountries(Request $request): JsonResponse
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $tenantId = $tenant->id;

            $query = Country::whereDoesntHave('tenants', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            });

            // Filtros
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }

            $countries = $query->active()->ordered()->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $countries->items(),
                'pagination' => [
                    'current_page' => $countries->currentPage(),
                    'last_page' => $countries->lastPage(),
                    'per_page' => $countries->perPage(),
                    'total' => $countries->total(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar países disponíveis', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/tenant/locations/states",
     *     summary="Listar estados disponíveis para o tenant",
     *     description="Retorna uma lista de estados que o tenant pode utilizar. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - Localizações"},
     *     security={{"sanctum":{}}},
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
    public function getStates(Request $request): JsonResponse
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $tenantId = $tenant->id;

            $query = State::whereHas('tenants', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })->with('country');

            // Filtros
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }

            if ($request->filled('country_id')) {
                $query->where('country_id', $request->country_id);
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $states = $query->active()->ordered()->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $states->items(),
                'pagination' => [
                    'current_page' => $states->currentPage(),
                    'last_page' => $states->lastPage(),
                    'per_page' => $states->perPage(),
                    'total' => $states->total(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar estados do tenant', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/tenant/locations/states",
     *     summary="Adicionar estado ao tenant",
     *     description="Adiciona um estado existente à lista de estados disponíveis para o tenant. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - Localizações"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"state_id"},
     *             @OA\Property(property="state_id", type="integer", example=25),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Estado adicionado ao tenant com sucesso"
     *     ),
     *     @OA\Response(response=422, description="Dados de validação inválidos")
     * )
     */
    public function addState(TenantLocationRequest $request): JsonResponse
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $tenantId = $tenant->id;

            $tenantState = TenantState::create([
                'tenant_id' => $tenantId,
                'state_id' => $request->state_id,
                'is_active' => $request->get('is_active', true)
            ]);

            $state = State::with('country')->find($request->state_id);

            return response()->json([
                'success' => true,
                'message' => 'Estado adicionado ao tenant com sucesso',
                'data' => [
                    'id' => $tenantState->id,
                    'state' => $state,
                    'is_active' => $tenantState->is_active,
                    'created_at' => $tenantState->created_at
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao adicionar estado ao tenant', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
                'state_id' => $request->state_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/tenant/locations/states/{id}",
     *     summary="Atualizar status do estado no tenant",
     *     description="Atualiza o status de ativação de um estado para o tenant. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - Localizações"},
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
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status do estado atualizado com sucesso"
     *     ),
     *     @OA\Response(response=404, description="Estado não encontrado para este tenant")
     * )
     */
    public function updateState(Request $request, int $id): JsonResponse
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $tenantId = $tenant->id;

            $tenantState = TenantState::where('tenant_id', $tenantId)
                                    ->where('id', $id)
                                    ->first();

            if (!$tenantState) {
                return response()->json([
                    'success' => false,
                    'error' => 'Estado não encontrado para este tenant'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $tenantState->update([
                'is_active' => $request->get('is_active', $tenantState->is_active)
            ]);

            $state = State::with('country')->find($tenantState->state_id);

            return response()->json([
                'success' => true,
                'message' => 'Status do estado atualizado com sucesso',
                'data' => [
                    'id' => $tenantState->id,
                    'state' => $state,
                    'is_active' => $tenantState->is_active,
                    'updated_at' => $tenantState->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar estado do tenant', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
                'tenant_state_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/tenant/locations/states/{id}",
     *     summary="Remover estado do tenant",
     *     description="Remove um estado da lista de estados disponíveis para o tenant. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - Localizações"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estado removido do tenant com sucesso"
     *     ),
     *     @OA\Response(response=404, description="Estado não encontrado para este tenant")
     * )
     */
    public function removeState(Request $request, int $id): JsonResponse
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $tenantId = $tenant->id;

            $tenantState = TenantState::where('tenant_id', $tenantId)
                                    ->where('id', $id)
                                    ->first();

            if (!$tenantState) {
                return response()->json([
                    'success' => false,
                    'error' => 'Estado não encontrado para este tenant'
                ], 404);
            }

            $state = State::with('country')->find($tenantState->state_id);
            $tenantState->delete();

            return response()->json([
                'success' => true,
                'message' => 'Estado removido do tenant com sucesso',
                'data' => [
                    'state' => $state
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao remover estado do tenant', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
                'tenant_state_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/tenant/locations/available-states",
     *     summary="Listar estados disponíveis para adicionar",
     *     description="Retorna uma lista de estados que ainda não foram adicionados ao tenant. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - Localizações"},
     *     security={{"sanctum":{}}},
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
     *     @OA\Response(
     *         response=200,
     *         description="Lista de estados disponíveis retornada com sucesso"
     *     )
     * )
     */
    public function getAvailableStates(Request $request): JsonResponse
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $tenantId = $tenant->id;

            $query = State::whereDoesntHave('tenants', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })->with('country');

            // Filtros
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }

            if ($request->filled('country_id')) {
                $query->where('country_id', $request->country_id);
            }

            $states = $query->active()->ordered()->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $states->items(),
                'pagination' => [
                    'current_page' => $states->currentPage(),
                    'last_page' => $states->lastPage(),
                    'per_page' => $states->perPage(),
                    'total' => $states->total(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar estados disponíveis', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/tenant/locations/cities",
     *     summary="Listar cidades disponíveis para o tenant",
     *     description="Retorna uma lista de cidades que o tenant pode utilizar. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - Localizações"},
     *     security={{"sanctum":{}}},
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
    public function getCities(Request $request): JsonResponse
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $tenantId = $tenant->id;

            $query = City::whereHas('tenants', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })->with(['state', 'country']);

            // Filtros
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('ibge_code', 'like', "%{$search}%");
                });
            }

            if ($request->filled('state_id')) {
                $query->where('state_id', $request->state_id);
            }

            if ($request->filled('country_id')) {
                $query->where('country_id', $request->country_id);
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $cities = $query->active()->ordered()->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $cities->items(),
                'pagination' => [
                    'current_page' => $cities->currentPage(),
                    'last_page' => $cities->lastPage(),
                    'per_page' => $cities->perPage(),
                    'total' => $cities->total(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar cidades do tenant', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/tenant/locations/cities",
     *     summary="Adicionar cidade ao tenant",
     *     description="Adiciona uma cidade existente à lista de cidades disponíveis para o tenant. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - Localizações"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"city_id"},
     *             @OA\Property(property="city_id", type="integer", example=1),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Cidade adicionada ao tenant com sucesso"
     *     ),
     *     @OA\Response(response=422, description="Dados de validação inválidos")
     * )
     */
    public function addCity(TenantLocationRequest $request): JsonResponse
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $tenantId = $tenant->id;

            $tenantCity = TenantCity::create([
                'tenant_id' => $tenantId,
                'city_id' => $request->city_id,
                'is_active' => $request->get('is_active', true)
            ]);

            $city = City::with(['state', 'country'])->find($request->city_id);

            return response()->json([
                'success' => true,
                'message' => 'Cidade adicionada ao tenant com sucesso',
                'data' => [
                    'id' => $tenantCity->id,
                    'city' => $city,
                    'is_active' => $tenantCity->is_active,
                    'created_at' => $tenantCity->created_at
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao adicionar cidade ao tenant', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
                'city_id' => $request->city_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/tenant/locations/cities/{id}",
     *     summary="Atualizar status da cidade no tenant",
     *     description="Atualiza o status de ativação de uma cidade para o tenant. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - Localizações"},
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
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status da cidade atualizado com sucesso"
     *     ),
     *     @OA\Response(response=404, description="Cidade não encontrada para este tenant")
     * )
     */
    public function updateCity(Request $request, int $id): JsonResponse
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $tenantId = $tenant->id;

            $tenantCity = TenantCity::where('tenant_id', $tenantId)
                                   ->where('id', $id)
                                   ->first();

            if (!$tenantCity) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cidade não encontrada para este tenant'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $tenantCity->update([
                'is_active' => $request->get('is_active', $tenantCity->is_active)
            ]);

            $city = City::with(['state', 'country'])->find($tenantCity->city_id);

            return response()->json([
                'success' => true,
                'message' => 'Status da cidade atualizado com sucesso',
                'data' => [
                    'id' => $tenantCity->id,
                    'city' => $city,
                    'is_active' => $tenantCity->is_active,
                    'updated_at' => $tenantCity->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar cidade do tenant', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
                'tenant_city_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/tenant/locations/cities/{id}",
     *     summary="Remover cidade do tenant",
     *     description="Remove uma cidade da lista de cidades disponíveis para o tenant. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - Localizações"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cidade removida do tenant com sucesso"
     *     ),
     *     @OA\Response(response=404, description="Cidade não encontrada para este tenant")
     * )
     */
    public function removeCity(Request $request, int $id): JsonResponse
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $tenantId = $tenant->id;

            $tenantCity = TenantCity::where('tenant_id', $tenantId)
                                   ->where('id', $id)
                                   ->first();

            if (!$tenantCity) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cidade não encontrada para este tenant'
                ], 404);
            }

            $city = City::with(['state', 'country'])->find($tenantCity->city_id);
            $tenantCity->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cidade removida do tenant com sucesso',
                'data' => [
                    'city' => $city
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao remover cidade do tenant', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
                'tenant_city_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/tenant/locations/available-cities",
     *     summary="Listar cidades disponíveis para adicionar",
     *     description="Retorna uma lista de cidades que ainda não foram adicionadas ao tenant. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - Localizações"},
     *     security={{"sanctum":{}}},
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
     *     @OA\Response(
     *         response=200,
     *         description="Lista de cidades disponíveis retornada com sucesso"
     *     )
     * )
     */
    public function getAvailableCities(Request $request): JsonResponse
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $tenantId = $tenant->id;

            $query = City::whereDoesntHave('tenants', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })->with(['state', 'country']);

            // Filtros
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('ibge_code', 'like', "%{$search}%");
                });
            }

            if ($request->filled('state_id')) {
                $query->where('state_id', $request->state_id);
            }

            if ($request->filled('country_id')) {
                $query->where('country_id', $request->country_id);
            }

            $cities = $query->active()->ordered()->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $cities->items(),
                'pagination' => [
                    'current_page' => $cities->currentPage(),
                    'last_page' => $cities->lastPage(),
                    'per_page' => $cities->perPage(),
                    'total' => $cities->total(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar cidades disponíveis', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/tenant/locations/neighborhoods",
     *     summary="Listar bairros disponíveis para o tenant",
     *     description="Retorna uma lista de bairros que o tenant pode utilizar. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - Localizações"},
     *     security={{"sanctum":{}}},
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
    public function getNeighborhoods(Request $request): JsonResponse
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $tenantId = $tenant->id;

            $query = Neighborhood::whereHas('tenants', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })->with(['city', 'state', 'country']);

            // Filtros
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
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

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $neighborhoods = $query->active()->ordered()->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $neighborhoods->items(),
                'pagination' => [
                    'current_page' => $neighborhoods->currentPage(),
                    'last_page' => $neighborhoods->lastPage(),
                    'per_page' => $neighborhoods->perPage(),
                    'total' => $neighborhoods->total(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar bairros do tenant', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/tenant/locations/neighborhoods",
     *     summary="Adicionar bairro ao tenant",
     *     description="Adiciona um bairro existente à lista de bairros disponíveis para o tenant. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - Localizações"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"neighborhood_id"},
     *             @OA\Property(property="neighborhood_id", type="integer", example=1),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Bairro adicionado ao tenant com sucesso"
     *     ),
     *     @OA\Response(response=422, description="Dados de validação inválidos")
     * )
     */
    public function addNeighborhood(TenantLocationRequest $request): JsonResponse
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $tenantId = $tenant->id;

            $tenantNeighborhood = TenantNeighborhood::create([
                'tenant_id' => $tenantId,
                'neighborhood_id' => $request->neighborhood_id,
                'is_active' => $request->get('is_active', true)
            ]);

            $neighborhood = Neighborhood::with(['city', 'state', 'country'])->find($request->neighborhood_id);

            return response()->json([
                'success' => true,
                'message' => 'Bairro adicionado ao tenant com sucesso',
                'data' => [
                    'id' => $tenantNeighborhood->id,
                    'neighborhood' => $neighborhood,
                    'is_active' => $tenantNeighborhood->is_active,
                    'created_at' => $tenantNeighborhood->created_at
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao adicionar bairro ao tenant', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
                'neighborhood_id' => $request->neighborhood_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/tenant/locations/neighborhoods/{id}",
     *     summary="Atualizar status do bairro no tenant",
     *     description="Atualiza o status de ativação de um bairro para o tenant. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - Localizações"},
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
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status do bairro atualizado com sucesso"
     *     ),
     *     @OA\Response(response=404, description="Bairro não encontrado para este tenant")
     * )
     */
    public function updateNeighborhood(Request $request, int $id): JsonResponse
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $tenantId = $tenant->id;

            $tenantNeighborhood = TenantNeighborhood::where('tenant_id', $tenantId)
                                                   ->where('id', $id)
                                                   ->first();

            if (!$tenantNeighborhood) {
                return response()->json([
                    'success' => false,
                    'error' => 'Bairro não encontrado para este tenant'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $tenantNeighborhood->update([
                'is_active' => $request->get('is_active', $tenantNeighborhood->is_active)
            ]);

            $neighborhood = Neighborhood::with(['city', 'state', 'country'])->find($tenantNeighborhood->neighborhood_id);

            return response()->json([
                'success' => true,
                'message' => 'Status do bairro atualizado com sucesso',
                'data' => [
                    'id' => $tenantNeighborhood->id,
                    'neighborhood' => $neighborhood,
                    'is_active' => $tenantNeighborhood->is_active,
                    'updated_at' => $tenantNeighborhood->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar bairro do tenant', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
                'tenant_neighborhood_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/tenant/locations/neighborhoods/{id}",
     *     summary="Remover bairro do tenant",
     *     description="Remove um bairro da lista de bairros disponíveis para o tenant. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - Localizações"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bairro removido do tenant com sucesso"
     *     ),
     *     @OA\Response(response=404, description="Bairro não encontrado para este tenant")
     * )
     */
    public function removeNeighborhood(Request $request, int $id): JsonResponse
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $tenantId = $tenant->id;

            $tenantNeighborhood = TenantNeighborhood::where('tenant_id', $tenantId)
                                                   ->where('id', $id)
                                                   ->first();

            if (!$tenantNeighborhood) {
                return response()->json([
                    'success' => false,
                    'error' => 'Bairro não encontrado para este tenant'
                ], 404);
            }

            $neighborhood = Neighborhood::with(['city', 'state', 'country'])->find($tenantNeighborhood->neighborhood_id);
            $tenantNeighborhood->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bairro removido do tenant com sucesso',
                'data' => [
                    'neighborhood' => $neighborhood
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao remover bairro do tenant', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
                'tenant_neighborhood_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/tenant/locations/available-neighborhoods",
     *     summary="Listar bairros disponíveis para adicionar",
     *     description="Retorna uma lista de bairros que ainda não foram adicionados ao tenant. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - Localizações"},
     *     security={{"sanctum":{}}},
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
     *     @OA\Response(
     *         response=200,
     *         description="Lista de bairros disponíveis retornada com sucesso"
     *     )
     * )
     */
    public function getAvailableNeighborhoods(Request $request): JsonResponse
    {
        try {
            $tenant = $this->getCurrentTenant($request);
            $tenantId = $tenant->id;

            $query = Neighborhood::whereDoesntHave('tenants', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })->with(['city', 'state', 'country']);

            // Filtros
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
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

            $neighborhoods = $query->active()->ordered()->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $neighborhoods->items(),
                'pagination' => [
                    'current_page' => $neighborhoods->currentPage(),
                    'last_page' => $neighborhoods->lastPage(),
                    'per_page' => $neighborhoods->perPage(),
                    'total' => $neighborhoods->total(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar bairros disponíveis', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }
}
