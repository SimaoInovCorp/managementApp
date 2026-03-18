<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\VatRate;
use Illuminate\Database\Seeder;

/**
 * Seeds demo articles for development testing.
 * Safe to re-run — skips existing references.
 */
class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $standard = VatRate::where('name', 'Standard')->value('id');
        $intermediate = VatRate::where('name', 'Intermediate')->value('id');
        $reduced = VatRate::where('name', 'Reduced')->value('id');
        $exempt = VatRate::where('name', 'Exempt')->value('id');

        $articles = [
            [
                'reference' => 'SRV-001',
                'name' => 'Web Development (hourly)',
                'description' => 'Custom web application development billed per hour.',
                'price' => 75.00,
                'vat_id' => $standard,
                'notes' => 'Rate applies to standard business hours.',
                'status' => 'active',
            ],
            [
                'reference' => 'SRV-002',
                'name' => 'IT Consulting (daily)',
                'description' => 'On-site or remote IT consultancy, billed per day.',
                'price' => 550.00,
                'vat_id' => $standard,
                'status' => 'active',
            ],
            [
                'reference' => 'SRV-003',
                'name' => 'Technical Support Package',
                'description' => 'Monthly managed technical support (up to 10h/month).',
                'price' => 299.00,
                'vat_id' => $standard,
                'notes' => 'Auto-renewed monthly. Unused hours do not carry over.',
                'status' => 'active',
            ],
            [
                'reference' => 'SRV-004',
                'name' => 'Project Management (hourly)',
                'description' => 'Dedicated project management resource billed per hour.',
                'price' => 60.00,
                'vat_id' => $standard,
                'status' => 'active',
            ],
            [
                'reference' => 'FOOD-001',
                'name' => 'Organic Bread Loaf',
                'description' => 'Artisan sourdough bread, 800g.',
                'price' => 3.50,
                'vat_id' => $reduced,
                'status' => 'active',
            ],
            [
                'reference' => 'FOOD-002',
                'name' => 'Olive Oil Extra Virgin (0.75L)',
                'description' => 'Cold-pressed Alentejo extra virgin olive oil.',
                'price' => 8.90,
                'vat_id' => $reduced,
                'notes' => 'Shelf life: 18 months from bottling date.',
                'status' => 'active',
            ],
            [
                'reference' => 'ELEC-001',
                'name' => 'Wireless Keyboard & Mouse Set',
                'description' => 'Ergonomic wireless keyboard and mouse combo, USB receiver.',
                'price' => 49.99,
                'vat_id' => $standard,
                'status' => 'active',
            ],
            [
                'reference' => 'ELEC-002',
                'name' => 'USB-C Hub (7-in-1)',
                'description' => '7-port USB-C hub: HDMI 4K, USB 3.0 x3, SD/microSD, PD 100W.',
                'price' => 39.90,
                'vat_id' => $standard,
                'status' => 'active',
            ],
            [
                'reference' => 'MED-001',
                'name' => 'General Health Consultation',
                'description' => 'Standard medical consultation with a general practitioner.',
                'price' => 45.00,
                'vat_id' => $exempt,
                'notes' => 'VAT exempt under health services regulation.',
                'status' => 'active',
            ],
            [
                'reference' => 'BLDG-001',
                'name' => 'Concrete Block (per unit)',
                'description' => 'Standard hollow concrete block 20x20x40cm.',
                'price' => 1.20,
                'vat_id' => $intermediate,
                'status' => 'active',
            ],
            [
                'reference' => 'BLDG-002',
                'name' => 'Portland Cement (25kg bag)',
                'description' => 'CEM I 52.5 N Portland cement, 25kg bag.',
                'price' => 8.50,
                'vat_id' => $intermediate,
                'status' => 'inactive',
            ],
        ];

        $existing = Article::pluck('reference')->toArray();

        foreach ($articles as $data) {
            if (! in_array($data['reference'], $existing, true)) {
                Article::create($data);
            }
        }

        $this->command->info('ArticleSeeder: '.count($articles).' articles processed.');
    }
}
