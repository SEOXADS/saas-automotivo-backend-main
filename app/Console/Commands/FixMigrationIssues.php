<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class FixMigrationIssues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:fix-issues
                            {--force : Forçar execução sem confirmação}
                            {--check-only : Apenas verificar problemas sem corrigir}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrigir problemas de migração em produção';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Verificando problemas de migração...');
        $this->newLine();

        $issues = $this->identifyMigrationIssues();

        if (empty($issues)) {
            $this->info('✅ Nenhum problema de migração identificado.');
            return 0;
        }

        $this->warn('⚠️  Problemas identificados:');
        foreach ($issues as $issue) {
            $this->line("  • {$issue}");
        }

        if ($this->option('check-only')) {
            return 0;
        }

        if (!$this->option('force') && !$this->confirm('Deseja corrigir esses problemas?')) {
            $this->info('Operação cancelada.');
            return 0;
        }

        $this->fixMigrationIssues($issues);

        $this->info('✅ Problemas de migração corrigidos!');
        $this->info('💡 Execute "php artisan migrate" para continuar com as migrações pendentes.');

        return 0;
    }

    /**
     * Identificar problemas de migração
     */
    private function identifyMigrationIssues(): array
    {
        $issues = [];

        // Verificar se a tabela tenants existe
        if (!Schema::hasTable('tenants')) {
            $issues[] = 'Tabela "tenants" não existe';
        }

        // Verificar colunas duplicadas ou conflitantes
        if (Schema::hasTable('tenants')) {
            $columns = Schema::getColumnListing('tenants');

            // Verificar se custom_domain já existe
            if (in_array('custom_domain', $columns)) {
                $issues[] = 'Coluna "custom_domain" já existe na tabela tenants';
            }

            // Verificar se is_default já existe
            if (in_array('is_default', $columns)) {
                $issues[] = 'Coluna "is_default" já existe na tabela tenants';
            }

            // Verificar outras colunas que podem causar conflito
            $conflictColumns = ['description', 'contact_email', 'contact_phone', 'address', 'theme_color', 'logo_url', 'social_media', 'business_hours'];
            foreach ($conflictColumns as $column) {
                if (in_array($column, $columns)) {
                    $issues[] = "Coluna '{$column}' já existe na tabela tenants";
                }
            }
        }

        // Verificar se as tabelas de configuração existem
        $configTables = ['tenant_profiles', 'tenant_themes', 'tenant_seo', 'tenant_portal_settings'];
        foreach ($configTables as $table) {
            if (!Schema::hasTable($table)) {
                $issues[] = "Tabela '{$table}' não existe";
            }
        }

        return $issues;
    }

    /**
     * Corrigir problemas de migração
     */
    private function fixMigrationIssues(array $issues): void
    {
        $this->info('🔧 Corrigindo problemas...');

        foreach ($issues as $issue) {
            $this->line("  Resolvendo: {$issue}");

            if (str_contains($issue, 'Coluna "custom_domain" já existe')) {
                $this->markMigrationAsRun('2025_01_20_000002_clean_duplicate_tenant_fields');
            }

            if (str_contains($issue, 'Coluna "is_default" já existe')) {
                $this->markMigrationAsRun('2025_08_29_114155_add_is_default_to_tenants_table');
            }

            if (str_contains($issue, 'Coluna') && str_contains($issue, 'já existe')) {
                $this->markMigrationAsRun('2025_08_23_014240_add_portal_config_fields_to_tenants_table');
            }
        }

        $this->newLine();
        $this->info('✅ Problemas corrigidos!');
    }

    /**
     * Marcar migração como executada
     */
    private function markMigrationAsRun(string $migrationName): void
    {
        try {
            $migration = DB::table('migrations')->where('migration', $migrationName)->first();

            if (!$migration) {
                DB::table('migrations')->insert([
                    'migration' => $migrationName,
                    'batch' => $this->getNextBatchNumber(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->line("    ✅ Migração '{$migrationName}' marcada como executada");
            } else {
                $this->line("    ℹ️  Migração '{$migrationName}' já estava marcada como executada");
            }
        } catch (\Exception $e) {
            $this->error("    ❌ Erro ao marcar migração '{$migrationName}': {$e->getMessage()}");
        }
    }

    /**
     * Obter próximo número de batch
     */
    private function getNextBatchNumber(): int
    {
        $lastBatch = DB::table('migrations')->max('batch');
        return ($lastBatch ?? 0) + 1;
    }
}
