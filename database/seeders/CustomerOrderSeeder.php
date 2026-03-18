<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderLine;
use App\Models\Entity;
use App\Models\Proposal;
use App\Services\CustomerOrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeds demo customer orders for development testing.
 * Safe to re-run — only creates orders if the table is empty.
 */
class CustomerOrderSeeder extends Seeder
{
    public function run(): void
    {
        if (CustomerOrder::exists()) {
            $this->command->info('CustomerOrderSeeder: skipped (data already exists).');

            return;
        }

        $service = new CustomerOrderService;

        $techSolutions = Entity::where('name', 'Tech Solutions Lda')->first();
        $parceiros = Entity::where('name', 'Parceiros Integrados SA')->first();
        $europarts = Entity::whereIn('type', ['supplier', 'both'])->where('name', 'Euro Parts GmbH')->first();

        // Grab some articles for lines
        $articles = Article::with('vatRate')->get()->keyBy('reference');
        $webDev = $articles->get('SRV-001');
        $consult = $articles->get('SRV-002');
        $support = $articles->get('SRV-003');
        $keyboard = $articles->get('ELEC-001');
        $hub = $articles->get('ELEC-002');

        // --- Order 1: Linked to the closed Proposal 1 (Tech Solutions) ---
        $proposal1 = Proposal::where('status', 'closed')->first();

        $order1Lines = [
            ['article' => $webDev,  'qty' => 40, 'supplier' => null],
            ['article' => $support, 'qty' => 3,  'supplier' => null],
        ];

        $enriched1 = array_map(fn (array $l) => [
            'quantity' => $l['qty'],
            'unit_price' => (float) ($l['article']?->price ?? 0),
            'vat_rate' => $l['article']?->vatRate ? (float) $l['article']->vatRate->rate : 0,
        ], $order1Lines);

        DB::transaction(function () use ($service, $techSolutions, $proposal1, $order1Lines, $enriched1): void {
            if (! $techSolutions) {
                return;
            }

            $order = CustomerOrder::create([
                'number' => $service->nextNumber(),
                'order_date' => Carbon::today()->subDays(10),
                'client_id' => $techSolutions->id,
                'proposal_id' => $proposal1?->id,
                'total_amount' => $service->computeTotal($enriched1),
                'status' => 'closed',
                'notes' => 'Annual web development and support — confirmed.',
            ]);

            foreach ($order1Lines as $index => $lineData) {
                if (! $lineData['article']) {
                    continue;
                }
                CustomerOrderLine::create([
                    'customer_order_id' => $order->id,
                    'article_id' => $lineData['article']->id,
                    'supplier_id' => null,
                    'quantity' => $lineData['qty'],
                    'unit_price' => (float) $lineData['article']->price,
                    'cost_price' => null,
                    'sort_order' => $index,
                ]);
            }
        });

        // --- Order 2: Independent draft for Parceiros (with equipment + supplier) ---
        $order2Lines = [
            ['article' => $webDev,   'qty' => 20, 'supplier' => null],
            ['article' => $consult,  'qty' => 1,  'supplier' => null],
            ['article' => $keyboard, 'qty' => 3,  'supplier' => $europarts],
            ['article' => $hub,      'qty' => 3,  'supplier' => $europarts],
        ];

        $enriched2 = array_map(fn (array $l) => [
            'quantity' => $l['qty'],
            'unit_price' => (float) ($l['article']?->price ?? 0),
            'vat_rate' => $l['article']?->vatRate ? (float) $l['article']->vatRate->rate : 0,
        ], $order2Lines);

        DB::transaction(function () use ($service, $parceiros, $order2Lines, $enriched2): void {
            if (! $parceiros) {
                return;
            }

            $order = CustomerOrder::create([
                'number' => $service->nextNumber(),
                'order_date' => Carbon::today()->subDays(2),
                'client_id' => $parceiros->id,
                'proposal_id' => null,
                'total_amount' => $service->computeTotal($enriched2),
                'status' => 'draft',
                'notes' => 'Web development project with peripheral equipment.',
            ]);

            foreach ($order2Lines as $index => $lineData) {
                if (! $lineData['article']) {
                    continue;
                }
                CustomerOrderLine::create([
                    'customer_order_id' => $order->id,
                    'article_id' => $lineData['article']->id,
                    'supplier_id' => $lineData['supplier']?->id,
                    'quantity' => $lineData['qty'],
                    'unit_price' => (float) $lineData['article']->price,
                    'cost_price' => null,
                    'sort_order' => $index,
                ]);
            }
        });

        $this->command->info('CustomerOrderSeeder: 2 customer orders seeded.');
    }
}
