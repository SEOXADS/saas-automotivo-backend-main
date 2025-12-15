<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantUrlPattern;
use App\Models\TenantUrl;
use App\Models\TenantUrlRedirect;
use App\Models\City;
use App\Models\Neighborhood;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="2. Admin Cliente - URLs Personalizadas",
 *     description="Endpoints para gestão de URLs personalizadas por tenant"
 * )
 */
class TenantUrlController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/tenant/urls/patterns",
     *     summary="Listar patterns de URL do tenant",
     *     description="Retorna uma lista de patterns de URL configurados para o tenant. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - URLs Personalizadas"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Buscar por pattern ou URL gerada",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="urlable_type",
     *         in="query",
     *         description="Filtrar por tipo de modelo",
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
     *         description="Lista de patterns retornada com sucesso"
     *     )
     * )
     */
    public function getPatterns(Request $request): JsonResponse
    {
        try {
            $tenantId = $request->user()->tenant_id;

            $query = TenantUrlPattern::forTenant($tenantId)->with('urlable');

            // Filtros
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('pattern', 'like', "%{$search}%")
                      ->orWhere('generated_url', 'like', "%{$search}%");
                });
            }

            if ($request->filled('urlable_type')) {
                $query->where('urlable_type', $request->urlable_type);
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $patterns = $query->orderedByPriority()->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $patterns->items(),
                'pagination' => [
                    'current_page' => $patterns->currentPage(),
                    'last_page' => $patterns->lastPage(),
                    'per_page' => $patterns->perPage(),
                    'total' => $patterns->total(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar patterns de URL do tenant', [
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
     *     path="/api/tenant/urls/patterns",
     *     summary="Criar pattern de URL",
     *     description="Cria um novo pattern de URL para o tenant. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - URLs Personalizadas"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"pattern", "urlable_type", "urlable_id"},
     *             @OA\Property(property="pattern", type="string", example="/veiculos/{slug}"),
     *             @OA\Property(property="urlable_type", type="string", example="App\\Models\\Vehicle"),
     *             @OA\Property(property="urlable_id", type="integer", example=1),
     *             @OA\Property(property="generated_url", type="string", example="/veiculos/honda-civic-2023"),
     *             @OA\Property(property="is_primary", type="boolean", example=true),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="priority", type="integer", example=100),
     *             @OA\Property(property="context_data", type="object", example={"spintext": "texto personalizado", "sintext": "sinônimo"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Pattern criado com sucesso"
     *     ),
     *     @OA\Response(response=422, description="Dados de validação inválidos")
     * )
     */
    public function createPattern(Request $request): JsonResponse
    {
        try {
            $tenantId = $request->user()->tenant_id;

            $validator = Validator::make($request->all(), [
                'pattern' => 'required|string|max:255',
                'urlable_type' => 'required|string|max:255',
                'urlable_id' => 'required|integer',
                'generated_url' => 'nullable|string|max:255',
                'is_primary' => 'boolean',
                'is_active' => 'boolean',
                'priority' => 'integer|min:0',
                'context_data' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar se o modelo existe
            $urlableType = $request->urlable_type;
            if (!class_exists($urlableType)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Tipo de modelo inválido'
                ], 422);
            }

            $urlable = $urlableType::find($request->urlable_id);
            if (!$urlable) {
                return response()->json([
                    'success' => false,
                    'error' => 'Modelo não encontrado'
                ], 404);
            }

            // Gerar URL se não fornecida
            $generatedUrl = $request->generated_url;
            if (!$generatedUrl) {
                $generatedUrl = $this->generateUrlFromPattern($request->pattern, $urlable);
            }

            // Verificar se a URL já existe para este tenant
            $existingPattern = TenantUrlPattern::forTenant($tenantId)
                ->byGeneratedUrl($generatedUrl)
                ->first();

            if ($existingPattern) {
                return response()->json([
                    'success' => false,
                    'error' => 'Esta URL já existe para este tenant'
                ], 409);
            }

            $pattern = TenantUrlPattern::create([
                'tenant_id' => $tenantId,
                'pattern' => $request->pattern,
                'urlable_type' => $urlableType,
                'urlable_id' => $request->urlable_id,
                'generated_url' => $generatedUrl,
                'is_primary' => $request->get('is_primary', false),
                'is_active' => $request->get('is_active', true),
                'priority' => $request->get('priority', 0),
                'context_data' => $request->get('context_data', [])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pattern criado com sucesso',
                'data' => $pattern->load('urlable')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao criar pattern de URL', [
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
     * @OA\Put(
     *     path="/api/tenant/urls/patterns/{id}",
     *     summary="Atualizar pattern de URL",
     *     description="Atualiza um pattern de URL existente. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - URLs Personalizadas"},
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
     *             @OA\Property(property="pattern", type="string", example="/veiculos/{slug}"),
     *             @OA\Property(property="generated_url", type="string", example="/veiculos/honda-civic-2023"),
     *             @OA\Property(property="is_primary", type="boolean", example=true),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="priority", type="integer", example=100),
     *             @OA\Property(property="context_data", type="object", example={"spintext": "texto personalizado", "sintext": "sinônimo"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pattern atualizado com sucesso"
     *     ),
     *     @OA\Response(response=404, description="Pattern não encontrado")
     * )
     */
    public function updatePattern(Request $request, int $id): JsonResponse
    {
        try {
            $tenantId = $request->user()->tenant_id;

            $pattern = TenantUrlPattern::forTenant($tenantId)->find($id);

            if (!$pattern) {
                return response()->json([
                    'success' => false,
                    'error' => 'Pattern não encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'pattern' => 'sometimes|string|max:255',
                'generated_url' => 'sometimes|string|max:255',
                'is_primary' => 'boolean',
                'is_active' => 'boolean',
                'priority' => 'integer|min:0',
                'context_data' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $oldUrl = $pattern->generated_url;
            $newUrl = $request->get('generated_url', $pattern->generated_url);

            // Se a URL mudou, criar redirect
            if ($oldUrl !== $newUrl && $request->has('generated_url')) {
                $this->createRedirect($tenantId, $oldUrl, $newUrl);
            }

            $pattern->update($request->only([
                'pattern', 'generated_url', 'is_primary', 'is_active', 'priority', 'context_data'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Pattern atualizado com sucesso',
                'data' => $pattern->load('urlable')
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar pattern de URL', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'pattern_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/tenant/urls/patterns/{id}",
     *     summary="Deletar pattern de URL",
     *     description="Remove um pattern de URL. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - URLs Personalizadas"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pattern removido com sucesso"
     *     ),
     *     @OA\Response(response=404, description="Pattern não encontrado")
     * )
     */
    public function deletePattern(Request $request, int $id): JsonResponse
    {
        try {
            $tenantId = $request->user()->tenant_id;

            $pattern = TenantUrlPattern::forTenant($tenantId)->find($id);

            if (!$pattern) {
                return response()->json([
                    'success' => false,
                    'error' => 'Pattern não encontrado'
                ], 404);
            }

            $pattern->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pattern removido com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao deletar pattern de URL', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'pattern_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Gerar URL a partir do pattern e dados do modelo
     */
    private function generateUrlFromPattern(string $pattern, $model): string
    {
        $url = $pattern;

        // Substituir placeholders comuns
        $replacements = [
            '{slug}' => Str::slug($model->title ?? $model->name ?? 'item'),
            '{id}' => $model->id,
            '{title}' => Str::slug($model->title ?? ''),
            '{name}' => Str::slug($model->name ?? ''),
        ];

        foreach ($replacements as $placeholder => $value) {
            $url = str_replace($placeholder, $value, $url);
        }

        return $url;
    }

    /**
     * @OA\Put(
     *     path="/api/tenant/urls/redirects/{id}",
     *     summary="Atualizar redirect",
     *     description="Atualiza um redirect existente. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - URLs Personalizadas"},
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
     *             @OA\Property(property="old_path", type="string", example="/veiculos/antigo"),
     *             @OA\Property(property="new_path", type="string", example="/veiculos/novo"),
     *             @OA\Property(property="status_code", type="integer", example=301),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Redirect atualizado com sucesso"
     *     ),
     *     @OA\Response(response=404, description="Redirect não encontrado")
     * )
     */
    public function updateRedirect(Request $request, int $id): JsonResponse
    {
        try {
            $tenantId = $request->user()->tenant_id;

            $redirect = TenantUrlRedirect::forTenant($tenantId)->find($id);

            if (!$redirect) {
                return response()->json([
                    'success' => false,
                    'error' => 'Redirect não encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'old_path' => 'sometimes|string|max:255',
                'new_path' => 'sometimes|string|max:255',
                'status_code' => 'integer|in:301,302,307,308',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $redirect->update($request->only([
                'old_path', 'new_path', 'status_code', 'is_active'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Redirect atualizado com sucesso',
                'data' => $redirect
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar redirect', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'redirect_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/tenant/urls/redirects/{id}",
     *     summary="Deletar redirect",
     *     description="Remove um redirect. Requer autenticação de tenant.",
     *     tags={"2. Admin Cliente - URLs Personalizadas"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Redirect removido com sucesso"
     *     ),
     *     @OA\Response(response=404, description="Redirect não encontrado")
     * )
     */
    public function deleteRedirect(Request $request, int $id): JsonResponse
    {
        try {
            $tenantId = $request->user()->tenant_id;

            $redirect = TenantUrlRedirect::forTenant($tenantId)->find($id);

            if (!$redirect) {
                return response()->json([
                    'success' => false,
                    'error' => 'Redirect não encontrado'
                ], 404);
            }

            $redirect->delete();

            return response()->json([
                'success' => true,
                'message' => 'Redirect removido com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao deletar redirect', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'redirect_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Criar redirect quando URL muda
     */
    private function createRedirect(int $tenantId, string $oldPath, string $newPath): void
    {
        TenantUrlRedirect::create([
            'tenant_id' => $tenantId,
            'old_path' => $oldPath,
            'new_path' => $newPath,
            'status_code' => 301,
            'is_active' => true
        ]);
    }
}
