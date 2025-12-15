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
 *     name="Configurações de Idioma",
 *     description="Endpoints para gerenciamento de configurações de idioma - Sistema de tokens unificado (JWT + Sanctum)"
 * )
 */
class LanguageConfigurationController extends Controller
{
    /**
     * Obter configurações de idioma
     */
    public function index(Request $request)
    {
        $user = TokenHelper::getAuthenticatedUser($request);

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        if (!in_array($user->role, ['super_admin', 'admin'])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $currentLanguage = SystemSetting::where('key', 'default_language')->first();
        $availableLanguages = SystemSetting::where('key', 'available_languages')->first();

        return response()->json([
            'current_language' => $currentLanguage ? $currentLanguage->value : 'pt',
            'available_languages' => $availableLanguages ? json_decode($availableLanguages->value) : ['pt', 'en', 'es']
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/site-config/languages",
     *     summary="Atualizar configurações de linguagem",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="default_language", type="string"),
     *             @OA\Property(property="available_languages", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="auto_detect", type="boolean"),
     *             @OA\Property(property="fallback_language", type="string"),
     *             @OA\Property(property="date_format", type="string"),
     *             @OA\Property(property="time_format", type="string"),
     *             @OA\Property(property="number_format", type="string"),
     *             @OA\Property(property="currency_format", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Configurações atualizadas")
     * )
     */
    public function updateLanguageSettings(Request $request)
    {
        $user = JWTAuth::user();
        if (!in_array($user->role, ['super_admin', 'admin'])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'default_language' => 'sometimes|string|max:10',
            'available_languages' => 'sometimes|array|min:1',
            'available_languages.*' => 'string|max:10',
            'auto_detect' => 'sometimes|boolean',
            'fallback_language' => 'sometimes|string|max:10',
            'date_format' => 'sometimes|string|max:20',
            'time_format' => 'sometimes|string|max:20',
            'number_format' => 'sometimes|string|max:10',
            'currency_format' => 'sometimes|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        foreach ($request->all() as $key => $value) {
            if ($key === 'available_languages') {
                $value = json_encode($value);
            }

            SystemSetting::updateOrCreate(
                ['key' => $key, 'group' => 'language'],
                ['value' => $value, 'updated_by' => $user->id]
            );
        }

        Cache::forget('language_settings');

        return response()->json(['message' => 'Configurações de linguagem atualizadas com sucesso']);
    }

    /**
     * @OA\Get(
     *     path="/api/super-admin/site-config/languages/available",
     *     summary="Listar idiomas disponíveis",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Lista de idiomas disponíveis")
     * )
     */
    public function getAvailableLanguages()
    {
        $user = JWTAuth::user();
        if (!in_array($user->role, ['super_admin', 'admin'])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $languages = [
            [
                'code' => 'pt_BR',
                'name' => 'Português (Brasil)',
                'native_name' => 'Português (Brasil)',
                'flag' => '🇧🇷',
                'direction' => 'ltr',
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'number_format' => 'pt_BR',
                'currency_format' => 'pt_BR',
            ],
            [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'flag' => '🇺🇸',
                'direction' => 'ltr',
                'date_format' => 'm/d/Y',
                'time_format' => 'h:i A',
                'number_format' => 'en',
                'currency_format' => 'en',
            ],
            [
                'code' => 'es',
                'name' => 'Español',
                'native_name' => 'Español',
                'flag' => '🇪🇸',
                'direction' => 'ltr',
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'number_format' => 'es',
                'currency_format' => 'es',
            ],
            [
                'code' => 'fr',
                'name' => 'Français',
                'native_name' => 'Français',
                'flag' => '🇫🇷',
                'direction' => 'ltr',
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'number_format' => 'fr',
                'currency_format' => 'fr',
            ],
            [
                'code' => 'de',
                'name' => 'Deutsch',
                'native_name' => 'Deutsch',
                'flag' => '🇩🇪',
                'direction' => 'ltr',
                'date_format' => 'd.m.Y',
                'time_format' => 'H:i',
                'number_format' => 'de',
                'currency_format' => 'de',
            ],
            [
                'code' => 'it',
                'name' => 'Italiano',
                'native_name' => 'Italiano',
                'flag' => '🇮🇹',
                'direction' => 'ltr',
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'number_format' => 'it',
                'currency_format' => 'it',
            ],
            [
                'code' => 'ar',
                'name' => 'العربية',
                'native_name' => 'العربية',
                'flag' => '🇸🇦',
                'direction' => 'rtl',
                'date_format' => 'Y/m/d',
                'time_format' => 'H:i',
                'number_format' => 'ar',
                'currency_format' => 'ar',
            ],
            [
                'code' => 'zh',
                'name' => '中文',
                'native_name' => '中文',
                'flag' => '🇨🇳',
                'direction' => 'ltr',
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i',
                'number_format' => 'zh',
                'currency_format' => 'zh',
            ],
            [
                'code' => 'ja',
                'name' => '日本語',
                'native_name' => '日本語',
                'flag' => '🇯🇵',
                'direction' => 'ltr',
                'date_format' => 'Y/m/d',
                'time_format' => 'H:i',
                'number_format' => 'ja',
                'currency_format' => 'ja',
            ],
            [
                'code' => 'ko',
                'name' => '한국어',
                'native_name' => '한국어',
                'flag' => '🇰🇷',
                'direction' => 'ltr',
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i',
                'number_format' => 'ko',
                'currency_format' => 'ko',
            ],
        ];

        return response()->json(['languages' => $languages]);
    }

    /**
     * @OA\Get(
     *     path="/api/super-admin/site-config/languages/translations",
     *     summary="Obter arquivos de tradução",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="language", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Arquivos de tradução")
     * )
     */
    public function getTranslationFiles(Request $request)
    {
        $user = JWTAuth::user();
        if (!in_array($user->role, ['super_admin', 'admin'])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $language = $request->get('language', 'pt_BR');
        $langPath = resource_path("lang/{$language}");

        if (!File::exists($langPath)) {
            return response()->json(['error' => 'Idioma não encontrado'], 404);
        }

        $files = [];
        foreach (File::files($langPath) as $file) {
            $filename = $file->getFilename();
            $content = include $file->getPathname();

            $files[] = [
                'filename' => $filename,
                'path' => $file->getRelativePathname(),
                'size' => $file->getSize(),
                'modified' => $file->getMTime(),
                'keys_count' => is_array($content) ? count($content, COUNT_RECURSIVE) : 0,
            ];
        }

        return response()->json([
            'language' => $language,
            'files' => $files,
            'total_files' => count($files),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/site-config/languages/translations/export",
     *     summary="Exportar traduções",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="languages", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="format", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Traduções exportadas")
     * )
     */
    public function exportTranslations(Request $request)
    {
        $user = JWTAuth::user();
        if (!in_array($user->role, ['super_admin', 'admin'])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'languages' => 'required|array',
            'format' => 'required|string|in:json,csv,xml'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        try {
            $languages = $request->get('languages');
            $format = $request->get('format');

            $exportData = [];

            foreach ($languages as $lang) {
                $langPath = resource_path("lang/{$lang}");
                if (File::exists($langPath)) {
                    $exportData[$lang] = $this->loadLanguageFiles($langPath);
                }
            }

            $filename = "translations-" . implode('-', $languages) . "-" . date('Y-m-d-H-i-s');

            switch ($format) {
                case 'json':
                    $content = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    $filename .= '.json';
                    break;
                case 'csv':
                    $content = $this->arrayToCsv($exportData);
                    $filename .= '.csv';
                    break;
                case 'xml':
                    $content = $this->arrayToXml($exportData);
                    $filename .= '.xml';
                    break;
            }

            // Salvar arquivo
            $exportPath = storage_path("exports/translations");
            if (!File::exists($exportPath)) {
                File::makeDirectory($exportPath, 0755, true);
            }

            File::put($exportPath . '/' . $filename, $content);

            return response()->json([
                'message' => 'Traduções exportadas com sucesso',
                'filename' => $filename,
                'download_url' => url("storage/exports/translations/{$filename}"),
                'languages' => $languages,
                'format' => $format,
                'exported_at' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao exportar traduções: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/site-config/languages/translations/import",
     *     summary="Importar traduções",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="language", type="string"),
     *             @OA\Property(property="file", type="string"),
     *             @OA\Property(property="overwrite", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Traduções importadas")
     * )
     */
    public function importTranslations(Request $request)
    {
        $user = JWTAuth::user();
        if (!in_array($user->role, ['super_admin', 'admin'])) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'language' => 'required|string|max:10',
            'file' => 'required|string',
            'overwrite' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        try {
            $language = $request->get('language');
            $filePath = $request->get('file');
            $overwrite = $request->get('overwrite', false);

            // Verificar se o arquivo existe
            if (!File::exists($filePath)) {
                return response()->json(['error' => 'Arquivo não encontrado'], 404);
            }

            // Carregar dados do arquivo
            $extension = File::extension($filePath);
            $importData = [];

            switch ($extension) {
                case 'json':
                    $importData = json_decode(File::get($filePath), true);
                    break;
                case 'csv':
                    $importData = $this->csvToArray(File::get($filePath));
                    break;
                case 'xml':
                    $importData = $this->xmlToArray(File::get($filePath));
                    break;
                default:
                    return response()->json(['error' => 'Formato de arquivo não suportado'], 400);
            }

            if (!$importData) {
                return response()->json(['error' => 'Erro ao ler arquivo de tradução'], 400);
            }

            // Importar traduções
            $imported = $this->importLanguageData($language, $importData, $overwrite);

            return response()->json([
                'message' => 'Traduções importadas com sucesso',
                'language' => $language,
                'imported_files' => $imported,
                'imported_at' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao importar traduções: ' . $e->getMessage()], 500);
        }
    }

    // Métodos auxiliares privados

    private function loadLanguageFiles($langPath)
    {
        $data = [];
        foreach (File::files($langPath) as $file) {
            $filename = $file->getBasename('.php');
            $data[$filename] = include $file->getPathname();
        }
        return $data;
    }

    private function arrayToCsv($data)
    {
        $csv = "Language,File,Key,Value\n";

        foreach ($data as $lang => $files) {
            foreach ($files as $file => $translations) {
                $this->flattenArray($translations, $csv, $lang, $file);
            }
        }

        return $csv;
    }

    private function flattenArray($array, &$csv, $lang, $file, $prefix = '')
    {
        foreach ($array as $key => $value) {
            $currentKey = $prefix ? $prefix . '.' . $key : $key;

            if (is_array($value)) {
                $this->flattenArray($value, $csv, $lang, $file, $currentKey);
            } else {
                $csv .= "\"{$lang}\",\"{$file}\",\"{$currentKey}\",\"{$value}\"\n";
            }
        }
    }

    private function arrayToXml($data)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<translations>' . "\n";

        foreach ($data as $lang => $files) {
            $xml .= "  <language code=\"{$lang}\">\n";
            foreach ($files as $file => $translations) {
                $xml .= "    <file name=\"{$file}\">\n";
                $this->arrayToXmlRecursive($translations, $xml, 4);
                $xml .= "    </file>\n";
            }
            $xml .= "  </language>\n";
        }

        $xml .= '</translations>';
        return $xml;
    }

    private function arrayToXmlRecursive($array, &$xml, $indent = 0)
    {
        $spaces = str_repeat(' ', $indent);

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $xml .= "{$spaces}<{$key}>\n";
                $this->arrayToXmlRecursive($value, $xml, $indent + 2);
                $xml .= "{$spaces}</{$key}>\n";
            } else {
                $xml .= "{$spaces}<{$key}>" . htmlspecialchars($value) . "</{$key}>\n";
            }
        }
    }

    private function csvToArray($csvContent)
    {
        $lines = explode("\n", $csvContent);
        $data = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            $parts = str_getcsv($line);
            if (count($parts) >= 4) {
                $lang = $parts[0];
                $file = $parts[1];
                $key = $parts[2];
                $value = $parts[3];

                if (!isset($data[$lang])) $data[$lang] = [];
                if (!isset($data[$lang][$file])) $data[$lang][$file] = [];

                $this->setNestedValue($data[$lang][$file], $key, $value);
            }
        }

        return $data;
    }

    private function xmlToArray($xmlContent)
    {
        $xml = simplexml_load_string($xmlContent);
        $data = [];

        foreach ($xml->language as $lang) {
            $langCode = (string) $lang['code'];
            $data[$langCode] = [];

            foreach ($lang->file as $file) {
                $fileName = (string) $file['name'];
                $data[$langCode][$fileName] = $this->xmlToArrayRecursive($file);
            }
        }

        return $data;
    }

    private function xmlToArrayRecursive($xml)
    {
        $data = [];

        foreach ($xml->children() as $child) {
            $name = $child->getName();

            if ($child->count() > 0) {
                $data[$name] = $this->xmlToArrayRecursive($child);
            } else {
                $data[$name] = (string) $child;
            }
        }

        return $data;
    }

    private function setNestedValue(&$array, $key, $value)
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $k) {
            if (!isset($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
    }

    private function importLanguageData($language, $data, $overwrite)
    {
        $imported = [];
        $langPath = resource_path("lang/{$language}");

        if (!File::exists($langPath)) {
            File::makeDirectory($langPath, 0755, true);
        }

        foreach ($data as $file => $translations) {
            $filePath = $langPath . '/' . $file . '.php';

            if (File::exists($filePath) && !$overwrite) {
                $existing = include $filePath;
                $translations = array_merge($existing, $translations);
            }

            $content = "<?php\n\nreturn " . var_export($translations, true) . ";\n";
            File::put($filePath, $content);

            $imported[] = $file;
        }

        return $imported;
    }
}
