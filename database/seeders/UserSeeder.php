<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the default test / admin user.
 *
 * Safe to re-run — uses updateOrCreate to avoid duplicates.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'spmmazb@gmail.com'],
            [
                'name' => 'Simao Morais',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );

        // Ensure the Administrator role is assigned
        if (! $user->hasRole('Administrator')) {
            $user->assignRole('Administrator');
        }

        $this->command->info("UserSeeder: user \"{$user->name}\" ready.");
    }
}
