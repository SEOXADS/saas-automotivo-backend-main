<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class SuperAdminTenantUserController extends Controller
{
    public function index($tenantId, Request $request)
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return response()->json(['error' => 'Tenant não encontrado'], 404);
        }

        $query = TenantUser::byTenant($tenantId)
            ->select('id','name','email','phone','role','is_active','last_login_at','created_at');

        if ($request->filled('role')) {
            $query->byRole($request->role);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name','like',"%{$s}%")->orWhere('email','like',"%{$s}%");
            });
        }

        return response()->json($query->latest()->paginate($request->get('per_page', 15)));
    }

    public function store($tenantId, Request $request)
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return response()->json(['error' => 'Tenant não encontrado'], 404);
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

        $exists = TenantUser::where('tenant_id',$tenantId)->where('email',$request->email)->exists();
        if ($exists) {
            return response()->json(['error' => 'Email já cadastrado neste tenant'], 409);
        }

        $user = TenantUser::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => $request->role,
            'is_active' => true,
        ]);

        return response()->json(['message' => 'Usuário criado', 'user' => $user->makeHidden(['password'])], 201);
    }

    public function show($tenantId, $id)
    {
        $user = TenantUser::byTenant($tenantId)->find($id);
        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }
        return response()->json(['user' => $user->makeHidden(['password'])]);
    }

    public function update($tenantId, $id, Request $request)
    {
        $user = TenantUser::byTenant($tenantId)->find($id);
        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:tenant_users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'role' => 'sometimes|string|in:admin,manager,salesperson,user',
            'is_active' => 'sometimes|boolean',
            'password' => 'sometimes|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        $data = $request->only(['name','email','phone','role','is_active']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);

        return response()->json(['message' => 'Usuário atualizado', 'user' => $user->fresh()->makeHidden(['password'])]);
    }

    public function destroy($tenantId, $id)
    {
        $user = TenantUser::byTenant($tenantId)->find($id);
        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }
        $user->delete();
        return response()->json(['message' => 'Usuário deletado']);
    }

    public function activate($tenantId, $id)
    {
        $user = TenantUser::byTenant($tenantId)->find($id);
        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }
        $user->update(['is_active' => true]);
        return response()->json(['message' => 'Usuário ativado', 'user' => $user->makeHidden(['password'])]);
    }

    public function deactivate($tenantId, $id)
    {
        $user = TenantUser::byTenant($tenantId)->find($id);
        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }
        $user->update(['is_active' => false]);
        return response()->json(['message' => 'Usuário desativado', 'user' => $user->makeHidden(['password'])]);
    }
}
