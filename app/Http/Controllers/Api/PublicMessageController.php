<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Tag(
 *     name="1. Portal Público",
 *     description="Endpoints públicos do portal de anúncios (sem autenticação)"
 * )
 */
class PublicMessageController extends Controller
{
    /**
     * Obter mensagens para um módulo específico
     *
     * @OA\Get(
     *     path="/api/public/messages/{module}",
     *     summary="Obter mensagens de um módulo",
     *     description="Retorna mensagens ativas de um módulo específico para o frontend",
     *     operationId="getPublicMessages",
     *     tags={"1. Portal Público"},
     *     @OA\Parameter(
     *         name="module",
     *         in="path",
     *         description="Nome do módulo",
     *         required=true,
     *         @OA\Schema(type="string", example="vehicles")
     *     ),
     *     @OA\Parameter(
     *         name="version_hash",
     *         in="query",
     *         description="Hash de versão para verificar alterações",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensagens obtidas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="messages", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="version_hash", type="string", example="hash123"),
     *                 @OA\Property(property="has_changes", type="boolean", example=false)
     *             ),
     *             @OA\Property(property="message", type="string", example="Mensagens obtidas com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=304,
     *         description="Sem alterações (Not Modified)"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Módulo não encontrado"
     *     )
     * )
     */
    public function getMessages(Request $request, string $module): JsonResponse
    {
        try {
            $cacheKey = "messages_{$module}";
            $cachedData = Cache::get($cacheKey);

            $messages = SystemMessage::byModule($module)
                ->active()
                ->ordered()
                ->get();

            if ($messages->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma mensagem encontrada para este módulo'
                ], 404);
            }

            // Gerar hash de versão para o módulo
            $moduleHash = $this->generateModuleHash($messages);

            // Verificar se houve alterações
            $clientHash = $request->query('version_hash');
            $hasChanges = $clientHash !== $moduleHash;

            if (!$hasChanges && $clientHash) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sem alterações'
                ], 304);
            }

            $responseData = [
                'messages' => $messages,
                'version_hash' => $moduleHash,
                'has_changes' => $hasChanges
            ];

            // Cache por 1 hora
            Cache::put($cacheKey, $responseData, 3600);

            return response()->json([
                'success' => true,
                'data' => $responseData,
                'message' => 'Mensagens obtidas com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter mensagens: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter hash de versão de um módulo
     *
     * @OA\Get(
     *     path="/api/public/messages/{module}/version",
     *     summary="Verificar versão das mensagens",
     *     description="Retorna apenas o hash de versão para verificar alterações",
     *     operationId="getMessagesVersion",
     *     tags={"1. Portal Público"},
     *     @OA\Parameter(
     *         name="module",
     *         in="path",
     *         description="Nome do módulo",
     *         required=true,
     *         @OA\Schema(type="string", example="vehicles")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Hash de versão obtido com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="version_hash", type="string", example="hash123")
     *             ),
     *             @OA\Property(property="message", type="string", example="Hash de versão obtido com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Módulo não encontrado"
     *     )
     * )
     */
    public function getVersion(string $module): JsonResponse
    {
        try {
            $messages = SystemMessage::byModule($module)->active()->get();

            if ($messages->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma mensagem encontrada para este módulo'
                ], 404);
            }

            $moduleHash = $this->generateModuleHash($messages);

            return response()->json([
                'success' => true,
                'data' => [
                    'version_hash' => $moduleHash
                ],
                'message' => 'Hash de versão obtido com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter hash de versão: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gerar hash de versão para um módulo
     */
    private function generateModuleHash($messages): string
    {
        $content = '';
        foreach ($messages as $message) {
            $content .= $message->id . $message->title . $message->type .
                       $message->message . $message->icon . $message->icon_library .
                       json_encode($message->options) . $message->is_active .
                       $message->sort_order . $message->updated_at;
        }

        return hash('sha256', $content);
    }
}
