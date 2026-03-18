<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Entity;
use App\Models\Proposal;
use App\Models\ProposalLine;
use App\Services\ProposalService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeds demo proposals for development testing.
 * Safe to re-run — only creates proposals if the table is empty.
 */
class ProposalSeeder extends Seeder
{
    public function run(): void
    {
        if (Proposal::exists()) {
            $this->command->info('ProposalSeeder: skipped (data already exists).');

            return;
        }

        $service = new ProposalService;

        $techSolutions = Entity::where('name', 'Tech Solutions Lda')->first();
        $obras = Entity::where('name', 'Obras & Construção SA')->first();
        $parceiros = Entity::where('name', 'Parceiros Integrados SA')->first();

        $materiais = Entity::whereIn('type', ['supplier', 'both'])->where('name', 'Materiais & Ferramentas Lda')->first();
        $europarts = Entity::whereIn('type', ['supplier', 'both'])->where('name', 'Euro Parts GmbH')->first();

        // Grab some articles for lines
        $articles = Article::with('vatRate')->get()->keyBy('reference');
        $webDev = $articles->get('SRV-001');
        $consult = $articles->get('SRV-002');
        $support = $articles->get('SRV-003');
        $keyboard = $articles->get('ELEC-001');
        $hub = $articles->get('ELEC-002');

        $proposals = [
            [
                'client' => $techSolutions,
                'proposal_date' => Carbon::today()->subDays(45),
                'validity_date' => Carbon::today()->subDays(15),
                'status' => 'closed',
                'notes' => 'Annual web development and support contract.',
                'lines' => [
                    ['article' => $webDev,  'qty' => 40,  'supplier' => null],
                    ['article' => $support, 'qty' => 3,   'supplier' => null],
                ],
            ],
            [
                'client' => $obras,
                'proposal_date' => Carbon::today()->subDays(10),
                'validity_date' => Carbon::today()->addDays(20),
                'status' => 'draft',
                'notes' => 'IT consulting and equipment supply for new office.',
                'lines' => [
                    ['article' => $consult,  'qty' => 2,  'supplier' => null],
                    ['article' => $keyboard, 'qty' => 5,  'supplier' => $europarts],
                    ['article' => $hub,      'qty' => 5,  'supplier' => $europarts],
                ],
            ],
            [
                'client' => $parceiros,
                'proposal_date' => Carbon::today()->subDays(3),
                'validity_date' => Carbon::today()->addDays(27),
                'status' => 'draft',
                'notes' => null,
                'lines' => [
                    ['article' => $webDev,  'qty' => 20, 'supplier' => null],
                    ['article' => $consult, 'qty' => 1,  'supplier' => null],
                ],
            ],
        ];

        foreach ($proposals as $proposalData) {
            /** @var Entity|null $client */
            $client = $proposalData['client'];
            if (! $client) {
                continue;
            }

            $lines = $proposalData['lines'];

            // Build enriched lines for total computation
            $enrichedLines = array_map(fn (array $l) => [
                'quantity' => $l['qty'],
                'unit_price' => (float) ($l['article']?->price ?? 0),
                'vat_rate' => $l['article']?->vatRate ? (float) $l['article']->vatRate->rate : 0,
            ], $lines);

            $total = $service->computeTotal($enrichedLines);

            DB::transaction(function () use ($proposalData, $client, $lines, $total, $service): void {
                $proposal = Proposal::create([
                    'number' => $service->nextNumber(),
                    'proposal_date' => $proposalData['proposal_date'],
                    'client_id' => $client->id,
                    'validity_date' => $proposalData['validity_date'],
                    'total_amount' => $total,
                    'status' => $proposalData['status'],
                    'notes' => $proposalData['notes'],
                ]);

                foreach ($lines as $index => $lineData) {
                    if (! $lineData['article']) {
                        continue;
                    }
                    ProposalLine::create([
                        'proposal_id' => $proposal->id,
                        'article_id' => $lineData['article']->id,
                        'supplier_id' => $lineData['supplier']?->id,
                        'quantity' => $lineData['qty'],
                        'unit_price' => (float) $lineData['article']->price,
                        'cost_price' => null,
                        'sort_order' => $index,
                    ]);
                }
            });
        }

        $this->command->info('ProposalSeeder: '.count($proposals).' proposals seeded.');
    }
}
