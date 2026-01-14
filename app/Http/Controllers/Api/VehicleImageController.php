<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="2. Admin Cliente",
 *     description="Endpoints para administradores do tenant (autenticação e gestão)"
 * )
 *
 * @OA\Schema(
 *     schema="VehicleImageResponse",
 *     title="Resposta de Imagem do Veículo",
 *     description="Resposta padrão para operações com imagens de veículos",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Imagem processada com sucesso"),
 *     @OA\Property(property="data", ref="#/components/schemas/VehicleImage")
 * )
 *
 * @OA\Schema(
 *     schema="VehicleImageListResponse",
 *     title="Lista de Imagens do Veículo",
 *     description="Resposta para listagem de imagens de veículos",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/VehicleImage")),
 *     @OA\Property(property="message", type="string", example="Imagens listadas com sucesso")
 * )
 */
class VehicleImageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $vehicleId = $request->route('vehicle_id');

            $images = VehicleImage::where('vehicle_id', $vehicleId)
                ->ordered()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $images,
                'message' => 'Imagens do veículo listadas com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar imagens: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Endpoint para criação de imagens'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $vehicleId = $request->route('vehicle_id');

            // Validar se o veículo existe
            $vehicle = Vehicle::findOrFail($vehicleId);

            $validator = Validator::make($request->all(), [
                'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
                'is_primary' => 'boolean',
                'sort_order' => 'integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $uploadedImages = [];
            $files = $request->file('images');

            if (!$files) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma imagem foi enviada'
                ], 400);
            }

            foreach ($files as $file) {
                $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('vehicles/' . $vehicleId, $filename, 'public');

                $image = VehicleImage::create([
                    'vehicle_id' => $vehicleId,
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'url' => Storage::url($path),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'width' => getimagesize($file->getPathname())[0] ?? null,
                    'height' => getimagesize($file->getPathname())[1] ?? null,
                    'is_primary' => $request->input('is_primary', false),
                    'sort_order' => $request->input('sort_order', 0)
                ]);

                $uploadedImages[] = $image;
            }

            // Se esta é a primeira imagem, definir como primária
            if (count($uploadedImages) > 0 && !$vehicle->images()->exists()) {
                $uploadedImages[0]->update(['is_primary' => true]);
            }

            return response()->json([
                'success' => true,
                'data' => $uploadedImages,
                'message' => 'Imagens enviadas com sucesso'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar imagens: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $vehicleId = request()->route('vehicle_id');

            $image = VehicleImage::where('id', $id)
                ->where('vehicle_id', $vehicleId)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $image,
                'message' => 'Imagem encontrada com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Imagem não encontrada'
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): JsonResponse
    {
        try {
            $vehicleId = request()->route('vehicle_id');

            $image = VehicleImage::where('id', $id)
                ->where('vehicle_id', $vehicleId)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $image,
                'message' => 'Imagem carregada para edição'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Imagem não encontrada'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $vehicleId = request()->route('vehicle_id');

            $image = VehicleImage::where('id', $id)
                ->where('vehicle_id', $vehicleId)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'is_primary' => 'boolean',
                'sort_order' => 'integer|min:0',
                'description' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $image->update($request->only(['is_primary', 'sort_order', 'description']));

            return response()->json([
                'success' => true,
                'data' => $image,
                'message' => 'Imagem atualizada com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar imagem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VehicleImage $vehicleImage): JsonResponse
    {
        try {
            // Deletar arquivo físico
            if (Storage::disk('public')->exists($vehicleImage->path)) {
                Storage::disk('public')->delete($vehicleImage->path);
            }

            $vehicleImage->delete();

            return response()->json([
                'success' => true,
                'message' => 'Imagem deletada com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar imagem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Servir imagem publicamente
     *
     * @OA\Get(
     *     path="/api/public/images/{tenantId}/{vehicleId}/{filename}",
     *     summary="Servir imagem de veículo",
     *     description="Retorna uma imagem específica de um veículo para visualização pública",
     *     operationId="serveVehicleImage",
     *     tags={"2. Admin Cliente"},
     *     @OA\Parameter(
     *         name="tenantId",
     *         in="path",
     *         description="ID do tenant",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="vehicleId",
     *         in="path",
     *         description="ID do veículo",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="filename",
     *         in="path",
     *         description="Nome do arquivo da imagem",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Imagem retornada com sucesso",
     *         @OA\MediaType(mediaType="image/*")
     *     ),
     *     @OA\Response(response=404, description="Imagem não encontrada")
     * )
     */
    /*public function serveImage($tenantId, $vehicleId, $filename)
    {
        try {
            // Verificar se o tenant existe e está ativo
            $tenant = \App\Models\Tenant::find($tenantId);
            if (!$tenant || $tenant->status !== 'active') {
                abort(404, 'Tenant não encontrado ou inativo');
            }

            // Verificar se o veículo existe e pertence ao tenant
            $vehicle = Vehicle::where('id', $vehicleId)
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->first();

            if (!$vehicle) {
                abort(404, 'Veículo não encontrado');
            }

            // Verificar se a imagem existe
            $image = VehicleImage::where('vehicle_id', $vehicleId)
                ->where('filename', $filename)
                ->first();

            if (!$image) {
                abort(404, 'Imagem não encontrada');
            }

            // Construir o caminho completo da imagem
            //$path = "tenants/{$tenantId}/vehicles/{$vehicleId}/{$filename}";
            $path = "vehicles/{$vehicleId}/{$filename}";

            // Verificar se o arquivo existe no storage
            if (!Storage::disk('public')->exists($path)) {
                abort(404, 'Arquivo não encontrado');
            }

            // Retornar a imagem com headers apropriados
            $file = Storage::disk('public')->get($path);
            $type = mime_content_type(Storage::disk('public')->path($path));

            return response($file, 200, [
                'Content-Type' => $type,
                'Cache-Control' => 'public, max-age=31536000', // Cache por 1 ano
                'Content-Disposition' => 'inline; filename="' . $image->original_name . '"'
            ]);

        } catch (\Exception $e) {
            abort(404, 'Imagem não encontrada');
        }
    }*/
    public function serveImage($tenantId, $vehicleId, $filename)
    {
        // Validate parameters
        if (!is_numeric($tenantId) || !is_numeric($vehicleId)) {
            abort(400, 'Invalid parameters');
        }
        
        $path = storage_path("app/public/tenants/{$tenantId}/vehicles/{$vehicleId}/{$filename}");
        
        // Check if file exists
        if (!Storage::exists("public/tenants/{$tenantId}/vehicles/{$vehicleId}/{$filename}")) {
            abort(404, 'Image not found');
        }
        
        // Check file permissions
        if (!file_exists($path)) {
            abort(404, 'File does not exist');
        }
        
        // Get file mime type
        $mime = mime_content_type($path);
        
        // Return the image with proper headers
        return response()->file($path, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    /**
     * Obter URL pública de uma imagem
     *
     * @OA\Get(
     *     path="/api/public/images/{tenantId}/{vehicleId}/{filename}/url",
     *     summary="Obter URL pública da imagem",
     *     description="Retorna a URL pública de uma imagem específica",
     *     operationId="getImageUrl",
     *     tags={"2. Admin Cliente"},
     *     @OA\Parameter(
     *         name="tenantId",
     *         in="path",
     *         description="ID do tenant",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="vehicleId",
     *         in="path",
     *         description="ID do veículo",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="filename",
     *         in="path",
     *         description="Nome do arquivo da imagem",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="URL da imagem",
     *         @OA\JsonContent(
     *             @OA\Property(property="url", type="string", example="http://example.com/storage/tenants/1/vehicles/5/image.jpg")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Imagem não encontrada")
     * )
     */
    public function getImageUrl($tenantId, $vehicleId, $filename)
    {
        try {
            // Verificar se o tenant existe e está ativo
            $tenant = \App\Models\Tenant::find($tenantId);
            if (!$tenant || $tenant->status !== 'active') {
                return response()->json(['error' => 'Tenant não encontrado ou inativo'], 404);
            }

            // Verificar se o veículo existe e pertence ao tenant
            $vehicle = Vehicle::where('id', $vehicleId)
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->first();

            if (!$vehicle) {
                return response()->json(['error' => 'Veículo não encontrado'], 404);
            }

            // Verificar se a imagem existe
            $image = VehicleImage::where('vehicle_id', $vehicleId)
                ->where('filename', $filename)
                ->first();

            if (!$image) {
                return response()->json(['error' => 'Imagem não encontrada'], 404);
            }

            // Retornar a URL pública
            $url = route('vehicle.image.serve', [
                'tenantId' => $tenantId,
                'vehicleId' => $vehicleId,
                'filename' => $filename
            ]);

            return response()->json([
                'url' => $url
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao obter URL da imagem'], 500);
        }
    }
}
