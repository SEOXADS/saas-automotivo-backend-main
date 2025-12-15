<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\City;
use App\Models\State;
use App\Models\Country;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrazilCitiesImportSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Iniciando importação de cidades do Brasil...');

        $brazil = Country::where('code', 'BR')->first();
        if (!$brazil) {
            $this->command->error('País Brasil não encontrado. Execute o CountrySeeder primeiro.');
            return;
        }

        try {
            // Baixar dados das cidades
            $response = Http::timeout(60)->get('https://raw.githubusercontent.com/alanwillms/geoinfo/refs/heads/master/latitude-longitude-cidades.csv');

            if (!$response->successful()) {
                $this->command->error('Erro ao baixar dados das cidades: ' . $response->status());
                return;
            }

            $csvData = $response->body();
            $lines = explode("\n", $csvData);

            // Pular cabeçalho
            array_shift($lines);

            $imported = 0;
            $errors = 0;

            foreach ($lines as $line) {
                if (empty(trim($line))) continue;

                // Parse CSV (formato: "id_municipio";"uf";"municipio";"longitude";"latitude")
                $data = str_getcsv($line, ';');

                if (count($data) < 5) continue;

                $municipioId = trim($data[0], '"');
                $uf = trim($data[1], '"');
                $municipio = trim($data[2], '"');
                $longitude = floatval(str_replace(',', '.', trim($data[3], '"')));
                $latitude = floatval(str_replace(',', '.', trim($data[4], '"')));

                // Buscar estado
                $state = State::where('code', $uf)->where('country_id', $brazil->id)->first();

                if (!$state) {
                    $this->command->warn("Estado não encontrado: {$uf} para cidade: {$municipio}");
                    $errors++;
                    continue;
                }

                // Criar ou atualizar cidade
                try {
                    City::updateOrCreate(
                        [
                            'state_id' => $state->id,
                            'name' => $municipio
                        ],
                        [
                            'name' => $municipio,
                            'state_id' => $state->id,
                            'country_id' => $brazil->id,
                            'ibge_code' => $municipioId,
                            'latitude' => $latitude,
                            'longitude' => $longitude,
                            'is_active' => true,
                        ]
                    );

                    $imported++;

                    if ($imported % 100 == 0) {
                        $this->command->info("Importadas {$imported} cidades...");
                    }

                } catch (\Exception $e) {
                    $this->command->warn("Erro ao importar cidade {$municipio}: " . $e->getMessage());
                    $errors++;
                }
            }

            $this->command->info("Importação concluída!");
            $this->command->info("Cidades importadas: {$imported}");
            $this->command->info("Erros: {$errors}");

        } catch (\Exception $e) {
            $this->command->error('Erro na importação: ' . $e->getMessage());
            Log::error('Erro na importação de cidades do Brasil', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
