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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('brand_id')->constrained('vehicle_brands');
            $table->foreignId('model_id')->constrained('vehicle_models');

            // Informações básicas
            $table->string('title'); // Título do anúncio
            $table->string('version')->nullable(); // Versão do veículo
            $table->year('year'); // Ano do veículo
            $table->year('model_year'); // Ano do modelo
            $table->string('color'); // Cor
            $table->enum('fuel_type', ['flex', 'gasolina', 'diesel', 'eletrico', 'hibrido', 'gnv'])->default('flex');
            $table->enum('transmission', ['manual', 'automatica', 'cvt', 'automatizada'])->default('manual');
            $table->integer('doors')->default(4); // Número de portas
            $table->integer('mileage')->default(0); // Quilometragem

            // Preços
            $table->decimal('price', 10, 2); // Preço
            $table->decimal('fipe_price', 10, 2)->nullable(); // Preço FIPE
            $table->boolean('accept_financing')->default(true); // Aceita financiamento
            $table->boolean('accept_exchange')->default(true); // Aceita troca

            // Detalhes técnicos
            $table->string('engine')->nullable(); // Motor
            $table->string('power')->nullable(); // Potência
            $table->string('torque')->nullable(); // Torque
            $table->string('consumption_city')->nullable(); // Consumo cidade
            $table->string('consumption_highway')->nullable(); // Consumo estrada

            // Informações adicionais
            $table->text('description')->nullable(); // Descrição
            $table->string('plate')->nullable(); // Placa
            $table->string('chassi')->nullable(); // Chassi
            $table->string('renavam')->nullable(); // RENAVAM
            $table->string('owner_name')->nullable(); // Nome do proprietário
            $table->string('owner_phone')->nullable(); // Telefone do proprietário
            $table->string('owner_email')->nullable(); // Email do proprietário

            // Status e controle
            $table->enum('status', ['available', 'sold', 'reserved', 'maintenance'])->default('available');
            $table->boolean('is_featured')->default(false); // Destaque
            $table->boolean('is_active')->default(true); // Ativo
            $table->integer('views')->default(0); // Visualizações
            $table->timestamp('published_at')->nullable(); // Data de publicação

            // Usuário responsável
            $table->foreignId('created_by')->constrained('tenant_users');
            $table->foreignId('updated_by')->nullable()->constrained('tenant_users');

            $table->timestamps();

            // Índices para melhor performance
            $table->index(['tenant_id', 'status', 'is_active']);
            $table->index(['brand_id', 'model_id']);
            $table->index(['price', 'status']);
            $table->index(['year', 'status']);
            $table->index(['fuel_type', 'transmission']);
            $table->index(['is_featured', 'status']);
            $table->index(['created_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
