<?php

namespace Database\Seeders;

use App\Models\CareFacility;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo accounts for the OpesCare Finder — one per surface a real operator uses.
 *
 * The platform's DemoDatabaseSeeder pulls in ~30 seeders for modules this
 * product no longer has (insurance, clinical records, public health). This one
 * seeds ONLY what the Finder can actually show: an admin console, the pharmacy
 * stock console, the facility desk (appointments + blood), a developer portal,
 * and a patient. It is idempotent — safe to run repeatedly.
 *
 * The one-click demo panel on the login page (config('demo.enabled')) already
 * references these exact emails; enabling OPESCARE_DEMO_MODE lights it up.
 * Every account is stamped is_demo = true so DemoAccessController::loginAs will
 * accept it, and password "demo1234" works on the normal login form too.
 *
 * Facility-scoped roles get BOTH primary_facility_id (so RequireFacilityContext
 * seeds the session) AND a FacilityRoleAssignment (so roleAtFacility resolves
 * without falling back to the global role) — the pairing this codebase requires,
 * learned the hard way.
 */
class FinderDemoSeeder extends Seeder
{
    private const PASSWORD = 'demo1234';

    public function run(): void
    {
        // Real tenants so the dashboards open populated, not empty.
        $pharmacy = DB::table('facilities')->where('name', 'Pharmacy ESSEC')->first()
            ?? DB::table('facilities')->whereIn('id', function ($q) {
                $q->select('facility_id')->from('care_facilities')->where('facility_type', 'pharmacy')->whereNotNull('facility_id');
            })->first();

        $hospital = DB::table('facilities')->where('name', 'Hôpital Central de Yaoundé')->first()
            ?? DB::table('facilities')->whereIn('id', function ($q) {
                $q->select('facility_id')->from('care_facilities')->where('facility_type', 'hospital')->whereNotNull('facility_id');
            })->first();

        $accounts = [
            // Admin — bypasses facility context, sees the whole directory.
            ['demo.admin@opescare.test',          'Demo — Platform Admin',    'platform_admin', null],
            // Facility types.
            ['demo.pharmacist@opescare.test',     'Demo — Pharmacy',          'pharmacist',     $pharmacy?->id],
            ['demo.facility.admin@opescare.test', 'Demo — Facility Admin',    'facility_admin', $hospital?->id],
            ['demo.facility.ceo@opescare.test',   'Demo — Facility Executive','facility_ceo',   $hospital?->id],
            // Developer — API credentials self-service.
            ['demo.developer@opescare.test',      'Demo — Developer',         'developer',      null],
            // Patient — the booking side, so the whole loop is walkable.
            ['demo.patient@opescare.test',        'Demo — Patient',           'patient',        null],
        ];

        foreach ($accounts as [$email, $name, $roleName, $facilityId]) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                $this->command?->warn("  skip {$email}: role {$roleName} not found");
                continue;
            }

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name'                => $name,
                    'password'            => Hash::make(self::PASSWORD),
                    'status'              => 'active',
                    'is_demo'             => true,
                    'role_id'             => $role->id,
                    'primary_facility_id' => $facilityId,
                    'email_verified_at'   => now(),
                ]
            );

            if ($facilityId) {
                FacilityRoleAssignment::updateOrCreate(
                    ['user_id' => $user->id, 'facility_id' => $facilityId, 'role_id' => $role->id],
                    ['is_active' => true, 'expires_at' => null, 'assigned_at' => now()]
                );
            }

            $this->command?->info("  {$email}  ({$roleName})" . ($facilityId ? "  → facility scoped" : ''));
        }

        // Give the demo hospital some published blood so the blood console and
        // the public Blood Finder both have something to show. source_system
        // 'portal' = genuinely reported, so it behaves exactly like real data.
        if ($hospital) {
            $listing = CareFacility::where('facility_id', $hospital->id)->first();
            if ($listing) {
                $this->seedBlood($listing->id);
            }
        }
    }

    private function seedBlood(string $listingId): void
    {
        if (! DB::getSchemaBuilder()->hasTable('blood_availability')) {
            return;
        }

        $stock = [
            ['O+',  'whole_blood', '11-20', 'available'],
            ['O-',  'whole_blood', '1-5',   'available'],
            ['A+',  'whole_blood', '21-50', 'available'],
            ['A-',  'whole_blood', '1-5',   'available'],
            ['B+',  'whole_blood', '6-10',  'available'],
            ['B-',  'whole_blood', '0',     'unavailable'],
            ['AB+', 'whole_blood', '6-10',  'available'],
            ['AB-', 'whole_blood', '0',     'unavailable'],
        ];

        foreach ($stock as [$group, $component, $range, $status]) {
            DB::table('blood_availability')->updateOrInsert(
                ['facility_id' => $listingId, 'blood_group' => $group, 'component_type' => $component],
                [
                    'id'                    => (string) Str::uuid(),
                    'units_available_range' => $range,
                    'availability_status'   => $status,
                    'freshness_status'      => 'fresh',
                    'source_system'         => 'portal',
                    'last_updated_at'       => now(),
                    'updated_at'            => now(),
                    'created_at'            => now(),
                ]
            );
        }

        $this->command?->info('  seeded 8 blood groups on the demo hospital');
    }
}
