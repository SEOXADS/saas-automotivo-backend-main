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
        Schema::create('tenant_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            // Informações da empresa
            $table->string('company_name')->nullable();
            $table->text('company_description')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_phone', 20)->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_website')->nullable();
            $table->string('company_cnpj', 18)->nullable();

            // Configurações em JSON
            $table->json('business_hours')->nullable(); // Horários de funcionamento
            $table->json('social_media')->nullable(); // Redes sociais
            $table->json('theme_settings')->nullable(); // Configurações de tema
            $table->json('portal_settings')->nullable(); // Configurações do portal
            $table->json('seo_settings')->nullable(); // Configurações de SEO
            $table->json('ai_settings')->nullable(); // Configurações de IA

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('tenant_id');
            $table->index('is_active');
            $table->unique('tenant_id'); // Um tenant só pode ter uma configuração
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_configurations');
    }
};
