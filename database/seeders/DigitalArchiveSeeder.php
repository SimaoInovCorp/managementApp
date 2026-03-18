<?php

namespace Database\Seeders;

use App\Models\DigitalArchive;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds demo digital archive metadata records.
 * No actual files are uploaded — path values are placeholders.
 * Safe to re-run.
 */
class DigitalArchiveSeeder extends Seeder
{
    public function run(): void
    {
        if (DigitalArchive::exists()) {
            $this->command->info('DigitalArchiveSeeder: skipped (data already exists).');

            return;
        }

        $admin = User::first();

        if (! $admin) {
            $this->command->warn('DigitalArchiveSeeder: No user found. Skipping.');

            return;
        }

        DigitalArchive::insert([
            [
                'name' => 'Company Certificate of Incorporation',
                'path' => 'archive/demo-cert.pdf',
                'category' => 'Legal',
                'entity_id' => null,
                'description' => 'Official certificate of incorporation from the Commercial Registry.',
                'uploaded_by' => $admin->id,
                'created_at' => now(),
            ],
            [
                'name' => 'Supplier Framework Agreement — Euro Parts',
                'path' => 'archive/demo-agreement.pdf',
                'category' => 'Contracts',
                'entity_id' => null,
                'description' => 'Signed framework supply agreement with Euro Parts GmbH.',
                'uploaded_by' => $admin->id,
                'created_at' => now(),
            ],
        ]);

        $this->command->info('DigitalArchiveSeeder: 2 archive records seeded (demo paths).');
    }
}
