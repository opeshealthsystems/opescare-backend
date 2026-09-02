<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Assigns roles and primary_facility_id to every demo user.
 *
 * Must run AFTER DemoFacilitiesSeeder (facilities) and RolesSeeder (roles).
 * Idempotent – safe to run multiple times.
 */
class DemoFacilityAssignmentsSeeder extends Seeder
{
    public function run(): void
    {
        // Resolve primary demo facility
        $facilityId = DB::table('facilities')
            ->where('is_demo', true)
            ->value('id');

        if (!$facilityId) {
            $this->command?->warn('DemoFacilityAssignmentsSeeder: no demo facility found — skipping.');
            return;
        }

        // Fetch all needed role IDs in one query
        $roleNames = [
            'doctor', 'multi_doctor', 'nurse', 'specialist', 'pharmacist',
            'labtech', 'facility_admin', 'facility_ceo', 'finance',
            'insurance_claims', 'insurance_preauth', 'patient', 'guardian',
            'platform_admin', 'developer',
        ];
        $roles = DB::table('roles')->whereIn('name', $roleNames)->pluck('id', 'name');

        // Roles that belong to patient-side portals — these users don't need a facility
        $noFacility = ['patient', 'guardian', 'developer'];

        // Map: demo email → role name
        $assignments = [
            'demo.doctor@opescare.test'         => 'doctor',
            'demo.multi.doctor@opescare.test'   => 'multi_doctor',
            'demo.nurse@opescare.test'           => 'nurse',
            'demo.specialist@opescare.test'      => 'specialist',
            'demo.pharmacist@opescare.test'      => 'pharmacist',
            'demo.labtech@opescare.test'         => 'labtech',
            'demo.facility.admin@opescare.test'  => 'facility_admin',
            'demo.facility.ceo@opescare.test'    => 'facility_ceo',
            'demo.finance@opescare.test'         => 'finance',
            'demo.insurance@opescare.test'       => 'insurance_claims',
            'demo.preauth@opescare.test'         => 'insurance_preauth',
            'demo.patient@opescare.test'         => 'patient',
            'demo.guardian@opescare.test'        => 'guardian',
            'demo.admin@opescare.test'           => 'platform_admin',
            'demo.developer@opescare.test'       => 'developer',
        ];

        foreach ($assignments as $email => $roleName) {
            $roleId = $roles->get($roleName);
            if (!$roleId) {
                continue; // Role not seeded yet – skip silently
            }

            DB::table('users')
                ->where('email', $email)
                ->update([
                    'role_id'             => $roleId,
                    'primary_facility_id' => in_array($roleName, $noFacility) ? null : $facilityId,
                    'updated_at'          => now(),
                ]);
        }
    }
}
