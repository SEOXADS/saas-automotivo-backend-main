<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TenantImageController extends Controller
{
    public function upload(Request $request)
    {
        // ✅ Add logging at the very start to debug 500 error
        Log::info('🖼️ TenantImageController::upload - START', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
        ]);

        try {
            Log::info('Image upload request received', [
                'type' => $request->input('type'),
                'has_image' => $request->hasFile('image'),
                'all_files' => array_keys($request->allFiles()),
                'content_type' => $request->header('Content-Type'),
            ]);

            // ✅ Check if file exists before validation
            if (!$request->hasFile('image')) {
                Log::error('No image file in request');
                return response()->json([
                    'success' => false,
                    'error' => 'Nenhum arquivo de imagem foi enviado'
                ], 400);
            }

            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
                'type' => 'required|string|in:logo,favicon,banner,social,og_image'
            ]);

            $file = $request->file('image');
            $type = $request->input('type');
            
            // ✅ FIX: Use Auth facade instead of auth() helper
            $tenantId = 'default';
            
            if (Auth::check() && Auth::user()) {
                $user = Auth::user();
                $tenantId = $user->tenant_id ?? 'default';
            } elseif ($request->header('X-Tenant-ID')) {
                $tenantId = $request->header('X-Tenant-ID');
            } elseif ($request->header('X-Tenant')) {
                $tenantId = $request->header('X-Tenant');
            }

            Log::info('Tenant ID resolved', ['tenant_id' => $tenantId]);
            
            // Create unique filename
            $filename = $type . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            // ✅ Ensure storage directory exists
            $directory = "tenants/{$tenantId}/images";
            
            // Store in tenant-specific folder
            $path = $file->storeAs($directory, $filename, 'public');

            if (!$path) {
                throw new \Exception('Failed to store file');
            }

            $url = asset('storage/' . $path);

            Log::info('✅ Image uploaded successfully', [
                'path' => $path,
                'url' => $url
            ]);

            return response()->json([
                'success' => true,
                'url' => $url,
                'image_url' => $url,
                'path' => $path
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Image upload validation failed', [
                'errors' => $e->errors()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ Image upload failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao fazer upload: ' . $e->getMessage()
            ], 500);
        }
    }
}
