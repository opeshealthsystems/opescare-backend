<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

/**
 * The five internal demo facilities the demo org fixtures hang off.
 *
 * These are NOT the patient-facing directory — that is `care_facilities`, which
 * holds 897 real MINSANTE institutions. These five exist only so demo staff
 * accounts, roles and org fixtures have a `facilities` row to belong to, and
 * they are flagged `is_demo` so the demo reset can reach them.
 *
 * Idempotent by primary key: this used to call Facility::create() in a loop,
 * which threw a unique-violation on the second run and aborted the whole
 * `php artisan db:seed` part-way through — after the registry seeders had
 * already written, so the database was left half-seeded with no clear signal
 * about what had and had not run.
 */
class DemoFacilitiesSeeder extends Seeder
{
    private const FACILITIES = [
        ['id' => '00000000-0000-0000-0000-100000000001', 'name' => 'Demo Central Hospital',      'type' => 'hospital',   'status' => 'active_demo', 'is_demo' => true],
        ['id' => '00000000-0000-0000-0000-100000000002', 'name' => 'Demo City Clinic',           'type' => 'clinic',     'status' => 'active_demo', 'is_demo' => true],
        ['id' => '00000000-0000-0000-0000-100000000003', 'name' => 'Demo Specialist Hospital',   'type' => 'hospital',   'status' => 'active_demo', 'is_demo' => true],
        ['id' => '00000000-0000-0000-0000-100000000004', 'name' => 'DemoCare Pharmacy',          'type' => 'pharmacy',   'status' => 'active_demo', 'is_demo' => true],
        ['id' => '00000000-0000-0000-0000-100000000005', 'name' => 'Demo Diagnostic Laboratory', 'type' => 'laboratory', 'status' => 'active_demo', 'is_demo' => true],
    ];

    public function run(): void
    {
        foreach (self::FACILITIES as $facility) {
            Facility::withoutGlobalScopes()->updateOrCreate(
                ['id' => $facility['id']],
                $facility
            );
        }

        $this->command?->info(
            'DemoFacilitiesSeeder: ' . count(self::FACILITIES) . ' demo facility(ies) ready.'
        );
    }
}
