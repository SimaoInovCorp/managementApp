<?php

namespace Database\Seeders;

use App\Models\CalendarEvent;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds demo calendar events. Safe to re-run.
 */
class CalendarEventSeeder extends Seeder
{
    public function run(): void
    {
        if (CalendarEvent::exists()) {
            $this->command->info('CalendarEventSeeder: skipped (data already exists).');

            return;
        }

        $admin = User::first();
        $techSolutions = Entity::where('name', 'Tech Solutions Lda')->first();

        if (! $admin) {
            $this->command->warn('CalendarEventSeeder: No user found. Skipping.');

            return;
        }

        CalendarEvent::insert([
            [
                'title' => 'Kickoff Meeting \u2014 Tech Solutions',
                'date' => '2026-03-20',
                'time' => '10:00',
                'duration_minutes' => 60,
                'user_id' => $admin->id,
                'entity_id' => $techSolutions?->id,
                'type_id' => null,
                'action_id' => null,
                'description' => 'Initial project kickoff meeting to align on requirements and timeline.',
                'shared_with' => json_encode([]),
                'knowledge' => null,
                'status' => 'confirmed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Euro Parts \u2014 Quarterly Review Call',
                'date' => '2026-03-25',
                'time' => '14:00',
                'duration_minutes' => 30,
                'user_id' => $admin->id,
                'entity_id' => null,
                'type_id' => null,
                'action_id' => null,
                'description' => 'Quarterly performance review call with Euro Parts GmbH account manager.',
                'shared_with' => json_encode([]),
                'knowledge' => 'Discuss pricing for next quarter bulk order.',
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->command->info('CalendarEventSeeder: 2 calendar events seeded.');
    }
}
