<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Neighborhood;
use App\Models\City;
use App\Models\State;
use App\Models\Country;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrazilNeighborhoodsImportSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Iniciando importação de bairros do Brasil...');

        $brazil = Country::where('code', 'BR')->first();
        if (!$brazil) {
            $this->command->error('País Brasil não encontrado. Execute o CountrySeeder primeiro.');
            return;
        }

        try {
            // Baixar dados dos bairros
            $response = Http::timeout(120)->get('https://raw.githubusercontent.com/alanwillms/geoinfo/refs/heads/master/latitude-longitude-bairros.csv');

            if (!$response->successful()) {
                $this->command->error('Erro ao baixar dados dos bairros: ' . $response->status());
                return;
            }

            $csvData = $response->body();
            $lines = explode("\n", $csvData);

            // Pular cabeçalho
            array_shift($lines);

            $imported = 0;
            $errors = 0;
            $notFound = 0;

            foreach ($lines as $line) {
                if (empty(trim($line))) continue;

                // Parse CSV (formato: "id_municipio";"id_bairro";"uf";"municipio";"bairro";"longitude";"latitude")
                $data = str_getcsv($line, ';');

                if (count($data) < 7) continue;

                $municipioId = trim($data[0], '"');
                $bairroId = trim($data[1], '"');
                $uf = trim($data[2], '"');
                $municipio = trim($data[3], '"');
                $bairro = trim($data[4], '"');
                $longitude = floatval(str_replace(',', '.', trim($data[5], '"')));
                $latitude = floatval(str_replace(',', '.', trim($data[6], '"')));

                // Buscar estado
                $state = State::where('code', $uf)->where('country_id', $brazil->id)->first();

                if (!$state) {
                    $this->command->warn("Estado não encontrado: {$uf} para bairro: {$bairro} em {$municipio}");
                    $errors++;
                    continue;
                }

                // Buscar cidade
                $city = City::where('name', $municipio)
                           ->where('state_id', $state->id)
                           ->where('country_id', $brazil->id)
                           ->first();

                if (!$city) {
                    // Tentar criar a cidade se não existir
                    $city = City::create([
                        'name' => $municipio,
                        'state_id' => $state->id,
                        'country_id' => $brazil->id,
                        'ibge_code' => $municipioId,
                        'is_active' => true,
                    ]);

                    $this->command->info("Cidade criada: {$municipio} - {$uf}");
                }

                // Criar ou atualizar bairro
                try {
                    Neighborhood::updateOrCreate(
                        [
                            'city_id' => $city->id,
                            'name' => $bairro
                        ],
                        [
                            'name' => $bairro,
                            'city_id' => $city->id,
                            'state_id' => $state->id,
                            'country_id' => $brazil->id,
                            'latitude' => $latitude,
                            'longitude' => $longitude,
                            'is_active' => true,
                        ]
                    );

                    $imported++;

                    if ($imported % 1000 == 0) {
                        $this->command->info("Importados {$imported} bairros...");
                    }

                } catch (\Exception $e) {
                    $this->command->warn("Erro ao importar bairro {$bairro} em {$municipio}: " . $e->getMessage());
                    $errors++;
                }
            }

            $this->command->info("Importação concluída!");
            $this->command->info("Bairros importados: {$imported}");
            $this->command->info("Erros: {$errors}");

        } catch (\Exception $e) {
            $this->command->error('Erro na importação: ' . $e->getMessage());
            Log::error('Erro na importação de bairros do Brasil', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
