<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alterar enum requer SQL bruto em muitos bancos
        if (Schema::hasColumn('tenants', 'plan')) {
            $connection = Schema::getConnection()->getDriverName();
            if ($connection === 'mysql') {
                DB::statement("ALTER TABLE tenants MODIFY plan ENUM('trial','basic','premium','enterprise') NOT NULL DEFAULT 'basic'");
            } else {
                // Outros bancos podem requerer estratégia diferente
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tenants', 'plan')) {
            $connection = Schema::getConnection()->getDriverName();
            if ($connection === 'mysql') {
                DB::statement("ALTER TABLE tenants MODIFY plan ENUM('basic','premium','enterprise') NOT NULL DEFAULT 'basic'");
            }
        }
    }
};
