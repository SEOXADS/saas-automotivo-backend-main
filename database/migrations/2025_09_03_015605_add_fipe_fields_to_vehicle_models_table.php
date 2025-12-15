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
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->string('fipe_code')->nullable()->after('fipe_id')->comment('Código FIPE do modelo');
            $table->string('fipe_name')->nullable()->after('fipe_code')->comment('Nome original do modelo na FIPE');
            $table->boolean('is_fipe_synced')->default(false)->after('fipe_name')->comment('Indica se o modelo foi sincronizado da FIPE');
            $table->timestamp('fipe_synced_at')->nullable()->after('is_fipe_synced')->comment('Data da última sincronização com a FIPE');

            // Índices
            $table->index('fipe_code');
            $table->index('is_fipe_synced');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->dropIndex(['fipe_code']);
            $table->dropIndex(['is_fipe_synced']);
            $table->dropColumn(['fipe_code', 'fipe_name', 'is_fipe_synced', 'fipe_synced_at']);
        });
    }
};
