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
        Schema::table('tenant_profiles', function (Blueprint $table) {
            // Alterar campos de imagem para text para suportar URLs longas
            $table->text('logo_url')->nullable()->change();
            $table->text('favicon_url')->nullable()->change();
            $table->text('banner_url')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_profiles', function (Blueprint $table) {
            // Reverter para string (pode causar perda de dados se URLs forem muito longas)
            $table->string('logo_url')->nullable()->change();
            $table->string('favicon_url')->nullable()->change();
            $table->string('banner_url')->nullable()->change();
        });
    }
};
