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
        Schema::create('tenant_robots_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('user_agent')->default('*');
            $table->json('allow')->nullable(); // Array de paths permitidos
            $table->json('disallow')->nullable(); // Array de paths bloqueados
            $table->integer('crawl_delay')->nullable(); // Delay em segundos
            $table->json('sitemap')->nullable(); // Array de URLs de sitemaps
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'user_agent']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_robots_configs');
    }
};
