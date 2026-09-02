<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Demo roles are already created by the base RolesSeeder.
 * This seeder is a no-op kept for interface compatibility.
 */
class DemoRolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Base roles are seeded by RolesSeeder (runs in DatabaseSeeder before demo data).
        // No additional demo-specific role/permission overrides required.
    }
}
