<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VehicleFeaturesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Características padrão (standard_features)
        $standardFeatures = [
            'Adaptado para Def. Físico',
            'Blindado',
            'Chave Reserva',
            'Garantia de Fábrica',
            'IPVA Pago',
            'Licenciado',
            'Manual',
            'Passagem por leilão',
            'Revisado em Concessionária',
            'Único Dono'
        ];

        // Opcionais (optional_features)
        $optionalFeatures = [
            // Conforto
            'Airbag laterais',
            'Airbag motorista',
            'Airbag passageiro',
            'Alarme',
            'Ar condicionado',
            'Ar condicionado digital',
            'Ar quente',
            'Banco do motorista com ajuste de altura',
            'Bancos de Couro',
            'Bancos elétricos com aquecimento',
            'Câmera de ré',
            'Capota Marítima',
            'CD player',
            'CD player com MP3',
            'Computador de bordo',
            'Controle de som no volante',
            'Controle de tração',
            'Controle de velocidade',
            'Desembaçador traseiro',
            'Direção elétrica',
            'Direção hidráulica',
            'Encosto de cabeça traseiro',
            'Entrada USB',
            'Farol de neblina',
            'Freio ABS',
            'GPS',
            'Insulfilm',
            'Limpador traseiro',
            'MP3 Player',
            'Para-choques na cor do veículo',
            'Porta copos',
            'Protetor de Caçamba',
            'Faróis de xenon',
            'Farol de milha',
            'Rodas de liga leve',
            'Retrovisor fotocrômico',
            'Retrovisores elétricos',
            'Sensor de chuva',
            'Sensor de estacionamento',
            'Sensor de luminosidade',
            'Teto solar',
            'Tração 4x4',
            'Travas elétricas',
            'Vidros elétricos',
            'Vidros elétricos traseiros',
            'Volante com regulagem de altura'
        ];

        // Salvar as características em um arquivo de configuração ou cache
        // para que possam ser usadas pelo sistema
        $features = [
            'standard_features' => $standardFeatures,
            'optional_features' => $optionalFeatures
        ];

        // Salvar no cache para uso pelo sistema
        \Illuminate\Support\Facades\Cache::put('vehicle_features', $features, 86400); // 24 horas

        $this->command->info('✅ Características padrão e opcionais dos veículos configuradas com sucesso.');
        $this->command->info('📊 Total de características padrão: ' . count($standardFeatures));
        $this->command->info('📊 Total de opcionais: ' . count($optionalFeatures));
    }
}
