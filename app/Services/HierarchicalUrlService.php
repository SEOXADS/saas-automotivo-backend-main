<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\TenantCity;
use App\Models\TenantNeighborhood;
use App\Models\TenantSeoUrl;
use App\Helpers\UrlHelper;
use Illuminate\Support\Facades\Log;

class HierarchicalUrlService
{
    /**
     * Gerar todas as URLs hierárquicas para um tenant
     */
    public function generateAllUrlsForTenant(int $tenantId): array
    {
        $results = [
            'brands' => 0,
            'vehicles' => 0,
            'city_urls' => 0,
            'neighborhood_urls' => 0,
            'total_urls' => 0
        ];

        try {
            // 1. Gerar URLs de marcas (categorias)
            $results['brands'] = $this->generateBrandUrls($tenantId);

            // 2. Gerar URLs de veículos base
            $results['vehicles'] = $this->generateVehicleUrls($tenantId);

            // 3. Gerar URLs com cidades
            $results['city_urls'] = $this->generateCityUrls($tenantId);

            // 4. Gerar URLs com bairros
            $results['neighborhood_urls'] = $this->generateNeighborhoodUrls($tenantId);

            $results['total_urls'] = $results['brands'] + $results['vehicles'] + $results['city_urls'] + $results['neighborhood_urls'];

            Log::info('URLs hierárquicas geradas para tenant', [
                'tenant_id' => $tenantId,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao gerar URLs hierárquicas', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        return $results;
    }

    /**
     * Gerar URLs de marcas (categorias)
     */
    private function generateBrandUrls(int $tenantId): int
    {
        $brands = VehicleBrand::whereHas('vehicles', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })->get();

        $count = 0;

        foreach ($brands as $brand) {
            // URL da marca: chevrolet
            $brandSlug = UrlHelper::generateBasicUrl($brand->name);

            $this->createSeoUrl($tenantId, [
                'path' => $brandSlug,
                'type' => 'collection',
                'canonical_url' => "/{$brandSlug}",
                'title' => "Carros {$brand->name}",
                'meta_description' => "Encontre os melhores carros {$brand->name} disponíveis",
                'breadcrumbs' => [
                    ['name' => 'Início', 'item' => '/'],
                    ['name' => $brand->name, 'item' => "/{$brandSlug}"]
                ]
            ]);

            // URL da categoria: comprar-carro/chevrolet
            $categorySlug = UrlHelper::generateBasicUrl("comprar-carro/{$brand->name}");

            $this->createSeoUrl($tenantId, [
                'path' => $categorySlug,
                'type' => 'collection',
                'canonical_url' => "/{$categorySlug}",
                'title' => "Comprar Carro {$brand->name}",
                'meta_description' => "Compre seu carro {$brand->name} com as melhores condições",
                'breadcrumbs' => [
                    ['name' => 'Início', 'item' => '/'],
                    ['name' => 'Comprar Carro', 'item' => '/comprar-carro'],
                    ['name' => $brand->name, 'item' => "/{$categorySlug}"]
                ]
            ]);

            $count += 2;
        }

        return $count;
    }

    /**
     * Gerar URLs de veículos base
     */
    private function generateVehicleUrls(int $tenantId): int
    {
        $vehicles = Vehicle::with(['brand', 'model'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        $count = 0;

        foreach ($vehicles as $vehicle) {
            $brandSlug = UrlHelper::generateBasicUrl($vehicle->brand->name);
            $vehicleSlug = UrlHelper::generateBasicUrl($vehicle->title);

            // URL do veículo: chevrolet/onix-10-2023
            $vehiclePath = "{$brandSlug}/{$vehicleSlug}";

            $this->createSeoUrl($tenantId, [
                'path' => $vehiclePath,
                'type' => 'vehicle_detail',
                'canonical_url' => "/{$vehiclePath}",
                'title' => $vehicle->title,
                'meta_description' => $vehicle->description ?: "Veja detalhes do {$vehicle->title}",
                'breadcrumbs' => [
                    ['name' => 'Início', 'item' => '/'],
                    ['name' => $vehicle->brand->name, 'item' => "/{$brandSlug}"],
                    ['name' => $vehicle->title, 'item' => "/{$vehiclePath}"]
                ],
                'route_params' => ['vehicle_id' => $vehicle->id]
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Gerar URLs com cidades
     */
    private function generateCityUrls(int $tenantId): int
    {
        $tenantCities = TenantCity::with(['city.state'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        $count = 0;

        foreach ($tenantCities as $tenantCity) {
            $city = $tenantCity->city;
            $citySlug = UrlHelper::generateBasicUrl("{$city->name}-{$city->state->uf}");

            // URLs de marcas com cidade: chevrolet/sao-paulo-sp
            $brands = VehicleBrand::whereHas('vehicles', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })->get();

            foreach ($brands as $brand) {
                $brandSlug = UrlHelper::generateBasicUrl($brand->name);

                $this->createSeoUrl($tenantId, [
                    'path' => "{$brandSlug}/{$citySlug}",
                    'type' => 'collection',
                    'canonical_url' => "/{$brandSlug}/{$citySlug}",
                    'title' => "Carros {$brand->name} em {$city->name}",
                    'meta_description' => "Encontre carros {$brand->name} em {$city->name} - {$city->state->name}",
                    'breadcrumbs' => [
                        ['name' => 'Início', 'item' => '/'],
                        ['name' => $brand->name, 'item' => "/{$brandSlug}"],
                        ['name' => $city->name, 'item' => "/{$brandSlug}/{$citySlug}"]
                    ]
                ]);

                $count++;
            }

            // URLs de veículos com cidade: chevrolet/onix-10-2023/sao-paulo-sp
            $vehicles = Vehicle::with(['brand', 'model'])
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->get();

            foreach ($vehicles as $vehicle) {
                $brandSlug = UrlHelper::generateBasicUrl($vehicle->brand->name);
                $vehicleSlug = UrlHelper::generateBasicUrl($vehicle->title);

                $this->createSeoUrl($tenantId, [
                    'path' => "{$brandSlug}/{$vehicleSlug}/{$citySlug}",
                    'type' => 'vehicle_detail',
                    'canonical_url' => "/{$brandSlug}/{$vehicleSlug}/{$citySlug}",
                    'title' => "{$vehicle->title} em {$city->name}",
                    'meta_description' => "Veja o {$vehicle->title} disponível em {$city->name} - {$city->state->name}",
                    'breadcrumbs' => [
                        ['name' => 'Início', 'item' => '/'],
                        ['name' => $vehicle->brand->name, 'item' => "/{$brandSlug}"],
                        ['name' => $city->name, 'item' => "/{$brandSlug}/{$citySlug}"],
                        ['name' => $vehicle->title, 'item' => "/{$brandSlug}/{$vehicleSlug}/{$citySlug}"]
                    ],
                    'route_params' => ['vehicle_id' => $vehicle->id, 'city_id' => $city->id]
                ]);

                $count++;
            }
        }

        return $count;
    }

    /**
     * Gerar URLs com bairros
     */
    private function generateNeighborhoodUrls(int $tenantId): int
    {
        $tenantNeighborhoods = TenantNeighborhood::with(['neighborhood.city.state'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        $count = 0;

        foreach ($tenantNeighborhoods as $tenantNeighborhood) {
            $neighborhood = $tenantNeighborhood->neighborhood;
            $city = $neighborhood->city;
            $neighborhoodSlug = UrlHelper::generateBasicUrl($neighborhood->name);
            $citySlug = UrlHelper::generateBasicUrl("{$city->name}-{$city->state->uf}");
            $neighborhoodCitySlug = "{$neighborhoodSlug}-{$citySlug}";

            // URLs de marcas com bairro: chevrolet/vila-madalena-sao-paulo-sp
            $brands = VehicleBrand::whereHas('vehicles', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })->get();

            foreach ($brands as $brand) {
                $brandSlug = UrlHelper::generateBasicUrl($brand->name);

                $this->createSeoUrl($tenantId, [
                    'path' => "{$brandSlug}/{$neighborhoodCitySlug}",
                    'type' => 'collection',
                    'canonical_url' => "/{$brandSlug}/{$neighborhoodCitySlug}",
                    'title' => "Carros {$brand->name} em {$neighborhood->name}",
                    'meta_description' => "Encontre carros {$brand->name} no bairro {$neighborhood->name} em {$city->name}",
                    'breadcrumbs' => [
                        ['name' => 'Início', 'item' => '/'],
                        ['name' => $brand->name, 'item' => "/{$brandSlug}"],
                        ['name' => 'Bairros', 'item' => "/{$brandSlug}/bairros"],
                        ['name' => $city->name, 'item' => "/{$brandSlug}/bairros/{$citySlug}"],
                        ['name' => $neighborhood->name, 'item' => "/{$brandSlug}/{$neighborhoodCitySlug}"]
                    ]
                ]);

                $count++;
            }

            // URLs de veículos com bairro: chevrolet/onix-10-2023/vila-madalena-sao-paulo-sp
            $vehicles = Vehicle::with(['brand', 'model'])
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->get();

            foreach ($vehicles as $vehicle) {
                $brandSlug = UrlHelper::generateBasicUrl($vehicle->brand->name);
                $vehicleSlug = UrlHelper::generateBasicUrl($vehicle->title);

                $this->createSeoUrl($tenantId, [
                    'path' => "{$brandSlug}/{$vehicleSlug}/{$neighborhoodCitySlug}",
                    'type' => 'vehicle_detail',
                    'canonical_url' => "/{$brandSlug}/{$vehicleSlug}/{$neighborhoodCitySlug}",
                    'title' => "{$vehicle->title} em {$neighborhood->name}",
                    'meta_description' => "Veja o {$vehicle->title} disponível no bairro {$neighborhood->name} em {$city->name}",
                    'breadcrumbs' => [
                        ['name' => 'Início', 'item' => '/'],
                        ['name' => $vehicle->brand->name, 'item' => "/{$brandSlug}"],
                        ['name' => 'Bairros', 'item' => "/{$brandSlug}/bairros"],
                        ['name' => $city->name, 'item' => "/{$brandSlug}/bairros/{$citySlug}"],
                        ['name' => $neighborhood->name, 'item' => "/{$brandSlug}/{$neighborhoodCitySlug}"],
                        ['name' => $vehicle->title, 'item' => "/{$brandSlug}/{$vehicleSlug}/{$neighborhoodCitySlug}"]
                    ],
                    'route_params' => [
                        'vehicle_id' => $vehicle->id,
                        'neighborhood_id' => $neighborhood->id,
                        'city_id' => $city->id
                    ]
                ]);

                $count++;
            }
        }

        return $count;
    }

    /**
     * Criar ou atualizar URL SEO
     */
    private function createSeoUrl(int $tenantId, array $data): void
    {
        TenantSeoUrl::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'locale' => 'pt-BR',
                'path' => $data['path']
            ],
            array_merge($data, [
                'tenant_id' => $tenantId,
                'locale' => 'pt-BR',
                'is_indexable' => true,
                'include_in_sitemap' => true,
                'sitemap_priority' => $data['type'] === 'vehicle_detail' ? 0.8 : 0.6,
                'sitemap_changefreq' => 'weekly',
                'lastmod' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ])
        );
    }

    /**
     * Limpar URLs existentes de um tenant
     */
    public function clearTenantUrls(int $tenantId): int
    {
        $deleted = TenantSeoUrl::where('tenant_id', $tenantId)->delete();

        Log::info('URLs limpas para tenant', [
            'tenant_id' => $tenantId,
            'deleted_count' => $deleted
        ]);

        return $deleted;
    }

    /**
     * Obter estatísticas de URLs de um tenant
     */
    public function getTenantUrlStats(int $tenantId): array
    {
        $stats = TenantSeoUrl::where('tenant_id', $tenantId)
            ->selectRaw('
                type,
                COUNT(*) as count,
                SUM(CASE WHEN include_in_sitemap THEN 1 ELSE 0 END) as sitemap_count,
                SUM(CASE WHEN is_indexable THEN 1 ELSE 0 END) as indexable_count
            ')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        return [
            'total_urls' => $stats->sum('count'),
            'by_type' => $stats->toArray(),
            'sitemap_urls' => $stats->sum('sitemap_count'),
            'indexable_urls' => $stats->sum('indexable_count')
        ];
    }
}
