<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo organisations + per-role demo accounts, one facility per facility type.
 *
 * Builds 8 "Opes{Type}" demo facilities (architecture §2) and, for each, one demo
 * user per applicable role (architecture §4) with an active facility_role_assignment.
 *
 * Idempotent — safe to run repeatedly. Facilities use deterministic UUIDs and are
 * matched on a stable id; users/assignments are matched on stable keys
 * (email / user×facility×role) so reseeding never duplicates.
 *
 * Conventions (match existing demo seeders):
 *   - facilities: is_demo=true, status 'active_demo'
 *   - users:      is_demo=true, status 'active', password 'DemoPass!2026'
 *   - role_id + is_demo are guarded on User — set explicitly via forceFill.
 *   - email convention: {role}@{slug}.opes.test  (slug = type w/o 'opes' prefix)
 */
class OpesDemoOrgsSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'DemoPass!2026';

    /**
     * Facility definitions: [name, type, slug, deterministic-uuid-suffix, [roles...]].
     * Role lists come verbatim from architecture §4.
     */
    private function facilities(): array
    {
        return [
            [
                'name'  => 'OpesHospital',
                'type'  => 'hospital',
                'slug'  => 'hospital',
                'uuid'  => '00000000-0000-0000-0000-101000000001',
                'roles' => [
                    'hospital_admin', 'doctor', 'specialist', 'nurse', 'ward_nurse',
                    'triage_nurse', 'receptionist', 'front_desk', 'labtech', 'lab_manager',
                    'pharmacist', 'pharmacy_manager', 'cashier', 'billing_officer',
                    'finance_manager', 'records_officer', 'data_steward',
                ],
            ],
            [
                'name'  => 'OpesClinic',
                'type'  => 'clinic',
                'slug'  => 'clinic',
                'uuid'  => '00000000-0000-0000-0000-101000000002',
                'roles' => [
                    'clinic_admin', 'doctor', 'nurse', 'receptionist', 'cashier',
                    'labtech', 'pharmacist',
                ],
            ],
            [
                'name'  => 'OpesPharmacy',
                'type'  => 'pharmacy',
                'slug'  => 'pharmacy',
                'uuid'  => '00000000-0000-0000-0000-101000000003',
                'roles' => [
                    'pharmacy_manager', 'pharmacist', 'pharmacy_technician',
                    'dispensing_officer', 'medicine_stock', 'cashier',
                ],
            ],
            [
                'name'  => 'OpesLab',
                'type'  => 'laboratory',
                'slug'  => 'laboratory',
                'uuid'  => '00000000-0000-0000-0000-101000000004',
                'roles' => [
                    'lab_manager', 'lab_scientist', 'labtech', 'lab_validator',
                    'sample_collection', 'cashier',
                ],
            ],
            [
                'name'  => 'OpesInsurance',
                'type'  => 'insurance',
                'slug'  => 'insurance',
                'uuid'  => '00000000-0000-0000-0000-101000000005',
                'roles' => [
                    'insurance_admin', 'insurance_reviewer', 'insurance_claims',
                    'insurance_preauth', 'insurance_finance',
                ],
            ],
            [
                'name'  => 'OpesHealthOrg',
                'type'  => 'health_org',
                'slug'  => 'healthorg',
                'uuid'  => '00000000-0000-0000-0000-101000000006',
                'roles' => [
                    'ngo_admin', 'health_program_manager', 'outreach_team',
                    'mobile_clinic_team',
                ],
            ],
            [
                'name'  => 'OpesDeveloper',
                'type'  => 'developer',
                'slug'  => 'developer',
                'uuid'  => '00000000-0000-0000-0000-101000000007',
                'roles' => [
                    'developer_org_admin', 'developer', 'api_partner', 'api_technical',
                    'webhook_manager', 'sandbox_developer',
                ],
            ],
            [
                'name'  => 'OpesLite',
                'type'  => 'lite',
                'slug'  => 'lite',
                'uuid'  => '00000000-0000-0000-0000-101000000008',
                'roles' => [
                    'lite_facility', 'lite_staff', 'lite_device', 'lite_offline_sync',
                ],
            ],
        ];
    }

    /** Deterministic pool of Cameroonian-style display names. */
    private array $namePool = [
        'Amina Nkeng', 'Emmanuel Fonkou', 'Brenda Achu', 'Joseph Tabe',
        'Solange Mbarga', 'Daniel Ngwa', 'Grace Ekane', 'Patrick Njoya',
        'Clarisse Atangana', 'Samuel Bello', 'Vanessa Tchoua', 'Ernest Mballa',
        'Marlyse Eyong', 'Roland Kemajou', 'Yvonne Ngassa', 'Felix Tandap',
        'Diane Mafany',
    ];

    public function run(): void
    {
        foreach ($this->facilities() as $def) {
            $facility = Facility::withoutDemoIsolation()
                ->firstOrNew(['id' => $def['uuid']]);
            $facility->name   = $def['name'];
            $facility->type   = $def['type'];
            $facility->status = 'active_demo';
            $facility->forceFill(['is_demo' => true]);
            $facility->save();

            $nameIdx = 0;
            foreach ($def['roles'] as $roleName) {
                $roleId = Role::where('name', $roleName)->value('id');
                if (!$roleId) {
                    $this->command?->warn(
                        "OpesDemoOrgsSeeder: role '{$roleName}' not found — skipping ({$def['name']})."
                    );
                    continue;
                }

                $email   = $roleName . '@' . $def['slug'] . '.opes.test';
                $person  = $this->namePool[$nameIdx % count($this->namePool)];
                $nameIdx++;
                $display = $this->titleize($roleName);

                // role_id + is_demo are guarded — set via forceFill (firstOrNew, not firstOrCreate).
                $user = User::withoutDemoIsolation()->firstOrNew(['email' => $email]);
                $user->name                = "{$person} ({$display})";
                $user->password            = Hash::make(self::DEMO_PASSWORD);
                $user->primary_facility_id = $facility->id;
                $user->status              = 'active';
                $user->forceFill(['role_id' => $roleId, 'is_demo' => true]);
                $user->save();

                // Active per-facility role assignment (idempotent on user×facility×role).
                $assignment = FacilityRoleAssignment::firstOrNew([
                    'user_id'     => $user->id,
                    'facility_id' => $facility->id,
                    'role_id'     => $roleId,
                ]);
                $assignment->is_active   = true;
                $assignment->assigned_at = $assignment->assigned_at ?? now();
                $assignment->expires_at  = null;
                $assignment->save();
            }
        }
    }

    private function titleize(string $roleName): string
    {
        return ucwords(str_replace('_', ' ', $roleName));
    }
}
