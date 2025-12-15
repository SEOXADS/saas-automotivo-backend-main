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
        Schema::create('tenant_analytics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('metric_type'); // page_view, lead_created, vehicle_viewed, search_performed
            $table->string('metric_name'); // home_page, vehicle_list, vehicle_detail, lead_form
            $table->json('metric_data')->nullable(); // Dados específicos da métrica
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->json('session_data')->nullable(); // Dados da sessão do usuário
            $table->timestamp('recorded_at');
            $table->timestamps();

            // Índices para performance
            $table->index(['tenant_id', 'metric_type']);
            $table->index(['tenant_id', 'recorded_at']);
            $table->index('metric_type');
            $table->index('recorded_at');

            // Chave estrangeira
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_analytics');
    }
};
