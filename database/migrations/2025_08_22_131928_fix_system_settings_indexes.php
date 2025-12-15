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
        Schema::table('system_settings', function (Blueprint $table) {
            // Remover o índice único da coluna key
            $table->dropUnique(['key']);

            // Adicionar índice único na combinação de group e key
            $table->unique(['group', 'key'], 'system_settings_group_key_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            // Reverter as mudanças
            $table->dropUnique('system_settings_group_key_unique');
            $table->unique(['key']);
        });
    }
};
