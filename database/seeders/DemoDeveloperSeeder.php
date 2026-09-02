<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the demo developer user and its sandbox API integration client.
 *
 * Runs after DemoUsersSeeder and DemoFacilityAssignmentsSeeder.
 * Idempotent – safe to run multiple times.
 */
class DemoDeveloperSeeder extends Seeder
{
    /** Stable UUID for the sandbox integration client record */
    private const CLIENT_UUID = '00000000-0000-0000-0000-300000000001';

    public function run(): void
    {
        $facilityId = DB::table('facilities')->where('is_demo', true)->value('id');
        $devRoleId  = DB::table('roles')->where('name', 'developer')->value('id');

        // ── Demo developer user ──────────────────────────────────────────────
        $devUserId = '00000000-0000-0000-0000-200000000050';

        $exists = DB::table('users')->where('email', 'demo.developer@opescare.test')->exists();
        if (!$exists) {
            DB::table('users')->insert([
                'id'                => $devUserId,
                'name'              => 'API Developer (Demo)',
                'email'             => 'demo.developer@opescare.test',
                'password'          => Hash::make('DemoPass!2026'),
                'is_demo'           => true,
                'role_id'           => $devRoleId,
                'primary_facility_id' => null,
                'status'            => 'active',
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        } else {
            DB::table('users')->where('email', 'demo.developer@opescare.test')->update([
                'role_id'    => $devRoleId,
                'updated_at' => now(),
            ]);
            $devUserId = DB::table('users')->where('email', 'demo.developer@opescare.test')->value('id');
        }

        // ── Sandbox API integration client ───────────────────────────────────
        $clientExists = DB::table('integration_clients')
            ->where('client_id', 'demo_dev_sandbox')
            ->exists();

        if (!$clientExists) {
            DB::table('integration_clients')->insert([
                'id'            => self::CLIENT_UUID,
                'name'          => 'Demo Developer Sandbox Client',
                'client_id'     => 'demo_dev_sandbox',
                'client_secret' => Hash::make('demo_secret_sandbox_2026'),
                'facility_id'   => $facilityId,
                'scopes'        => json_encode([
                    'pharmacy:stock:read',
                    'blood:inventory:read',
                    'patient:diagnostics:read',
                    'lab:results:read',
                    'patient:profile:read',
                ]),
                'status'        => 'active',
                'environment'   => 'sandbox',
                'created_by'    => $devUserId,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }
}
