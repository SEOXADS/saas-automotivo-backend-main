<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use App\Models\SystemSetting;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Helpers\TokenHelper;

/**
 * @OA\Tag(
 *     name="3. Super Admin",
 *     description="Endpoints para gerenciamento de plugins"
 * )
 */
class PluginManagerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/site-config/plugins",
     *     summary="Listar plugins disponíveis",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Lista de plugins")
     * )
     */
    public function getPlugins(Request $request)
    {
        $user = TokenHelper::getAuthenticatedUser($request);
        if (!in_array($user->role, ['super_admin', 'admin'])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $plugins = Cache::remember('available_plugins', 3600, function () {
            return [
                [
                    'id' => 'analytics',
                    'name' => 'Google Analytics',
                    'description' => 'Integração com Google Analytics para métricas do site',
                    'version' => '1.0.0',
                    'author' => 'Portal Veículos',
                    'enabled' => $this->isPluginEnabled('analytics'),
                    'settings' => $this->getPluginSettingsInternal('analytics'),
                ],
                [
                    'id' => 'chat',
                    'name' => 'Chat Online',
                    'description' => 'Sistema de chat online para atendimento ao cliente',
                    'version' => '1.0.0',
                    'author' => 'Portal Veículos',
                    'enabled' => $this->isPluginEnabled('chat'),
                    'settings' => $this->getPluginSettingsInternal('chat'),
                ],
                [
                    'id' => 'payment',
                    'name' => 'Gateway de Pagamento',
                    'description' => 'Integração com gateways de pagamento (Stripe, PagSeguro)',
                    'version' => '1.0.0',
                    'author' => 'Portal Veículos',
                    'enabled' => $this->isPluginEnabled('payment'),
                    'settings' => $this->getPluginSettingsInternal('payment'),
                ],
                [
                    'id' => 'notification',
                    'name' => 'Sistema de Notificações',
                    'description' => 'Sistema de notificações push e email',
                    'version' => '1.0.0',
                    'author' => 'Portal Veículos',
                    'enabled' => $this->isPluginEnabled('notification'),
                    'settings' => $this->getPluginSettingsInternal('notification'),
                ],
                [
                    'id' => 'backup',
                    'name' => 'Backup Automático',
                    'description' => 'Sistema de backup automático para o banco de dados',
                    'version' => '1.0.0',
                    'author' => 'Portal Veículos',
                    'enabled' => $this->isPluginEnabled('backup'),
                    'settings' => $this->getPluginSettingsInternal('backup'),
                ],
            ];
        });

        return response()->json(['plugins' => $plugins]);
    }

    /**
     * @OA\Post(
     *     path="/api/site-config/plugins/{pluginId}/toggle",
     *     summary="Ativar/desativar plugin",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="pluginId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="enabled", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Plugin atualizado")
     * )
     */
    public function togglePlugin(Request $request, $pluginId)
    {
        $user = TokenHelper::getAuthenticatedUser($request);
        if (!in_array($user->role, ['super_admin', 'admin'])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        $enabled = $request->boolean('enabled');

        SystemSetting::updateOrCreate(
            ['key' => "plugin_{$pluginId}_enabled", 'group' => 'plugins'],
            ['value' => $enabled, 'updated_by' => $user->id]
        );

        Cache::forget('available_plugins');

        $status = $enabled ? 'ativado' : 'desativado';
        return response()->json(['message' => "Plugin {$pluginId} {$status} com sucesso"]);
    }

    /**
     * @OA\Get(
     *     path="/api/site-config/plugins/{pluginId}/settings",
     *     summary="Obter configurações de um plugin",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="pluginId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Configurações do plugin")
     * )
     */
    public function getPluginSettings(Request $request, $pluginId)
    {
        $user = TokenHelper::getAuthenticatedUser($request);
        if (!in_array($user->role, ['super_admin', 'admin'])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $settings = Cache::remember("plugin_{$pluginId}_settings", 3600, function () use ($pluginId) {
            return SystemSetting::where('group', "plugin_{$pluginId}")->get()->keyBy('key');
        });

        $pluginSettings = $this->getDefaultPluginSettings($pluginId);

        foreach ($pluginSettings as $key => $defaultValue) {
            $pluginSettings[$key] = $settings->get($key)?->value ?? $defaultValue;
        }

        return response()->json(['settings' => $pluginSettings]);
    }

    /**
     * @OA\Post(
     *     path="/api/site-config/plugins/{pluginId}/settings",
     *     summary="Atualizar configurações de um plugin",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="pluginId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(response=200, description="Configurações atualizadas")
     * )
     */
    public function updatePluginSettings(Request $request, $pluginId)
    {
        $user = TokenHelper::getAuthenticatedUser($request);
        if (!in_array($user->role, ['super_admin', 'admin'])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $defaultSettings = $this->getDefaultPluginSettings($pluginId);
        $validationRules = $this->getPluginValidationRules($pluginId);

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        foreach ($request->all() as $key => $value) {
            if (array_key_exists($key, $defaultSettings)) {
                SystemSetting::updateOrCreate(
                    ['key' => $key, 'group' => "plugin_{$pluginId}"],
                    ['value' => $value, 'updated_by' => $user->id]
                );
            }
        }

        Cache::forget("plugin_{$pluginId}_settings");

        return response()->json(['message' => "Configurações do plugin {$pluginId} atualizadas com sucesso"]);
    }

    /**
     * Verificar se um plugin está ativado
     */
    private function isPluginEnabled($pluginId)
    {
        $setting = SystemSetting::where('key', "plugin_{$pluginId}_enabled")
            ->where('group', 'plugins')
            ->first();

        return (bool) ($setting?->value ?? false);
    }

    /**
     * Obter configurações de um plugin (método privado para uso interno)
     */
    private function getPluginSettingsInternal($pluginId)
    {
        $settings = SystemSetting::where('group', "plugin_{$pluginId}")->get()->keyBy('key');
        $defaultSettings = $this->getDefaultPluginSettings($pluginId);

        foreach ($defaultSettings as $key => $defaultValue) {
            $defaultSettings[$key] = $settings->get($key)?->value ?? $defaultValue;
        }

        return $defaultSettings;
    }

    /**
     * Obter configurações padrão de um plugin
     */
    private function getDefaultPluginSettings($pluginId)
    {
        $defaults = [
            'analytics' => [
                'tracking_id' => '',
                'anonymize_ip' => true,
                'track_pageviews' => true,
                'track_events' => true,
            ],
            'chat' => [
                'enabled' => false,
                'welcome_message' => 'Olá! Como posso ajudar?',
                'operating_hours' => '08:00-18:00',
                'timezone' => 'America/Sao_Paulo',
            ],
            'payment' => [
                'stripe_enabled' => false,
                'stripe_public_key' => '',
                'stripe_secret_key' => '',
                'pagseguro_enabled' => false,
                'pagseguro_email' => '',
                'pagseguro_token' => '',
            ],
            'notification' => [
                'email_enabled' => true,
                'push_enabled' => false,
                'sms_enabled' => false,
                'default_sender' => 'noreply@portalveiculos.com',
            ],
            'backup' => [
                'auto_backup' => true,
                'backup_frequency' => 'daily',
                'retention_days' => 30,
                'backup_path' => '/backups',
            ],
        ];

        return $defaults[$pluginId] ?? [];
    }

    /**
     * Obter regras de validação para um plugin
     */
    private function getPluginValidationRules($pluginId)
    {
        $rules = [
            'analytics' => [
                'tracking_id' => 'sometimes|string|max:100',
                'anonymize_ip' => 'sometimes|boolean',
                'track_pageviews' => 'sometimes|boolean',
                'track_events' => 'sometimes|boolean',
            ],
            'chat' => [
                'enabled' => 'sometimes|boolean',
                'welcome_message' => 'sometimes|string|max:500',
                'operating_hours' => 'sometimes|string|max:50',
                'timezone' => 'sometimes|string|max:50',
            ],
            'payment' => [
                'stripe_enabled' => 'sometimes|boolean',
                'stripe_public_key' => 'sometimes|string|max:255',
                'stripe_secret_key' => 'sometimes|string|max:255',
                'pagseguro_enabled' => 'sometimes|boolean',
                'pagseguro_email' => 'sometimes|email|max:255',
                'pagseguro_token' => 'sometimes|string|max:255',
            ],
            'notification' => [
                'email_enabled' => 'sometimes|boolean',
                'push_enabled' => 'sometimes|boolean',
                'sms_enabled' => 'sometimes|boolean',
                'default_sender' => 'sometimes|email|max:255',
            ],
            'backup' => [
                'auto_backup' => 'sometimes|boolean',
                'backup_frequency' => 'sometimes|string|in:daily,weekly,monthly',
                'retention_days' => 'sometimes|integer|min:1|max:365',
                'backup_path' => 'sometimes|string|max:255',
            ],
        ];

        return $rules[$pluginId] ?? [];
    }
}
