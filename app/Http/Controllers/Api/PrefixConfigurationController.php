<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use App\Models\SystemSetting;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\JsonResponse;
use App\Helpers\TokenHelper;

/**
 * @OA\Tag(
 *     name="Configurações de Prefixo",
 *     description="Endpoints para gerenciamento de configurações de prefixo - Sistema de tokens unificado (JWT + Sanctum)"
 * )
 */
class PrefixConfigurationController extends Controller
{
    /**
     * Obter configurações de prefixo
     */
    public function index(Request $request): JsonResponse
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $settings = Cache::remember('prefix_settings', 3600, function () {
            return SystemSetting::where('group', 'prefixes')->get()->keyBy('key');
        });

        return response()->json([
            'url_prefix' => $settings->get('url_prefix')?->value ?? '',
            'api_prefix' => $settings->get('api_prefix')?->value ?? 'api',
            'admin_prefix' => $settings->get('admin_prefix')?->value ?? 'admin',
            'tenant_prefix' => $settings->get('tenant_prefix')?->value ?? 'tenant',
            'file_prefix' => $settings->get('file_prefix')?->value ?? 'file',
            'image_prefix' => $settings->get('image_prefix')?->value ?? 'img',
            'document_prefix' => $settings->get('document_prefix')?->value ?? 'doc',
            'backup_prefix' => $settings->get('backup_prefix')?->value ?? 'backup',
            'log_prefix' => $settings->get('log_prefix')?->value ?? 'log',
            'temp_prefix' => $settings->get('temp_prefix')?->value ?? 'temp',
        ]);
    }

    /**
     * Atualizar configurações de prefixo
     */
    public function update(Request $request): JsonResponse
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        if (!in_array($user->role, ['super_admin', 'admin'])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'vehicle_prefix' => 'sometimes|string|max:10',
            'lead_prefix' => 'sometimes|string|max:10',
            'invoice_prefix' => 'sometimes|string|max:10',
            'quote_prefix' => 'sometimes|string|max:10'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($request->all() as $key => $value) {
            if (in_array($key, ['vehicle_prefix', 'lead_prefix', 'invoice_prefix', 'quote_prefix'])) {
                SystemSetting::updateOrCreate(
                    ['group' => 'prefixes', 'key' => $key],
                    ['value' => $value, 'updated_by' => $user->id]
                );
            }
        }

        return response()->json(['message' => 'Configurações atualizadas com sucesso']);
    }

    /**
     * Resetar configurações de prefixo
     */
    public function reset(Request $request): JsonResponse
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        if (!in_array($user->role, ['super_admin', 'admin'])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $defaultPrefixes = [
            'vehicle_prefix' => 'V',
            'lead_prefix' => 'L',
            'invoice_prefix' => 'INV',
            'quote_prefix' => 'QT'
        ];

        foreach ($defaultPrefixes as $key => $value) {
            SystemSetting::updateOrCreate(
                ['group' => 'prefixes', 'key' => $key],
                ['value' => $value, 'updated_by' => $user->id]
            );
        }

        return response()->json(['message' => 'Configurações resetadas para padrão']);
    }

    /**
     * @OA\Get(
     *     path="/api/super-admin/site-config/prefixes/validate",
     *     summary="Validar configurações de prefixos",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Validação dos prefixos")
     * )
     */
    public function validatePrefixes()
    {
        $user = JWTAuth::user();
        if (!in_array($user->role, ['super_admin', 'admin'])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $settings = Cache::remember('prefix_settings', 3600, function () {
            return SystemSetting::where('group', 'prefixes')->get()->keyBy('key');
        });

        $validation = [
            'url_prefix' => [
                'value' => $settings->get('url_prefix')?->value ?? '',
                'valid' => true,
                'message' => 'OK'
            ],
            'api_prefix' => [
                'value' => $settings->get('api_prefix')?->value ?? 'api',
                'valid' => true,
                'message' => 'OK'
            ],
            'admin_prefix' => [
                'value' => $settings->get('admin_prefix')?->value ?? 'admin',
                'valid' => true,
                'message' => 'OK'
            ],
            'tenant_prefix' => [
                'value' => $settings->get('tenant_prefix')?->value ?? 'tenant',
                'valid' => true,
                'message' => 'OK'
            ],
        ];

        // Validar se os prefixos não conflitam
        $prefixes = array_column($validation, 'value');
        $duplicates = array_diff_assoc($prefixes, array_unique($prefixes));

        if (!empty($duplicates)) {
            foreach ($duplicates as $key => $duplicate) {
                $validation[$key]['valid'] = false;
                $validation[$key]['message'] = 'Conflito com outro prefixo';
            }
        }

        // Validar se os prefixos são válidos para URLs
        foreach ($validation as $key => &$item) {
            if (!empty($item['value']) && !preg_match('/^[a-zA-Z0-9_-]+$/', $item['value'])) {
                $item['valid'] = false;
                $item['message'] = 'Caracteres inválidos (apenas letras, números, hífen e underscore)';
            }
        }

        return response()->json([
            'validation' => $validation,
            'has_conflicts' => !empty($duplicates),
            'all_valid' => collect($validation)->every('valid')
        ]);
    }
}
