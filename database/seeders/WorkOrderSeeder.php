<?php

namespace Database\Seeders;

use App\Models\Entity;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds demo work orders for development testing.
 * Safe to re-run — only creates orders if the table is empty.
 */
class WorkOrderSeeder extends Seeder
{
    public function run(): void
    {
        if (WorkOrder::exists()) {
            $this->command->info('WorkOrderSeeder: skipped (data already exists).');

            return;
        }

        $service = new WorkOrderService;
        $techSolutions = Entity::where('name', 'Tech Solutions Lda')->first();
        $parceiros = Entity::where('name', 'Parceiros Integrados SA')->first();

        if ($techSolutions) {
            WorkOrder::create([
                'number' => $service->nextNumber(),
                'date' => Carbon::today()->subDays(7),
                'client_id' => $techSolutions->id,
                'description' => 'Website redesign — implement new design mockups and migrate legacy content to new CMS.',
                'status' => 'closed',
            ]);
        }

        if ($parceiros) {
            WorkOrder::create([
                'number' => $service->nextNumber(),
                'date' => Carbon::today(),
                'client_id' => $parceiros->id,
                'description' => 'Install and configure 3× keyboard and 3× USB hub at Parceiros head office.',
                'status' => 'draft',
            ]);
        }

        $this->command->info('WorkOrderSeeder: 2 work orders seeded.');
    }
}
