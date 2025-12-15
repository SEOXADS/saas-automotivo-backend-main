<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenant_robots_configs', function (Blueprint $table) {
            // Adicionar novas colunas sem remover as antigas primeiro
            $table->string('locale', 10)->default('pt-BR')->after('tenant_id');
            $table->text('user_agent_rules')->nullable()->after('is_active');
            $table->text('disallow_rules')->nullable()->after('user_agent_rules');
            $table->text('allow_rules')->nullable()->after('disallow_rules');
            $table->text('crawl_delay_new')->nullable()->after('allow_rules'); // Nome diferente para evitar conflito
            $table->text('sitemap_urls')->nullable()->after('crawl_delay_new');
            $table->text('custom_rules')->nullable()->after('sitemap_urls');
            $table->text('host_directive')->nullable()->after('custom_rules');
            $table->boolean('include_sitemap_index')->default(true)->after('host_directive');
            $table->boolean('include_sitemap_files')->default(true)->after('include_sitemap_index');
            $table->text('notes')->nullable()->after('include_sitemap_files');
            $table->timestamp('last_generated_at')->nullable()->after('notes');
            $table->string('last_generated_by')->nullable()->after('last_generated_at');

            // Adicionar novos índices
            $table->index(['tenant_id', 'locale']);
            $table->unique(['tenant_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_robots_configs', function (Blueprint $table) {
            // Remover novas colunas
            $table->dropColumn([
                'locale',
                'user_agent_rules',
                'disallow_rules',
                'allow_rules',
                'crawl_delay_new',
                'sitemap_urls',
                'custom_rules',
                'host_directive',
                'include_sitemap_index',
                'include_sitemap_files',
                'notes',
                'last_generated_at',
                'last_generated_by'
            ]);
        });
    }
};
