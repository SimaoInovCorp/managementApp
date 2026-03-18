<?php

namespace Database\Seeders;

use App\Models\CustomerAccount;
use App\Models\Entity;
use Illuminate\Database\Seeder;

/**
 * Seeds demo customer account ledger entries. Safe to re-run.
 */
class CustomerAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (CustomerAccount::exists()) {
            $this->command->info('CustomerAccountSeeder: skipped (data already exists).');

            return;
        }

        $techSolutions = Entity::where('name', 'Tech Solutions Lda')->first();

        if (! $techSolutions) {
            $this->command->warn('CustomerAccountSeeder: Tech Solutions Lda not found. Skipping.');

            return;
        }

        // Append-only entries — created_at set explicitly, no updated_at
        CustomerAccount::insert([
            [
                'entity_id' => $techSolutions->id,
                'description' => 'Invoice #00001 payment received',
                'debit' => 0.00,
                'credit' => 5000.00,
                'date' => '2026-03-01',
                'created_at' => now(),
            ],
            [
                'entity_id' => $techSolutions->id,
                'description' => 'Bank processing charges',
                'debit' => 15.00,
                'credit' => 0.00,
                'date' => '2026-03-05',
                'created_at' => now(),
            ],
            [
                'entity_id' => $techSolutions->id,
                'description' => 'Partial advance payment for project',
                'debit' => 0.00,
                'credit' => 2500.00,
                'date' => '2026-03-10',
                'created_at' => now(),
            ],
        ]);

        $this->command->info('CustomerAccountSeeder: 3 ledger entries seeded.');
    }
}
