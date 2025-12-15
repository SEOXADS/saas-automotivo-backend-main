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
        Schema::table('tenants', function (Blueprint $table) {
            // Campos para configuração do portal
            if (!Schema::hasColumn('tenants', 'custom_domain')) {
                $table->string('custom_domain')->nullable()->after('subdomain')->comment('Domínio próprio do tenant');
            }
            if (!Schema::hasColumn('tenants', 'description')) {
                $table->text('description')->nullable()->after('custom_domain')->comment('Descrição da empresa');
            }
            if (!Schema::hasColumn('tenants', 'contact_email')) {
                $table->string('contact_email')->nullable()->after('description')->comment('Email de contato para o portal');
            }
            if (!Schema::hasColumn('tenants', 'contact_phone')) {
                $table->string('contact_phone')->nullable()->after('contact_email')->comment('Telefone de contato para o portal');
            }
            if (!Schema::hasColumn('tenants', 'address')) {
                $table->text('address')->nullable()->after('contact_phone')->comment('Endereço da empresa');
            }
            if (!Schema::hasColumn('tenants', 'theme_color')) {
                $table->string('theme_color')->default('#007bff')->after('address')->comment('Cor principal do tema');
            }
            if (!Schema::hasColumn('tenants', 'logo_url')) {
                $table->string('logo_url')->nullable()->after('theme_color')->comment('URL do logo da empresa');
            }
            if (!Schema::hasColumn('tenants', 'social_media')) {
                $table->json('social_media')->nullable()->after('logo_url')->comment('Redes sociais da empresa');
            }
            if (!Schema::hasColumn('tenants', 'business_hours')) {
                $table->json('business_hours')->nullable()->after('social_media')->comment('Horário de funcionamento');
            }
            if (!Schema::hasColumn('tenants', 'allow_registration')) {
                $table->boolean('allow_registration')->default(false)->after('social_media')->comment('Permitir registro de usuários');
            }
            if (!Schema::hasColumn('tenants', 'require_approval')) {
                $table->boolean('require_approval')->default(true)->after('allow_registration')->comment('Requer aprovação para novos usuários');
            }
            if (!Schema::hasColumn('tenants', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('require_approval')->comment('Se é o tenant padrão');
            }
        });

        // Adicionar índices apenas se as colunas existirem
        if (Schema::hasColumn('tenants', 'custom_domain')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->index('custom_domain');
            });
        }
        if (Schema::hasColumn('tenants', 'is_default')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->index('is_default');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['custom_domain']);
            $table->dropIndex(['is_default']);

            $table->dropColumn([
                'custom_domain',
                'description',
                'contact_email',
                'contact_phone',
                'address',
                'theme_color',
                'logo_url',
                'social_media',
                'business_hours',
                'allow_registration',
                'require_approval',
                'is_default'
            ]);
        });
    }
};
