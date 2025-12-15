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
        Schema::create('vehicle_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome da marca (ex: Toyota, Ford, etc.)
            $table->string('slug')->unique(); // Slug para URLs amigáveis
            $table->string('logo')->nullable(); // Logo da marca
            $table->text('description')->nullable(); // Descrição da marca
            $table->boolean('is_active')->default(true); // Status da marca
            $table->integer('sort_order')->default(0); // Ordem de exibição
            $table->timestamps();

            // Índices
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_brands');
    }
};
