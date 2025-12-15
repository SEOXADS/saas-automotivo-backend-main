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
        Schema::create('system_messages', function (Blueprint $table) {
            $table->id();
            $table->string('module', 100); // Ex: vehicles, leads, users, etc.
            $table->string('title', 255);
            $table->enum('type', ['error', 'success', 'info', 'warning', 'question', 'loading']);
            $table->text('message');
            $table->string('icon', 100)->nullable(); // Ícone da biblioteca (ex: FontAwesome, Heroicons)
            $table->string('icon_library', 50)->nullable(); // Biblioteca de ícones
            $table->json('options')->nullable(); // Opções adicionais (botões, configurações)
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('version_hash', 64)->default(''); // Hash para verificar alterações
            $table->timestamps();

            // Índices
            $table->index(['module', 'type']);
            $table->index(['module', 'is_active']);
            $table->index('version_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_messages');
    }
};
