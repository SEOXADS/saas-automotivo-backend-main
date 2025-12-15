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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome da empresa
            $table->string('subdomain')->unique(); // Subdomínio único
            $table->string('domain')->nullable(); // Domínio customizado
            $table->string('email')->unique(); // Email do tenant
            $table->string('phone')->nullable(); // Telefone
            $table->string('logo')->nullable(); // Logo da empresa
            $table->json('config')->nullable(); // Configurações customizadas
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->enum('plan', ['basic', 'premium', 'enterprise'])->default('basic');
            $table->timestamp('trial_ends_at')->nullable(); // Data fim do trial
            $table->timestamp('subscription_ends_at')->nullable(); // Data fim da assinatura
            $table->json('features')->nullable(); // Features disponíveis
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
