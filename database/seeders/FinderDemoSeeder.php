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
        // The operational `facilities` table (tenants) is not linked to the
        // `care_facilities` directory in every environment — care_facilities.facility_id
        // is NULL in production — so the old directory-join fallback resolved to NULL and
        // left the demo facility accounts with no facility. A facility-scoped role with no
        // facility bounces off RequireFacilityContext straight to /login, so the whole
        // facility-admin demo was a login loop. Resolve by type instead, and CREATE a demo
        // hospital when the estate has none, so the accounts always get a real facility.
        $pharmacy = DB::table('facilities')->where('name', 'Pharmacy ESSEC')->first()
            ?? DB::table('facilities')->where('type', 'pharmacy')->first()
            ?? DB::table('facilities')->first();

        $hospital = DB::table('facilities')->where('name', 'Hôpital Central de Yaoundé')->first()
            ?? DB::table('facilities')->where('type', 'hospital')->first();
        if (! $hospital) {
            $hospitalId = (string) Str::uuid();
            DB::table('facilities')->insert([
                'id'         => $hospitalId,
                'name'       => 'Hôpital Central de Yaoundé',
                'type'       => 'hospital',
                'status'     => 'active',
                'is_demo'    => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $hospital = DB::table('facilities')->where('id', $hospitalId)->first();
        }

        $accounts = [
            // Admin — bypasses facility context, sees the whole directory.
            ['demo.admin@opescare.test',          'Demo — Platform Admin',    'platform_admin', null],
            // Facility types.
            ['demo.pharmacist@opescare.test',     'Demo — Pharmacy',          'pharmacist',     $pharmacy?->id],
            ['demo.facility.admin@opescare.test', 'Demo — Facility Admin',    'facility_admin', $hospital?->id],
            ['demo.facility.ceo@opescare.test',   'Demo — Facility Executive','facility_ceo',   $hospital?->id],
            // Front desk — the clinical operations desk (/facility): appointments,
            // blood inventory, messages. Facility-scoped so RequireFacilityContext resolves.
            ['demo.receptionist@opescare.test',   'Demo — Front Desk',        'receptionist',   $hospital?->id],
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
        // Operational blood stock for the /facility blood console, which reads
        // blood_inventories by the operational facility_id. Keyed on the demo
        // hospital's own id so the receptionist sees real counts, not an empty desk.
        if ($hospital) {
            $this->seedBlood($hospital->id);
        }

        // Seed a few realistic notifications for the demo accounts so the bell
        // and notification centre show real content the moment you log in.
        $this->call(DemoNotificationsSeeder::class);
    }

    private function seedBlood(string $facilityId): void
    {
        if (! DB::getSchemaBuilder()->hasTable('blood_inventories')) {
            return;
        }

        // Real integer counts — the operational console shows the number; the
        // public Blood Finder derives its coarse ranges from these via
        // BloodAvailabilityProjector. B-/AB- kept at 0 so the "unavailable"
        // state is represented on the shelf too.
        $stock = [
            ['O+',  'whole_blood', 15],
            ['O-',  'whole_blood', 4],
            ['A+',  'whole_blood', 30],
            ['A-',  'whole_blood', 3],
            ['B+',  'whole_blood', 8],
            ['B-',  'whole_blood', 0],
            ['AB+', 'whole_blood', 8],
            ['AB-', 'whole_blood', 0],
        ];

        foreach ($stock as [$group, $component, $units]) {
            \App\Models\BloodInventory::updateOrCreate(
                ['facility_id' => $facilityId, 'blood_group' => $group, 'component' => $component],
                ['available_units' => $units, 'last_stock_update' => now()]
            );
        }

        $this->command?->info('  seeded 8 blood groups on the demo hospital');
    }
}
