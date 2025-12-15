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
use App\Models\TenantUser;
use App\Models\UserActivity;
use App\Models\UserSession;

/**
 * @OA\Tag(
 *     name="2. Admin Cliente",
 *     description="Endpoints para administradores do tenant (autenticação e gestão)"
 * )
 */

class TenantUserProfileController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/profile",
     *     summary="Exibir perfil do usuário do Tenant",
     *     description="Retorna as informações do perfil do usuário do Tenant autenticado",
     *     operationId="tenantUserProfileShow",
     *     tags={"2. Admin Cliente"},
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
     *                 @OA\Property(property="name", type="string", example="Usuário Tenant"),
     *                 @OA\Property(property="email", type="string", example="user@tenant.com"),
     *                 @OA\Property(property="username", type="string", example="user"),
     *                 @OA\Property(property="avatar", type="string", nullable=true, example="avatars/tenant/avatar.jpg"),
     *                 @OA\Property(property="phone", type="string", nullable=true, example="+55 11 99999-9999"),
     *                 @OA\Property(property="role", type="string", example="admin"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="tenant_id", type="integer", example=1),
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
            $user = Auth::guard('api')->user();

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
                    'tenant_id' => $user->tenant_id,
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
     *     path="/api/profile",
     *     summary="Atualizar perfil do usuário do Tenant",
     *     description="Atualiza as informações básicas do perfil do usuário do Tenant",
     *     operationId="tenantUserProfileUpdate",
     *     tags={"2. Admin Cliente"},
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
            $user = Auth::guard('api')->user();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'username' => 'sometimes|string|max:100|unique:tenant_users,username,' . $user->id,
                'phone' => 'nullable|string|max:20',
                'email' => 'sometimes|email|unique:tenant_users,email,' . $user->id
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user->update($validator->validated());

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
     *     path="/api/profile/password",
     *     summary="Alterar senha do usuário do Tenant",
     *     description="Altera a senha do usuário do Tenant com verificação da senha atual",
     *     operationId="tenantUserProfileUpdatePassword",
     *     tags={"2. Admin Cliente"},
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
            $user = Auth::guard('api')->user();

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

            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

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
     *     path="/api/profile/avatar",
     *     summary="Atualizar avatar do usuário do Tenant",
     *     description="Faz upload de uma nova imagem de avatar para o usuário do Tenant",
     *     operationId="tenantUserProfileUpdateAvatar",
     *     tags={"2. Admin Cliente"},
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
     *                 @OA\Property(property="avatar", type="string", example="avatars/tenant-users/avatar.jpg"),
     *                 @OA\Property(property="avatar_url", type="string", example="http://localhost/storage/avatars/tenant-users/avatar.jpg")
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
            $user = Auth::guard('api')->user();

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
            $avatarPath = $request->file('avatar')->store('avatars/tenant-users', 'public');

            $user->update(['avatar' => $avatarPath]);

            // Registrar atividade
            $this->logActivity($user->id, 'avatar_updated', 'Avatar atualizado');

            return response()->json([
                'success' => true,
                'message' => 'Avatar atualizado com sucesso',
                'data' => [
                    'avatar' => $avatarPath,
                    'avatar_url' => Storage::disk('public')->url($avatarPath)
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
     *     path="/api/profile/avatar",
     *     summary="Remover avatar do usuário do Tenant",
     *     description="Remove o avatar atual do usuário do Tenant",
     *     operationId="tenantUserProfileDeleteAvatar",
     *     tags={"2. Admin Cliente"},
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
            $user = Auth::guard('api')->user();

            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->update(['avatar' => null]);

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
     *     path="/api/profile/activity",
     *     summary="Histórico de atividades do usuário do Tenant",
     *     description="Retorna o histórico de atividades do usuário do Tenant",
     *     operationId="tenantUserProfileGetActivity",
     *     tags={"2. Admin Cliente"},
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
            $user = Auth::guard('api')->user();

            $activities = UserActivity::where('user_id', $user->id)
                ->where('user_type', 'tenant_user')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

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
     *     path="/api/profile/sessions",
     *     summary="Sessões ativas do usuário do Tenant",
     *     description="Retorna todas as sessões ativas do usuário do Tenant",
     *     operationId="tenantUserProfileGetSessions",
     *     tags={"2. Admin Cliente"},
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
            $user = Auth::guard('api')->user();

            $sessions = UserSession::where('user_id', $user->id)
                ->where('user_type', 'tenant_user')
                ->where('is_active', true)
                ->orderBy('last_activity', 'desc')
                ->get();

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
     *     path="/api/profile/sessions/{sessionId}/revoke",
     *     summary="Revogar sessão específica do usuário do Tenant",
     *     description="Revoga uma sessão específica do usuário do Tenant",
     *     operationId="tenantUserProfileRevokeSession",
     *     tags={"2. Admin Cliente"},
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
            $user = Auth::guard('api')->user();

            $session = UserSession::where('id', $sessionId)
                ->where('user_id', $user->id)
                ->where('user_type', 'tenant_user')
                ->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sessão não encontrada'
                ], 404);
            }

            $session->update(['is_active' => false]);

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
     *     path="/api/profile/sessions/revoke-all",
     *     summary="Revogar todas as sessões do usuário do Tenant",
     *     description="Revoga todas as sessões ativas do usuário do Tenant",
     *     operationId="tenantUserProfileRevokeAllSessions",
     *     tags={"2. Admin Cliente"},
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
            $user = Auth::guard('api')->user();

            UserSession::where('user_id', $user->id)
                ->where('user_type', 'tenant_user')
                ->where('is_active', true)
                ->update(['is_active' => false]);

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
     *     path="/api/profile/preferences",
     *     summary="Preferências do usuário do Tenant",
     *     description="Retorna as preferências configuradas do usuário do Tenant",
     *     operationId="tenantUserProfileGetPreferences",
     *     tags={"2. Admin Cliente"},
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
     *                     property="dashboard",
     *                     type="object",
     *                     @OA\Property(property="widgets_order", type="array", @OA\Items(type="string"))
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
            $user = Auth::guard('api')->user();

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
                ],
                'dashboard' => [
                    'show_recent_vehicles' => true,
                    'show_statistics' => true,
                    'show_recent_leads' => true,
                    'widgets_order' => ['stats', 'vehicles', 'leads']
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
     *     path="/api/profile/preferences",
     *     summary="Atualizar preferências do usuário do Tenant",
     *     description="Atualiza as preferências configuradas do usuário do Tenant",
     *     operationId="tenantUserProfileUpdatePreferences",
     *     tags={"2. Admin Cliente"},
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
     *                 property="dashboard",
     *                 type="object",
     *                 @OA\Property(property="widgets_order", type="array", @OA\Items(type="string"))
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
            $user = Auth::guard('api')->user();

            $validator = Validator::make($request->all(), [
                'theme' => 'sometimes|in:light,dark,auto',
                'language' => 'sometimes|string|max:10',
                'timezone' => 'sometimes|string|max:50',
                'date_format' => 'sometimes|string|max:20',
                'time_format' => 'sometimes|string|max:10',
                'notifications.email' => 'sometimes|boolean',
                'notifications.push' => 'sometimes|boolean',
                'notifications.sms' => 'sometimes|boolean',
                'dashboard.show_recent_vehicles' => 'sometimes|boolean',
                'dashboard.show_statistics' => 'sometimes|boolean',
                'dashboard.show_recent_leads' => 'sometimes|boolean',
                'dashboard.widgets_order' => 'sometimes|array'
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

            $user->update(['preferences' => $newPreferences]);

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
     * @OA\Get(
     *     path="/api/profile/notifications",
     *     summary="Configurações de notificações do usuário do Tenant",
     *     description="Retorna as configurações de notificações do usuário do Tenant",
     *     operationId="tenantUserProfileGetNotifications",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Configurações de notificações retornadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
     *                     type="object",
     *                     @OA\Property(property="new_leads", type="boolean", example=true),
     *                     @OA\Property(property="system_alerts", type="boolean", example=true),
     *                     @OA\Property(property="marketing", type="boolean", example=false)
     *                 ),
     *                 @OA\Property(
     *                     property="push",
     *                     type="object",
     *                     @OA\Property(property="new_leads", type="boolean", example=true),
     *                     @OA\Property(property="system_alerts", type="boolean", example=false)
     *                 ),
     *                 @OA\Property(
     *                     property="sms",
     *                     type="object",
     *                     @OA\Property(property="new_leads", type="boolean", example=false),
     *                     @OA\Property(property="system_alerts", type="boolean", example=false)
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
     * Obter configurações de notificações
     */
    public function getNotifications(): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();

            $notifications = $user->notification_settings ?? [
                'email' => [
                    'new_leads' => true,
                    'vehicle_updates' => true,
                    'system_alerts' => true,
                    'marketing' => false
                ],
                'push' => [
                    'new_leads' => true,
                    'vehicle_updates' => true,
                    'system_alerts' => true
                ],
                'sms' => [
                    'new_leads' => false,
                    'system_alerts' => false
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $notifications
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar configurações de notificações: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/profile/notifications",
     *     summary="Atualizar configurações de notificações do usuário do Tenant",
     *     description="Atualiza as configurações de notificações do usuário do Tenant",
     *     operationId="tenantUserProfileUpdateNotifications",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="email",
     *                 type="object",
     *                 @OA\Property(property="new_leads", type="boolean", example=true),
     *                 @OA\Property(property="system_alerts", type="boolean", example=true),
     *                 @OA\Property(property="marketing", type="boolean", example=false)
     *             ),
     *             @OA\Property(
     *                 property="push",
     *                 type="object",
     *                 @OA\Property(property="new_leads", type="boolean", example=true),
     *                 @OA\Property(property="system_alerts", type="boolean", example=false)
     *             ),
     *             @OA\Property(
     *                 property="sms",
     *                 type="object",
     *                 @OA\Property(property="new_leads", type="boolean", example=false),
     *                 @OA\Property(property="system_alerts", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configurações de notificações atualizadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Configurações de notificações atualizadas com sucesso"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
     *                     type="object",
     *                     @OA\Property(property="new_leads", type="boolean", example=true),
     *                     @OA\Property(property="system_alerts", type="boolean", example=true)
     *                 )
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
     * Atualizar configurações de notificações
     */
    public function updateNotifications(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();

            $validator = Validator::make($request->all(), [
                'email.new_leads' => 'sometimes|boolean',
                'email.vehicle_updates' => 'sometimes|boolean',
                'email.system_alerts' => 'sometimes|boolean',
                'email.marketing' => 'sometimes|boolean',
                'push.new_leads' => 'sometimes|boolean',
                'push.vehicle_updates' => 'sometimes|boolean',
                'push.system_alerts' => 'sometimes|boolean',
                'sms.new_leads' => 'sometimes|boolean',
                'sms.system_alerts' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $currentNotifications = $user->notification_settings ?? [];
            $newNotifications = array_merge($currentNotifications, $validator->validated());

            $user->update(['notification_settings' => $newNotifications]);

            // Registrar atividade
            $this->logActivity($user->id, 'notifications_updated', 'Configurações de notificações atualizadas');

            return response()->json([
                'success' => true,
                'message' => 'Configurações de notificações atualizadas com sucesso',
                'data' => $newNotifications
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar notificações: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/profile/security",
     *     summary="Configurações de segurança do usuário do Tenant",
     *     description="Retorna as configurações de segurança do usuário do Tenant",
     *     operationId="tenantUserProfileGetSecuritySettings",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Configurações de segurança retornadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="two_factor_enabled", type="boolean", example=false),
     *                 @OA\Property(property="login_notifications", type="boolean", example=true),
     *                 @OA\Property(property="session_timeout", type="integer", example=3600),
     *                 @OA\Property(property="require_password_change", type="boolean", example=false)
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
     * Obter configurações de segurança
     */
    public function getSecuritySettings(): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();

            $securitySettings = $user->security_settings ?? [
                'two_factor_enabled' => false,
                'login_notifications' => true,
                'session_timeout' => 30, // minutos
                'max_failed_attempts' => 5,
                'password_expiry_days' => 90,
                'require_password_change' => false
            ];

            return response()->json([
                'success' => true,
                'data' => $securitySettings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar configurações de segurança: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar configurações de segurança
     */
    public function updateSecuritySettings(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();

            $validator = Validator::make($request->all(), [
                'two_factor_enabled' => 'sometimes|boolean',
                'login_notifications' => 'sometimes|boolean',
                'session_timeout' => 'sometimes|integer|min:5|max:480',
                'max_failed_attempts' => 'sometimes|integer|min:3|max:10',
                'password_expiry_days' => 'sometimes|integer|min:30|max:365',
                'require_password_change' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $currentSecurity = $user->security_settings ?? [];
            $newSecurity = array_merge($currentSecurity, $validator->validated());

            $user->update(['security_settings' => $newSecurity]);

            // Registrar atividade
            $this->logActivity($user->id, 'security_updated', 'Configurações de segurança atualizadas');

            return response()->json([
                'success' => true,
                'message' => 'Configurações de segurança atualizadas com sucesso',
                'data' => $newSecurity
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar configurações de segurança: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registrar atividade do usuário
     */
    private function logActivity($userId, $action, $description): void
    {
        try {
            UserActivity::create([
                'user_id' => $userId,
                'user_type' => 'tenant_user',
                'action' => $action,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        } catch (\Exception $e) {
            // Log do erro mas não falhar a operação principal
            \Log::error('Erro ao registrar atividade: ' . $e->getMessage());
        }
    }
}
