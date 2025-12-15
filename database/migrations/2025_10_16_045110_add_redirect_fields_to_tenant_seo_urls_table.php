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
        Schema::table('tenant_seo_urls', function (Blueprint $table) {
            // Campos adicionais para redirects
            $table->enum('redirect_type', ['none', '301', '302', 'canonical'])->default('none')->after('extra_meta');
            $table->string('redirect_target', 512)->nullable()->after('redirect_type');
            $table->string('redirect_reason', 255)->nullable()->after('redirect_target'); // 'slug_changed', 'moved_permanently', 'technical_route'
            $table->string('previous_slug', 512)->nullable()->after('redirect_reason'); // Para auditoria
            $table->datetime('redirect_date')->nullable()->after('previous_slug');

            // Índices para performance
            $table->index(['tenant_id', 'redirect_type', 'redirect_target'], 'idx_tenant_seo_urls_redirect');
            $table->index(['tenant_id', 'previous_slug'], 'idx_tenant_seo_urls_previous_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_seo_urls', function (Blueprint $table) {
            // Remove índices primeiro
            $table->dropIndex('idx_tenant_seo_urls_redirect');
            $table->dropIndex('idx_tenant_seo_urls_previous_slug');

            // Remove campos de redirect
            $table->dropColumn([
                'redirect_type',
                'redirect_target',
                'redirect_reason',
                'previous_slug',
                'redirect_date'
            ]);
        });
    }
};
