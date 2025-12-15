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
        Schema::create('tenant_url_redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('old_path')->index();
            $table->string('new_path')->index();
            $table->integer('status_code')->default(301);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('redirected_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'old_path']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_url_redirects');
    }
};
