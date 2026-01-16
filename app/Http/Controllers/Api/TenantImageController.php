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
        Log::info('🖼️ TenantImageController::upload - START', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
        ]);

        try {
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
            
            // Get tenant ID
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
            
            // Store in tenant-specific folder
            $directory = "tenants/{$tenantId}/images";
            $path = $file->storeAs($directory, $filename, 'public');

            if (!$path) {
                throw new \Exception('Failed to store file');
            }

            // ✅ FIX: Generate URL based on environment
            // Use APP_URL from .env to ensure correct domain
            $appUrl = config('app.url', 'http://localhost:8000');
            $url = rtrim($appUrl, '/') . '/storage/' . $path;

            Log::info('✅ Image uploaded successfully', [
                'path' => $path,
                'url' => $url,
                'app_url' => $appUrl
            ]);

            return response()->json([
                'success' => true,
                'url' => $url,
                'image_url' => $url,
                'path' => $path,
                // ✅ Also return relative path for flexibility
                'relative_path' => '/storage/' . $path
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
