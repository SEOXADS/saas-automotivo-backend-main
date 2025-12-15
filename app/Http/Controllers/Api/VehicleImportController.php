<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Models\Tenant;
use App\Helpers\UrlHelper;
use OpenApi\Annotations as OA;

class VehicleImportController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/vehicles/import/webmotors",
     *   summary="Importa dados de um anúncio do Webmotors",
     *   tags={"Importação"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="url",
     *     in="query",
     *     required=true,
     *     description="URL completa do anúncio do Webmotors",
     *     @OA\Schema(type="string", format="uri")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Dados importados com sucesso",
     *     @OA\JsonContent(ref="#/components/schemas/ImportedVehiclePayload")
     *   ),
     *   @OA\Response(response=422, description="URL inválida"),
     *   @OA\Response(response=502, description="Falha ao acessar o anúncio"),
     *   @OA\Response(response=500, description="Erro interno ao processar a importação")
     * )
     */
    public function importFromWebmotors(Request $request)
    {
        // Aceitar URL tanto via query (GET) quanto via body (POST)
        $url = $request->query('url') ?? $request->input('url');
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'URL inválida'], 422);
        }

        if (stripos(parse_url($url, PHP_URL_HOST) ?? '', 'webmotors.com.br') === false) {
            return response()->json(['error' => 'URL não pertence ao domínio Webmotors'], 422);
        }

        try {
            $response = Http::timeout(15)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            ])->get($url);

            if (!$response->ok()) {
                return response()->json(['error' => 'Falha ao acessar o anúncio'], 502);
            }

            $html = $response->body();

            $jsonLd = $this->extractFromJsonLd($html);
            $data = $jsonLd ?? $this->extractFromMeta($html) ?? [];

            $mapped = $this->mapToForm($data);

            // imagens
            $images = $this->collectImages($html, $jsonLd);
            if (!empty($images)) {
                $mapped['images'] = $images;
            }

            // Resolver brand_id e model_id por nome, se possível
            if (!empty($mapped['brand_name'])) {
                $brand = $this->findBrandByName($mapped['brand_name']);
                if ($brand) {
                    $mapped['brand_id'] = $brand->id;
                    if (!empty($mapped['model_name'])) {
                        $model = $this->findModelByNameAndBrand($mapped['model_name'], $brand->id);
                        if ($model) {
                            $mapped['model_id'] = $model->id;
                        }
                    }
                }
            }

            return response()->json(array_filter($mapped, function ($v) {
                return $v !== null && $v !== '';
            }));
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Não foi possível importar este anúncio'], 500);
        }
    }

    /**
     * @OA\Get(
     *   path="/api/vehicles/import/olx",
     *   summary="Importa dados de um anúncio da OLX",
     *   tags={"Importação"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="url",
     *     in="query",
     *     required=true,
     *     description="URL completa do anúncio da OLX",
     *     @OA\Schema(type="string", format="uri")
     *   ),
     *   @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/ImportedVehiclePayload")),
     *   @OA\Response(response=422, description="URL inválida"),
     *   @OA\Response(response=502, description="Falha ao acessar o anúncio"),
     *   @OA\Response(response=500, description="Erro interno")
     * )
     */
    public function importFromOlx(Request $request)
    {
        // Aceitar URL tanto via query (GET) quanto via body (POST)
        $url = $request->query('url') ?? $request->input('url');
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'URL inválida'], 422);
        }
        if (stripos(parse_url($url, PHP_URL_HOST) ?? '', 'olx.com.br') === false) {
            return response()->json(['error' => 'URL não pertence ao domínio OLX'], 422);
        }

        try {
            $response = Http::timeout(15)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            ])->get($url);

            if (!$response->ok()) {
                return response()->json(['error' => 'Falha ao acessar o anúncio'], 502);
            }

            $html = $response->body();
            $jsonLd = $this->extractFromJsonLd($html);
            $data = $jsonLd ?? $this->extractFromMeta($html) ?? [];
            $mapped = $this->mapToForm($data);
            $images = $this->collectImages($html, $jsonLd);
            if (!empty($images)) {
                $mapped['images'] = $images;
            }

            if (!empty($mapped['brand_name'])) {
                $brand = $this->findBrandByName($mapped['brand_name']);
                if ($brand) {
                    $mapped['brand_id'] = $brand->id;
                    if (!empty($mapped['model_name'])) {
                        $model = $this->findModelByNameAndBrand($mapped['model_name'], $brand->id);
                        if ($model) {
                            $mapped['model_id'] = $model->id;
                        }
                    }
                }
            }

            return response()->json(array_filter($mapped, function ($v) { return $v !== null && $v !== ''; }));
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Não foi possível importar este anúncio'], 500);
        }
    }

    /**
     * @OA\Get(
     *   path="/api/vehicles/import/icarros",
     *   summary="Importa dados de um anúncio do iCarros",
     *   tags={"Importação"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="url",
     *     in="query",
     *     required=true,
     *     description="URL completa do anúncio do iCarros",
     *     @OA\Schema(type="string", format="uri")
     *   ),
     *   @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/ImportedVehiclePayload")),
     *   @OA\Response(response=422, description="URL inválida"),
     *   @OA\Response(response=502, description="Falha ao acessar o anúncio"),
     *   @OA\Response(response=500, description="Erro interno")
     * )
     */
    public function importFromICarros(Request $request)
    {
        // Aceitar URL tanto via query (GET) quanto via body (POST)
        $url = $request->query('url') ?? $request->input('url');
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'URL inválida'], 422);
        }

        $host = parse_url($url, PHP_URL_HOST) ?? '';
        if (stripos($host, 'icarros.com.br') === false && stripos($host, 'icarros.com') === false) {
            return response()->json(['error' => 'URL não pertence ao domínio iCarros'], 422);
        }

        try {
            $response = Http::timeout(15)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            ])->get($url);

            if (!$response->ok()) {
                return response()->json(['error' => 'Falha ao acessar o anúncio'], 502);
            }

            $html = $response->body();
            $jsonLd = $this->extractFromJsonLd($html);
            $data = $jsonLd ?? $this->extractFromMeta($html) ?? [];
            $mapped = $this->mapToForm($data);
            $images = $this->collectImages($html, $jsonLd);
            if (!empty($images)) {
                $mapped['images'] = $images;
            }

            if (!empty($mapped['brand_name'])) {
                $brand = $this->findBrandByName($mapped['brand_name']);
                if ($brand) {
                    $mapped['brand_id'] = $brand->id;
                    if (!empty($mapped['model_name'])) {
                        $model = $this->findModelByNameAndBrand($mapped['model_name'], $brand->id);
                        if ($model) {
                            $mapped['model_id'] = $model->id;
                        }
                    }
                }
            }

            return response()->json(array_filter($mapped, function ($v) { return $v !== null && $v !== ''; }));
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Não foi possível importar este anúncio'], 500);
        }
    }

    /**
     * @OA\Get(
     *   path="/api/vehicles/import/omegaveiculos",
     *   summary="Importa dados de um anúncio do Omega Veículos",
     *   tags={"Importação"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="url",
     *     in="query",
     *     required=true,
     *     description="URL completa do anúncio do site Omega Veículos",
     *     @OA\Schema(type="string", format="uri")
     *   ),
     *   @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/ImportedVehiclePayload")),
     *   @OA\Response(response=422, description="URL inválida"),
     *   @OA\Response(response=502, description="Falha ao acessar o anúncio"),
     *   @OA\Response(response=500, description="Erro interno")
     * )
     */
    public function importFromOmegaVeiculos(Request $request)
    {
        // Aceitar URL tanto via query (GET) quanto via body (POST)
        $url = $request->query('url') ?? $request->input('url');
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'URL inválida'], 422);
        }
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        if (stripos($host, 'omegaveiculos.com.br') === false) {
            return response()->json(['error' => 'URL não pertence ao domínio omegaveiculos.com.br'], 422);
        }

        try {
            $response = Http::timeout(15)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            ])->get($url);

            if (!$response->ok()) {
                return response()->json(['error' => 'Falha ao acessar o anúncio'], 502);
            }

            $html = $response->body();
            $jsonLd = $this->extractFromJsonLd($html);
            $data = $jsonLd ?? $this->extractFromMeta($html) ?? [];
            $mapped = $this->mapToForm($data);
            $images = $this->collectImages($html, $jsonLd);
            if (!empty($images)) {
                $mapped['images'] = $images;
            }

            if (!empty($mapped['brand_name'])) {
                $brand = $this->findBrandByName($mapped['brand_name']);
                if ($brand) {
                    $mapped['brand_id'] = $brand->id;
                    if (!empty($mapped['model_name'])) {
                        $model = $this->findModelByNameAndBrand($mapped['model_name'], $brand->id);
                        if ($model) {
                            $mapped['model_id'] = $model->id;
                        }
                    }
                }
            }

            return response()->json(array_filter($mapped, function ($v) { return $v !== null && $v !== ''; }));
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Não foi possível importar este anúncio'], 500);
        }
    }

    /**
     * @OA\Schema(
     *   schema="ImportedVehiclePayload",
     *   type="object",
     *   @OA\Property(property="brand_id", type="integer", nullable=true),
     *   @OA\Property(property="model_id", type="integer", nullable=true),
     *   @OA\Property(property="brand_name", type="string", nullable=true),
     *   @OA\Property(property="model_name", type="string", nullable=true),
     *   @OA\Property(property="title", type="string", example="Volkswagen Gol 1.6 MSI"),
     *   @OA\Property(property="version", type="string", nullable=true),
     *   @OA\Property(property="year", type="integer", example=2019),
     *   @OA\Property(property="model_year", type="integer", example=2020),
     *   @OA\Property(property="color", type="string", nullable=true),
     *   @OA\Property(property="fuel_type", type="string", enum={"flex","gasolina","diesel","eletrico","hibrido","gnv"}),
     *   @OA\Property(property="transmission", type="string", enum={"manual","automatica","cvt","automatizada"}),
     *   @OA\Property(property="doors", type="integer", example=4),
     *   @OA\Property(property="mileage", type="integer", example=45000),
     *   @OA\Property(property="price", type="number", format="float", example=55990.00),
     *   @OA\Property(property="description", type="string", nullable=true),
     *   @OA\Property(property="condition", type="string", enum={"new","used"}),
     *   @OA\Property(property="images", type="array", @OA\Items(type="string", format="uri"))
     * )
     */

    private function extractFromJsonLd(string $html): ?array
    {
        if (!preg_match_all('/<script[^>]*type=\"application\/ld\+json\"[^>]*>(.*?)<\/script>/is', $html, $matches)) {
            return null;
        }
        foreach ($matches[1] as $json) {
            $json = html_entity_decode($json);
            $decoded = json_decode($json, true);
            if (!$decoded) {
                continue;
            }
            // Às vezes vem como array de objetos
            $candidates = is_array($decoded) && array_keys($decoded) === range(0, count($decoded) - 1) ? $decoded : [$decoded];
            foreach ($candidates as $node) {
                if (isset($node['@type']) && (stripos($node['@type'], 'Product') !== false || stripos($node['@type'], 'Vehicle') !== false)) {
                    return $node;
                }
            }
        }
        return null;
    }

    private function collectImages(string $html, ?array $jsonLd): array
    {
        $images = [];

        // 1) JSON-LD
        if ($jsonLd && isset($jsonLd['image'])) {
            if (is_string($jsonLd['image'])) {
                $images[] = $jsonLd['image'];
            } elseif (is_array($jsonLd['image'])) {
                foreach ($jsonLd['image'] as $img) {
                    if (is_string($img)) {
                        $images[] = $img;
                    } elseif (is_array($img) && isset($img['url'])) {
                        $images[] = $img['url'];
                    }
                }
            }
        }

        // 2) OpenGraph
        if (preg_match_all('/<meta[^>]*property=\"og:image\"[^>]*content=\"([^\"]+)\"/i', $html, $m)) {
            foreach ($m[1] as $u) {
                $images[] = $u;
            }
        }

        // 3) Tags IMG comuns (src ou data-*)
        if (preg_match_all('/<img[^>]+(src|data-src|data-large|data-original)=\"([^\"]+)\"[^>]*>/i', $html, $m)) {
            foreach ($m[2] as $u) {
                $images[] = $u;
            }
        }

        // Limpeza e dedupe
        $images = array_values(array_unique(array_map(function ($u) {
            $u = html_entity_decode($u);
            if (str_starts_with($u, '//')) {
                $u = 'https:' . $u;
            }
            return $u;
        }, array_filter($images, function ($u) {
            return is_string($u) && preg_match('/\.(jpg|jpeg|png|webp)(\?|$)/i', $u);
        }))));

        // limitar a 20 para evitar payload gigante
        return array_slice($images, 0, 20);
    }

    private function extractFromMeta(string $html): ?array
    {
        $out = [];
        if (preg_match('/<meta[^>]*property=\"og:title\"[^>]*content=\"([^\"]+)\"/i', $html, $m)) {
            $out['name'] = $m[1];
        }
        if (preg_match('/<meta[^>]*property=\"og:description\"[^>]*content=\"([^\"]+)\"/i', $html, $m)) {
            $out['description'] = $m[1];
        }
        if (preg_match('/Preço[^0-9]*([\d\.\,]+)/i', $html, $m)) {
            $out['offers']['price'] = $m[1];
            $out['offers']['priceCurrency'] = 'BRL';
        }
        return $out ?: null;
    }

    private function mapToForm(array $d): array
    {
        $brandName = $d['brand'] ?? ($d['vehicleBrand'] ?? ($d['brand']['name'] ?? null));
        $modelName = $d['model'] ?? ($d['vehicleModel'] ?? null);
        $name = $d['name'] ?? null;
        $description = $d['description'] ?? null;
        $prodYear = $d['productionDate'] ?? ($d['modelDate'] ?? null);
        $modelYear = $d['vehicleModelDate'] ?? null;
        $color = $d['color'] ?? null;
        $fuel = $d['fuelType'] ?? null;
        $transmission = $d['vehicleTransmission'] ?? null;
        $doors = $d['numberOfDoors'] ?? null;
        $mileage = is_array($d['mileageFromOdometer'] ?? null) ? ($d['mileageFromOdometer']['value'] ?? null) : ($d['mileageFromOdometer'] ?? ($d['mileage'] ?? null));
        $price = is_array($d['offers'] ?? null) ? ($d['offers']['price'] ?? null) : ($d['offers'] ?? null);

        return [
            'brand_name' => $brandName,
            'model_name' => $modelName,
            'original_title' => $name, // Título original para referência
            'version' => $d['vehicleConfiguration'] ?? null,
            'year' => $this->toInt($prodYear),
            'model_year' => $this->toInt($modelYear) ?? $this->toInt($prodYear),
            'color' => $color,
            'fuel_type' => $this->mapFuel($fuel),
            'transmission' => $this->mapTransmission($transmission),
            'doors' => $this->toInt($doors),
            'mileage' => $this->toInt($mileage),
            'price' => $this->toFloat($price),
            'description' => $description,
            'condition' => $this->mapCondition($d['itemCondition'] ?? null),
            'status' => 'available', // Status padrão para veículos importados
            'image_urls' => $this->extractImageUrls($d),
        ];
    }

    private function toInt($value): ?int
    {
        if ($value === null || $value === '') return null;
        $num = preg_replace('/[^0-9]/', '', (string)$value);
        return $num === '' ? null : (int)$num;
    }

    private function toFloat($value): ?float
    {
        if ($value === null || $value === '') return null;
        $normalized = str_replace(['.', ' '], ['', ''], (string)$value);
        $normalized = str_replace(',', '.', $normalized);
        return is_numeric($normalized) ? (float)$normalized : null;
    }

    private function mapFuel($fuel): ?string
    {
        if (!$fuel) return null;
        $v = $this->normalize((string)$fuel);
        if (str_contains($v, 'flex')) return 'flex';
        if (str_contains($v, 'gas') || str_contains($v, 'gasolina')) return 'gasolina';
        if (str_contains($v, 'etan') || str_contains($v, 'alcool')) return 'gasolina';
        if (str_contains($v, 'diesel')) return 'diesel';
        if (str_contains($v, 'eletr')) return 'eletrico';
        if (str_contains($v, 'hibr')) return 'hibrido';
        return 'flex'; // fallback para flex
    }

    private function mapTransmission($t): ?string
    {
        if (!$t) return null;
        $v = $this->normalize((string)$t);
        if (str_contains($v, 'manual')) return 'manual';
        if (str_contains($v, 'cvt')) return 'cvt';
        if (str_contains($v, 'auto')) return 'automatica';
        return 'manual'; // fallback para manual
    }

    private function mapCondition($c): ?string
    {
        if (!$c) return null;
        $v = $this->normalize((string)$c);
        if (str_contains($v, 'new') || str_contains($v, 'novo')) return 'new';
        if (str_contains($v, 'semi')) return 'used';
        return 'used';
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
        return preg_replace('/[^a-z0-9]+/', ' ', $s);
    }

    private function findBrandByName(string $name): ?VehicleBrand
    {
        $norm = trim($this->normalize($name));
        $brands = VehicleBrand::all(['id','name']);
        foreach ($brands as $b) {
            if (trim($this->normalize($b->name)) === $norm) {
                return $b;
            }
        }
        // fallback: starts with
        foreach ($brands as $b) {
            if (str_starts_with(trim($this->normalize($b->name)), $norm) || str_starts_with($norm, trim($this->normalize($b->name)))) {
                return $b;
            }
        }
        return null;
    }

    private function findModelByNameAndBrand(string $name, int $brandId): ?VehicleModel
    {
        $norm = trim($this->normalize($name));
        $models = VehicleModel::where('brand_id', $brandId)->get(['id','name','brand_id']);
        foreach ($models as $m) {
            if (trim($this->normalize($m->name)) === $norm) {
                return $m;
            }
        }
        foreach ($models as $m) {
            if (str_starts_with(trim($this->normalize($m->name)), $norm) || str_starts_with($norm, trim($this->normalize($m->name)))) {
                return $m;
            }
        }
        return null;
    }

    /**
     * Download e salva imagem de um veículo importado
     */
    private function downloadAndSaveImage(string $imageUrl, Vehicle $vehicle, bool $isPrimary = false): ?VehicleImage
    {
        try {
            // Validar URL
            if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                Log::warning("URL de imagem inválida: {$imageUrl}");
                return null;
            }

            // Fazer download da imagem
            $response = Http::timeout(30)->get($imageUrl);

            if (!$response->successful()) {
                Log::warning("Falha ao baixar imagem: {$imageUrl}, Status: {$response->status()}");
                return null;
            }

            $imageContent = $response->body();
            $contentType = $response->header('Content-Type');

            // Validar tipo de conteúdo
            if (!$this->isValidImageType($contentType)) {
                Log::warning("Tipo de imagem inválido: {$contentType} para URL: {$imageUrl}");
                return null;
            }

            // Gerar nome único para o arquivo usando o método especializado
            $extension = $this->getExtensionFromMimeType($contentType);
            $filename = $this->generateUniqueFilename($extension, $imageUrl, $vehicle->tenant_id);

            // Caminho de armazenamento
            $storagePath = "tenants/{$vehicle->tenant_id}/vehicles/{$vehicle->id}";
            $fullPath = $storagePath . '/' . $filename;

            // Salvar arquivo
            if (!Storage::disk('public')->put($fullPath, $imageContent)) {
                Log::error("Falha ao salvar imagem: {$fullPath}");
                return null;
            }

            // Criar registro no banco
            $vehicleImage = new VehicleImage([
                'vehicle_id' => $vehicle->id,
                'filename' => $filename,
                'original_name' => basename($imageUrl),
                'path' => $fullPath,
                'url' => $filename, // Slug para URL amigável
                'size' => strlen($imageContent),
                'mime_type' => $contentType,
                'is_primary' => $isPrimary,
                'order' => $isPrimary ? 1 : 0,
            ]);

            $vehicleImage->save();

            Log::info("Imagem importada com sucesso: {$filename} para veículo {$vehicle->id}");
            return $vehicleImage;

        } catch (\Exception $e) {
            Log::error("Erro ao baixar/salvar imagem: {$imageUrl}", [
                'error' => $e->getMessage(),
                'vehicle_id' => $vehicle->id,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Processa múltiplas imagens para um veículo
     */
    private function processVehicleImages(array $imageUrls, Vehicle $vehicle): array
    {
        $savedImages = [];
        $primaryImageSet = false;
        $processedUrls = [];

        foreach ($imageUrls as $index => $imageUrl) {
            if (empty($imageUrl)) continue;

            // Verificar se a URL já foi processada para este veículo
            if ($this->isImageUrlAlreadyProcessed($imageUrl, $vehicle->id)) {
                Log::info("URL de imagem já processada, pulando: {$imageUrl}");
                continue;
            }

            // Verificar se a URL já foi processada nesta sessão
            if (in_array($imageUrl, $processedUrls)) {
                Log::info("URL de imagem duplicada na sessão, pulando: {$imageUrl}");
                continue;
            }

            $isPrimary = !$primaryImageSet && $index === 0;
            $image = $this->downloadAndSaveImage($imageUrl, $vehicle, $isPrimary);

            if ($image) {
                $savedImages[] = $image;
                $processedUrls[] = $imageUrl; // Marcar como processada
                if ($isPrimary) {
                    $primaryImageSet = true;
                }
            }
        }

        return $savedImages;
    }

    /**
     * Valida se o tipo de conteúdo é uma imagem válida
     */
    private function isValidImageType(string $contentType): bool
    {
        $validTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/bmp'
        ];

        return in_array(strtolower($contentType), $validTypes);
    }

    /**
     * Obtém extensão do arquivo baseado no MIME type
     */
    private function getExtensionFromMimeType(string $mimeType): string
    {
        $mimeToExtension = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp'
        ];

        return $mimeToExtension[strtolower($mimeType)] ?? 'jpg';
    }

    /**
     * Limpa URLs de imagem inválidas ou vazias
     */
    private function cleanImageUrls(array $urls): array
    {
        return array_filter($urls, function($url) {
            return !empty($url) && filter_var($url, FILTER_VALIDATE_URL);
        });
    }

        /**
     * Gera um nome de arquivo único, verificando se já existe
     */
    private function generateUniqueFilename(string $extension, string $imageUrl, int $tenantId): string
    {
        $maxAttempts = 10;
        $attempt = 0;

        do {
            $timestamp = time();
            $uniqueId = uniqid('', true);
            $hash = substr(md5($imageUrl . $timestamp . $uniqueId . $attempt), 0, 8);
            $filename = "imported_{$timestamp}_{$hash}.{$extension}";

            // Verificar se o arquivo já existe no storage
            $storagePath = "tenants/{$tenantId}/vehicles";
            $fullPath = $storagePath . '/' . $filename;

            if (!Storage::disk('public')->exists($fullPath)) {
                return $filename;
            }

            $attempt++;
        } while ($attempt < $maxAttempts);

        // Se chegou aqui, adicionar um número aleatório extra
        $randomSuffix = mt_rand(1000, 9999);
        $timestamp = time();
        $uniqueId = uniqid('', true);
        $hash = substr(md5($imageUrl . $timestamp . $uniqueId . $randomSuffix), 0, 8);

        return "imported_{$timestamp}_{$hash}_{$randomSuffix}.{$extension}";
    }

    /**
     * Verifica se uma URL de imagem já foi processada para evitar duplicatas
     */
    private function isImageUrlAlreadyProcessed(string $imageUrl, int $vehicleId): bool
    {
        // Verificar se já existe uma imagem com esta URL original
        return VehicleImage::where('vehicle_id', $vehicleId)
            ->where('original_name', basename($imageUrl))
            ->exists();
    }

    /**
     * Extrai URLs de imagens dos dados importados
     */
    private function extractImageUrls(array $data): array
    {
        $imageUrls = [];

        // Tentar diferentes campos comuns para imagens
        $possibleImageFields = [
            'image',
            'images',
            'photo',
            'photos',
            'picture',
            'pictures',
            'thumbnail',
            'thumbnails',
            'media',
            'gallery'
        ];

        foreach ($possibleImageFields as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];

                if (is_string($value)) {
                    // URL única
                    if (filter_var($value, FILTER_VALIDATE_URL)) {
                        $imageUrls[] = $value;
                    }
                } elseif (is_array($value)) {
                    // Array de URLs
                    foreach ($value as $item) {
                        if (is_string($item) && filter_var($item, FILTER_VALIDATE_URL)) {
                            $imageUrls[] = $item;
                        } elseif (is_array($item) && isset($item['url']) && filter_var($item['url'], FILTER_VALIDATE_URL)) {
                            $imageUrls[] = $item['url'];
                        } elseif (is_array($item) && isset($item['src']) && filter_var($item['src'], FILTER_VALIDATE_URL)) {
                            $imageUrls[] = $item['src'];
                        }
                    }
                }
            }
        }

        // Limpar e retornar URLs válidas
        return $this->cleanImageUrls($imageUrls);
    }

    /**
     * Obtém o tenant_id do usuário autenticado ou do header
     */
    private function getTenantId(Request $request): ?int
    {
        // Tentar obter do usuário autenticado
        if (Auth::check()) {
            $user = Auth::user();
            if (method_exists($user, 'tenant_id')) {
                return $user->tenant_id;
            }
        }

        // Tentar obter do header X-Tenant-ID
        $tenantId = $request->header('X-Tenant-ID');
        if ($tenantId && is_numeric($tenantId)) {
            return (int) $tenantId;
        }

        // Tentar obter do parâmetro da query (GET) ou do body (POST)
        $tenantId = $request->query('tenant_id') ?? $request->input('tenant_id');
        if ($tenantId && is_numeric($tenantId)) {
            return (int) $tenantId;
        }

        // Tentar obter do contexto de multitenancy se disponível
        if (class_exists('\\Spatie\\Multitenancy\\Multitenancy')) {
            $currentTenant = app('currentTenant');
            if ($currentTenant && method_exists($currentTenant, 'getId')) {
                return $currentTenant->getId();
            }
        }

        return null;
    }

    /**
     * Gera título automático para o veículo usando brand_id e model_id
     */
    private function generateVehicleTitle($brand, $model, array $mapped): string
    {
        $title = $brand->name . ' ' . $model->name;

        // Adicionar versão se disponível
        if (!empty($mapped['version'])) {
            $title .= ' ' . $mapped['version'];
        }

        // Adicionar ano se disponível
        if (!empty($mapped['year'])) {
            $title .= ' ' . $mapped['year'];
        }

        // Adicionar combustível se disponível
        if (!empty($mapped['fuel_type'])) {
            $title .= ' ' . ucfirst($mapped['fuel_type']);
        }

        // Adicionar transmissão se disponível
        if (!empty($mapped['transmission'])) {
            $title .= ' ' . ucfirst($mapped['transmission']);
        }

        // Adicionar cor se disponível
        if (!empty($mapped['color'])) {
            $title .= ' ' . $mapped['color'];
        }

        return trim($title);
    }
}
