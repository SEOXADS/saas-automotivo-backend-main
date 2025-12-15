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
        Schema::create('tenant_integrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('integration_type'); // google_analytics, facebook_pixel, whatsapp, etc.
            $table->string('name'); // Nome da integração
            $table->text('description')->nullable(); // Descrição da integração
            $table->json('config'); // Configurações da integração (API keys, etc.)
            $table->boolean('is_active')->default(true);
            $table->boolean('is_required')->default(false); // Se é obrigatória
            $table->json('webhook_urls')->nullable(); // URLs para webhooks
            $table->timestamp('last_sync_at')->nullable(); // Última sincronização
            $table->json('sync_status')->nullable(); // Status da sincronização
            $table->text('error_message')->nullable(); // Última mensagem de erro
            $table->timestamps();

            // Índices para performance
            $table->index(['tenant_id', 'integration_type']);
            $table->index(['tenant_id', 'is_active']);
            $table->index('integration_type');
            $table->index('is_active');

            // Chave estrangeira
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            // Índice único para evitar duplicatas
            $table->unique(['tenant_id', 'integration_type'], 'tenant_integration_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_integrations');
    }
};
