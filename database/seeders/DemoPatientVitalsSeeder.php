<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Database\Seeders\Support\DemoFacilityResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo vitals for the ONE demo patient, so the mobile Health Vitals card has
 * something real to render.
 *
 * Deliberate boundaries:
 *   - Touches exactly one patient (self::PATIENT) and one facility — her real
 *     hospital, resolved from the directory at run time. It will NOT run
 *     against a database where either is missing, so it can never attach
 *     invented readings to an arbitrary facility or to a real patient.
 *   - Everything it writes carries a stable UUID in the reserved
 *     00000000-0000-0000-00xx- demo space and every row is prefixed/labelled
 *     as demo data (visit `is_demo`, triage complaint, lab notes).
 *   - Idempotent: re-running inserts nothing new.
 *   - NOT registered in DatabaseSeeder — run it explicitly:
 *         php artisan db:seed --class=DemoPatientVitalsSeeder
 *
 * Values mirror the home-dashboard design reference (HR 72, BP 120/80,
 * SpO2 98%, blood sugar 102 mg/dL) so the built screen can be compared to it
 * side by side. They are within adult reference ranges on purpose — the
 * out-of-range rendering path is covered by tests
 * (tests/Feature/Mobile/MobileVitalsTest.php), not by pretending the demo
 * patient is unwell.
 */
class DemoPatientVitalsSeeder extends Seeder
{
    private const PATIENT  = '00000000-0000-0000-0000-300000000001'; // Demo Patient One
    // Resolved at run time to her real hospital; the literal below is only a
    // fallback for a database whose directory has not been seeded yet.
    private const FACILITY_FALLBACK = '00000000-0000-0000-0000-100000000001';

    private string $facility = self::FACILITY_FALLBACK;
    private const NURSE    = '00000000-0000-0000-0000-200000000010'; // Nurse Fatou
    private const DOCTOR   = '00000000-0000-0000-0000-200000000001'; // Dr. Amara Diallo

    private const VISIT      = '00000000-0000-0000-0011-100000000001';
    private const TRIAGE     = '00000000-0000-0000-0012-100000000001';
    private const LAB_ORDER  = '00000000-0000-0000-0013-100000000001';
    private const LAB_RESULT = '00000000-0000-0000-0014-100000000001';

    public function run(): void
    {
        if (! DB::table('patients')->where('id', self::PATIENT)->exists()) {
            $this->command?->warn('DemoPatientVitalsSeeder skipped: demo patient not present.');
            return;
        }

        $this->facility = DemoFacilityResolver::primaryHospital() ?? self::FACILITY_FALLBACK;

        if (! DB::table('facilities')->where('id', $this->facility)->exists()) {
            $this->command?->warn('DemoPatientVitalsSeeder skipped: no resolvable facility.');
            return;
        }

        $nurse  = DB::table('users')->where('id', self::NURSE)->exists() ? self::NURSE : null;
        $doctor = DB::table('users')->where('id', self::DOCTOR)->exists() ? self::DOCTOR : null;

        // The reference screen reads "Last updated: Today, 7:30 AM".
        $today     = Carbon::today()->setTime(7, 30);
        $lastWeek  = Carbon::today()->subDays(9)->setTime(11, 15);

        $this->upsertVisit($lastWeek);
        $this->upsertTriage($nurse);
        $this->upsertVitals($today, $lastWeek, $nurse);
        $this->upsertGlucose($today, $doctor);

        $this->command?->info('DemoPatientVitalsSeeder: demo vitals ready for ' . self::PATIENT . '.');
    }

    /** One closed demo visit to hang the triage record off. */
    private function upsertVisit(Carbon $startedAt): void
    {
        if (DB::table('visits')->where('id', self::VISIT)->exists()) {
            return;
        }

        DB::table('visits')->insert([
            'id'          => self::VISIT,
            'patient_id'  => self::PATIENT,
            'facility_id' => $this->facility,
            'provider_id' => DB::table('users')->where('id', self::DOCTOR)->exists() ? self::DOCTOR : null,
            'visit_type'  => 'outpatient',
            'status'      => 'closed',
            'started_at'  => $startedAt,
            'ended_at'    => $startedAt->copy()->addMinutes(45),
            'is_demo'     => true,
            'created_at'  => $startedAt,
            'updated_at'  => $startedAt,
        ]);
    }

    private function upsertTriage(?string $nurse): void
    {
        if (DB::table('triage_records')->where('id', self::TRIAGE)->exists()) {
            return;
        }

        DB::table('triage_records')->insert([
            'id'                   => self::TRIAGE,
            'visit_id'             => self::VISIT,
            'nurse_id'             => $nurse,
            'presenting_complaint' => 'DEMO DATA — routine wellness check, no complaint',
            'pain_score'           => 0,
            'acuity_score'         => 'V',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
    }

    /**
     * Two readings into `triage_vital_signs` — the table that carries
     * `patient_id` and a real `recorded_at`. Two rows, not one, so the
     * endpoint's "newest wins per measure" merge is exercised by real data.
     */
    private function upsertVitals(Carbon $today, Carbon $lastWeek, ?string $nurse): void
    {
        $rows = [
            [
                'id'                  => '00000000-0000-0000-0015-100000000001',
                'recorded_at'         => $today,
                'temperature'         => 36.8,
                'pulse_rate'          => 72,
                'respiratory_rate'    => 16,
                'systolic_bp'         => 120,
                'diastolic_bp'        => 80,
                'oxygen_saturation'   => 98,
                'weight_kg'           => 74.50,
                'height_cm'           => 176.00,
            ],
            [
                'id'                  => '00000000-0000-0000-0015-100000000002',
                'recorded_at'         => $lastWeek,
                'temperature'         => 37.1,
                'pulse_rate'          => 78,
                'respiratory_rate'    => 17,
                'systolic_bp'         => 124,
                'diastolic_bp'        => 82,
                'oxygen_saturation'   => 97,
                'weight_kg'           => 75.20,
                'height_cm'           => 176.00,
            ],
        ];

        foreach ($rows as $row) {
            if (DB::table('triage_vital_signs')->where('id', $row['id'])->exists()) {
                continue;
            }

            DB::table('triage_vital_signs')->insert(array_merge($row, [
                'triage_record_id'    => self::TRIAGE,
                'visit_id'            => self::VISIT,
                'patient_id'          => self::PATIENT,
                'gcs_score'           => 15,
                'pain_score'          => 0,
                'consciousness_level' => 'alert',
                'recorded_by'         => $nurse ?? self::NURSE,
                'created_at'          => $row['recorded_at'],
                'updated_at'          => $row['recorded_at'],
            ]));
        }
    }

    /**
     * Blood sugar has no vitals column in the schema — the only real source is
     * a published lab result, so the demo seeds one, flagged `normal` by the
     * "laboratory" exactly as a real result would be.
     */
    private function upsertGlucose(Carbon $today, ?string $doctor): void
    {
        if (! DB::table('lab_orders')->where('id', self::LAB_ORDER)->exists()) {
            DB::table('lab_orders')->insert([
                'id'                  => self::LAB_ORDER,
                'patient_id'          => self::PATIENT,
                'facility_id'         => $this->facility,
                'visit_id'            => self::VISIT,
                'ordered_by'          => $doctor,
                'test_name'           => 'DEMO DATA — Random blood glucose',
                'test_code'           => 'GLU',
                'urgency'             => 'routine',
                'status'              => 'resulted',
                'clinical_indication' => 'Demo dataset for the mobile Health Vitals card',
                'ordered_at'          => $today->copy()->subHours(1),
                'collected_at'        => $today->copy()->subMinutes(45),
                'resulted_at'         => $today,
                'created_at'          => $today,
                'updated_at'          => $today,
            ]);
        }

        if (DB::table('lab_results')->where('id', self::LAB_RESULT)->exists()) {
            return;
        }

        DB::table('lab_results')->insert([
            'id'              => self::LAB_RESULT,
            'lab_order_id'    => self::LAB_ORDER,
            'patient_id'      => self::PATIENT,
            'parameter_name'  => 'Glucose (random)',
            'value'           => '102',
            'unit'            => 'mg/dL',
            'reference_range' => '70 - 140',
            'flag'            => 'normal',
            'notes'           => 'DEMO DATA — seeded by DemoPatientVitalsSeeder',
            'resulted_at'     => $today,
            'created_at'      => $today,
            'updated_at'      => $today,
        ]);
    }
}
