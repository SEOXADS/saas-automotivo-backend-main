<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantUser;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Helpers\TokenHelper;

/**
 * @OA\Tag(
 *     name="2. Admin Cliente",
 *     description="Endpoints para administradores do tenant (autenticação e gestão)"
 * )
 */
class TenantUserController extends Controller
{
    /**
     * Listar usuários do tenant
     */
    public function index(Request $request): JsonResponse
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $tenantId = $user->tenant_id;

        $query = TenantUser::byTenant($tenantId)
            ->select('id', 'name', 'email', 'phone', 'role', 'is_active', 'last_login_at', 'created_at');

        // Filtros
        if ($request->filled('role')) {
            $query->byRole($request->role);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15);

        return response()->json($users);
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Criar novo usuário",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=201, description="Usuário criado")
     * )
     */
    public function store(Request $request)
    {
        $currentUser = TokenHelper::getAuthenticatedUser($request);

        if (!in_array($currentUser->role, ['admin', 'manager'])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|in:admin,manager,salesperson,user',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        $existingUser = TenantUser::where('email', $request->email)
            ->where('tenant_id', $currentUser->tenant_id)
            ->first();

        if ($existingUser) {
            return response()->json(['error' => 'Email já cadastrado'], 409);
        }

        if ($request->role === 'admin' && $currentUser->role !== 'admin') {
            return response()->json(['error' => 'Apenas admins podem criar outros admins'], 403);
        }

        $user = TenantUser::create([
            'tenant_id' => $currentUser->tenant_id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => $request->role,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Usuário criado com sucesso',
            'user' => $user->makeHidden(['password'])
        ], 201);
    }

    /**
     * Exibir usuário específico
     */
    public function show(Request $request, $id)
    {
        $currentUser = TokenHelper::getAuthenticatedUser($request);

        $user = TenantUser::byTenant($currentUser->tenant_id)->find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        return response()->json($user);
    }

    /**
     * Atualizar usuário
     */
    public function update(Request $request, $id)
    {
        $currentUser = TokenHelper::getAuthenticatedUser($request);
        $user = TenantUser::byTenant($currentUser->tenant_id)->find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:tenant_users,email,' . $id,
            'role' => 'sometimes|in:admin,manager,salesperson',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->update($request->only(['name', 'email', 'role', 'is_active']));

        return response()->json($user);
    }

    /**
     * Excluir usuário
     */
    public function destroy(Request $request, $id)
    {
        $currentUser = TokenHelper::getAuthenticatedUser($request);

        if ($currentUser->role !== 'admin') {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $user = TenantUser::byTenant($currentUser->tenant_id)->find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        if ($user->id === $currentUser->id) {
            return response()->json(['error' => 'Não é possível excluir seu próprio usuário'], 400);
        }

        $user->delete();

        return response()->json(['message' => 'Usuário excluído com sucesso']);
    }

    /**
     * Ativar usuário
     */
    public function activate(Request $request, $id)
    {
        $currentUser = TokenHelper::getAuthenticatedUser($request);

        if (!in_array($currentUser->role, ['admin', 'manager'])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $user = TenantUser::byTenant($currentUser->tenant_id)->find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        $user->update(['is_active' => true]);

        return response()->json(['message' => 'Usuário ativado com sucesso']);
    }

    /**
     * Desativar usuário
     */
    public function deactivate(Request $request, $id)
    {
        $currentUser = TokenHelper::getAuthenticatedUser($request);

        if (!in_array($currentUser->role, ['admin', 'manager'])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $user = TenantUser::byTenant($currentUser->tenant_id)->find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        if ($user->id === $currentUser->id) {
            return response()->json(['error' => 'Não é possível desativar seu próprio usuário'], 400);
        }

        $user->update(['is_active' => false]);

        return response()->json(['message' => 'Usuário desativado com sucesso']);
    }
}
