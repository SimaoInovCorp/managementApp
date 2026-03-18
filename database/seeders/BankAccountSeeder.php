<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use Illuminate\Database\Seeder;

/**
 * Seeds demo bank accounts. Safe to re-run.
 */
class BankAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (BankAccount::exists()) {
            $this->command->info('BankAccountSeeder: skipped (data already exists).');

            return;
        }

        BankAccount::create([
            'name' => 'Main Operating Account — Caixa Geral de Depósitos',
            'iban' => 'PT50003500030000000000033',
            'bic' => 'CGDIPTPL',
            'balance' => 50000.00,
            'status' => 'active',
        ]);

        BankAccount::create([
            'name' => 'Secondary Account — Santander',
            'iban' => 'PT50001800030000000000051',
            'bic' => 'TOTAPTPL',
            'balance' => 10000.00,
            'status' => 'active',
        ]);

        $this->command->info('BankAccountSeeder: 2 bank accounts seeded.');
    }
}
