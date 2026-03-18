<?php

namespace Database\Seeders;

use App\Models\Entity;
use App\Models\SupplierInvoice;
use App\Models\SupplierOrder;
use App\Services\SupplierInvoiceService;
use Illuminate\Database\Seeder;

/**
 * Seeds demo supplier invoices. Safe to re-run.
 */
class SupplierInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        if (SupplierInvoice::exists()) {
            $this->command->info('SupplierInvoiceSeeder: skipped (data already exists).');

            return;
        }

        $service = new SupplierInvoiceService;
        $europarts = Entity::where('name', 'Euro Parts GmbH')->first();

        if (! $europarts) {
            $this->command->warn('SupplierInvoiceSeeder: Euro Parts GmbH not found. Skipping.');

            return;
        }

        // Invoice 1 — linked to supplier order 1, paid
        $order1 = SupplierOrder::where('number', 1)->first();

        SupplierInvoice::create([
            'number' => $service->nextNumber(),
            'invoice_date' => '2026-02-15',
            'due_date' => '2026-03-15',
            'supplier_id' => $europarts->id,
            'supplier_order_id' => $order1?->id,
            'total_amount' => 1840.50,
            'document_path' => null,
            'payment_proof_path' => null,
            'status' => 'paid',
        ]);

        // Invoice 2 — standalone, pending
        SupplierInvoice::create([
            'number' => $service->nextNumber(),
            'invoice_date' => '2026-03-01',
            'due_date' => '2026-03-31',
            'supplier_id' => $europarts->id,
            'supplier_order_id' => null,
            'total_amount' => 640.00,
            'document_path' => null,
            'payment_proof_path' => null,
            'status' => 'pending',
        ]);

        $this->command->info('SupplierInvoiceSeeder: 2 supplier invoices seeded.');
    }
}
