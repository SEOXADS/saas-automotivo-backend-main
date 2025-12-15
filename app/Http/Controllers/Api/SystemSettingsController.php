<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use App\Helpers\TokenHelper;

/**
 * @OA\Tag(
 *     name="3. Super Admin",
 *     description="Endpoints para super administradores do sistema"
 * )
 */
class SystemSettingsController extends Controller
{
    /**
     * Listar configurações
     */
    public function index(Request $request): JsonResponse
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $data = SystemSetting::where('group', 'general')->pluck('value', 'key');
        return response()->json($data);
    }

    public function saveGeneral(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'admin_email' => 'required|email',
            'timezone' => 'required|string',
            'date_format' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        $this->upsert('general', 'app_name', $request->app_name);
        $this->upsert('general', 'app_url', $request->app_url);
        $this->upsert('general', 'admin_email', $request->admin_email);
        $this->upsert('general', 'timezone', $request->timezone);
        $this->upsert('general', 'date_format', $request->date_format);

        return response()->json(['message' => 'Configurações salvas com sucesso']);
    }

    public function getSecurity()
    {
        $data = SystemSetting::where('group', 'security')->pluck('value', 'key');
        return response()->json($data);
    }

    public function saveSecurity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_timeout' => 'required|integer|min:5|max:1440',
            'max_login_attempts' => 'required|integer|min:1|max:20',
            'min_password_length' => 'required|integer|min:6|max:64',
            'require_2fa' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        $this->upsert('security', 'session_timeout', (int) $request->session_timeout);
        $this->upsert('security', 'max_login_attempts', (int) $request->max_login_attempts);
        $this->upsert('security', 'min_password_length', (int) $request->min_password_length);
        $this->upsert('security', 'require_2fa', (bool) $request->get('require_2fa', false));

        return response()->json(['message' => 'Segurança salva com sucesso']);
    }

    public function getDatabase()
    {
        $data = SystemSetting::where('group', 'database')->pluck('value', 'key');
        return response()->json($data);
    }

    public function saveDatabase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'auto_backup_enabled' => 'boolean',
            'backup_frequency' => 'required|string|in:daily,weekly,monthly',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        $this->upsert('database', 'auto_backup_enabled', (bool) $request->get('auto_backup_enabled', true));
        $this->upsert('database', 'backup_frequency', $request->backup_frequency);

        return response()->json(['message' => 'Banco de dados salvo com sucesso']);
    }

    public function getNotifications()
    {
        $data = SystemSetting::where('group', 'notifications')->pluck('value', 'key');
        return response()->json($data);
    }

    public function saveNotifications(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email_notifications' => 'boolean',
            'system_alerts' => 'boolean',
            'maintenance_mode' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        $this->upsert('notifications', 'email_notifications', (bool) $request->get('email_notifications', true));
        $this->upsert('notifications', 'system_alerts', (bool) $request->get('system_alerts', true));
        $this->upsert('notifications', 'maintenance_mode', (bool) $request->get('maintenance_mode', false));

        return response()->json(['message' => 'Notificações salvas com sucesso']);
    }

    private function upsert(string $group, string $key, $value): void
    {
        $userId = optional(TokenHelper::getAuthenticatedUser(request()))->id;
        SystemSetting::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $value, 'updated_by' => $userId]
        );
    }
}
