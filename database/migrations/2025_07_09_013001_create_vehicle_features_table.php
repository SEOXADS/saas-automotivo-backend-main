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
        Schema::create('vehicle_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->string('name'); // Nome da característica
            $table->string('category'); // Categoria (conforto, segurança, etc.)
            $table->string('icon')->nullable(); // Ícone da característica
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Índices
            $table->index(['vehicle_id', 'category']);
            $table->index(['vehicle_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_features');
    }
};
