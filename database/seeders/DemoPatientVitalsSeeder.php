<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Database\Seeders\Support\DemoCareTeam;
use Database\Seeders\Support\DemoFacilityResolver;
use Database\Seeders\Support\TargetsDemoPatient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo vitals for a demo patient, so the mobile Health Vitals card has
 * something real to render.
 *
 * Deliberate boundaries:
 *   - Touches exactly one patient per run (the canonical demo patient, or the
 *     one given to `forPatient()`) and one facility — the real hospital,
 *     resolved from the directory at run time. It will NOT run against a
 *     database where either is missing, so it can never attach invented
 *     readings to an arbitrary facility or to a real patient.
 *   - Every row carries a stable UUID (reserved 00000000-0000-0000-00xx- space
 *     for the canonical patient, derived for anyone else) and is labelled as
 *     demo data (visit `is_demo`, triage complaint, lab notes).
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
    use TargetsDemoPatient;

    private const VISIT      = '00000000-0000-0000-0011-100000000001';
    private const TRIAGE     = '00000000-0000-0000-0012-100000000001';
    private const LAB_ORDER  = '00000000-0000-0000-0013-100000000001';
    private const LAB_RESULT = '00000000-0000-0000-0014-100000000001';
    private const VITALS_TODAY     = '00000000-0000-0000-0015-100000000001';
    private const VITALS_LAST_WEEK = '00000000-0000-0000-0015-100000000002';

    private string $facility = '';

    public function run(): void
    {
        if (! DB::table('patients')->where('id', $this->patientId())->exists()) {
            $this->command?->warn('DemoPatientVitalsSeeder skipped: demo patient not present.');
            return;
        }

        $facility = DemoFacilityResolver::primaryHospital();

        if (! $facility || ! DB::table('facilities')->where('id', $facility)->exists()) {
            $this->command?->warn('DemoPatientVitalsSeeder skipped: no resolvable facility.');
            return;
        }
        $this->facility = $facility;

        $nurse  = DemoCareTeam::nurse();
        $doctor = DemoCareTeam::doctor();

        // The reference screen reads "Last updated: Today, 7:30 AM".
        $today    = Carbon::today()->setTime(7, 30);
        $lastWeek = Carbon::today()->subDays(9)->setTime(11, 15);

        $this->upsertVisit($lastWeek, $doctor);
        $this->upsertTriage($nurse);
        $this->upsertVitals($today, $lastWeek, $nurse ?? $doctor);
        $this->upsertGlucose($today, $doctor);

        $this->command?->info('DemoPatientVitalsSeeder: demo vitals ready for ' . $this->patientId() . '.');
    }

    /** One closed demo visit to hang the triage record off. */
    private function upsertVisit(Carbon $startedAt, ?string $doctor): void
    {
        $id = $this->uid(self::VISIT);

        if (DB::table('visits')->where('id', $id)->exists()) {
            return;
        }

        DB::table('visits')->insert([
            'id'          => $id,
            'patient_id'  => $this->patientId(),
            'facility_id' => $this->facility,
            'provider_id' => $doctor,
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
        $id = $this->uid(self::TRIAGE);

        if (DB::table('triage_records')->where('id', $id)->exists()) {
            return;
        }

        DB::table('triage_records')->insert([
            'id'                   => $id,
            'visit_id'             => $this->uid(self::VISIT),
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
    private function upsertVitals(Carbon $today, Carbon $lastWeek, ?string $recordedBy): void
    {
        $rows = [
            ['id' => self::VITALS_TODAY,     'recorded_at' => $today,    'temperature' => 36.8, 'pulse_rate' => 72, 'respiratory_rate' => 16, 'systolic_bp' => 120, 'diastolic_bp' => 80, 'oxygen_saturation' => 98, 'weight_kg' => 74.50, 'height_cm' => 176.00],
            ['id' => self::VITALS_LAST_WEEK, 'recorded_at' => $lastWeek, 'temperature' => 37.1, 'pulse_rate' => 78, 'respiratory_rate' => 17, 'systolic_bp' => 124, 'diastolic_bp' => 82, 'oxygen_saturation' => 97, 'weight_kg' => 75.20, 'height_cm' => 176.00],
        ];

        foreach ($rows as $row) {
            $id = $this->uid($row['id']);
            unset($row['id']);

            if (DB::table('triage_vital_signs')->where('id', $id)->exists()) {
                continue;
            }

            DB::table('triage_vital_signs')->insert(array_merge($row, [
                'id'                  => $id,
                'triage_record_id'    => $this->uid(self::TRIAGE),
                'visit_id'            => $this->uid(self::VISIT),
                'patient_id'          => $this->patientId(),
                'gcs_score'           => 15,
                'pain_score'          => 0,
                'consciousness_level' => 'alert',
                'recorded_by'         => $recordedBy,
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
        $orderId  = $this->uid(self::LAB_ORDER);
        $resultId = $this->uid(self::LAB_RESULT);

        if (! DB::table('lab_orders')->where('id', $orderId)->exists()) {
            DB::table('lab_orders')->insert([
                'id'                  => $orderId,
                'patient_id'          => $this->patientId(),
                'facility_id'         => $this->facility,
                'visit_id'            => $this->uid(self::VISIT),
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

        if (DB::table('lab_results')->where('id', $resultId)->exists()) {
            return;
        }

        DB::table('lab_results')->insert([
            'id'              => $resultId,
            'lab_order_id'    => $orderId,
            'patient_id'      => $this->patientId(),
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
