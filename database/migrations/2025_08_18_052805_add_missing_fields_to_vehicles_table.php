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
            // Campos básicos faltantes
            $table->enum('vehicle_type', ['car', 'motorcycle', 'truck', 'suv', 'pickup', 'van', 'bus', 'other'])->default('car')->after('model_id');
            $table->enum('condition', ['new', 'used'])->default('used')->after('vehicle_type');
            $table->decimal('classified_price', 10, 2)->nullable()->after('price'); // Preço do classificado
            $table->string('cost_type')->nullable()->after('classified_price'); // Tipo de custo

            // Campos de controle
            $table->boolean('hide_mileage')->default(false)->after('mileage'); // Não exibir quilometragem
            $table->boolean('use_same_observation')->default(true)->after('description'); // Usar mesma observação do site

            // Campos de mídia e links
            $table->string('video_link')->nullable()->after('renavam'); // Link do vídeo

            // Campos de observações personalizadas
            $table->text('custom_observation')->nullable()->after('use_same_observation'); // Observação personalizada
            $table->json('classified_observations')->nullable()->after('custom_observation'); // Observações por classificado

            // Campos de características
            $table->json('standard_features')->nullable()->after('custom_observation'); // Características padrão (checkboxes)
            $table->json('optional_features')->nullable()->after('standard_features'); // Opcionais (checkboxes)

            // Campos de status adicionais
            $table->boolean('is_licensed')->default(false)->after('is_featured'); // Licenciado
            $table->boolean('has_warranty')->default(false)->after('is_licensed'); // Tem garantia
            $table->boolean('is_adapted')->default(false)->after('has_warranty'); // Adaptado para deficientes
            $table->boolean('is_armored')->default(false)->after('is_adapted'); // Blindado
            $table->boolean('has_spare_key')->default(false)->after('is_armored'); // Tem chave reserva
            $table->boolean('ipva_paid')->default(false)->after('has_spare_key'); // IPVA pago
            $table->boolean('has_manual')->default(false)->after('ipva_paid'); // Tem manual
            $table->boolean('auction_history')->default(false)->after('has_manual'); // Passou por leilão
            $table->boolean('dealer_serviced')->default(false)->after('auction_history'); // Revisado em concessionária
            $table->boolean('single_owner')->default(false)->after('dealer_serviced'); // Único dono
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'vehicle_type',
                'condition',
                'classified_price',
                'cost_type',
                'hide_mileage',
                'use_same_observation',
                'video_link',
                'custom_observation',
                'classified_observations',
                'standard_features',
                'optional_features',
                'is_licensed',
                'has_warranty',
                'is_adapted',
                'is_armored',
                'has_spare_key',
                'ipva_paid',
                'has_manual',
                'auction_history',
                'dealer_serviced',
                'single_owner'
            ]);
        });
    }
};
