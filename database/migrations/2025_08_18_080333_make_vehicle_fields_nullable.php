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
        Schema::table('vehicles', function (Blueprint $table) {
            // Campos básicos - tornar nullable
            $table->string('title')->nullable()->change();
            $table->string('version')->nullable()->change();
            $table->year('year')->nullable()->change();
            $table->year('model_year')->nullable()->change();
            $table->string('color')->nullable()->change();
            $table->enum('fuel_type', ['flex', 'gasolina', 'diesel', 'eletrico', 'hibrido', 'gnv'])->nullable()->change();
            $table->enum('transmission', ['manual', 'automatica', 'cvt', 'automatizada'])->nullable()->change();
            $table->integer('doors')->nullable()->change();
            $table->integer('mileage')->nullable()->change();

            // Preços - tornar nullable
            $table->decimal('price', 10, 2)->nullable()->change();
            $table->decimal('fipe_price', 10, 2)->nullable()->change();
            $table->boolean('accept_financing')->nullable()->change();
            $table->boolean('accept_exchange')->nullable()->change();

            // Detalhes técnicos - tornar nullable
            $table->string('engine')->nullable()->change();
            $table->string('power')->nullable()->change();
            $table->string('torque')->nullable()->change();
            $table->string('consumption_city')->nullable()->change();
            $table->string('consumption_highway')->nullable()->change();

            // Informações adicionais - tornar nullable
            $table->text('description')->nullable()->change();
            $table->string('plate')->nullable()->change();
            $table->string('chassi')->nullable()->change();
            $table->string('renavam')->nullable()->change();
            $table->string('owner_name')->nullable()->change();
            $table->string('owner_phone')->nullable()->change();
            $table->string('owner_email')->nullable()->change();

            // Status e controle - tornar nullable
            $table->enum('status', ['available', 'sold', 'reserved', 'maintenance'])->nullable()->change();
            $table->boolean('is_featured')->nullable()->change();
            $table->boolean('is_active')->nullable()->change();
            $table->integer('views')->nullable()->change();
            $table->timestamp('published_at')->nullable()->change();

            // Novos campos adicionados anteriormente - já são nullable
            // vehicle_type, condition, classified_price, cost_type, hide_mileage
            // use_same_observation, video_link, custom_observation, classified_observations
            // standard_features, optional_features, is_licensed, has_warranty, is_adapted
            // is_armored, has_spare_key, ipva_paid, has_manual, auction_history
            // dealer_serviced, single_owner
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Reverter campos básicos para not null
            $table->string('title')->nullable(false)->change();
            $table->string('version')->nullable(false)->change();
            $table->year('year')->nullable(false)->change();
            $table->year('model_year')->nullable(false)->change();
            $table->string('color')->nullable(false)->change();
            $table->enum('fuel_type', ['flex', 'gasolina', 'diesel', 'eletrico', 'hibrido', 'gnv'])->nullable(false)->change();
            $table->enum('transmission', ['manual', 'automatica', 'cvt', 'automatizada'])->nullable(false)->change();
            $table->integer('doors')->nullable(false)->change();
            $table->integer('mileage')->nullable(false)->change();

            // Reverter preços para not null
            $table->decimal('price', 10, 2)->nullable(false)->change();
            $table->decimal('fipe_price', 10, 2)->nullable(false)->change();
            $table->boolean('accept_financing')->nullable(false)->change();
            $table->boolean('accept_exchange')->nullable(false)->change();

            // Reverter detalhes técnicos para not null
            $table->string('engine')->nullable(false)->change();
            $table->string('power')->nullable(false)->change();
            $table->string('torque')->nullable(false)->change();
            $table->string('consumption_city')->nullable(false)->change();
            $table->string('consumption_highway')->nullable(false)->change();

            // Reverter informações adicionais para not null
            $table->text('description')->nullable(false)->change();
            $table->string('plate')->nullable(false)->change();
            $table->string('chassi')->nullable(false)->change();
            $table->string('renavam')->nullable(false)->change();
            $table->string('owner_name')->nullable(false)->change();
            $table->string('owner_phone')->nullable(false)->change();
            $table->string('owner_email')->nullable(false)->change();

            // Reverter status e controle para not null
            $table->enum('status', ['available', 'sold', 'reserved', 'maintenance'])->nullable(false)->change('available');
            $table->boolean('is_featured')->nullable(false)->change();
            $table->boolean('is_active')->nullable(false)->change();
            $table->integer('views')->nullable(false)->change();
            $table->timestamp('published_at')->nullable(false)->change();
        });
    }
};
