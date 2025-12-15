<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BrazilianCitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $cities = [
            // Acre (state_id: 1) - Already has cities, skipping

            // Alagoas (state_id: 2) - Already has cities, skipping

            // Amapá (state_id: 3)
            ['name' => 'Macapá', 'state_id' => 3, 'country_id' => 1, 'ibge_code' => '1600303', 'is_active' => 1],
            ['name' => 'Santana', 'state_id' => 3, 'country_id' => 1, 'ibge_code' => '1600600', 'is_active' => 1],
            ['name' => 'Laranjal do Jari', 'state_id' => 3, 'country_id' => 1, 'ibge_code' => '1600279', 'is_active' => 1],
            ['name' => 'Oiapoque', 'state_id' => 3, 'country_id' => 1, 'ibge_code' => '1600501', 'is_active' => 1],
            ['name' => 'Mazagão', 'state_id' => 3, 'country_id' => 1, 'ibge_code' => '1600402', 'is_active' => 1],

            // Amazonas (state_id: 4)
            ['name' => 'Manaus', 'state_id' => 4, 'country_id' => 1, 'ibge_code' => '1302603', 'is_active' => 1],
            ['name' => 'Parintins', 'state_id' => 4, 'country_id' => 1, 'ibge_code' => '1303403', 'is_active' => 1],
            ['name' => 'Itacoatiara', 'state_id' => 4, 'country_id' => 1, 'ibge_code' => '1301902', 'is_active' => 1],
            ['name' => 'Manacapuru', 'state_id' => 4, 'country_id' => 1, 'ibge_code' => '1302504', 'is_active' => 1],
            ['name' => 'Coari', 'state_id' => 4, 'country_id' => 1, 'ibge_code' => '1301209', 'is_active' => 1],

            // Bahia (state_id: 5)
            ['name' => 'Salvador', 'state_id' => 5, 'country_id' => 1, 'ibge_code' => '2927408', 'is_active' => 1],
            ['name' => 'Feira de Santana', 'state_id' => 5, 'country_id' => 1, 'ibge_code' => '2910800', 'is_active' => 1],
            ['name' => 'Vitória da Conquista', 'state_id' => 5, 'country_id' => 1, 'ibge_code' => '2933307', 'is_active' => 1],
            ['name' => 'Camaçari', 'state_id' => 5, 'country_id' => 1, 'ibge_code' => '2905701', 'is_active' => 1],
            ['name' => 'Itabuna', 'state_id' => 5, 'country_id' => 1, 'ibge_code' => '2915601', 'is_active' => 1],

            // Ceará (state_id: 6)
            ['name' => 'Fortaleza', 'state_id' => 6, 'country_id' => 1, 'ibge_code' => '2304400', 'is_active' => 1],
            ['name' => 'Caucaia', 'state_id' => 6, 'country_id' => 1, 'ibge_code' => '2303709', 'is_active' => 1],
            ['name' => 'Juazeiro do Norte', 'state_id' => 6, 'country_id' => 1, 'ibge_code' => '2307304', 'is_active' => 1],
            ['name' => 'Maracanaú', 'state_id' => 6, 'country_id' => 1, 'ibge_code' => '2307650', 'is_active' => 1],
            ['name' => 'Sobral', 'state_id' => 6, 'country_id' => 1, 'ibge_code' => '2312908', 'is_active' => 1],

            // Distrito Federal (state_id: 7)
            ['name' => 'Brasília', 'state_id' => 7, 'country_id' => 1, 'ibge_code' => '5300108', 'is_active' => 1],
            ['name' => 'Taguatinga', 'state_id' => 7, 'country_id' => 1, 'ibge_code' => '5300108', 'is_active' => 1],
            ['name' => 'Ceilândia', 'state_id' => 7, 'country_id' => 1, 'ibge_code' => '5300108', 'is_active' => 1],
            ['name' => 'Samambaia', 'state_id' => 7, 'country_id' => 1, 'ibge_code' => '5300108', 'is_active' => 1],
            ['name' => 'Planaltina', 'state_id' => 7, 'country_id' => 1, 'ibge_code' => '5300108', 'is_active' => 1],

            // Espírito Santo (state_id: 8)
            ['name' => 'Vitória', 'state_id' => 8, 'country_id' => 1, 'ibge_code' => '3205309', 'is_active' => 1],
            ['name' => 'Vila Velha', 'state_id' => 8, 'country_id' => 1, 'ibge_code' => '3205200', 'is_active' => 1],
            ['name' => 'Serra', 'state_id' => 8, 'country_id' => 1, 'ibge_code' => '3205002', 'is_active' => 1],
            ['name' => 'Cariacica', 'state_id' => 8, 'country_id' => 1, 'ibge_code' => '3201308', 'is_active' => 1],
            ['name' => 'Cachoeiro de Itapemirim', 'state_id' => 8, 'country_id' => 1, 'ibge_code' => '3201209', 'is_active' => 1],

            // Goiás (state_id: 9)
            ['name' => 'Goiânia', 'state_id' => 9, 'country_id' => 1, 'ibge_code' => '5208707', 'is_active' => 1],
            ['name' => 'Aparecida de Goiânia', 'state_id' => 9, 'country_id' => 1, 'ibge_code' => '5201405', 'is_active' => 1],
            ['name' => 'Anápolis', 'state_id' => 9, 'country_id' => 1, 'ibge_code' => '5201108', 'is_active' => 1],
            ['name' => 'Rio Verde', 'state_id' => 9, 'country_id' => 1, 'ibge_code' => '5218805', 'is_active' => 1],
            ['name' => 'Luziânia', 'state_id' => 9, 'country_id' => 1, 'ibge_code' => '5212501', 'is_active' => 1],

            // Maranhão (state_id: 10)
            ['name' => 'São Luís', 'state_id' => 10, 'country_id' => 1, 'ibge_code' => '2111300', 'is_active' => 1],
            ['name' => 'Imperatriz', 'state_id' => 10, 'country_id' => 1, 'ibge_code' => '2105302', 'is_active' => 1],
            ['name' => 'São José de Ribamar', 'state_id' => 10, 'country_id' => 1, 'ibge_code' => '2111201', 'is_active' => 1],
            ['name' => 'Timon', 'state_id' => 10, 'country_id' => 1, 'ibge_code' => '2112209', 'is_active' => 1],
            ['name' => 'Caxias', 'state_id' => 10, 'country_id' => 1, 'ibge_code' => '2103000', 'is_active' => 1],

            // Mato Grosso (state_id: 11)
            ['name' => 'Cuiabá', 'state_id' => 11, 'country_id' => 1, 'ibge_code' => '5103403', 'is_active' => 1],
            ['name' => 'Várzea Grande', 'state_id' => 11, 'country_id' => 1, 'ibge_code' => '5108402', 'is_active' => 1],
            ['name' => 'Rondonópolis', 'state_id' => 11, 'country_id' => 1, 'ibge_code' => '5107602', 'is_active' => 1],
            ['name' => 'Sinop', 'state_id' => 11, 'country_id' => 1, 'ibge_code' => '5107909', 'is_active' => 1],
            ['name' => 'Tangará da Serra', 'state_id' => 11, 'country_id' => 1, 'ibge_code' => '5107958', 'is_active' => 1],

            // Mato Grosso do Sul (state_id: 12)
            ['name' => 'Campo Grande', 'state_id' => 12, 'country_id' => 1, 'ibge_code' => '5002704', 'is_active' => 1],
            ['name' => 'Dourados', 'state_id' => 12, 'country_id' => 1, 'ibge_code' => '5003702', 'is_active' => 1],
            ['name' => 'Três Lagoas', 'state_id' => 12, 'country_id' => 1, 'ibge_code' => '5008305', 'is_active' => 1],
            ['name' => 'Corumbá', 'state_id' => 12, 'country_id' => 1, 'ibge_code' => '5003207', 'is_active' => 1],
            ['name' => 'Ponta Porã', 'state_id' => 12, 'country_id' => 1, 'ibge_code' => '5006606', 'is_active' => 1],

            // Minas Gerais (state_id: 13)
            ['name' => 'Belo Horizonte', 'state_id' => 13, 'country_id' => 1, 'ibge_code' => '3106200', 'is_active' => 1],
            ['name' => 'Uberlândia', 'state_id' => 13, 'country_id' => 1, 'ibge_code' => '3170206', 'is_active' => 1],
            ['name' => 'Contagem', 'state_id' => 13, 'country_id' => 1, 'ibge_code' => '3118601', 'is_active' => 1],
            ['name' => 'Juiz de Fora', 'state_id' => 13, 'country_id' => 1, 'ibge_code' => '3136702', 'is_active' => 1],
            ['name' => 'Betim', 'state_id' => 13, 'country_id' => 1, 'ibge_code' => '3106705', 'is_active' => 1],

            // Pará (state_id: 14)
            ['name' => 'Belém', 'state_id' => 14, 'country_id' => 1, 'ibge_code' => '1501402', 'is_active' => 1],
            ['name' => 'Ananindeua', 'state_id' => 14, 'country_id' => 1, 'ibge_code' => '1500800', 'is_active' => 1],
            ['name' => 'Santarém', 'state_id' => 14, 'country_id' => 1, 'ibge_code' => '1506807', 'is_active' => 1],
            ['name' => 'Marabá', 'state_id' => 14, 'country_id' => 1, 'ibge_code' => '1504208', 'is_active' => 1],
            ['name' => 'Castanhal', 'state_id' => 14, 'country_id' => 1, 'ibge_code' => '1502400', 'is_active' => 1],

            // Paraíba (state_id: 15)
            ['name' => 'João Pessoa', 'state_id' => 15, 'country_id' => 1, 'ibge_code' => '2507507', 'is_active' => 1],
            ['name' => 'Campina Grande', 'state_id' => 15, 'country_id' => 1, 'ibge_code' => '2504009', 'is_active' => 1],
            ['name' => 'Santa Rita', 'state_id' => 15, 'country_id' => 1, 'ibge_code' => '2513703', 'is_active' => 1],
            ['name' => 'Patos', 'state_id' => 15, 'country_id' => 1, 'ibge_code' => '2510808', 'is_active' => 1],
            ['name' => 'Bayeux', 'state_id' => 15, 'country_id' => 1, 'ibge_code' => '2501500', 'is_active' => 1],

            // Paraná (state_id: 16)
            ['name' => 'Curitiba', 'state_id' => 16, 'country_id' => 1, 'ibge_code' => '4106902', 'is_active' => 1],
            ['name' => 'Londrina', 'state_id' => 16, 'country_id' => 1, 'ibge_code' => '4113700', 'is_active' => 1],
            ['name' => 'Maringá', 'state_id' => 16, 'country_id' => 1, 'ibge_code' => '4115200', 'is_active' => 1],
            ['name' => 'Ponta Grossa', 'state_id' => 16, 'country_id' => 1, 'ibge_code' => '4119905', 'is_active' => 1],
            ['name' => 'Cascavel', 'state_id' => 16, 'country_id' => 1, 'ibge_code' => '4104808', 'is_active' => 1],

            // Pernambuco (state_id: 17)
            ['name' => 'Recife', 'state_id' => 17, 'country_id' => 1, 'ibge_code' => '2611606', 'is_active' => 1],
            ['name' => 'Jaboatão dos Guararapes', 'state_id' => 17, 'country_id' => 1, 'ibge_code' => '2607901', 'is_active' => 1],
            ['name' => 'Olinda', 'state_id' => 17, 'country_id' => 1, 'ibge_code' => '2609600', 'is_active' => 1],
            ['name' => 'Caruaru', 'state_id' => 17, 'country_id' => 1, 'ibge_code' => '2604106', 'is_active' => 1],
            ['name' => 'Petrolina', 'state_id' => 17, 'country_id' => 1, 'ibge_code' => '2611101', 'is_active' => 1],

            // Piauí (state_id: 18)
            ['name' => 'Teresina', 'state_id' => 18, 'country_id' => 1, 'ibge_code' => '2211001', 'is_active' => 1],
            ['name' => 'Parnaíba', 'state_id' => 18, 'country_id' => 1, 'ibge_code' => '2207702', 'is_active' => 1],
            ['name' => 'Picos', 'state_id' => 18, 'country_id' => 1, 'ibge_code' => '2208007', 'is_active' => 1],
            ['name' => 'Floriano', 'state_id' => 18, 'country_id' => 1, 'ibge_code' => '2203909', 'is_active' => 1],
            ['name' => 'Piripiri', 'state_id' => 18, 'country_id' => 1, 'ibge_code' => '2208304', 'is_active' => 1],

            // Rio de Janeiro (state_id: 19)
            ['name' => 'Rio de Janeiro', 'state_id' => 19, 'country_id' => 1, 'ibge_code' => '3304557', 'is_active' => 1],
            ['name' => 'São Gonçalo', 'state_id' => 19, 'country_id' => 1, 'ibge_code' => '3304904', 'is_active' => 1],
            ['name' => 'Duque de Caxias', 'state_id' => 19, 'country_id' => 1, 'ibge_code' => '3301702', 'is_active' => 1],
            ['name' => 'Nova Iguaçu', 'state_id' => 19, 'country_id' => 1, 'ibge_code' => '3303500', 'is_active' => 1],
            ['name' => 'Niterói', 'state_id' => 19, 'country_id' => 1, 'ibge_code' => '3303302', 'is_active' => 1],

            // Rio Grande do Norte (state_id: 20)
            ['name' => 'Natal', 'state_id' => 20, 'country_id' => 1, 'ibge_code' => '2408102', 'is_active' => 1],
            ['name' => 'Mossoró', 'state_id' => 20, 'country_id' => 1, 'ibge_code' => '2408003', 'is_active' => 1],
            ['name' => 'Parnamirim', 'state_id' => 20, 'country_id' => 1, 'ibge_code' => '2403251', 'is_active' => 1],
            ['name' => 'São Gonçalo do Amarante', 'state_id' => 20, 'country_id' => 1, 'ibge_code' => '2412005', 'is_active' => 1],
            ['name' => 'Macaíba', 'state_id' => 20, 'country_id' => 1, 'ibge_code' => '2407104', 'is_active' => 1],

            // Rio Grande do Sul (state_id: 21)
            ['name' => 'Porto Alegre', 'state_id' => 21, 'country_id' => 1, 'ibge_code' => '4314902', 'is_active' => 1],
            ['name' => 'Caxias do Sul', 'state_id' => 21, 'country_id' => 1, 'ibge_code' => '4305108', 'is_active' => 1],
            ['name' => 'Pelotas', 'state_id' => 21, 'country_id' => 1, 'ibge_code' => '4314407', 'is_active' => 1],
            ['name' => 'Canoas', 'state_id' => 21, 'country_id' => 1, 'ibge_code' => '4304606', 'is_active' => 1],
            ['name' => 'Santa Maria', 'state_id' => 21, 'country_id' => 1, 'ibge_code' => '4316907', 'is_active' => 1],

            // Rondônia (state_id: 22)
            ['name' => 'Porto Velho', 'state_id' => 22, 'country_id' => 1, 'ibge_code' => '1100205', 'is_active' => 1],
            ['name' => 'Ji-Paraná', 'state_id' => 22, 'country_id' => 1, 'ibge_code' => '1100122', 'is_active' => 1],
            ['name' => 'Ariquemes', 'state_id' => 22, 'country_id' => 1, 'ibge_code' => '1100023', 'is_active' => 1],
            ['name' => 'Vilhena', 'state_id' => 22, 'country_id' => 1, 'ibge_code' => '1100304', 'is_active' => 1],
            ['name' => 'Cacoal', 'state_id' => 22, 'country_id' => 1, 'ibge_code' => '1100049', 'is_active' => 1],

            // Roraima (state_id: 23)
            ['name' => 'Boa Vista', 'state_id' => 23, 'country_id' => 1, 'ibge_code' => '1400100', 'is_active' => 1],
            ['name' => 'Rorainópolis', 'state_id' => 23, 'country_id' => 1, 'ibge_code' => '1400472', 'is_active' => 1],
            ['name' => 'Caracaraí', 'state_id' => 23, 'country_id' => 1, 'ibge_code' => '1400209', 'is_active' => 1],
            ['name' => 'Alto Alegre', 'state_id' => 23, 'country_id' => 1, 'ibge_code' => '1400050', 'is_active' => 1],
            ['name' => 'Mucajaí', 'state_id' => 23, 'country_id' => 1, 'ibge_code' => '1400308', 'is_active' => 1],

            // Santa Catarina (state_id: 24)
            ['name' => 'Florianópolis', 'state_id' => 24, 'country_id' => 1, 'ibge_code' => '4205407', 'is_active' => 1],
            ['name' => 'Joinville', 'state_id' => 24, 'country_id' => 1, 'ibge_code' => '4209102', 'is_active' => 1],
            ['name' => 'Blumenau', 'state_id' => 24, 'country_id' => 1, 'ibge_code' => '4202404', 'is_active' => 1],
            ['name' => 'São José', 'state_id' => 24, 'country_id' => 1, 'ibge_code' => '4216602', 'is_active' => 1],
            ['name' => 'Criciúma', 'state_id' => 24, 'country_id' => 1, 'ibge_code' => '4204608', 'is_active' => 1],

            // São Paulo (state_id: 25)
            ['name' => 'São Paulo', 'state_id' => 25, 'country_id' => 1, 'ibge_code' => '3550308', 'is_active' => 1],
            ['name' => 'Guarulhos', 'state_id' => 25, 'country_id' => 1, 'ibge_code' => '3518800', 'is_active' => 1],
            ['name' => 'Campinas', 'state_id' => 25, 'country_id' => 1, 'ibge_code' => '3509502', 'is_active' => 1],
            ['name' => 'São Bernardo do Campo', 'state_id' => 25, 'country_id' => 1, 'ibge_code' => '3548708', 'is_active' => 1],
            ['name' => 'Santo André', 'state_id' => 25, 'country_id' => 1, 'ibge_code' => '3547809', 'is_active' => 1],

            // Sergipe (state_id: 26)
            ['name' => 'Aracaju', 'state_id' => 26, 'country_id' => 1, 'ibge_code' => '2800308', 'is_active' => 1],
            ['name' => 'Nossa Senhora do Socorro', 'state_id' => 26, 'country_id' => 1, 'ibge_code' => '2804706', 'is_active' => 1],
            ['name' => 'Lagarto', 'state_id' => 26, 'country_id' => 1, 'ibge_code' => '2803500', 'is_active' => 1],
            ['name' => 'Itabaiana', 'state_id' => 26, 'country_id' => 1, 'ibge_code' => '2802908', 'is_active' => 1],
            ['name' => 'São Cristóvão', 'state_id' => 26, 'country_id' => 1, 'ibge_code' => '2806701', 'is_active' => 1],

        ];

        foreach ($cities as $city) {
            $city['created_at'] = $now;
            $city['updated_at'] = $now;
            
            // Use INSERT IGNORE to skip duplicates
            DB::table('cities')->insertOrIgnore($city);
        }

        $this->command->info('Brazilian cities seeded successfully!');
    }
}
