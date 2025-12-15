<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\TenantUser;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Helpers\TokenHelper;

/**
 * @OA\Tag(
 *     name="2. Admin Cliente",
 *     description="Endpoints para administradores do tenant (autenticação e gestão)"
 * )
 */
class LeadController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/leads",
     *     summary="Listar leads",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número da página",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Itens por página",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrar por status",
     *         @OA\Schema(type="string", enum={"new", "contacted", "qualified", "negotiating", "closed_won", "closed_lost"})
     *     ),
     *     @OA\Parameter(
     *         name="source",
     *         in="query",
     *         description="Filtrar por origem",
     *         @OA\Schema(type="string", enum={"site", "whatsapp", "facebook", "instagram", "google", "outro"})
     *     ),
     *     @OA\Parameter(
     *         name="assigned_to",
     *         in="query",
     *         description="Filtrar por responsável",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de leads",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $perPage = $request->get('per_page', 15);

        $query = Lead::byTenant($user->tenant_id)
            ->with(['vehicle', 'assignedTo']);

        // Filtros
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('source')) {
            $query->bySource($request->source);
        }

        if ($request->filled('assigned_to')) {
            $query->assignedTo($request->assigned_to);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        // Ordenação
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $leads = $query->paginate($perPage);

        return response()->json([
            'data' => $leads->items(),
            'current_page' => $leads->currentPage(),
            'per_page' => $leads->perPage(),
            'total' => $leads->total(),
            'last_page' => $leads->lastPage(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/leads/{id}",
     *     summary="Exibir lead específico",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do lead",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dados do lead",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Lead não encontrado")
     * )
     */
    public function show($id)
    {
        $user = TokenHelper::getAuthenticatedUser(request());

        $lead = Lead::byTenant($user->tenant_id)
            ->with(['vehicle', 'assignedTo', 'statusHistory'])
            ->find($id);

        if (!$lead) {
            return response()->json(['error' => 'Lead não encontrado'], 404);
        }

        return response()->json($lead);
    }

    /**
     * @OA\Post(
     *     path="/api/leads",
     *     summary="Criar novo lead",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="vehicle_id", type="integer"),
     *             @OA\Property(property="source", type="string"),
     *             @OA\Property(property="assigned_to", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Lead criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function store(Request $request)
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'message' => 'nullable|string',
            'source' => 'nullable|string|max:100',
            'assigned_to' => 'nullable|exists:tenant_users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lead = Lead::create([
            'tenant_id' => $user->tenant_id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'vehicle_id' => $request->vehicle_id,
            'message' => $request->message,
            'source' => $request->source,
            'assigned_to' => $request->assigned_to,
            'status' => 'new',
            'created_by' => $user->id
        ]);

        return response()->json($lead, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/leads/{id}",
     *     summary="Atualizar lead",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do lead",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="assigned_to", type="integer"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lead atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Lead não encontrado")
     * )
     */
    public function update(Request $request, $id)
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        $lead = Lead::byTenant($user->tenant_id)->find($id);

        if (!$lead) {
            return response()->json(['error' => 'Lead não encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email',
            'phone' => 'sometimes|string|max:20',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'message' => 'nullable|string',
            'source' => 'nullable|string|max:100',
            'assigned_to' => 'nullable|exists:tenant_users,id',
            'status' => 'sometimes|in:new,contacted,qualified,proposal,closed,won,lost'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lead->update($request->only([
            'name', 'email', 'phone', 'vehicle_id', 'message',
            'source', 'assigned_to', 'status'
        ]));

        return response()->json($lead);
    }

    /**
     * @OA\Delete(
     *     path="/api/leads/{id}",
     *     summary="Excluir lead",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do lead",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lead excluído com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Lead não encontrado")
     * )
     */
    public function destroy($id)
    {
        $user = TokenHelper::getAuthenticatedUser(request());

        $lead = Lead::byTenant($user->tenant_id)->find($id);

        if (!$lead) {
            return response()->json(['error' => 'Lead não encontrado'], 404);
        }

        $lead->delete();

        return response()->json(['message' => 'Lead excluído com sucesso']);
    }

    /**
     * @OA\Post(
     *     path="/api/leads/{id}/status",
     *     summary="Atualizar status do lead",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do lead",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", enum={"new", "contacted", "qualified", "negotiating", "closed_won", "closed_lost"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Lead não encontrado")
     * )
     */
    public function updateStatus(Request $request, $id)
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        $lead = Lead::byTenant($user->tenant_id)->find($id);

        if (!$lead) {
            return response()->json(['error' => 'Lead não encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:new,contacted,qualified,proposal,closed,won,lost',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldStatus = $lead->status;
        $lead->update(['status' => $request->status]);

        // Registrar mudança de status
        $lead->statusHistory()->create([
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'changed_by' => $user->id,
            'notes' => $request->notes
        ]);

        return response()->json($lead);
    }

    /**
     * @OA\Get(
     *     path="/api/leads/dashboard",
     *     summary="Dashboard de leads",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dados do dashboard",
     *         @OA\JsonContent(
     *             @OA\Property(property="total_leads", type="integer"),
     *             @OA\Property(property="new_leads", type="integer"),
     *             @OA\Property(property="contacted_leads", type="integer"),
     *             @OA\Property(property="qualified_leads", type="integer"),
     *             @OA\Property(property="closed_won", type="integer"),
     *             @OA\Property(property="closed_lost", type="integer"),
     *             @OA\Property(property="conversion_rate", type="number"),
     *             @OA\Property(property="leads_by_source", type="object"),
     *             @OA\Property(property="recent_leads", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function dashboard()
    {
        $user = TokenHelper::getAuthenticatedUser(request());

        $totalLeads = Lead::byTenant($user->tenant_id)->count();
        $newLeads = Lead::byTenant($user->tenant_id)->where('status', 'new')->count();
        $qualifiedLeads = Lead::byTenant($user->tenant_id)->where('status', 'qualified')->count();
        $wonLeads = Lead::byTenant($user->tenant_id)->where('status', 'won')->count();

        $leadsByStatus = Lead::byTenant($user->tenant_id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $leadsBySource = Lead::byTenant($user->tenant_id)
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();

        $recentLeads = Lead::byTenant($user->tenant_id)
            ->with(['vehicle', 'assignedTo'])
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'total_leads' => $totalLeads,
            'new_leads' => $newLeads,
            'qualified_leads' => $qualifiedLeads,
            'won_leads' => $wonLeads,
            'leads_by_status' => $leadsByStatus,
            'leads_by_source' => $leadsBySource,
            'recent_leads' => $recentLeads
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/leads/{id}/assign",
     *     summary="Atribuir lead a um usuário",
     *     tags={"2. Admin Cliente"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do lead",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="assigned_to", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lead atribuído com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Lead não encontrado")
     * )
     */
    public function assign(Request $request, $id)
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        $lead = Lead::byTenant($user->tenant_id)->find($id);

        if (!$lead) {
            return response()->json(['error' => 'Lead não encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|exists:tenant_users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lead->update(['assigned_to' => $request->assigned_to]);

        return response()->json($lead);
    }
}
