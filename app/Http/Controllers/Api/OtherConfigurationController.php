<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Super Admin - Configurações do Sistema",
 *     description="Endpoints para Super Administradores configurarem o sistema SaaS - Sistema de tokens unificado (JWT + Sanctum)"
 * )
 */
class OtherConfigurationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/super-admin/other-config/sitemap",
     *     summary="Gerar mapa do site",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Mapa do site gerado")
     * )
     */
    public function generateSitemap()
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'super_admin') {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        try {
            // Gerar sitemap XML
            $sitemap = $this->buildSitemap();

            // Salvar no storage
            $filename = 'sitemap-' . date('Y-m-d-H-i-s') . '.xml';
            Storage::disk('public')->put('sitemaps/' . $filename, $sitemap);

            // Salvar configuração
            SystemSetting::updateOrCreate(
                ['key' => 'last_sitemap_generated', 'group' => 'sitemap'],
                ['value' => ['timestamp' => now()->toISOString()], 'updated_by' => $user->id]
            );

            return response()->json([
                'message' => 'Sitemap gerado com sucesso',
                'filename' => $filename,
                'url' => config('app.url') . '/storage/sitemaps/' . $filename,
                'generated_at' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao gerar sitemap: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/other-config/clear-cache",
     *     summary="Limpar cache do sistema",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="cache_types", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cache limpo")
     * )
     */
    public function clearCache(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'super_admin') {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'cache_types' => 'sometimes|array|in:config,cache,route,view,compiled,all'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        $cacheTypes = $request->get('cache_types', ['all']);
        $cleared = [];

        try {
            if (in_array('all', $cacheTypes) || in_array('config', $cacheTypes)) {
                Artisan::call('config:clear');
                $cleared[] = 'config';
            }

            if (in_array('all', $cacheTypes) || in_array('cache', $cacheTypes)) {
                Artisan::call('cache:clear');
                $cleared[] = 'cache';
            }

            if (in_array('all', $cacheTypes) || in_array('route', $cacheTypes)) {
                Artisan::call('route:clear');
                $cleared[] = 'route';
            }

            if (in_array('all', $cacheTypes) || in_array('view', $cacheTypes)) {
                Artisan::call('view:clear');
                $cleared[] = 'view';
            }

            if (in_array('all', $cacheTypes) || in_array('compiled', $cacheTypes)) {
                Artisan::call('clear-compiled');
                $cleared[] = 'compiled';
            }

            // Limpar cache do Redis se disponível
            if (app()->bound('redis')) {
                app('redis')->flushdb();
                $cleared[] = 'redis';
            }

            // Registrar ação
            SystemSetting::updateOrCreate(
                ['key' => 'last_cache_clear', 'group' => 'system'],
                ['value' => ['timestamp' => now()->toISOString()], 'updated_by' => $user->id]
            );

            return response()->json([
                'message' => 'Cache limpo com sucesso',
                'cleared_types' => $cleared,
                'cleared_at' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao limpar cache: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/super-admin/other-config/storage-info",
     *     summary="Informações de armazenamento",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Informações de armazenamento")
     * )
     */
    public function getStorageInfo()
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'super_admin') {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        try {
            $storageInfo = [
                'disk_usage' => $this->getDiskUsage(),
                'storage_paths' => [
                    'public' => storage_path('app/public'),
                    'logs' => storage_path('logs'),
                    'framework' => storage_path('framework'),
                    'backups' => storage_path('backups'),
                ],
                'file_counts' => [
                    'public_files' => count(Storage::disk('public')->allFiles()),
                    'log_files' => count(File::files(storage_path('logs'))),
                    'backup_files' => count(File::files(storage_path('backups'))),
                ],
                'last_cleanup' => SystemSetting::where('key', 'last_storage_cleanup')
                    ->where('group', 'system')
                    ->first()?->value ?? 'Nunca',
            ];

            return response()->json($storageInfo);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao obter informações de armazenamento: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/other-config/storage-cleanup",
     *     summary="Limpeza de armazenamento",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="cleanup_types", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="older_than_days", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Limpeza realizada")
     * )
     */
    public function storageCleanup(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'super_admin') {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'cleanup_types' => 'required|array|in:logs,backups,temp,all',
            'older_than_days' => 'sometimes|integer|min:1|max:365'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        $cleanupTypes = $request->get('cleanup_types');
        $olderThanDays = $request->get('older_than_days', 30);
        $cutoffDate = now()->subDays($olderThanDays);
        $cleaned = [];

        try {
            if (in_array('all', $cleanupTypes) || in_array('logs', $cleanupTypes)) {
                $cleaned['logs'] = $this->cleanupLogs($cutoffDate);
            }

            if (in_array('all', $cleanupTypes) || in_array('backups', $cleanupTypes)) {
                $cleaned['backups'] = $this->cleanupBackups($cutoffDate);
            }

            if (in_array('all', $cleanupTypes) || in_array('temp', $cleanupTypes)) {
                $cleaned['temp'] = $this->cleanupTempFiles($cutoffDate);
            }

            // Registrar limpeza
            SystemSetting::updateOrCreate(
                ['key' => 'last_storage_cleanup', 'group' => 'system'],
                ['value' => ['timestamp' => now()->toISOString()], 'updated_by' => $user->id]
            );

            return response()->json([
                'message' => 'Limpeza de armazenamento realizada com sucesso',
                'cleaned' => $cleaned,
                'cleaned_at' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro na limpeza: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/super-admin/other-config/cronjobs",
     *     summary="Listar cronjobs do sistema",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Lista de cronjobs")
     * )
     */
    public function getCronjobs()
    {
        $user = Auth::user();
        if (!$user || $user->role !== "super_admin") {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        try {
            $cronjobs = [
                [
                    'name' => 'Backup Automático',
                    'command' => 'backup:run',
                    'schedule' => '0 2 * * *', // 2 AM daily
                    'description' => 'Backup automático do banco de dados',
                    'enabled' => true,
                    'last_run' => $this->getLastCronRun('backup:run'),
                    'next_run' => $this->getNextCronRun('0 2 * * *'),
                ],
                [
                    'name' => 'Limpeza de Cache',
                    'command' => 'cache:clear',
                    'schedule' => '0 4 * * *', // 4 AM daily
                    'description' => 'Limpeza automática de cache',
                    'enabled' => true,
                    'last_run' => $this->getLastCronRun('cache:clear'),
                    'next_run' => $this->getNextCronRun('0 4 * * *'),
                ],
                [
                    'name' => 'Sincronização de Catálogo',
                    'command' => 'catalog:sync',
                    'schedule' => '0 6 * * *', // 6 AM daily
                    'description' => 'Sincronização com catálogo externo',
                    'enabled' => true,
                    'last_run' => $this->getLastCronRun('catalog:sync'),
                    'next_run' => $this->getNextCronRun('0 6 * * *'),
                ],
                [
                    'name' => 'Limpeza de Logs',
                    'command' => 'logs:clean',
                    'schedule' => '0 1 * * 0', // 1 AM Sunday
                    'description' => 'Limpeza semanal de logs antigos',
                    'enabled' => true,
                    'last_run' => $this->getLastCronRun('logs:clean'),
                    'next_run' => $this->getNextCronRun('0 1 * * 0'),
                ],
            ];

            return response()->json(['cronjobs' => $cronjobs]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao obter cronjobs: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/other-config/cronjobs/{command}/run",
     *     summary="Executar cronjob manualmente",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="command", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Cronjob executado")
     * )
     */
    public function runCronjob($command)
    {
        $user = Auth::user();
        if (!$user || $user->role !== "super_admin") {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        try {
            $allowedCommands = ['backup:run', 'cache:clear', 'catalog:sync', 'logs:clean'];

            if (!in_array($command, $allowedCommands)) {
                return response()->json(['error' => 'Comando não permitido'], 400);
            }

            $output = Artisan::call($command);
            $outputText = Artisan::output();

            // Registrar execução
            SystemSetting::updateOrCreate(
                ['key' => "last_cron_run_{$command}", 'group' => 'cronjobs'],
                ['value' => ['timestamp' => now()->toISOString()], 'updated_by' => $user->id]
            );

            return response()->json([
                'message' => "Cronjob {$command} executado com sucesso",
                'command' => $command,
                'output' => $outputText,
                'executed_at' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao executar cronjob: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/other-config/backup/system",
     *     summary="Criar backup do sistema",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="backup_type", type="string"),
     *             @OA\Property(property="include_files", type="boolean"),
     *             @OA\Property(property="compression", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Backup criado")
     * )
     */
    public function createSystemBackup(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== "super_admin") {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'backup_type' => 'required|string|in:full,partial,essential',
            'include_files' => 'sometimes|boolean',
            'compression' => 'sometimes|string|in:gzip,zip,none'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        try {
            $backupType = $request->get('backup_type');
            $includeFiles = $request->get('include_files', false);
            $compression = $request->get('compression', 'gzip');

            // Criar diretório de backup se não existir
            $backupPath = storage_path('backups/system');
            if (!File::exists($backupPath)) {
                File::makeDirectory($backupPath, 0755, true);
            }

            $timestamp = now()->format('Y-m-d-H-i-s');
            $filename = "system-backup-{$backupType}-{$timestamp}";

            // Backup do banco de dados
            $dbBackup = $this->createDatabaseBackupInternal($filename);

            // Backup de arquivos se solicitado
            $filesBackup = null;
            if ($includeFiles) {
                $filesBackup = $this->createFilesBackup($filename);
            }

            // Comprimir se necessário
            if ($compression !== 'none') {
                $filename = $this->compressBackup($filename, $compression);
            }

            // Registrar backup
            SystemSetting::updateOrCreate(
                ['key' => 'last_system_backup', 'group' => 'backups'],
                ['value' => ['timestamp' => now()->toISOString()], 'updated_by' => $user->id]
            );

            return response()->json([
                'message' => 'Backup do sistema criado com sucesso',
                'backup_type' => $backupType,
                'filename' => $filename,
                'database_backup' => $dbBackup,
                'files_backup' => $filesBackup,
                'compression' => $compression,
                'created_at' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao criar backup: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/other-config/backup/database",
     *     summary="Criar backup do banco de dados",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="tables", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="compression", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Backup do banco criado")
     * )
     */
    public function createDatabaseBackup(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== "super_admin") {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'tables' => 'sometimes|array|min:1',
            'tables.*' => 'string|max:100',
            'compression' => 'sometimes|string|in:gzip,zip,none'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        try {
            $tables = $request->get('tables', []);
            $compression = $request->get('compression', 'gzip');

            // Criar diretório de backup se não existir
            $backupPath = storage_path('backups/database');
            if (!File::exists($backupPath)) {
                File::makeDirectory($backupPath, 0755, true);
            }

            $timestamp = now()->format('Y-m-d-H-i-s');
            $filename = "db-backup-{$timestamp}.sql";
            $fullPath = $backupPath . '/' . $filename;

            // Comando mysqldump
            $command = "mysqldump -h " . config('database.connections.mysql.host') .
                      " -u " . config('database.connections.mysql.username') .
                      " -p" . config('database.connections.mysql.password') .
                      " " . config('database.connections.mysql.database');

            if (!empty($tables)) {
                $command .= " " . implode(' ', $tables);
            }

            $command .= " > {$fullPath}";

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('Erro ao executar mysqldump');
            }

            // Comprimir se necessário
            if ($compression !== 'none') {
                $filename = $this->compressBackup($filename, $compression);
            }

            // Registrar backup
            SystemSetting::updateOrCreate(
                ['key' => 'last_database_backup', 'group' => 'backups'],
                ['value' => ['timestamp' => now()->toISOString()], 'updated_by' => $user->id]
            );

            return response()->json([
                'message' => 'Backup do banco de dados criado com sucesso',
                'filename' => $filename,
                'tables' => $tables,
                'compression' => $compression,
                'size' => File::size($fullPath),
                'created_at' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao criar backup do banco: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/super-admin/other-config/system-update/check",
     *     summary="Verificar atualizações do sistema",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Status das atualizações")
     * )
     */
    public function checkSystemUpdates()
    {
        $user = Auth::user();
        if (!$user || $user->role !== "super_admin") {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        try {
            $currentVersion = config('app.version', '1.0.0');
            $lastCheck = SystemSetting::where('key', 'last_update_check')
                ->where('group', 'system')
                ->first()?->value;

            $updateInfo = [
                'current_version' => $currentVersion,
                'last_check' => $lastCheck,
                'update_available' => false,
                'latest_version' => null,
                'changelog' => [],
                'security_updates' => [],
                'recommended' => false,
                'auto_update_enabled' => false,
            ];

            // Verificar se há atualizações disponíveis
            // Aqui você pode implementar a lógica para verificar com um servidor de atualizações
            // Por enquanto, vamos simular que não há atualizações

            // Registrar verificação
            SystemSetting::updateOrCreate(
                ['key' => 'last_update_check', 'group' => 'system'],
                ['value' => ['timestamp' => now()->toISOString()], 'updated_by' => $user->id]
            );

            return response()->json($updateInfo);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao verificar atualizações: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/super-admin/other-config/system-update/install",
     *     summary="Instalar atualização do sistema",
     *     tags={"3. Super Admin"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string"),
     *             @OA\Property(property="backup_before", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Atualização instalada")
     * )
     */
    public function installSystemUpdate(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== "super_admin") {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'version' => 'required|string',
            'backup_before' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }

        try {
            $version = $request->get('version');
            $backupBefore = $request->get('backup_before', true);

            // Criar backup antes da atualização se solicitado
            if ($backupBefore) {
                $this->createSystemBackup(new Request([
                    'backup_type' => 'full',
                    'include_files' => true,
                    'compression' => 'gzip'
                ]));
            }

            // Aqui você implementaria a lógica de atualização
            // Por exemplo, baixar arquivos, executar migrações, etc.

            // Registrar atualização
            SystemSetting::updateOrCreate(
                ['key' => 'last_system_update', 'group' => 'system'],
                ['value' => ['timestamp' => now()->toISOString()], 'updated_by' => $user->id]
            );

            SystemSetting::updateOrCreate(
                ['key' => 'system_version', 'group' => 'system'],
                ['value' => ['version' => $version], 'updated_by' => $user->id]
            );

            return response()->json([
                'message' => 'Atualização do sistema instalada com sucesso',
                'version' => $version,
                'installed_at' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao instalar atualização: ' . $e->getMessage()], 500);
        }
    }

    // Métodos auxiliares privados

    private function buildSitemap()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // URLs principais
        $urls = [
            url('/'),
            url('/about'),
            url('/contact'),
            url('/vehicles'),
            url('/brands'),
        ];

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$url}</loc>\n";
            $xml .= "    <lastmod>" . now()->toISOString() . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }

    private function getDiskUsage()
    {
        $total = disk_total_space(storage_path());
        $free = disk_free_space(storage_path());
        $used = $total - $free;

        return [
            'total' => $this->formatBytes($total),
            'used' => $this->formatBytes($used),
            'free' => $this->formatBytes($free),
            'usage_percentage' => round(($used / $total) * 100, 2)
        ];
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function cleanupLogs($cutoffDate)
    {
        $logPath = storage_path('logs');
        $deleted = 0;

        foreach (File::files($logPath) as $file) {
            if (File::lastModified($file) < $cutoffDate->timestamp) {
                File::delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    private function cleanupBackups($cutoffDate)
    {
        $backupPath = storage_path('backups');
        $deleted = 0;

        if (File::exists($backupPath)) {
            foreach (File::files($backupPath) as $file) {
                if (File::lastModified($file) < $cutoffDate->timestamp) {
                    File::delete($file);
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    private function cleanupTempFiles($cutoffDate)
    {
        $tempPath = storage_path('framework/temp');
        $deleted = 0;

        if (File::exists($tempPath)) {
            foreach (File::files($tempPath) as $file) {
                if (File::lastModified($file) < $cutoffDate->timestamp) {
                    File::delete($file);
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    private function getLastCronRun($command)
    {
        $setting = SystemSetting::where('key', "last_cron_run_{$command}")
            ->where('group', 'cronjobs')
            ->first();

        return $setting?->value ?? 'Nunca';
    }

    private function getNextCronRun($schedule)
    {
        // Implementar lógica para calcular próxima execução baseada no cron
        // Por enquanto, retorna uma data estimada
        return now()->addDay()->toISOString();
    }

    private function createDatabaseBackupInternal($filename)
    {
        // Implementar backup do banco
        return "db-backup-{$filename}.sql";
    }

    private function createFilesBackup($filename)
    {
        // Implementar backup de arquivos
        return "files-backup-{$filename}.tar.gz";
    }

    private function compressBackup($filename, $compression)
    {
        // Implementar compressão
        return $filename . '.' . $compression;
    }
}
