<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['name' => 'Portugal',        'code' => 'PT'],
            ['name' => 'Spain',           'code' => 'ES'],
            ['name' => 'France',          'code' => 'FR'],
            ['name' => 'Germany',         'code' => 'DE'],
            ['name' => 'United Kingdom',  'code' => 'GB'],
            ['name' => 'United States',   'code' => 'US'],
            ['name' => 'Italy',           'code' => 'IT'],
            ['name' => 'Netherlands',     'code' => 'NL'],
            ['name' => 'Belgium',         'code' => 'BE'],
            ['name' => 'Brazil',          'code' => 'BR'],
            ['name' => 'Switzerland',     'code' => 'CH'],
            ['name' => 'Luxembourg',      'code' => 'LU'],
            ['name' => 'Ireland',         'code' => 'IE'],
            ['name' => 'China',           'code' => 'CN'],
            ['name' => 'Japan',           'code' => 'JP'],
        ];

        foreach ($countries as $country) {
            Country::firstOrCreate(['code' => $country['code']], $country);
        }
    }
}
