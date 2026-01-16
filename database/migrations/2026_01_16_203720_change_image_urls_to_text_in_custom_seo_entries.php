<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_seo_entries', function (Blueprint $table) {
            $table->text('og_image_url')->nullable()->change();
            $table->text('twitter_image_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('custom_seo_entries', function (Blueprint $table) {
            $table->string('og_image_url', 500)->nullable()->change();
            $table->string('twitter_image_url', 500)->nullable()->change();
        });
    }
};
