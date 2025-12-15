<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Helpers\TokenHelper;

/**
 * @OA\Tag(
 *     name="2. Admin Cliente",
 *     description="Endpoints para administradores do tenant (autenticação e gestão)"
 * )
 */
class VehicleBrandController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/brands",
     *     summary="Listar marcas de veículos",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Lista de marcas")
     * )
     */
    public function index(Request $request)
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $query = VehicleBrand::query();

        // Filtros
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Ordenação
        $query->orderBy('name');

        // Se for paginação
        if ($request->filled('paginate') && $request->boolean('paginate')) {
            $brands = $query->paginate($request->get('per_page', 15));
        } else {
            // Para select/dropdown - retorna todas ativas
            $brands = $query->active()->get(['id', 'name', 'logo']);
        }

        return response()->json($brands);
    }

    /**
     * @OA\Post(
     *     path="/api/brands",
     *     summary="Criar nova marca",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="logo", type="string", format="binary"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Marca criada com sucesso")
     * )
     */
    public function store(Request $request)
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        // Verificar se é admin ou manager
        if (!in_array($user->role, ['admin', 'manager'])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:vehicle_brands,name',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        $brandData = [
            'name' => $request->name,
            'slug' => \Illuminate\Support\Str::slug($request->name),
            'is_active' => $request->get('is_active', true),
        ];

        // Upload do logo se fornecido
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('brands/logos', 's3');
            $brandData['logo'] = config('filesystems.disks.s3.url') . '/' . $logoPath;
        }

        $brand = VehicleBrand::create($brandData);

        return response()->json([
            'message' => 'Marca criada com sucesso',
            'brand' => $brand
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/brands/{id}",
     *     summary="Exibir detalhes da marca",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalhes da marca")
     * )
     */
    public function show($id)
    {
        $brand = VehicleBrand::with(['models' => function ($query) {
            $query->active()->orderBy('name');
        }])->find($id);

        if (!$brand) {
            return response()->json(['error' => 'Marca não encontrada'], 404);
        }

        return response()->json(['brand' => $brand]);
    }

    /**
     * @OA\Put(
     *     path="/api/brands/{id}",
     *     summary="Atualizar marca",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Marca atualizada")
     * )
     */
    public function update(Request $request, $id)
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        // Verificar se é admin ou manager
        if (!in_array($user->role, ['admin', 'manager'])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $brand = VehicleBrand::find($id);

        if (!$brand) {
            return response()->json(['error' => 'Marca não encontrada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:vehicle_brands,name,' . $id,
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        $updateData = $request->only(['name', 'is_active']);

        if ($request->filled('name')) {
            $updateData['slug'] = \Illuminate\Support\Str::slug($request->name);
        }

        // Upload de novo logo se fornecido
        if ($request->hasFile('logo')) {
            // Deletar logo anterior se existir
            if ($brand->logo) {
                $oldLogoPath = parse_url($brand->logo, PHP_URL_PATH);
                Storage::disk('s3')->delete($oldLogoPath);
            }

            $logoPath = $request->file('logo')->store('brands/logos', 's3');
            $updateData['logo'] = config('filesystems.disks.s3.url') . '/' . $logoPath;
        }

        $brand->update($updateData);

        return response()->json([
            'message' => 'Marca atualizada com sucesso',
            'brand' => $brand->fresh()
        ]);
    }

    /**
     * Excluir marca
     */
    public function destroy(Request $request, $id)
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        // Apenas admin pode deletar marcas
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $brand = VehicleBrand::find($id);

        if (!$brand) {
            return response()->json(['error' => 'Marca não encontrada'], 404);
        }

        // Verificar se há modelos associados
        if ($brand->models()->count() > 0) {
            return response()->json(['error' => 'Não é possível excluir marca com modelos associados'], 400);
        }

        // Excluir logo se existir
        if ($brand->logo) {
            Storage::disk('public')->delete($brand->logo);
        }

        $brand->delete();

        return response()->json(['message' => 'Marca excluída com sucesso']);
    }

    /**
     * Lista marcas para o portal público (sem autenticação)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexPublic(Request $request)
    {
        try {
            // Buscar tenant pelo subdomínio
            $subdomain = $request->header('X-Tenant-Subdomain');

            if (!$subdomain) {
                return response()->json(['error' => 'Tenant não especificado'], 400);
            }

            $tenant = \App\Models\Tenant::bySubdomain($subdomain)->active()->first();

            if (!$tenant) {
                return response()->json(['error' => 'Tenant não encontrado'], 404);
            }

            $query = VehicleBrand::query()
                ->whereHas('vehicles', function($q) use ($tenant) {
                    $q->where('tenant_id', $tenant->id)
                      ->where('is_active', true)
                      ->where('status', 'available');
                })
                ->active()
                ->orderBy('name');

            // Busca por texto
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $brands = $query->get(['id', 'name', 'slug', 'logo_url', 'description']);

            // Formatar dados para resposta pública
            $brands->transform(function ($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'logo_url' => $brand->logo_url,
                    'description' => $brand->description
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $brands,
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'subdomain' => $tenant->subdomain
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar marcas públicas: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }
}
