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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('set null');
            $table->string('name'); // Nome do lead
            $table->string('email'); // Email do lead
            $table->string('phone'); // Telefone do lead
            $table->text('message')->nullable(); // Mensagem do lead
            $table->enum('source', ['site', 'whatsapp', 'facebook', 'instagram', 'google', 'outro'])->default('site');
            $table->enum('status', ['new', 'contacted', 'qualified', 'negotiating', 'closed_won', 'closed_lost'])->default('new');
            $table->json('utm_data')->nullable(); // Dados UTM para tracking
            $table->string('ip_address')->nullable(); // IP do visitante
            $table->string('user_agent')->nullable(); // User Agent
            $table->timestamp('contacted_at')->nullable(); // Data do primeiro contato
            $table->timestamp('qualified_at')->nullable(); // Data da qualificação
            $table->timestamp('closed_at')->nullable(); // Data do fechamento
            $table->foreignId('assigned_to')->nullable()->constrained('tenant_users'); // Vendedor responsável
            $table->text('notes')->nullable(); // Observações
            $table->timestamps();

            // Índices
            $table->index(['tenant_id', 'status']);
            $table->index(['vehicle_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['source', 'status']);
            $table->index(['created_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
