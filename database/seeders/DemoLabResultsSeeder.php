<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\ReferenceRange;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Demo laboratory data for finder.opescare.cloud.
 *
 * Everything written here is marked `is_demo = true` and carries a
 * `demo_seed_key`, so re-running the seeder updates its own rows instead of
 * stacking duplicate panels, and a demo reset can find them.
 *
 * ── The demo is designed to prove the honest paths, not to look tidy ──────
 *
 * It deliberately contains:
 *
 *   - a CRITICAL result the laboratory did NOT flag (Potassium 6.9 mmol/L),
 *     derived from the published critical threshold. This is the safety case:
 *     a critical value must reach the patient as critical even when the
 *     analyser's flag column was left empty.
 *   - an ABNORMAL result the laboratory DID flag (Haemoglobin, flag "L").
 *   - an UNFLAGGED result — Ferritin — for which no reference range is
 *     published and the laboratory sent none. It must render "Not recorded",
 *     never a number and never "Normal". No range is seeded for it ON PURPOSE.
 *   - a FREE-TEXT result (Malaria RDT, "Negative") that is not chartable.
 *   - a PENDING order with no results at all, which the sample-collection
 *     screen lists as awaiting a sample.
 *   - three Haemoglobin results across three months, so the trend screen has a
 *     real series rather than a single point.
 *
 * The reference intervals seeded into `reference_ranges` are labelled
 * `source = 'demo_seed'`. They are illustrative adult intervals for the demo
 * estate; they are not a clinical authority and are not presented as one.
 */
class DemoLabResultsSeeder extends Seeder
{
    /** Prefix for every demo_seed_key this seeder owns. */
    private const KEY = 'demo_labs_v1';

    public function run(): void
    {
        $patient = $this->demoPatient();

        if ($patient === null) {
            $this->command?->warn('DemoLabResultsSeeder: no demo patient found — nothing seeded.');

            return;
        }

        $facility = $this->demoLaboratory();

        if ($facility === null) {
            $this->command?->warn('DemoLabResultsSeeder: no demo laboratory facility found — nothing seeded.');

            return;
        }

        $this->seedReferenceRanges();
        $this->seedCatalogue($facility);
        $this->seedOrders($patient, $facility);
        $this->seedLabVisit($patient, $facility);

        $this->command?->info('DemoLabResultsSeeder: laboratory demo data seeded for ' . $patient->health_id . '.');
    }

    // ── Anchors ──────────────────────────────────────────────────────────────

    private function demoPatient(): ?Patient
    {
        // withoutGlobalScopes so the seeder works whether or not the process is
        // running with OPESCARE_DEMO_MODE on; the row it wants is explicitly
        // the demo one either way.
        return Patient::query()
            ->withoutGlobalScopes()
            ->where('is_demo', true)
            ->orderBy('created_at')
            ->first();
    }

    /**
     * A demo facility that is registered AND listed on the care map, so the
     * collection screen has a real address to show.
     *
     * @return object{facility_id: string, care_facility_id: string, name: string}|null
     */
    private function demoLaboratory(): ?object
    {
        $row = DB::table('facilities as f')
            ->join('care_facilities as cf', 'cf.facility_id', '=', 'f.id')
            ->where('f.is_demo', true)
            ->whereNotNull('cf.address')
            ->orderByRaw("case when lower(f.type) = 'laboratory' then 0 when lower(f.type) = 'hospital' then 1 else 2 end")
            ->first(['f.id as facility_id', 'cf.id as care_facility_id', 'f.name as name']);

        return $row === null ? null : (object) [
            'facility_id'      => (string) $row->facility_id,
            'care_facility_id' => (string) $row->care_facility_id,
            'name'             => (string) $row->name,
        ];
    }

    private function clinicianId(): ?string
    {
        return User::query()
            ->withoutGlobalScopes()
            ->where('is_demo', true)
            ->where('name', 'ilike', '%Dr.%')
            ->value('id');
    }

    private function labTechId(): ?string
    {
        return User::query()
            ->withoutGlobalScopes()
            ->where('is_demo', true)
            ->where('name', 'ilike', '%Lab%')
            ->value('id')
            ?? $this->clinicianId();
    }

    // ── Reference ranges ─────────────────────────────────────────────────────

    /**
     * Published intervals for the demo analytes.
     *
     * NOTE the omission: Ferritin is NOT here. That is the point — the demo has
     * to show what the app does with a result nobody can compare to anything.
     */
    private function seedReferenceRanges(): void
    {
        $ranges = [
            // [test_code, test_name, unit, sex, age_group, low, high, crit_low, crit_high]
            ['HGB',  'Haemoglobin',            'g/dL',    'female', 'adult', 12.0,  16.0,  7.0,  20.0],
            ['HGB',  'Haemoglobin',            'g/dL',    'male',   'adult', 13.0,  17.0,  7.0,  20.0],
            ['WBC',  'White Cell Count',       '10^9/L',  'all',    'adult', 4.0,   11.0,  1.5,  30.0],
            ['PLT',  'Platelet Count',         '10^9/L',  'all',    'adult', 150.0, 400.0, 50.0, 1000.0],
            ['K',    'Potassium',              'mmol/L',  'all',    'adult', 3.5,   5.1,   2.5,  6.5],
            ['NA',   'Sodium',                 'mmol/L',  'all',    'adult', 135.0, 145.0, 120.0, 160.0],
            ['CREA', 'Creatinine',             'umol/L',  'female', 'adult', 53.0,  97.0,  null, 500.0],
            ['CREA', 'Creatinine',             'umol/L',  'male',   'adult', 62.0,  106.0, null, 500.0],
            ['GLUF', 'Fasting Blood Glucose',  'mmol/L',  'all',    'adult', 3.9,   5.5,   2.2,  22.2],
        ];

        foreach ($ranges as [$code, $name, $unit, $sex, $ageGroup, $low, $high, $critLow, $critHigh]) {
            ReferenceRange::query()->updateOrCreate(
                [
                    'test_code' => $code,
                    'sex'       => $sex,
                    'age_group' => $ageGroup,
                    'facility_id' => null,
                ],
                [
                    'test_name'     => $name,
                    'loinc_code'    => null,
                    'unit'          => $unit,
                    'normal_low'    => $low,
                    'normal_high'   => $high,
                    'critical_low'  => $critLow,
                    'critical_high' => $critHigh,
                    // Not a clinical authority — illustrative intervals for the
                    // demo estate, and labelled as such.
                    'source'        => 'demo_seed',
                    'is_active'     => true,
                ]
            );
        }
    }

    // ── Orderable catalogue ──────────────────────────────────────────────────

    /** What the demo laboratory publishes as orderable. */
    private function seedCatalogue(object $facility): void
    {
        $tests = [
            ['FBC',  'Full Blood Count',           'Whole blood (EDTA)'],
            ['RFT',  'Renal Function Test',        'Serum'],
            ['LFT',  'Liver Function Test',        'Serum'],
            ['LIPID', 'Lipid Panel',               'Serum (fasting)'],
            ['GLUF', 'Fasting Blood Glucose',      'Fluoride plasma'],
            ['HBA1C', 'Glycated Haemoglobin',      'Whole blood (EDTA)'],
            ['MRDT', 'Malaria Rapid Diagnostic Test', 'Whole blood'],
            ['FERR', 'Ferritin',                   'Serum'],
            ['TSH',  'Thyroid Stimulating Hormone', 'Serum'],
            ['URIC', 'Urine Culture',              'Mid-stream urine'],
        ];

        foreach ($tests as [$code, $name, $specimen]) {
            $existing = DB::table('lab_test_availability')
                ->where('facility_id', $facility->care_facility_id)
                ->where('local_test_code', $code)
                ->value('id');

            $row = [
                'facility_id'                      => $facility->care_facility_id,
                'test_name'                        => $name,
                'local_test_code'                  => $code,
                'loinc_code'                       => null,
                'specimen_type'                    => $specimen,
                'turnaround_time'                  => null,
                'price'                            => null,
                'currency'                         => 'XAF',
                'requires_doctor_order'            => true,
                'sample_collection_available'      => true,
                'home_sample_collection_available' => false,
                'availability_status'              => 'available',
                // The catalogue was published by this seeder, now. Freshness is
                // never backdated to look more current than it is.
                'freshness_status'                 => 'fresh',
                'last_updated_at'                  => now(),
                'updated_at'                       => now(),
            ];

            if ($existing !== null) {
                DB::table('lab_test_availability')->where('id', $existing)->update($row);
                continue;
            }

            DB::table('lab_test_availability')->insert($row + [
                'id'         => (string) Str::uuid(),
                'created_at' => now(),
            ]);
        }
    }

    // ── Orders and results ───────────────────────────────────────────────────

    private function seedOrders(Patient $patient, object $facility): void
    {
        $clinician = $this->clinicianId();
        $tech      = $this->labTechId();

        /*
         * Each entry: key, panel name, code, status, days ago, clinician note,
         * and the analytes.
         *
         * An analyte is [name, value, unit, laboratory's own flag, the
         * laboratory's own free-text range, note]. A null flag means the
         * laboratory published none and the API has to derive one — or say it
         * could not.
         */
        $orders = [
            [
                'key'      => 'fbc_1',
                'name'     => 'Full Blood Count',
                'code'     => 'FBC',
                'status'   => 'resulted',
                'days_ago' => 92,
                'note'     => 'Anaemia screen. Iron studies added on the same sample.',
                'results'  => [
                    // Flagged by the laboratory itself.
                    ['Haemoglobin', '9.8', 'g/dL', 'L', '12.0 – 16.0', 'Microcytic picture; iron studies advised.'],
                    ['White Cell Count', '7.2', '10^9/L', null, null, null],
                    ['Platelet Count', '268', '10^9/L', null, null, null],
                    // No laboratory flag AND no published range: the app must
                    // render this "Not recorded", not a number and not "Normal".
                    ['Ferritin', '42', 'ng/mL', null, null, null],
                ],
            ],
            [
                'key'      => 'fbc_2',
                'name'     => 'Full Blood Count',
                'code'     => 'FBC',
                'status'   => 'resulted',
                'days_ago' => 44,
                'note'     => null,
                'results'  => [
                    ['Haemoglobin', '10.6', 'g/dL', 'L', '12.0 – 16.0', null],
                    ['White Cell Count', '6.9', '10^9/L', null, null, null],
                    ['Platelet Count', '284', '10^9/L', null, null, null],
                ],
            ],
            [
                'key'      => 'renal_1',
                'name'     => 'Renal Function Test',
                'code'     => 'RFT',
                'status'   => 'resulted',
                'days_ago' => 41,
                'note'     => null,
                'results'  => [
                    // THE SAFETY CASE. The analyser published no flag. 6.9 is
                    // above the published critical threshold of 6.5, so the API
                    // must raise it to `critical` on its own.
                    ['Potassium', '6.9', 'mmol/L', null, null, null],
                    ['Sodium', '139', 'mmol/L', null, null, null],
                    ['Creatinine', '84', 'umol/L', null, null, null],
                ],
            ],
            [
                'key'      => 'mrdt_1',
                'name'     => 'Malaria Rapid Diagnostic Test',
                'code'     => 'MRDT',
                'status'   => 'resulted',
                'days_ago' => 18,
                'note'     => null,
                'results'  => [
                    // Free text — a real result, but not a point on any line.
                    ['Malaria RDT (P. falciparum)', 'Negative', null, 'neg', 'Negative', null],
                ],
            ],
            [
                'key'      => 'fbc_3',
                'name'     => 'Full Blood Count',
                'code'     => 'FBC',
                'status'   => 'resulted',
                'days_ago' => 13,
                'note'     => 'Haemoglobin recovering on oral iron. Repeat in six weeks.',
                'results'  => [
                    ['Haemoglobin', '11.4', 'g/dL', 'L', '12.0 – 16.0', 'Improving on treatment.'],
                    ['White Cell Count', '6.4', '10^9/L', null, null, null],
                    ['Platelet Count', '301', '10^9/L', null, null, null],
                ],
            ],
            [
                'key'      => 'glucose_1',
                'name'     => 'Fasting Blood Glucose',
                'code'     => 'GLUF',
                'status'   => 'resulted',
                'days_ago' => 6,
                'note'     => null,
                'results'  => [
                    ['Fasting Blood Glucose', '5.1', 'mmol/L', null, null, null],
                ],
            ],
            [
                // No results at all: awaiting a sample. The dashboard counts it
                // as pending and the collection screen lists it.
                'key'      => 'lipid_pending',
                'name'     => 'Lipid Panel',
                'code'     => 'LIPID',
                'status'   => 'ordered',
                'days_ago' => 2,
                'note'     => 'Fasting sample required.',
                'results'  => [],
            ],
        ];

        foreach ($orders as $spec) {
            $orderedAt = now()->subDays($spec['days_ago'])->setTime(8, 30);
            $resulted  = $spec['results'] !== [];
            $seedKey   = self::KEY . ':' . $spec['key'];

            $order = LabOrder::query()
                ->withoutGlobalScopes()
                ->where('demo_seed_key', $seedKey)
                ->first();

            $attributes = [
                'patient_id'          => $patient->id,
                'facility_id'         => $facility->facility_id,
                'visit_id'            => null,
                'ordered_by'          => $clinician,
                'test_name'           => $spec['name'],
                'test_code'           => $spec['code'],
                'urgency'             => $spec['key'] === 'renal_1' ? 'urgent' : 'routine',
                'status'              => $spec['status'],
                'clinical_indication' => null,
                'notes'               => $spec['note'],
                'ordered_at'          => $orderedAt,
                'collected_at'        => $resulted ? $orderedAt->copy()->addHours(1) : null,
                'resulted_at'         => $resulted ? $orderedAt->copy()->addHours(9) : null,
                'is_demo'             => true,
                'demo_seed_key'       => $seedKey,
            ];

            if ($order === null) {
                $order = LabOrder::query()->create($attributes);
            } else {
                // updateQuietly: nothing about this order is a real clinical
                // event, and the seeder must not fire result-created listeners
                // on a re-run.
                $order->updateQuietly($attributes);
            }

            $this->seedResults($order, $patient, $spec, $orderedAt, $tech);
        }
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function seedResults(LabOrder $order, Patient $patient, array $spec, \Illuminate\Support\Carbon $orderedAt, ?string $tech): void
    {
        foreach ($spec['results'] as $index => [$analyte, $value, $unit, $flag, $reference, $note]) {
            $seedKey = self::KEY . ':' . $spec['key'] . ':' . $index;

            $attributes = [
                'lab_order_id'    => $order->id,
                'patient_id'      => $patient->id,
                'parameter_name'  => $analyte,
                'value'           => $value,
                'unit'            => $unit,
                'reference_range' => $reference,
                'flag'            => $flag,
                'notes'           => $note,
                'verified_by'     => $tech,
                'resulted_at'     => $orderedAt->copy()->addHours(9),
                'is_demo'         => true,
                'demo_seed_key'   => $seedKey,
            ];

            $existing = LabResult::query()
                ->withoutGlobalScopes()
                ->where('demo_seed_key', $seedKey)
                ->first();

            if ($existing === null) {
                LabResult::query()->create($attributes);
                continue;
            }

            $existing->updateQuietly($attributes);
        }
    }

    // ── The lab visit the sample is given at ─────────────────────────────────

    private function seedLabVisit(Patient $patient, object $facility): void
    {
        $existing = Appointment::query()
            ->where('patient_id', $patient->id)
            ->where('appointment_type', 'laboratory')
            ->where('facility_id', $facility->facility_id)
            ->first();

        $attributes = [
            'patient_id'            => $patient->id,
            'facility_id'           => $facility->facility_id,
            'appointment_type'      => 'laboratory',
            'status'                => 'booked',
            'scheduled_at'          => now()->addDays(3)->setTime(7, 30),
            'reason'                => 'Fasting sample for the lipid panel.',
            'billing_deferred'      => false,
            'telemedicine_deferred' => false,
        ];

        if ($existing === null) {
            Appointment::query()->create($attributes);

            return;
        }

        $existing->updateQuietly($attributes);
    }
}
