<?php

namespace Database\Seeders;

use App\Models\VatRate;
use Illuminate\Database\Seeder;

class VatRateSeeder extends Seeder
{
    public function run(): void
    {
        // Standard VAT rates (Portugal example, in English)
        $rates = [
            ['name' => 'Standard',    'rate' => 23.00],
            ['name' => 'Intermediate', 'rate' => 13.00],
            ['name' => 'Reduced',     'rate' =>  6.00],
            ['name' => 'Exempt',      'rate' =>  0.00],
        ];

        foreach ($rates as $rate) {
            VatRate::firstOrCreate(['name' => $rate['name']], $rate);
        }
    }
}
