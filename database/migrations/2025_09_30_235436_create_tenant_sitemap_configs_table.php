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
        Schema::create('tenant_sitemap_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['index', 'images', 'videos', 'articles', 'vehicles', 'pages']);
            $table->string('url');
            $table->boolean('is_active')->default(true);
            $table->decimal('priority', 3, 1)->default(0.5); // 0.0 a 1.0
            $table->enum('change_frequency', ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'])->default('weekly');
            $table->json('config_data')->nullable(); // Dados específicos do tipo
            $table->timestamps();

            $table->unique(['tenant_id', 'url']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_sitemap_configs');
    }
};
