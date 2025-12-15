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
        if (!Schema::hasColumn('tenants', 'is_default')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->boolean('is_default')->default(false)->after('status');
            });
        }

        // Adicionar índice apenas se a coluna existir
        if (Schema::hasColumn('tenants', 'is_default')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->index(['is_default', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['is_default', 'status']);
            $table->dropColumn('is_default');
        });
    }
};
