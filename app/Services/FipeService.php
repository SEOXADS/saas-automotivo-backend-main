<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\VehicleModel;
use App\Models\VehicleBrand;
use Exception;

class FipeService
{
    private $baseUrl;
    private $token;
    private $rateLimitPerDay;
    private $cacheTtl;

    public function __construct()
    {
        $this->baseUrl = config('services.fipe.base_url');
        $this->token = config('services.fipe.token');
        $this->rateLimitPerDay = config('services.fipe.rate_limit_per_day');
        $this->cacheTtl = config('services.fipe.cache_ttl');
    }

    /**
     * Obter referências de meses da FIPE
     */
    public function getReferences()
    {
        $cacheKey = 'fipe_references';

        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            try {
                $response = Http::withHeaders([
                    'X-Subscription-Token' => $this->token
                ])->get("{$this->baseUrl}/references");

                if ($response->successful()) {
                    $this->logApiCall('references');
                    return $response->json();
                }

                Log::error('FIPE API Error - References', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return null;
            } catch (Exception $e) {
                Log::error('FIPE API Exception - References', ['error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Obter marcas por tipo de veículo
     */
    public function getBrands(string $vehicleType, int $reference = null)
    {
        $cacheKey = "fipe_brands_{$vehicleType}_{$reference}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($vehicleType, $reference) {
            try {
                $url = "{$this->baseUrl}/{$vehicleType}/brands";
                if ($reference) {
                    $url .= "?reference={$reference}";
                }

                $response = Http::withHeaders([
                    'X-Subscription-Token' => $this->token
                ])->get($url);

                if ($response->successful()) {
                    $this->logApiCall('brands');
                    return $response->json();
                }

                Log::error('FIPE API Error - Brands', [
                    'vehicle_type' => $vehicleType,
                    'reference' => $reference,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return null;
            } catch (Exception $e) {
                Log::error('FIPE API Exception - Brands', [
                    'vehicle_type' => $vehicleType,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * Obter modelos por marca
     */
    public function getModels(string $vehicleType, int $brandId, int $reference = null)
    {
        // Buscar a marca para obter o código FIPE
        $brand = VehicleBrand::find($brandId);
        if (!$brand) {
            Log::error('Marca não encontrada', ['brand_id' => $brandId]);
            return null;
        }

        // Usar o código FIPE se disponível, senão usar o ID
        $fipeBrandId = !empty($brand->code) ? $brand->code : $brandId;

        $cacheKey = "fipe_models_{$vehicleType}_{$fipeBrandId}_{$reference}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($vehicleType, $fipeBrandId, $brandId, $reference) {
            try {
                $url = "{$this->baseUrl}/{$vehicleType}/brands/{$fipeBrandId}/models";
                if ($reference) {
                    $url .= "?reference={$reference}";
                }

                $response = Http::withHeaders([
                    'X-Subscription-Token' => $this->token
                ])->get($url);

                if ($response->successful()) {
                    $this->logApiCall('models');
                    $models = $response->json();

                    Log::info('Chamando syncModels', [
                        'brand_id' => $brandId,
                        'models_count' => count($models)
                    ]);

                    // Sincronizar modelos automaticamente
                    $this->syncModels($brandId, $models);

                    return $models;
                }

                Log::error('FIPE API Error - Models', [
                    'vehicle_type' => $vehicleType,
                    'brand_id' => $brandId,
                    'fipe_brand_id' => $fipeBrandId,
                    'reference' => $reference,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return null;
            } catch (Exception $e) {
                Log::error('FIPE API Exception - Models', [
                    'vehicle_type' => $vehicleType,
                    'brand_id' => $brandId,
                    'fipe_brand_id' => $fipeBrandId,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * Obter anos por modelo
     */
    public function getYears(string $vehicleType, int $brandId, int $modelId, int $reference = null)
    {
        $cacheKey = "fipe_years_{$vehicleType}_{$brandId}_{$modelId}_{$reference}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($vehicleType, $brandId, $modelId, $reference) {
            try {
                $url = "{$this->baseUrl}/{$vehicleType}/brands/{$brandId}/models/{$modelId}/years";
                if ($reference) {
                    $url .= "?reference={$reference}";
                }

                $response = Http::withHeaders([
                    'X-Subscription-Token' => $this->token
                ])->get($url);

                if ($response->successful()) {
                    $this->logApiCall('years');
                    return $response->json();
                }

                Log::error('FIPE API Error - Years', [
                    'vehicle_type' => $vehicleType,
                    'brand_id' => $brandId,
                    'model_id' => $modelId,
                    'reference' => $reference,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return null;
            } catch (Exception $e) {
                Log::error('FIPE API Exception - Years', [
                    'vehicle_type' => $vehicleType,
                    'brand_id' => $brandId,
                    'model_id' => $modelId,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * Obter informações completas do veículo
     */
    public function getVehicleInfo(string $vehicleType, int $brandId, int $modelId, string $yearId, int $reference = null)
    {
        $cacheKey = "fipe_vehicle_{$vehicleType}_{$brandId}_{$modelId}_{$yearId}_{$reference}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($vehicleType, $brandId, $modelId, $yearId, $reference) {
            try {
                $url = "{$this->baseUrl}/{$vehicleType}/brands/{$brandId}/models/{$modelId}/years/{$yearId}";
                if ($reference) {
                    $url .= "?reference={$reference}";
                }

                $response = Http::withHeaders([
                    'X-Subscription-Token' => $this->token
                ])->get($url);

                if ($response->successful()) {
                    $this->logApiCall('vehicle_info');
                    return $response->json();
                }

                Log::error('FIPE API Error - Vehicle Info', [
                    'vehicle_type' => $vehicleType,
                    'brand_id' => $brandId,
                    'model_id' => $modelId,
                    'year_id' => $yearId,
                    'reference' => $reference,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return null;
            } catch (Exception $e) {
                Log::error('FIPE API Exception - Vehicle Info', [
                    'vehicle_type' => $vehicleType,
                    'brand_id' => $brandId,
                    'model_id' => $modelId,
                    'year_id' => $yearId,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * Buscar veículo por código FIPE
     */
    public function searchVehicleByCode(string $codeFipe, int $reference = null)
    {
        $cacheKey = "fipe_search_code_{$codeFipe}_{$reference}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($codeFipe, $reference) {
            try {
                $url = "{$this->baseUrl}/{$codeFipe}";
                if ($reference) {
                    $url .= "?reference={$reference}";
                }

                $response = Http::withHeaders([
                    'X-Subscription-Token' => $this->token
                ])->get($url);

                if ($response->successful()) {
                    $this->logApiCall('search_by_code');
                    return $response->json();
                }

                Log::error('FIPE API Error - Search by Code', [
                    'code_fipe' => $codeFipe,
                    'reference' => $reference,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return null;
            } catch (Exception $e) {
                Log::error('FIPE API Exception - Search by Code', [
                    'code_fipe' => $codeFipe,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * Limpar cache da FIPE
     */
    public function clearCache()
    {
        $keys = [
            'fipe_references',
            'fipe_brands_*',
            'fipe_models_*',
            'fipe_years_*',
            'fipe_vehicle_*',
            'fipe_search_code_*'
        ];

        foreach ($keys as $key) {
            if (str_contains($key, '*')) {
                // Limpar chaves com wildcard
                $this->clearCacheByPattern($key);
            } else {
                Cache::forget($key);
            }
        }

        return ['message' => 'Cache da FIPE limpo com sucesso'];
    }

    /**
     * Obter estatísticas de uso da API
     */
    public function getUsageStats()
    {
        $today = now()->format('Y-m-d');
        $cacheKey = "fipe_usage_stats_{$today}";

        return Cache::remember($cacheKey, 3600, function () use ($today) {
            $stats = Cache::get("fipe_api_calls_{$today}", []);

            return [
                'date' => $today,
                'total_calls' => count($stats),
                'calls_by_endpoint' => array_count_values($stats),
                'remaining_calls' => $this->rateLimitPerDay - count($stats),
                'rate_limit' => $this->rateLimitPerDay
            ];
        });
    }

    /**
     * Verificar se ainda há chamadas disponíveis
     */
    public function hasAvailableCalls(): bool
    {
        $today = now()->format('Y-m-d');
        $calls = Cache::get("fipe_api_calls_{$today}", []);

        return count($calls) < $this->rateLimitPerDay;
    }

    /**
     * Log de chamadas à API
     */
    private function logApiCall(string $endpoint)
    {
        $today = now()->format('Y-m-d');
        $cacheKey = "fipe_api_calls_{$today}";

        $calls = Cache::get($cacheKey, []);
        $calls[] = [
            'endpoint' => $endpoint,
            'timestamp' => now()->toISOString(),
            'user_id' => Auth::check() ? Auth::id() : 'public'
        ];

        Cache::put($cacheKey, $calls, 86400); // 24 horas
    }

    /**
     * Limpar cache por padrão
     */
    private function clearCacheByPattern(string $pattern)
    {
        $pattern = str_replace('*', '', $pattern);

        // Esta é uma implementação simplificada
        // Em produção, você pode usar Redis SCAN ou similar
        $keys = [
            'fipe_brands_cars',
            'fipe_brands_motorcycles',
            'fipe_brands_trucks',
            'fipe_models_cars_23',
            'fipe_models_motorcycles_1',
            'fipe_vehicle_cars_23_5585_2022-3'
        ];

        foreach ($keys as $key) {
            if (str_contains($key, $pattern)) {
                Cache::forget($key);
            }
        }
    }

    /**
     * Sincronizar modelos da FIPE com a tabela vehicle_models
     */
    private function syncModels(int $brandId, array $fipeModels): void
    {
        try {
            Log::info('Iniciando sincronização de modelos FIPE', [
                'brand_id' => $brandId,
                'total_models' => count($fipeModels)
            ]);

            // Verificar se a marca existe na nossa tabela
            $brand = VehicleBrand::find($brandId);
            if (!$brand) {
                Log::warning('Marca não encontrada para sincronização de modelos', [
                    'brand_id' => $brandId
                ]);
                return;
            }

            $syncedCount = 0;
            $updatedCount = 0;

            foreach ($fipeModels as $fipeModel) {
                if (!isset($fipeModel['code']) || !isset($fipeModel['name'])) {
                    continue;
                }

                $fipeId = (string) $fipeModel['code'];
                $modelName = $fipeModel['name'];

                // Verificar se o modelo já existe pelo fipe_id ou fipe_code
                $existingModel = VehicleModel::byFipeId($fipeId)
                    ->orWhere('fipe_code', $fipeId)
                    ->first();

                if ($existingModel) {
                    // Atualizar modelo existente se necessário
                    if ($existingModel->name !== $modelName || $existingModel->brand_id !== $brandId) {
                        $existingModel->update([
                            'name' => $modelName,
                            'brand_id' => $brandId,
                            'slug' => Str::slug($modelName),
                            'fipe_code' => $fipeId,
                            'fipe_name' => $modelName,
                            'is_fipe_synced' => true,
                            'fipe_synced_at' => now(),
                        ]);
                        $updatedCount++;
                    }
                } else {
                    // Criar novo modelo
                    VehicleModel::create([
                        'brand_id' => $brandId,
                        'fipe_id' => $fipeId,
                        'fipe_code' => $fipeId,
                        'fipe_name' => $modelName,
                        'name' => $modelName,
                        'slug' => Str::slug($modelName),
                        'is_active' => true,
                        'is_fipe_synced' => true,
                        'fipe_synced_at' => now(),
                        'sort_order' => 0,
                    ]);
                    $syncedCount++;
                }
            }

            Log::info('Sincronização de modelos FIPE concluída', [
                'brand_id' => $brandId,
                'brand_name' => $brand->name,
                'novos_modelos' => $syncedCount,
                'modelos_atualizados' => $updatedCount,
                'total_fipe_models' => count($fipeModels)
            ]);

        } catch (Exception $e) {
            Log::error('Erro na sincronização de modelos FIPE', [
                'brand_id' => $brandId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
