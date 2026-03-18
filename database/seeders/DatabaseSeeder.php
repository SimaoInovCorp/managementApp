<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            VatRateSeeder::class,
            PermissionSeeder::class,
            UserSeeder::class,
            EntitySeeder::class,
            ContactSeeder::class,
            ArticleSeeder::class,
            ProposalSeeder::class,
            CustomerOrderSeeder::class,
            SupplierOrderSeeder::class,
            WorkOrderSeeder::class,
            BankAccountSeeder::class,
            CustomerAccountSeeder::class,
            SupplierInvoiceSeeder::class,
            CalendarEventSeeder::class,
            DigitalArchiveSeeder::class,
        ]);
    }
}
