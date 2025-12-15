<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="3. Super Admin",
 *     description="Endpoints para super administradores do sistema"
 * )
 */
class AuthConfigurationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/site-config/auth",
     *     summary="Obter configurações de autenticação",
     *     tags={"Auth Configuration"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Configurações de autenticação")
     * )
     */
    public function getAuthSettings()
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'super_admin') {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $settings = Cache::remember('auth_settings', 3600, function () {
            return SystemSetting::where('group', 'auth')->get()->keyBy('key');
        });

        return response()->json([
            'registration_enabled' => (bool) ($settings->get('registration_enabled')?->value ?? true),
            'email_verification_required' => (bool) ($settings->get('email_verification_required')?->value ?? false),
            'password_min_length' => (int) ($settings->get('password_min_length')?->value ?? 8),
            'password_require_special_chars' => (bool) ($settings->get('password_require_special_chars')?->value ?? true),
            'password_require_numbers' => (bool) ($settings->get('password_require_numbers')?->value ?? true),
            'password_require_uppercase' => (bool) ($settings->get('password_require_uppercase')?->value ?? true),
            'session_timeout' => (int) ($settings->get('session_timeout')?->value ?? 120),
            'max_login_attempts' => (int) ($settings->get('max_login_attempts')?->value ?? 5),
            'lockout_duration' => (int) ($settings->get('lockout_duration')?->value ?? 15),
            'two_factor_enabled' => (bool) ($settings->get('two_factor_enabled')?->value ?? false),
            'social_login_enabled' => (bool) ($settings->get('social_login_enabled')?->value ?? false),
            'google_oauth_enabled' => (bool) ($settings->get('google_oauth_enabled')?->value ?? false),
            'facebook_oauth_enabled' => (bool) ($settings->get('facebook_oauth_enabled')?->value ?? false),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/site-config/auth",
     *     summary="Atualizar configurações de autenticação",
     *     tags={"Auth Configuration"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="registration_enabled", type="boolean"),
     *             @OA\Property(property="email_verification_required", type="boolean"),
     *             @OA\Property(property="password_min_length", type="integer"),
     *             @OA\Property(property="password_require_special_chars", type="boolean"),
     *             @OA\Property(property="password_require_numbers", type="boolean"),
     *             @OA\Property(property="password_require_uppercase", type="boolean"),
     *             @OA\Property(property="session_timeout", type="integer"),
     *             @OA\Property(property="max_login_attempts", type="integer"),
     *             @OA\Property(property="lockout_duration", type="integer"),
     *             @OA\Property(property="two_factor_enabled", type="boolean"),
     *             @OA\Property(property="social_login_enabled", type="boolean"),
     *             @OA\Property(property="google_oauth_enabled", type="boolean"),
     *             @OA\Property(property="facebook_oauth_enabled", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Configurações atualizadas")
     * )
     */
    public function updateAuthSettings(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'super_admin') {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'registration_enabled' => 'sometimes|boolean',
            'email_verification_required' => 'sometimes|boolean',
            'password_min_length' => 'sometimes|integer|min:6|max:50',
            'password_require_special_chars' => 'sometimes|boolean',
            'password_require_numbers' => 'sometimes|boolean',
            'password_require_uppercase' => 'sometimes|boolean',
            'session_timeout' => 'sometimes|integer|min:15|max:1440',
            'max_login_attempts' => 'sometimes|integer|min:1|max:20',
            'lockout_duration' => 'sometimes|integer|min:1|max:1440',
            'two_factor_enabled' => 'sometimes|boolean',
            'social_login_enabled' => 'sometimes|boolean',
            'google_oauth_enabled' => 'sometimes|boolean',
            'facebook_oauth_enabled' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        foreach ($request->all() as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key, 'group' => 'auth'],
                ['value' => $value, 'updated_by' => $user->id]
            );
        }

        Cache::forget('auth_settings');

        return response()->json(['message' => 'Configurações de autenticação atualizadas com sucesso']);
    }

    /**
     * @OA\Get(
     *     path="/api/site-config/auth/oauth",
     *     summary="Obter configurações de OAuth",
     *     tags={"Auth Configuration"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Configurações de OAuth")
     * )
     */
    public function getOAuthSettings()
    {
        $user = Auth::user();
        if (!in_array($user->role, ['super_admin', 'admin'])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $settings = Cache::remember('oauth_settings', 3600, function () {
            return SystemSetting::where('group', 'oauth')->get()->keyBy('key');
        });

        return response()->json([
            'google_client_id' => $settings->get('google_client_id')?->value ?? '',
            'google_client_secret' => $settings->get('google_client_secret')?->value ?? '',
            'google_redirect_uri' => $settings->get('google_redirect_uri')?->value ?? '',
            'facebook_app_id' => $settings->get('facebook_app_id')?->value ?? '',
            'facebook_app_secret' => $settings->get('facebook_app_secret')?->value ?? '',
            'facebook_redirect_uri' => $settings->get('facebook_redirect_uri')?->value ?? '',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/site-config/auth/oauth",
     *     summary="Atualizar configurações de OAuth",
     *     tags={"Auth Configuration"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="google_client_id", type="string"),
     *             @OA\Property(property="google_client_secret", type="string"),
     *             @OA\Property(property="google_redirect_uri", type="string"),
     *             @OA\Property(property="facebook_app_id", type="string"),
     *             @OA\Property(property="facebook_app_secret", type="string"),
     *             @OA\Property(property="facebook_redirect_uri", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Configurações atualizadas")
     * )
     */
    public function updateOAuthSettings(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['super_admin', 'admin'])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'google_client_id' => 'sometimes|string|max:255',
            'google_client_secret' => 'sometimes|string|max:255',
            'google_redirect_uri' => 'sometimes|url|max:500',
            'facebook_app_id' => 'sometimes|string|max:255',
            'facebook_app_secret' => 'sometimes|string|max:255',
            'facebook_redirect_uri' => 'sometimes|url|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        foreach ($request->all() as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key, 'group' => 'oauth'],
                ['value' => $value, 'updated_by' => $user->id]
            );
        }

        Cache::forget('oauth_settings');

        return response()->json(['message' => 'Configurações de OAuth atualizadas com sucesso']);
    }
}
