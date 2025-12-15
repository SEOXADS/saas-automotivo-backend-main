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
        Schema::create('portal_cache', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('cache_key'); // Chave do cache
            $table->text('cache_value'); // Valor do cache (pode ser JSON)
            $table->string('cache_type')->default('data'); // data, html, json, etc.
            $table->timestamp('expires_at')->nullable(); // Quando expira
            $table->string('tags')->nullable(); // Tags para invalidação seletiva
            $table->integer('hit_count')->default(0); // Contador de hits
            $table->timestamp('last_accessed_at')->nullable(); // Último acesso
            $table->timestamps();

            // Índices para performance
            $table->index(['tenant_id', 'cache_key']);
            $table->index(['tenant_id', 'expires_at']);
            $table->index('cache_key');
            $table->index('expires_at');
            $table->index('last_accessed_at');

            // Chave estrangeira
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            // Índice único para evitar duplicatas
            $table->unique(['tenant_id', 'cache_key'], 'portal_cache_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_cache');
    }
};
