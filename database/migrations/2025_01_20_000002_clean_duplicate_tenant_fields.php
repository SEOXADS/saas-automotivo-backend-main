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
        // Renomear alguns campos para maior clareza
        if (Schema::hasColumn('tenants', 'domain') && !Schema::hasColumn('tenants', 'custom_domain')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->renameColumn('domain', 'custom_domain');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Restaurar campos removidos
            $table->text('description')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('logo_url')->nullable();
            $table->json('social_media')->nullable();
            $table->json('business_hours')->nullable();
            $table->string('theme_color')->default('#007bff');
            $table->boolean('allow_registration')->default(false);
            $table->boolean('require_approval')->default(true);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->renameColumn('custom_domain', 'domain');
        });
    }
};
