<?php

namespace Database\Seeders;

use App\Concerns\PermissionConstants;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeds all application permissions and creates the default "Administrator"
 * role with full access to every module.
 *
 * Safe to re-run (uses firstOrCreate).
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions to avoid stale state
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create every permission string
        foreach (PermissionConstants::all() as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        // 2. Create the Administrator role and grant all permissions
        $admin = Role::firstOrCreate([
            'name' => 'Administrator',
            'guard_name' => 'web',
        ]);

        $admin->syncPermissions(PermissionConstants::all());

        // 3. Assign the Administrator role to the first registered user (if any)
        $firstUser = User::first();

        if ($firstUser && ! $firstUser->hasRole('Administrator')) {
            $firstUser->assignRole('Administrator');
        }

        $this->command->info('PermissionSeeder: '.count(PermissionConstants::all()).' permissions seeded. Administrator role created.');
    }
}
