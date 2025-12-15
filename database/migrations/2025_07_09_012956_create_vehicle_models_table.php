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
        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('vehicle_brands')->onDelete('cascade');
            $table->string('name'); // Nome do modelo (ex: Corolla, Fiesta, etc.)
            $table->string('slug'); // Slug para URLs amigáveis
            $table->text('description')->nullable(); // Descrição do modelo
            $table->enum('category', ['hatch', 'sedan', 'suv', 'pickup', 'van', 'coupe', 'conversivel', 'outro'])->default('outro');
            $table->boolean('is_active')->default(true); // Status do modelo
            $table->integer('sort_order')->default(0); // Ordem de exibição
            $table->timestamps();

            // Índices
            $table->unique(['brand_id', 'slug']);
            $table->index(['brand_id', 'is_active']);
            $table->index(['category', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_models');
    }
};
