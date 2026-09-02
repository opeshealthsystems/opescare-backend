<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Attaches service listings (departments a patient can browse and book against)
 * to REAL Cameroonian facilities from the registry.
 *
 * This seeder used to invent three fake directory entries — "Demo Central
 * Hospital", "Demo City Clinic", "DemoCare Pharmacy" — and hang the services
 * off those. Because `care_facilities` is the public, patient-facing directory
 * (MobileFacilityController and the Care Map both read it, and it has no
 * is_demo column to isolate on), those fixtures showed up in the real facility
 * list alongside — and above — genuine MINSANTE-sourced institutions. Demo
 * scaffolding must never appear in the real institutional directory, so the
 * services are now attached to real facilities looked up by name.
 *
 * Idempotent. Skips any facility that isn't in the registry yet (run
 * CameroonFacilityRegistrySeeder + CareMapRegistryStubSeeder first) rather
 * than failing, so ordering never breaks a fresh install.
 */
class DemoServicesSeeder extends Seeder
{
    /**
     * Real facilities (matched on facility_name) and the services each offers.
     * Service ids are deterministic so re-running never duplicates a row.
     */
    private const SERVICE_MAP = [
        'Hôpital Central de Yaoundé' => [
            ['id' => '00000000-0000-0000-0019-100000000001', 'name' => 'Emergency Medicine',                'category' => 'emergency',    'walk_in' => true],
            ['id' => '00000000-0000-0000-0019-100000000002', 'name' => 'General Outpatient Consultation',   'category' => 'consultation', 'walk_in' => true],
            ['id' => '00000000-0000-0000-0019-100000000003', 'name' => 'Cardiology',                        'category' => 'specialist',   'walk_in' => false],
            ['id' => '00000000-0000-0000-0019-100000000004', 'name' => 'Clinical Laboratory',               'category' => 'diagnostic',   'walk_in' => true],
            ['id' => '00000000-0000-0000-0019-100000000005', 'name' => 'Blood Bank',                        'category' => 'blood_bank',   'walk_in' => false],
            ['id' => '00000000-0000-0000-0019-100000000006', 'name' => 'Inpatient / General Ward',          'category' => 'inpatient',    'walk_in' => false],
        ],
        'Hôpital Laquintinie de Douala' => [
            ['id' => '00000000-0000-0000-0019-100000000007', 'name' => 'Family Medicine',                   'category' => 'consultation', 'walk_in' => true],
            ['id' => '00000000-0000-0000-0019-100000000008', 'name' => 'Antenatal Care',                    'category' => 'maternal',     'walk_in' => false],
            ['id' => '00000000-0000-0000-0019-100000000009', 'name' => 'Child Immunisation',                'category' => 'paediatric',   'walk_in' => true],
        ],
        'Pharmacie Centrale de Yaoundé' => [
            ['id' => '00000000-0000-0000-0019-100000000010', 'name' => 'Dispensing & Retail Pharmacy',      'category' => 'pharmacy',     'walk_in' => true],
            ['id' => '00000000-0000-0000-0019-100000000011', 'name' => 'Medication Counselling',            'category' => 'pharmacy',     'walk_in' => true],
        ],
    ];

    public function run(): void
    {
        $attached = 0;
        $skipped  = 0;

        foreach (self::SERVICE_MAP as $facilityName => $services) {
            $facilityId = DB::table('care_facilities')
                ->where('facility_name', $facilityName)
                ->where('country_code', 'CM')
                ->value('id');

            if (! $facilityId) {
                $this->command?->warn(
                    "DemoServicesSeeder: '{$facilityName}' not found in care_facilities — skipping its services. "
                    . 'Run CameroonFacilityRegistrySeeder + CareMapRegistryStubSeeder first.'
                );
                $skipped += count($services);
                continue;
            }

            foreach ($services as $s) {
                if (DB::table('care_facility_services')->where('id', $s['id'])->exists()) {
                    continue;
                }

                DB::table('care_facility_services')->insert([
                    'id'                   => $s['id'],
                    'facility_id'          => $facilityId,
                    'service_name'         => $s['name'],
                    'service_category'     => $s['category'],
                    'availability_status'  => 'available',
                    'appointment_required' => ! $s['walk_in'],
                    'walk_in_allowed'      => $s['walk_in'],
                    'last_updated_at'      => now(),
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);

                $attached++;
            }
        }

        $this->command?->info(
            "DemoServicesSeeder: {$attached} service(s) attached to real facilities, {$skipped} skipped."
        );
    }
}
