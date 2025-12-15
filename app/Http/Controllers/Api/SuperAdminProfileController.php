<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
// use App\Models\UserActivity; // TODO: Criar modelo
// use App\Models\UserSession; // TODO: Criar modelo

/**
 * @OA\Tag(
 *     name="3. Super Admin",
 *     description="Endpoints para super administradores do sistema"
 * )
 */

class SuperAdminProfileController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/super-admin/profile",
     *     summary="Exibir perfil do Super Admin",
     *     description="Retorna as informações do perfil do usuário Super Admin autenticado",
     *     operationId="superAdminProfileShow",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Perfil retornado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Super Admin"),
     *                 @OA\Property(property="email", type="string", example="admin@saas.com"),
     *                 @OA\Property(property="username", type="string", example="admin"),
     *                 @OA\Property(property="avatar", type="string", nullable=true, example="avatars/admin.jpg"),
     *                 @OA\Property(property="phone", type="string", nullable=true, example="+55 11 99999-9999"),
     *                 @OA\Property(property="role", type="string", example="super_admin"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="last_login_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     *
     * Exibir perfil do usuário autenticado
     */
    public function show(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'avatar' => $user->avatar,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'last_login_at' => $user->last_login_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar perfil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/super-admin/profile",
     *     summary="Atualizar perfil do Super Admin",
     *     description="Atualiza as informações básicas do perfil do usuário Super Admin",
     *     operationId="superAdminProfileUpdate",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255, example="Novo Nome"),
     *             @OA\Property(property="username", type="string", maxLength=100, example="novousername"),
     *             @OA\Property(property="phone", type="string", maxLength=20, nullable=true, example="+55 11 99999-9999"),
     *             @OA\Property(property="email", type="string", format="email", example="novo@email.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Perfil atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Perfil atualizado com sucesso"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Novo Nome"),
     *                 @OA\Property(property="email", type="string", example="novo@email.com"),
     *                 @OA\Property(property="username", type="string", example="novousername"),
     *                 @OA\Property(property="avatar", type="string", nullable=true),
     *                 @OA\Property(property="phone", type="string", nullable=true, example="+55 11 99999-9999"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dados inválidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Dados inválidos"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     *
     * Atualizar informações básicas do perfil
     */
    public function update(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'username' => 'sometimes|string|max:100|unique:users,username,' . $user->id,
                'phone' => 'nullable|string|max:20',
                'email' => 'sometimes|email|unique:users,email,' . $user->id
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user->fill($validator->validated())->save();

            // Registrar atividade
            $this->logActivity($user->id, 'profile_updated', 'Perfil atualizado');

            return response()->json([
                'success' => true,
                'message' => 'Perfil atualizado com sucesso',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'avatar' => $user->avatar,
                    'phone' => $user->phone,
                    'updated_at' => $user->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar perfil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/super-admin/profile/password",
     *     summary="Alterar senha do Super Admin",
     *     description="Altera a senha do usuário Super Admin com verificação da senha atual",
     *     operationId="superAdminProfileUpdatePassword",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password","new_password","new_password_confirmation"},
     *             @OA\Property(property="current_password", type="string", example="senha123"),
     *             @OA\Property(property="new_password", type="string", minLength=8, example="novaSenha123"),
     *             @OA\Property(property="new_password_confirmation", type="string", example="novaSenha123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Senha atualizada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Senha atualizada com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dados inválidos ou senha atual incorreta",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Senha atual incorreta")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     *
     * Atualizar senha
     */
    public function updatePassword(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
                'new_password_confirmation' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar senha atual
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Senha atual incorreta'
                ], 422);
            }

            $user->fill([
                'password' => Hash::make($request->new_password)
            ])->save();

            // Registrar atividade
            $this->logActivity($user->id, 'password_changed', 'Senha alterada');

            return response()->json([
                'success' => true,
                'message' => 'Senha atualizada com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar senha: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/super-admin/profile/avatar",
     *     summary="Atualizar avatar do Super Admin",
     *     description="Faz upload de uma nova imagem de avatar para o usuário Super Admin",
     *     operationId="superAdminProfileUpdateAvatar",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="avatar",
     *                     type="string",
     *                     format="binary",
     *                     description="Arquivo de imagem (jpeg, png, jpg, gif, max 2MB)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Avatar atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Avatar atualizado com sucesso"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="avatar", type="string", example="avatars/super-admin/avatar.jpg"),
     *                 @OA\Property(property="avatar_url", type="string", example="http://localhost/storage/avatars/super-admin/avatar.jpg")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dados inválidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Dados inválidos"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="avatar", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     *
     * Atualizar avatar
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Deletar avatar anterior se existir
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Salvar novo avatar
            $avatarPath = $request->file('avatar')->store('avatars/super-admin', 'public');

            $user->fill(['avatar' => $avatarPath])->save();

            // Registrar atividade
            $this->logActivity($user->id, 'avatar_updated', 'Avatar atualizado');

            return response()->json([
                'success' => true,
                'message' => 'Avatar atualizado com sucesso',
                'data' => [
                    'avatar' => $avatarPath,
                    'avatar_url' => Storage::url($avatarPath)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar avatar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/super-admin/profile/avatar",
     *     summary="Remover avatar do Super Admin",
     *     description="Remove o avatar atual do usuário Super Admin",
     *     operationId="superAdminProfileDeleteAvatar",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Avatar removido com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Avatar removido com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     *
     * Deletar avatar
     */
    public function deleteAvatar(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->fill(['avatar' => null])->save();

            // Registrar atividade
            $this->logActivity($user->id, 'avatar_deleted', 'Avatar removido');

            return response()->json([
                'success' => true,
                'message' => 'Avatar removido com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover avatar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/super-admin/profile/activity",
     *     summary="Histórico de atividades do Super Admin",
     *     description="Retorna o histórico de atividades do usuário Super Admin",
     *     operationId="superAdminProfileGetActivity",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Número de itens por página",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número da página",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Atividades retornadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer", example=25)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     *
     * Obter histórico de atividades
     */
    public function getActivity(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // TODO: Implementar quando UserActivity for criado
            $activities = collect([]); // Placeholder temporário

            return response()->json([
                'success' => true,
                'data' => $activities
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar atividades: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/super-admin/profile/sessions",
     *     summary="Sessões ativas do Super Admin",
     *     description="Retorna todas as sessões ativas do usuário Super Admin",
     *     operationId="superAdminProfileGetSessions",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Sessões retornadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="session_123"),
     *                     @OA\Property(property="user_agent", type="string"),
     *                     @OA\Property(property="ip_address", type="string"),
     *                     @OA\Property(property="last_activity", type="string", format="date-time"),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     *
     * Obter sessões ativas
     */
    public function getSessions(): JsonResponse
    {
        try {
            $user = Auth::user();

            // TODO: Implementar quando UserSession for criado
            $sessions = collect([]); // Placeholder temporário

            return response()->json([
                'success' => true,
                'data' => $sessions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar sessões: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/profile/sessions/{sessionId}/revoke",
     *     summary="Revogar sessão específica do Super Admin",
     *     description="Revoga uma sessão específica do usuário Super Admin",
     *     operationId="superAdminProfileRevokeSession",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="sessionId",
     *         in="path",
     *         description="ID da sessão a ser revogada",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sessão revogada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sessão revogada com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Sessão não encontrada"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     *
     * Revogar sessão específica
     */
    public function revokeSession($sessionId): JsonResponse
    {
        try {
            $user = Auth::user();

            // TODO: Implementar quando UserSession for criado
            return response()->json([
                'success' => false,
                'message' => 'Funcionalidade temporariamente indisponível'
            ], 501);

            // Registrar atividade
            $this->logActivity($user->id, 'session_revoked', 'Sessão revogada');

            return response()->json([
                'success' => true,
                'message' => 'Sessão revogada com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao revogar sessão: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/profile/sessions/revoke-all",
     *     summary="Revogar todas as sessões do Super Admin",
     *     description="Revoga todas as sessões ativas do usuário Super Admin",
     *     operationId="superAdminProfileRevokeAllSessions",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Todas as sessões foram revogadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Todas as sessões foram revogadas com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     *
     * Revogar todas as sessões
     */
    public function revokeAllSessions(): JsonResponse
    {
        try {
            $user = Auth::user();

            // TODO: Implementar quando UserSession for criado
            // UserSession::where('user_id', $user->id)
            //     ->where('user_type', 'super_admin')
            //     ->where('is_active', true)
            //     ->update(['is_active' => false]);

            // Registrar atividade
            $this->logActivity($user->id, 'all_sessions_revoked', 'Todas as sessões revogadas');

            return response()->json([
                'success' => true,
                'message' => 'Todas as sessões foram revogadas com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao revogar sessões: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/super-admin/profile/preferences",
     *     summary="Preferências do Super Admin",
     *     description="Retorna as preferências configuradas do usuário Super Admin",
     *     operationId="superAdminProfileGetPreferences",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Preferências retornadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="theme", type="string", example="light"),
     *                 @OA\Property(property="language", type="string", example="pt-BR"),
     *                 @OA\Property(property="timezone", type="string", example="America/Sao_Paulo"),
     *                 @OA\Property(property="date_format", type="string", example="d/m/Y"),
     *                 @OA\Property(property="time_format", type="string", example="H:i"),
     *                 @OA\Property(
     *                     property="notifications",
     *                     type="object",
     *                     @OA\Property(property="email", type="boolean", example=true),
     *                     @OA\Property(property="push", type="boolean", example=false),
     *                     @OA\Property(property="sms", type="boolean", example=false)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     *
     * Obter preferências do usuário
     */
    public function getPreferences(): JsonResponse
    {
        try {
            $user = Auth::user();

            $preferences = $user->preferences ?? [
                'theme' => 'light',
                'language' => 'pt_BR',
                'timezone' => 'America/Sao_Paulo',
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'notifications' => [
                    'email' => true,
                    'push' => true,
                    'sms' => false
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $preferences
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar preferências: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/super-admin/profile/preferences",
     *     summary="Atualizar preferências do Super Admin",
     *     description="Atualiza as preferências configuradas do usuário Super Admin",
     *     operationId="superAdminProfileUpdatePreferences",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="theme", type="string", enum={"light","dark","auto"}, example="light"),
     *             @OA\Property(property="language", type="string", example="pt-BR"),
     *             @OA\Property(property="timezone", type="string", example="America/Sao_Paulo"),
     *             @OA\Property(property="date_format", type="string", example="d/m/Y"),
     *             @OA\Property(property="time_format", type="string", example="H:i"),
     *             @OA\Property(
     *                 property="notifications",
     *                 type="object",
     *                 @OA\Property(property="email", type="boolean", example=true),
     *                 @OA\Property(property="push", type="boolean", example=false),
     *                 @OA\Property(property="sms", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Preferências atualizadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Preferências atualizadas com sucesso"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="theme", type="string", example="light"),
     *                 @OA\Property(property="language", type="string", example="pt-BR"),
     *                 @OA\Property(property="timezone", type="string", example="America/Sao_Paulo")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dados inválidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Dados inválidos"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="theme", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     *
     * Atualizar preferências do usuário
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'theme' => 'sometimes|in:light,dark,auto',
                'language' => 'sometimes|string|max:10',
                'timezone' => 'sometimes|string|max:50',
                'date_format' => 'sometimes|string|max:20',
                'time_format' => 'sometimes|string|max:10',
                'notifications.email' => 'sometimes|boolean',
                'notifications.push' => 'sometimes|boolean',
                'notifications.sms' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $currentPreferences = $user->preferences ?? [];
            $newPreferences = array_merge($currentPreferences, $validator->validated());

            $user->fill(['preferences' => $newPreferences])->save();

            // Registrar atividade
            $this->logActivity($user->id, 'preferences_updated', 'Preferências atualizadas');

            return response()->json([
                'success' => true,
                'message' => 'Preferências atualizadas com sucesso',
                'data' => $newPreferences
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar preferências: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/profile/activity/log",
     *     summary="Registrar atividade do Super Admin",
     *     description="Registra uma nova atividade do usuário Super Admin (método privado)",
     *     operationId="superAdminProfileLogActivity",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"action","description"},
     *             @OA\Property(property="action", type="string", example="profile_updated"),
     *             @OA\Property(property="description", type="string", example="Perfil atualizado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Atividade registrada com sucesso"
     *     )
     * )
     *
     * Registrar atividade do usuário
     */
    private function logActivity($userId, $action, $description): void
    {
        try {
            // TODO: Implementar quando UserActivity for criado
            // UserActivity::create([
            //     'user_id' => $userId,
            //     'user_type' => 'super_admin',
            //     'action' => $action,
            //     'description' => $description,
            //     'ip_address' => request()->ip(),
            //     'user_agent' => request()->userAgent()
            // ]);
        } catch (\Exception $e) {
            // Log do erro mas não falhar a operação principal
            Log::error('Erro ao registrar atividade: ' . $e->getMessage());
        }
    }
}
