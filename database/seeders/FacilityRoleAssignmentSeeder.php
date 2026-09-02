<?php
namespace Database\Seeders;

use App\Models\FacilityRoleAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;

class FacilityRoleAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Migrating global role_id assignments to per-facility assignments...');

        $migrated = 0;
        $skipped  = 0;

        User::whereNotNull('role_id')
            ->whereNotNull('primary_facility_id')
            ->chunkById(100, function ($users) use (&$migrated, &$skipped) {
                foreach ($users as $user) {
                    $exists = FacilityRoleAssignment::where('user_id', $user->id)
                        ->where('facility_id', $user->primary_facility_id)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    FacilityRoleAssignment::create([
                        'user_id'     => $user->id,
                        'facility_id' => $user->primary_facility_id,
                        'role_id'     => $user->role_id,
                        'is_active'   => true,
                        'assigned_by' => null,
                        'assigned_at' => $user->created_at,
                    ]);
                    $migrated++;
                }
            });

        $this->command->info("Done. Migrated: {$migrated} | Skipped (already exists): {$skipped}");
    }
}
