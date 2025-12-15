<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Tag(
 *     name="3. Super Admin",
 *     description="Endpoints para super administradores do sistema"
 * )
 *
 * @OA\Schema(
 *     schema="SystemMessageRequest",
 *     title="Requisição de Mensagem do Sistema",
 *     description="Dados para criar/atualizar mensagem do sistema",
 *     required={"module", "title", "type", "message"},
 *     @OA\Property(property="module", type="string", example="vehicles", description="Módulo da mensagem"),
 *     @OA\Property(property="title", type="string", example="Veículo Criado", description="Título da mensagem"),
 *     @OA\Property(property="type", type="string", enum={"error", "success", "info", "warning", "question", "loading"}, example="success", description="Tipo da mensagem"),
 *     @OA\Property(property="message", type="string", example="Veículo criado com sucesso!", description="Conteúdo da mensagem"),
 *     @OA\Property(property="icon", type="string", nullable=true, example="check-circle", description="Ícone da mensagem"),
 *     @OA\Property(property="icon_library", type="string", nullable=true, example="heroicons", description="Biblioteca de ícones"),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Status ativo da mensagem"),
 *     @OA\Property(property="sort_order", type="integer", example=1, description="Ordem de exibição"),
 *     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true, description="Data de expiração")
 * )
 *
 * @OA\Schema(
 *     schema="SystemMessageResponse",
 *     title="Resposta de Mensagem do Sistema",
 *     description="Resposta padrão para operações com mensagens do sistema",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Mensagem processada com sucesso"),
 *     @OA\Property(property="data", type="object")
 * )
 */
class SystemMessageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/system-messages",
     *     summary="Listar mensagens do sistema",
     *     description="Retorna todas as mensagens do sistema com filtros opcionais",
     *     operationId="getSystemMessages",
     *     tags={"Mensagens do Sistema"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="module",
     *         in="query",
     *         description="Filtrar por módulo",
     *         required=false,
     *         @OA\Schema(type="string", example="vehicles")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrar por tipo",
     *         required=false,
     *         @OA\Schema(type="string", enum={"error", "success", "info", "warning", "question", "loading"})
     *     ),
     *     @OA\Parameter(
     *         name="active",
     *         in="query",
     *         description="Filtrar por status ativo",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensagens listadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string", example="Mensagens listadas com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = SystemMessage::query();

            // Filtros
            if ($request->filled('module')) {
                $query->byModule($request->module);
            }

            if ($request->filled('type')) {
                $query->byType($request->type);
            }

            if ($request->has('active')) {
                $active = filter_var($request->active, FILTER_VALIDATE_BOOLEAN);
                if ($active) {
                    $query->active();
                }
            }

            $messages = $query->ordered()->get();

            return response()->json([
                'success' => true,
                'data' => $messages,
                'message' => 'Mensagens listadas com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar mensagens: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/system-messages",
     *     summary="Criar nova mensagem",
     *     description="Cria uma nova mensagem do sistema",
     *     operationId="createSystemMessage",
     *     tags={"Mensagens do Sistema"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Mensagem criada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Mensagem criada com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dados inválidos"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'module' => 'required|string|max:100',
                'title' => 'required|string|max:255',
                'type' => 'required|in:error,success,info,warning,question,loading',
                'message' => 'required|string',
                'icon' => 'nullable|string|max:100',
                'icon_library' => 'nullable|string|max:50',
                'options' => 'nullable|array',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $message = SystemMessage::create([
                'module' => $request->module,
                'title' => $request->title,
                'type' => $request->type,
                'message' => $request->message,
                'icon' => $request->icon,
                'icon_library' => $request->icon_library,
                'options' => $request->options,
                'is_active' => $request->input('is_active', true),
                'sort_order' => $request->input('sort_order', 0)
            ]);

            // Limpar cache
            $this->clearMessagesCache($request->module);

            return response()->json([
                'success' => true,
                'data' => $message,
                'message' => 'Mensagem criada com sucesso'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar mensagem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/system-messages/{id}",
     *     summary="Exibir mensagem específica",
     *     description="Retorna uma mensagem específica do sistema",
     *     operationId="getSystemMessage",
     *     tags={"Mensagens do Sistema"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da mensagem",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensagem encontrada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
      *             @OA\Property(property="data", type="object"),
 *             @OA\Property(property="message", type="string", example="Mensagem encontrada com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Mensagem não encontrada"
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $message = SystemMessage::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $message,
                'message' => 'Mensagem encontrada com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Mensagem não encontrada'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/system-messages/{id}",
     *     summary="Atualizar mensagem",
     *     description="Atualiza uma mensagem existente do sistema",
     *     operationId="updateSystemMessage",
     *     tags={"Mensagens do Sistema"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da mensagem",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensagem atualizada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
      *             @OA\Property(property="data", type="object"),
 *             @OA\Property(property="message", example="Mensagem atualizada com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Mensagem não encontrada"
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $message = SystemMessage::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'module' => 'sometimes|required|string|max:100',
                'title' => 'sometimes|required|string|max:255',
                'type' => 'sometimes|required|in:error,success,info,warning,question,loading',
                'message' => 'sometimes|required|string',
                'icon' => 'nullable|string|max:100',
                'icon_library' => 'nullable|string|max:50',
                'options' => 'nullable|array',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $message->update($request->only([
                'module', 'title', 'type', 'message', 'icon',
                'icon_library', 'options', 'is_active', 'sort_order'
            ]));

            // Limpar cache
            $this->clearMessagesCache($message->module);

            return response()->json([
                'success' => true,
                'data' => $message,
                'message' => 'Mensagem atualizada com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar mensagem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/api/system-messages/{id}",
     *     summary="Excluir mensagem",
     *     description="Remove uma mensagem do sistema",
     *     operationId="deleteSystemMessage",
     *     tags={"Mensagens do Sistema"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da mensagem",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensagem excluída com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Mensagem excluída com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Mensagem não encontrada"
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $message = SystemMessage::findOrFail($id);
            $module = $message->module;

            $message->delete();

            // Limpar cache
            $this->clearMessagesCache($module);

            return response()->json([
                'success' => true,
                'message' => 'Mensagem excluída com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir mensagem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpar cache de mensagens
     */
    private function clearMessagesCache(string $module): void
    {
        Cache::forget("messages_{$module}");
    }
}
