<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SuperAdminTenantVehicleController extends Controller
{
    /**
     * Listar todos os veículos de um tenant específico
     */
    public function index(Request $request, $tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);

            $query = Vehicle::byTenant($tenantId)->with(['brand', 'model', 'images']);

            // Filtros
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('brand_id')) {
                $query->where('brand_id', $request->brand_id);
            }

            if ($request->has('model_id')) {
                $query->where('model_id', $request->model_id);
            }

            if ($request->has('year')) {
                $query->where('year', $request->year);
            }

            if ($request->has('fuel_type')) {
                $query->where('fuel_type', $request->fuel_type);
            }

            if ($request->has('transmission')) {
                $query->where('transmission', $request->transmission);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('plate', 'like', "%{$search}%");
                });
            }

            $vehicles = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $vehicles,
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'subdomain' => $tenant->subdomain
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar veículos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Criar novo veículo para um tenant
     */
    public function store(Request $request, $tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'brand_id' => 'required|exists:vehicle_brands,id',
                'model_id' => 'required|exists:vehicle_models,id',
                'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
                'fuel_type' => 'required|in:flex,gasolina,diesel,eletrico,hibrido,gnv',
                'transmission' => 'required|in:manual,automatica,cvt,automatizada',
                'mileage' => 'nullable|integer|min:0',
                'color' => 'nullable|string|max:100',
                'price' => 'required|numeric|min:0',
                'plate' => 'nullable|string|max:20|unique:vehicles,plate',
                'status' => 'nullable|in:active,inactive,sold,reserved',
                'features' => 'nullable|array',
                'features.*' => 'string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $vehicleData = $validator->validated();
            $vehicleData['tenant_id'] = $tenantId;
            $vehicleData['status'] = $vehicleData['status'] ?? 'active';

            $vehicle = Vehicle::create($vehicleData);

            return response()->json([
                'success' => true,
                'message' => 'Veículo criado com sucesso',
                'data' => $vehicle->load(['brand', 'model'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar veículo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibir detalhes de um veículo específico
     */
    public function show($tenantId, $vehicleId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);
            $vehicle = Vehicle::byTenant($tenantId)
                ->with(['brand', 'model', 'images', 'features'])
                ->findOrFail($vehicleId);

            return response()->json([
                'success' => true,
                'data' => $vehicle,
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'subdomain' => $tenant->subdomain
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar veículo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar veículo
     */
    public function update(Request $request, $tenantId, $vehicleId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);
            $vehicle = Vehicle::byTenant($tenantId)->findOrFail($vehicleId);

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'brand_id' => 'sometimes|exists:vehicle_brands,id',
                'model_id' => 'sometimes|exists:vehicle_models,id',
                'year' => 'sometimes|integer|min:1900|max:' . (date('Y') + 1),
                'fuel_type' => 'sometimes|in:flex,gasolina,diesel,eletrico,hibrido,gnv',
                'transmission' => 'sometimes|in:manual,automatica,cvt,automatizada',
                'mileage' => 'nullable|integer|min:0',
                'color' => 'nullable|string|max:100',
                'price' => 'sometimes|numeric|min:0',
                'plate' => 'nullable|string|max:20|unique:vehicles,plate,' . $vehicleId,
                'status' => 'sometimes|in:active,inactive,sold,reserved',
                'features' => 'nullable|array',
                'features.*' => 'string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $vehicle->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Veículo atualizado com sucesso',
                'data' => $vehicle->fresh()->load(['brand', 'model'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar veículo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deletar veículo
     */
    public function destroy($tenantId, $vehicleId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);
            $vehicle = Vehicle::byTenant($tenantId)->findOrFail($vehicleId);

            $vehicle->delete();

            return response()->json([
                'success' => true,
                'message' => 'Veículo deletado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar veículo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ativar veículo
     */
    public function activate($tenantId, $vehicleId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);
            $vehicle = Vehicle::byTenant($tenantId)->findOrFail($vehicleId);

            $vehicle->update(['status' => 'active']);

            return response()->json([
                'success' => true,
                'message' => 'Veículo ativado com sucesso',
                'data' => $vehicle->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao ativar veículo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Desativar veículo
     */
    public function deactivate($tenantId, $vehicleId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);
            $vehicle = Vehicle::byTenant($tenantId)->findOrFail($vehicleId);

            $vehicle->update(['status' => 'inactive']);

            return response()->json([
                'success' => true,
                'message' => 'Veículo desativado com sucesso',
                'data' => $vehicle->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao desativar veículo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estatísticas dos veículos do tenant
     */
    public function getStats($tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);

            $stats = Vehicle::byTenant($tenantId)
                ->selectRaw('
                    COUNT(*) as total_vehicles,
                    COUNT(CASE WHEN status = "active" THEN 1 END) as active_vehicles,
                    COUNT(CASE WHEN status = "inactive" THEN 1 END) as inactive_vehicles,
                    COUNT(CASE WHEN status = "sold" THEN 1 END) as sold_vehicles,
                    COUNT(CASE WHEN status = "reserved" THEN 1 END) as reserved_vehicles,
                    AVG(price) as average_price,
                    MIN(price) as min_price,
                    MAX(price) as max_price,
                    AVG(year) as average_year,
                    MIN(year) as min_year,
                    MAX(year) as max_year
                ')
                ->first();

            $brandStats = Vehicle::byTenant($tenantId)
                ->join('vehicle_brands', 'vehicles.brand_id', '=', 'vehicle_brands.id')
                ->select('vehicle_brands.name', DB::raw('COUNT(*) as count'))
                ->groupBy('vehicle_brands.id', 'vehicle_brands.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();

            $fuelTypeStats = Vehicle::byTenant($tenantId)
                ->select('fuel_type', DB::raw('COUNT(*) as count'))
                ->groupBy('fuel_type')
                ->get();

            $transmissionStats = Vehicle::byTenant($tenantId)
                ->select('transmission', DB::raw('COUNT(*) as count'))
                ->groupBy('transmission')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'overview' => $stats,
                    'brands' => $brandStats,
                    'fuel_types' => $fuelTypeStats,
                    'transmissions' => $transmissionStats,
                    'tenant' => [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'subdomain' => $tenant->subdomain
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar estatísticas: ' . $e->getMessage()
            ], 500);
        }
    }
}
