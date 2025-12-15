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
        Schema::create('vehicle_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->string('filename'); // Nome do arquivo
            $table->string('original_name'); // Nome original
            $table->string('path'); // Caminho da imagem
            $table->string('url'); // URL da imagem
            $table->integer('size')->nullable(); // Tamanho em bytes
            $table->string('mime_type')->nullable(); // Tipo MIME
            $table->integer('width')->nullable(); // Largura
            $table->integer('height')->nullable(); // Altura
            $table->boolean('is_primary')->default(false); // Imagem principal
            $table->integer('sort_order')->default(0); // Ordem de exibição
            $table->timestamps();

            // Índices
            $table->index(['vehicle_id', 'is_primary']);
            $table->index(['vehicle_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_images');
    }
};
