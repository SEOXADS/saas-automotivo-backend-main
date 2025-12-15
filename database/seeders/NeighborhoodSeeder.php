<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Neighborhood;
use App\Models\City;
use App\Models\State;
use App\Models\Country;

class NeighborhoodSeeder extends Seeder
{
    public function run(): void
    {
        $brazil = Country::where('code', 'BR')->first();
        if (!$brazil) {
            $this->command->error('País Brasil não encontrado. Execute o CountrySeeder primeiro.');
            return;
        }

        // Principais bairros das principais cidades
        $neighborhoods = [
            // São Paulo
            ['name' => 'Centro', 'city_name' => 'São Paulo', 'state_code' => 'SP', 'zip_code' => '01000-000'],
            ['name' => 'Vila Madalena', 'city_name' => 'São Paulo', 'state_code' => 'SP', 'zip_code' => '05433-000'],
            ['name' => 'Pinheiros', 'city_name' => 'São Paulo', 'state_code' => 'SP', 'zip_code' => '05422-000'],
            ['name' => 'Itaim Bibi', 'city_name' => 'São Paulo', 'state_code' => 'SP', 'zip_code' => '04530-000'],
            ['name' => 'Jardins', 'city_name' => 'São Paulo', 'state_code' => 'SP', 'zip_code' => '01452-000'],
            ['name' => 'Moema', 'city_name' => 'São Paulo', 'state_code' => 'SP', 'zip_code' => '04077-000'],
            ['name' => 'Vila Olímpia', 'city_name' => 'São Paulo', 'state_code' => 'SP', 'zip_code' => '04551-000'],
            ['name' => 'Brooklin', 'city_name' => 'São Paulo', 'state_code' => 'SP', 'zip_code' => '04571-000'],

            // Rio de Janeiro
            ['name' => 'Copacabana', 'city_name' => 'Rio de Janeiro', 'state_code' => 'RJ', 'zip_code' => '22000-000'],
            ['name' => 'Ipanema', 'city_name' => 'Rio de Janeiro', 'state_code' => 'RJ', 'zip_code' => '22400-000'],
            ['name' => 'Leblon', 'city_name' => 'Rio de Janeiro', 'state_code' => 'RJ', 'zip_code' => '22430-000'],
            ['name' => 'Barra da Tijuca', 'city_name' => 'Rio de Janeiro', 'state_code' => 'RJ', 'zip_code' => '22600-000'],
            ['name' => 'Centro', 'city_name' => 'Rio de Janeiro', 'state_code' => 'RJ', 'zip_code' => '20000-000'],
            ['name' => 'Botafogo', 'city_name' => 'Rio de Janeiro', 'state_code' => 'RJ', 'zip_code' => '22250-000'],
            ['name' => 'Flamengo', 'city_name' => 'Rio de Janeiro', 'state_code' => 'RJ', 'zip_code' => '22210-000'],
            ['name' => 'Tijuca', 'city_name' => 'Rio de Janeiro', 'state_code' => 'RJ', 'zip_code' => '20520-000'],

            // Belo Horizonte
            ['name' => 'Centro', 'city_name' => 'Belo Horizonte', 'state_code' => 'MG', 'zip_code' => '30112-000'],
            ['name' => 'Savassi', 'city_name' => 'Belo Horizonte', 'state_code' => 'MG', 'zip_code' => '30112-000'],
            ['name' => 'Funcionários', 'city_name' => 'Belo Horizonte', 'state_code' => 'MG', 'zip_code' => '30112-000'],
            ['name' => 'Lourdes', 'city_name' => 'Belo Horizonte', 'state_code' => 'MG', 'zip_code' => '30112-000'],
            ['name' => 'Pampulha', 'city_name' => 'Belo Horizonte', 'state_code' => 'MG', 'zip_code' => '31365-000'],
            ['name' => 'Cidade Nova', 'city_name' => 'Belo Horizonte', 'state_code' => 'MG', 'zip_code' => '31170-000'],

            // Salvador
            ['name' => 'Centro', 'city_name' => 'Salvador', 'state_code' => 'BA', 'zip_code' => '40020-000'],
            ['name' => 'Barra', 'city_name' => 'Salvador', 'state_code' => 'BA', 'zip_code' => '40140-000'],
            ['name' => 'Ondina', 'city_name' => 'Salvador', 'state_code' => 'BA', 'zip_code' => '40170-000'],
            ['name' => 'Pituba', 'city_name' => 'Salvador', 'state_code' => 'BA', 'zip_code' => '41830-000'],
            ['name' => 'Rio Vermelho', 'city_name' => 'Salvador', 'state_code' => 'BA', 'zip_code' => '41950-000'],
            ['name' => 'Itaigara', 'city_name' => 'Salvador', 'state_code' => 'BA', 'zip_code' => '41815-000'],

            // Curitiba
            ['name' => 'Centro', 'city_name' => 'Curitiba', 'state_code' => 'PR', 'zip_code' => '80020-000'],
            ['name' => 'Batel', 'city_name' => 'Curitiba', 'state_code' => 'PR', 'zip_code' => '80420-000'],
            ['name' => 'Mercês', 'city_name' => 'Curitiba', 'state_code' => 'PR', 'zip_code' => '80810-000'],
            ['name' => 'Bigorrilho', 'city_name' => 'Curitiba', 'state_code' => 'PR', 'zip_code' => '80730-000'],
            ['name' => 'Água Verde', 'city_name' => 'Curitiba', 'state_code' => 'PR', 'zip_code' => '80620-000'],
            ['name' => 'Cristo Rei', 'city_name' => 'Curitiba', 'state_code' => 'PR', 'zip_code' => '80050-000'],

            // Porto Alegre
            ['name' => 'Centro', 'city_name' => 'Porto Alegre', 'state_code' => 'RS', 'zip_code' => '90010-000'],
            ['name' => 'Moinhos de Vento', 'city_name' => 'Porto Alegre', 'state_code' => 'RS', 'zip_code' => '90570-000'],
            ['name' => 'Bela Vista', 'city_name' => 'Porto Alegre', 'state_code' => 'RS', 'zip_code' => '90450-000'],
            ['name' => 'Boa Vista', 'city_name' => 'Porto Alegre', 'state_code' => 'RS', 'zip_code' => '90130-000'],
            ['name' => 'Cidade Baixa', 'city_name' => 'Porto Alegre', 'state_code' => 'RS', 'zip_code' => '90050-000'],
            ['name' => 'Floresta', 'city_name' => 'Porto Alegre', 'state_code' => 'RS', 'zip_code' => '90220-000'],

            // Recife
            ['name' => 'Centro', 'city_name' => 'Recife', 'state_code' => 'PE', 'zip_code' => '50020-000'],
            ['name' => 'Boa Viagem', 'city_name' => 'Recife', 'state_code' => 'PE', 'zip_code' => '51030-000'],
            ['name' => 'Casa Forte', 'city_name' => 'Recife', 'state_code' => 'PE', 'zip_code' => '52061-000'],
            ['name' => 'Graças', 'city_name' => 'Recife', 'state_code' => 'PE', 'zip_code' => '52011-000'],
            ['name' => 'Parnamirim', 'city_name' => 'Recife', 'state_code' => 'PE', 'zip_code' => '52060-000'],
            ['name' => 'Espinheiro', 'city_name' => 'Recife', 'state_code' => 'PE', 'zip_code' => '52020-000'],

            // Fortaleza
            ['name' => 'Centro', 'city_name' => 'Fortaleza', 'state_code' => 'CE', 'zip_code' => '60000-000'],
            ['name' => 'Aldeota', 'city_name' => 'Fortaleza', 'state_code' => 'CE', 'zip_code' => '60115-000'],
            ['name' => 'Meireles', 'city_name' => 'Fortaleza', 'state_code' => 'CE', 'zip_code' => '60165-000'],
            ['name' => 'Dionísio Torres', 'city_name' => 'Fortaleza', 'state_code' => 'CE', 'zip_code' => '60135-000'],
            ['name' => 'Praia de Iracema', 'city_name' => 'Fortaleza', 'state_code' => 'CE', 'zip_code' => '60060-000'],
            ['name' => 'Varjota', 'city_name' => 'Fortaleza', 'state_code' => 'CE', 'zip_code' => '60170-000'],

            // Belém
            ['name' => 'Centro', 'city_name' => 'Belém', 'state_code' => 'PA', 'zip_code' => '66000-000'],
            ['name' => 'Nazaré', 'city_name' => 'Belém', 'state_code' => 'PA', 'zip_code' => '66035-000'],
            ['name' => 'Batista Campos', 'city_name' => 'Belém', 'state_code' => 'PA', 'zip_code' => '66033-000'],
            ['name' => 'Marco', 'city_name' => 'Belém', 'state_code' => 'PA', 'zip_code' => '66093-000'],
            ['name' => 'Umarizal', 'city_name' => 'Belém', 'state_code' => 'PA', 'zip_code' => '66050-000'],
            ['name' => 'Reduto', 'city_name' => 'Belém', 'state_code' => 'PA', 'zip_code' => '66053-000'],

            // Florianópolis
            ['name' => 'Centro', 'city_name' => 'Florianópolis', 'state_code' => 'SC', 'zip_code' => '88010-000'],
            ['name' => 'Jurerê Internacional', 'city_name' => 'Florianópolis', 'state_code' => 'SC', 'zip_code' => '88053-000'],
            ['name' => 'Campeche', 'city_name' => 'Florianópolis', 'state_code' => 'SC', 'zip_code' => '88063-000'],
            ['name' => 'Ingleses', 'city_name' => 'Florianópolis', 'state_code' => 'SC', 'zip_code' => '88058-000'],
            ['name' => 'Lagoa da Conceição', 'city_name' => 'Florianópolis', 'state_code' => 'SC', 'zip_code' => '88062-000'],
            ['name' => 'Trindade', 'city_name' => 'Florianópolis', 'state_code' => 'SC', 'zip_code' => '88040-000'],

            // Goiânia
            ['name' => 'Centro', 'city_name' => 'Goiânia', 'state_code' => 'GO', 'zip_code' => '74000-000'],
            ['name' => 'Setor Bueno', 'city_name' => 'Goiânia', 'state_code' => 'GO', 'zip_code' => '74230-000'],
            ['name' => 'Setor Marista', 'city_name' => 'Goiânia', 'state_code' => 'GO', 'zip_code' => '74180-000'],
            ['name' => 'Setor Oeste', 'city_name' => 'Goiânia', 'state_code' => 'GO', 'zip_code' => '74120-000'],
            ['name' => 'Setor Sul', 'city_name' => 'Goiânia', 'state_code' => 'GO', 'zip_code' => '74085-000'],
            ['name' => 'Setor Leste', 'city_name' => 'Goiânia', 'state_code' => 'GO', 'zip_code' => '74610-000'],

            // São Luís
            ['name' => 'Centro', 'city_name' => 'São Luís', 'state_code' => 'MA', 'zip_code' => '65000-000'],
            ['name' => 'Renascença', 'city_name' => 'São Luís', 'state_code' => 'MA', 'zip_code' => '65075-000'],
            ['name' => 'Calhau', 'city_name' => 'São Luís', 'state_code' => 'MA', 'zip_code' => '65071-000'],
            ['name' => 'Ponta d\'Areia', 'city_name' => 'São Luís', 'state_code' => 'MA', 'zip_code' => '65077-000'],
            ['name' => 'São Francisco', 'city_name' => 'São Luís', 'state_code' => 'MA', 'zip_code' => '65076-000'],
            ['name' => 'Tirirical', 'city_name' => 'São Luís', 'state_code' => 'MA', 'zip_code' => '65065-000'],

            // Vitória
            ['name' => 'Centro', 'city_name' => 'Vitória', 'state_code' => 'ES', 'zip_code' => '29000-000'],
            ['name' => 'Praia do Canto', 'city_name' => 'Vitória', 'state_code' => 'ES', 'zip_code' => '29055-000'],
            ['name' => 'Jardim da Penha', 'city_name' => 'Vitória', 'state_code' => 'ES', 'zip_code' => '29060-000'],
            ['name' => 'Santa Lúcia', 'city_name' => 'Vitória', 'state_code' => 'ES', 'zip_code' => '29056-000'],
            ['name' => 'Enseada do Suá', 'city_name' => 'Vitória', 'state_code' => 'ES', 'zip_code' => '29050-000'],
            ['name' => 'Mata da Praia', 'city_name' => 'Vitória', 'state_code' => 'ES', 'zip_code' => '29066-000'],

            // Brasília
            ['name' => 'Plano Piloto', 'city_name' => 'Brasília', 'state_code' => 'DF', 'zip_code' => '70000-000'],
            ['name' => 'Asa Norte', 'city_name' => 'Brasília', 'state_code' => 'DF', 'zip_code' => '70800-000'],
            ['name' => 'Asa Sul', 'city_name' => 'Brasília', 'state_code' => 'DF', 'zip_code' => '70300-000'],
            ['name' => 'Lago Sul', 'city_name' => 'Brasília', 'state_code' => 'DF', 'zip_code' => '71680-000'],
            ['name' => 'Lago Norte', 'city_name' => 'Brasília', 'state_code' => 'DF', 'zip_code' => '71500-000'],
            ['name' => 'Sudoeste', 'city_name' => 'Brasília', 'state_code' => 'DF', 'zip_code' => '70680-000'],
        ];

        foreach ($neighborhoods as $neighborhoodData) {
            $state = State::where('code', $neighborhoodData['state_code'])->first();
            $city = City::where('name', $neighborhoodData['city_name'])
                       ->where('state_id', $state->id)
                       ->first();

            if ($state && $city) {
                Neighborhood::updateOrCreate(
                    [
                        'city_id' => $city->id,
                        'name' => $neighborhoodData['name']
                    ],
                    [
                        'name' => $neighborhoodData['name'],
                        'city_id' => $city->id,
                        'state_id' => $state->id,
                        'country_id' => $brazil->id,
                        'zip_code' => $neighborhoodData['zip_code'],
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('Principais bairros do Brasil criados com sucesso!');
    }
}
