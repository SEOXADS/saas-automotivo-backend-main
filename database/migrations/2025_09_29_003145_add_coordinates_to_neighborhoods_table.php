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
        Schema::table('neighborhoods', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('zip_code')->comment('Latitude do bairro');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude')->comment('Longitude do bairro');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('neighborhoods', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
