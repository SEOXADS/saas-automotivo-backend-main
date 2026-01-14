<?php

namespace App\Http\Controllers\Api; // ✅ MUST BE \Api

use App\Http\Controllers\Controller; // ✅ Import Parent Controller
use App\Models\CustomSeoEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomSEOController extends Controller
{
    public function index(Request $request)
    {
        try {
            $tenantId = $request->get('tenant_id');
            $query = CustomSeoEntry::query();
            
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }
            
            $entries = $query->orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $entries
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao listar custom SEO: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Erro ao listar configurações SEO'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tenant_id' => 'required|integer|exists:tenants,id',
                'page_url' => 'required|string|max:255',
                'page_title' => 'required|string|max:255',
                'subtitle' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'meta_keywords' => 'nullable|string',
                'meta_author' => 'nullable|string|max:255',
                'meta_robots' => 'nullable|string|max:50',
                'og_title' => 'nullable|string|max:255',
                'og_description' => 'nullable|string',
                'og_image_url' => 'nullable|string|max:500',
                'og_site_name' => 'nullable|string|max:255',
                'og_type' => 'nullable|string|max:50',
                'og_locale' => 'nullable|string|max:10',
                'twitter_card' => 'nullable|string|max:50',
                'twitter_title' => 'nullable|string|max:255',
                'twitter_description' => 'nullable|string',
                'twitter_image_url' => 'nullable|string|max:500',
                'twitter_site' => 'nullable|string|max:100',
                'twitter_creator' => 'nullable|string|max:100',
                'canonical_url' => 'nullable|string|max:500',
                'structured_data' => 'nullable|array',
            ]);

            // Check if entry already exists
            $existing = CustomSeoEntry::where('tenant_id', $validated['tenant_id'])
                ->where('page_url', $validated['page_url'])
                ->first();

            if ($existing) {
                $existing->update($validated);
                $entry = $existing->fresh();
            } else {
                $entry = CustomSeoEntry::create($validated);
            }

            return response()->json([
                'success' => true,
                'data' => $entry,
                'message' => 'Configuração SEO salva com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao salvar custom SEO: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Erro ao salvar: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $entry = CustomSeoEntry::findOrFail($id);
            return response()->json(['success' => true, 'data' => $entry]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Não encontrado'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $entry = CustomSeoEntry::findOrFail($id);
            $validated = $request->validate([
                'page_url' => 'sometimes|string|max:255',
                'page_title' => 'sometimes|string|max:255',
                'subtitle' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'meta_keywords' => 'nullable|string',
                'meta_author' => 'nullable|string|max:255',
                'meta_robots' => 'nullable|string|max:50',
                'og_title' => 'nullable|string|max:255',
                'og_description' => 'nullable|string',
                'og_image_url' => 'nullable|string|max:500',
                'og_site_name' => 'nullable|string|max:255',
                'og_type' => 'nullable|string|max:50',
                'og_locale' => 'nullable|string|max:10',
                'twitter_card' => 'nullable|string|max:50',
                'twitter_title' => 'nullable|string|max:255',
                'twitter_description' => 'nullable|string',
                'twitter_image_url' => 'nullable|string|max:500',
                'twitter_site' => 'nullable|string|max:100',
                'twitter_creator' => 'nullable|string|max:100',
                'canonical_url' => 'nullable|string|max:500',
                'structured_data' => 'nullable|array',
            ]);

            $entry->update($validated);
            return response()->json(['success' => true, 'data' => $entry->fresh()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Erro ao atualizar'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $entry = CustomSeoEntry::findOrFail($id);
            $entry->delete();
            return response()->json(['success' => true, 'message' => 'Excluído com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Erro ao excluir'], 500);
        }
    }

    /*public function getByUrl(Request $request)
    {
        try {
            $tenantId = $request->get('tenant_id');
            $url = $request->get('url');

            $entry = CustomSeoEntry::where('tenant_id', $tenantId)
                ->where('page_url', $url)
                ->first();

            if (!$entry) {
                return response()->json(['success' => true, 'data' => null]);
            }

            return response()->json(['success' => true, 'data' => $entry]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Erro ao buscar'], 500);
        }
    }*/

    public function getByUrl(Request $request)
    {
        try {
            // ✅ FIX: Use query() or input() for GET parameters
            $tenantId = $request->query('tenant_id'); // or $request->input('tenant_id')
            $url = $request->query('url'); // or $request->input('url')
            
            // Log the actual values
            Log::info('getByUrl called with:', [
                'tenant_id' => $tenantId,
                'url' => $url,
                'all_query' => $request->query(),
                'all_input' => $request->all()
            ]);

            // Validate required parameters
            if (!$tenantId || !$url) {
                Log::warning('Missing parameters', [
                    'tenant_id' => $tenantId,
                    'url' => $url
                ]);
                
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'Missing tenant_id or url parameter'
                ]);
            }

            $entry = CustomSeoEntry::where('tenant_id', $tenantId)
                ->where('page_url', $url)
                ->first();

            Log::info('Query result:', $entry ? $entry->toArray() : ['result' => 'null']);

            if (!$entry) {
                Log::warning('No SEO entry found for tenant: ' . $tenantId . ' and URL: ' . $url);
                return response()->json(['success' => true, 'data' => null]);
            }

            return response()->json(['success' => true, 'data' => $entry]);
        } catch (\Exception $e) {
            Log::error('Error in getByUrl: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'error' => 'Erro ao buscar'], 500);
        }
    }

}
