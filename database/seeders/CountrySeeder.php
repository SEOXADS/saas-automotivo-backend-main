<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            [
                'name' => 'Brasil',
                'code' => 'BR',
                'phone_code' => '+55',
                'currency' => 'BRL',
                'is_active' => true,
            ],
            [
                'name' => 'Estados Unidos',
                'code' => 'US',
                'phone_code' => '+1',
                'currency' => 'USD',
                'is_active' => true,
            ],
            [
                'name' => 'Argentina',
                'code' => 'AR',
                'phone_code' => '+54',
                'currency' => 'ARS',
                'is_active' => true,
            ],
            [
                'name' => 'Chile',
                'code' => 'CL',
                'phone_code' => '+56',
                'currency' => 'CLP',
                'is_active' => true,
            ],
            [
                'name' => 'Colômbia',
                'code' => 'CO',
                'phone_code' => '+57',
                'currency' => 'COP',
                'is_active' => true,
            ],
            [
                'name' => 'México',
                'code' => 'MX',
                'phone_code' => '+52',
                'currency' => 'MXN',
                'is_active' => true,
            ],
            [
                'name' => 'Peru',
                'code' => 'PE',
                'phone_code' => '+51',
                'currency' => 'PEN',
                'is_active' => true,
            ],
            [
                'name' => 'Uruguai',
                'code' => 'UY',
                'phone_code' => '+598',
                'currency' => 'UYU',
                'is_active' => true,
            ],
            [
                'name' => 'Paraguai',
                'code' => 'PY',
                'phone_code' => '+595',
                'currency' => 'PYG',
                'is_active' => true,
            ],
            [
                'name' => 'Bolívia',
                'code' => 'BO',
                'phone_code' => '+591',
                'currency' => 'BOB',
                'is_active' => true,
            ],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(
                ['code' => $country['code']],
                $country
            );
        }
    }
}
