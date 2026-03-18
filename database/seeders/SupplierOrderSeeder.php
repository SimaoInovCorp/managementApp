<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Entity;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderLine;
use App\Services\SupplierOrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeds demo supplier orders for development testing.
 * Safe to re-run — only creates orders if the table is empty.
 */
class SupplierOrderSeeder extends Seeder
{
    public function run(): void
    {
        if (SupplierOrder::exists()) {
            $this->command->info('SupplierOrderSeeder: skipped (data already exists).');

            return;
        }

        $service = new SupplierOrderService;
        $europarts = Entity::whereIn('type', ['supplier', 'both'])->where('name', 'Euro Parts GmbH')->first();

        if (! $europarts) {
            $this->command->warn('SupplierOrderSeeder: No supplier found. Skipping.');

            return;
        }

        $articles = Article::with('vatRate')->get()->keyBy('reference');
        $keyboard = $articles->get('ELEC-001');
        $hub = $articles->get('ELEC-002');
        $webDev = $articles->get('SRV-001');

        // --- Order 1: Equipment purchase from Euro Parts (closed) ---
        $lines1 = [
            ['article' => $keyboard, 'qty' => 10],
            ['article' => $hub,      'qty' => 5],
        ];

        $enriched1 = array_map(fn (array $l) => [
            'quantity' => $l['qty'],
            'unit_price' => (float) ($l['article']?->price ?? 0),
            'vat_rate' => $l['article']?->vatRate ? (float) $l['article']->vatRate->rate : 0,
        ], $lines1);

        DB::transaction(function () use ($service, $europarts, $lines1, $enriched1): void {
            $order = SupplierOrder::create([
                'number' => $service->nextNumber(),
                'order_date' => Carbon::today()->subDays(5),
                'supplier_id' => $europarts->id,
                'customer_order_id' => null,
                'total_amount' => $service->computeTotal($enriched1),
                'status' => 'closed',
                'notes' => 'Stock replenishment — keyboards and hubs.',
            ]);

            foreach ($lines1 as $index => $lineData) {
                if (! $lineData['article']) {
                    continue;
                }
                SupplierOrderLine::create([
                    'supplier_order_id' => $order->id,
                    'article_id' => $lineData['article']->id,
                    'quantity' => $lineData['qty'],
                    'unit_price' => (float) $lineData['article']->price,
                    'sort_order' => $index,
                ]);
            }
        });

        // --- Order 2: Draft equipment order from Euro Parts ---
        $lines2 = [
            ['article' => $keyboard, 'qty' => 5],
        ];

        $enriched2 = array_map(fn (array $l) => [
            'quantity' => $l['qty'],
            'unit_price' => (float) ($l['article']?->price ?? 0),
            'vat_rate' => $l['article']?->vatRate ? (float) $l['article']->vatRate->rate : 0,
        ], $lines2);

        DB::transaction(function () use ($service, $europarts, $lines2, $enriched2): void {
            $order = SupplierOrder::create([
                'number' => $service->nextNumber(),
                'order_date' => Carbon::today(),
                'supplier_id' => $europarts->id,
                'customer_order_id' => null,
                'total_amount' => $service->computeTotal($enriched2),
                'status' => 'draft',
                'notes' => null,
            ]);

            foreach ($lines2 as $index => $lineData) {
                if (! $lineData['article']) {
                    continue;
                }
                SupplierOrderLine::create([
                    'supplier_order_id' => $order->id,
                    'article_id' => $lineData['article']->id,
                    'quantity' => $lineData['qty'],
                    'unit_price' => (float) $lineData['article']->price,
                    'sort_order' => $index,
                ]);
            }
        });

        $this->command->info('SupplierOrderSeeder: 2 supplier orders seeded.');
    }
}
