<?php

namespace Database\Seeders\Passport;

use App\Enums\SelfReportedVitalMeasure;
use Database\Seeders\Support\DemoStoryIds;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Demonstration home readings for page 25 of the Health Passport.
 *
 * The demo estate already records vitals taken AT A FACILITY —
 * DemoPatientVitalsSeeder writes two triage sets at Hôpital Central de Yaoundé
 * — but nothing has ever been written to `patient_recorded_vitals`, so the
 * half of the page that matters most is blank on the deployed demo: the half
 * that shows what the patient measured herself, and shows it kept apart from
 * what a nurse measured. A reviewer cannot tell whether the separation works
 * if only one side of it has rows.
 *
 * ── THE DIARY FITS THE STORY THE REST OF THE DEMO ALREADY TELLS ──────────
 *
 * DEMO_DATA_BRIEF.md: a woman in her thirties in Yaoundé with Type 2 diabetes
 * (2023) and hypertension (2025), on Metformin and Amlodipine, reviewed
 * quarterly at Hôpital Central de Yaoundé, blood pressure persistently above
 * target. These are the readings that story actually generates:
 *
 *   BLOOD PRESSURE — a morning diary running nine weeks, the thing every
 *     clinic asks a hypertensive patient to keep. It starts above target in the
 *     150s/90s, and comes down into the 130s/80s over the weeks after the
 *     review three weeks ago. That is the point of a diary: a run of numbers
 *     that means something the single clinic reading cannot.
 *
 *   TEMPERATURE — two readings nineteen and eighteen days ago, an evening fever
 *     of 38.4 °C and the following morning's 37.6 °C. The seeded laboratory
 *     record has a malaria rapid test eighteen days ago, reported negative.
 *     The home thermometer is why she went in.
 *
 *   BLOOD SUGAR — four home strip readings, three fasting and one after a meal.
 *     The seeded laboratory fasting glucose six days ago is 5.1 mmol/L, so the
 *     home fasting readings sit just above it, where a strip meter sits.
 *     The one post-meal reading exists so the page has to carry a measurement
 *     context: SelfReportedVitalMeasure records that applying a fasting band to
 *     a post-meal sample "would call a healthy patient diabetic", and a demo
 *     that only ever shows fasting readings never exercises that.
 *
 *   WEIGHT — three readings drifting gently down from 75.8 kg, consistent with
 *     the 75.2 kg and 74.5 kg the facility recorded on the same page and with a
 *     care plan targeting glycaemic control.
 *
 * NOTHING IS FLAGGED, here or on the page. The values are chosen to be
 * clinically coherent, not to be dramatic: no home hypertensive crisis, no
 * hypoglycaemia. A demo that invents an emergency the rest of the record does
 * not corroborate teaches a reviewer the wrong thing about the page.
 *
 * ── BOUNDARIES ───────────────────────────────────────────────────────────
 *
 *  - `patient_recorded_vitals` has NO `is_demo` column, so the patient the row
 *    hangs off is the only isolation there is. Every write here is gated on
 *    `patients.is_demo` AND on the patient's own record carrying the
 *    indication: the diary, the weights and the thermometer readings go only to
 *    a demo patient with a recorded hypertension diagnosis, and the glucose
 *    readings only where diabetes is recorded too. The demo child, the
 *    emergency fixture and every real patient are therefore never touched.
 *  - `provenance` and `source_system` are NEVER written. The column has a
 *    database CHECK pinning provenance to 'self_reported' and a default that
 *    sets it; a seeder that passed the value would be a write path that could
 *    lie about it, which is the one thing that table exists to prevent.
 *  - Idempotent. Every row is keyed on a UUID derived from the patient id and a
 *    stable key (Support\DemoStoryIds), so a second run rewrites the same rows
 *    with their dates recomputed relative to the new "now".
 *  - Nothing is voided. `voided_at` stays null so these read as live entries;
 *    the retraction path is covered by tests, not by pretending the demo
 *    patient mistyped.
 *  - Discovered and run by DemoHealthPassportSeeder; registered nowhere.
 */
class VitalsDemoSeeder extends Seeder
{
    /**
     * The morning blood-pressure diary: [days ago, hour, minute, systolic,
     * diastolic, note]. Above target, then improving after the review.
     */
    private const BLOOD_PRESSURE = [
        [63, 7, 10, 152, 94, 'Morning, before my tablet'],
        [56, 7,  5, 148, 92, null],
        [42, 6, 55, 150, 96, 'Slept badly'],
        [28, 7, 15, 146, 90, null],
        [21, 7,  0, 144, 88, null],
        [14, 7,  5, 138, 86, null],
        [7,  6, 50, 136, 84, null],
        [3,  7, 10, 134, 82, 'Sitting, after five minutes rest'],
    ];

    /** [days ago, hour, minute, mmol/L, context, note] */
    private const BLOOD_SUGAR = [
        [21, 6, 40, 6.4, 'fasting',    null],
        [13, 20, 30, 7.8, 'after_meal', 'Two hours after dinner'],
        [7,  6, 45, 5.8, 'fasting',    null],
        [2,  6, 35, 5.6, 'fasting',    'Before breakfast'],
    ];

    /** [days ago, hour, minute, kg] */
    private const WEIGHT = [
        [56, 7, 30, 75.8],
        [28, 7, 25, 75.1],
        [5,  7, 20, 74.6],
    ];

    /** [days ago, hour, minute, °C, note] — the fever that sent her to the clinic. */
    private const TEMPERATURE = [
        [19, 21, 40, 38.4, 'Feeling hot and shivery'],
        [18, 7, 20, 37.6, 'Still warm this morning'],
    ];

    private const CUFF        = 'Omron M2 home cuff';
    private const METER       = 'Accu-Chek Active strip meter';
    private const SCALE       = 'Bathroom scale';
    private const THERMOMETER = 'Digital thermometer';

    public function run(): void
    {
        $patients = $this->hypertensiveDemoPatients();

        if ($patients->isEmpty()) {
            $this->command?->info('VitalsDemoSeeder: no demo patient carries a hypertension diagnosis — nothing seeded.');

            return;
        }

        $now     = Carbon::now();
        $written = 0;

        foreach ($patients as $patient) {
            $enteredBy = $this->patientsOwnUser($patient->email);

            foreach (self::BLOOD_PRESSURE as $i => [$daysAgo, $hour, $minute, $systolic, $diastolic, $note]) {
                $written += $this->write($patient->id, 'bp:' . $i, [
                    'measure'         => SelfReportedVitalMeasure::BloodPressure->value,
                    'value_primary'   => $systolic,
                    'value_secondary' => $diastolic,
                    'unit'            => SelfReportedVitalMeasure::BloodPressure->defaultUnit(),
                    'device_label'    => self::CUFF,
                    'note'            => $note,
                    'measured_at'     => $now->copy()->subDays($daysAgo)->setTime($hour, $minute),
                ], $enteredBy);
            }

            foreach (self::WEIGHT as $i => [$daysAgo, $hour, $minute, $kilograms]) {
                $written += $this->write($patient->id, 'weight:' . $i, [
                    'measure'         => SelfReportedVitalMeasure::Weight->value,
                    'value_primary'   => $kilograms,
                    'value_secondary' => null,
                    'unit'            => SelfReportedVitalMeasure::Weight->defaultUnit(),
                    'device_label'    => self::SCALE,
                    'note'            => null,
                    'measured_at'     => $now->copy()->subDays($daysAgo)->setTime($hour, $minute),
                ], $enteredBy);
            }

            foreach (self::TEMPERATURE as $i => [$daysAgo, $hour, $minute, $celsius, $note]) {
                $written += $this->write($patient->id, 'temperature:' . $i, [
                    'measure'         => SelfReportedVitalMeasure::Temperature->value,
                    'value_primary'   => $celsius,
                    'value_secondary' => null,
                    'unit'            => SelfReportedVitalMeasure::Temperature->defaultUnit(),
                    'device_label'    => self::THERMOMETER,
                    'note'            => $note,
                    'measured_at'     => $now->copy()->subDays($daysAgo)->setTime($hour, $minute),
                ], $enteredBy);
            }

            if (! $this->hasDiabetes($patient->id)) {
                continue;
            }

            foreach (self::BLOOD_SUGAR as $i => [$daysAgo, $hour, $minute, $mmol, $context, $note]) {
                $written += $this->write($patient->id, 'glucose:' . $i, [
                    'measure'             => SelfReportedVitalMeasure::BloodSugar->value,
                    'value_primary'       => $mmol,
                    'value_secondary'     => null,
                    // Recorded in the unit the meter shows, and stored in it.
                    // The enum accepts mg/dL and mmol/L side by side precisely
                    // so nothing has to be converted; a factor-of-18 mistake in
                    // that conversion is a dosing error.
                    'unit'                => 'mmol/L',
                    'measurement_context' => $context,
                    'device_label'        => self::METER,
                    'note'                => $note,
                    'measured_at'         => $now->copy()->subDays($daysAgo)->setTime($hour, $minute),
                ], $enteredBy);
            }
        }

        $this->command?->info("VitalsDemoSeeder: {$written} home reading(s) for {$patients->count()} demo patient(s).");
    }

    // ── Who keeps a diary ────────────────────────────────────────────────

    /**
     * The demo patients whose own record asks for these readings.
     *
     * Matched on the ICD-10 code the clinical-core seeder writes (I10), with a
     * text fallback for a record that carries the condition under a name rather
     * than a code — the same test RadiologyDemoSeeder applies, so the two pages
     * agree about who this patient is.
     *
     * @return Collection<int,object>
     */
    private function hypertensiveDemoPatients(): Collection
    {
        $ids = DB::table('diagnoses')
            ->where(fn ($q) => $q->where('code', 'I10')->orWhere('display_name', 'ilike', '%hypertens%'))
            ->distinct()
            ->pluck('patient_id');

        if ($ids->isEmpty()) {
            return collect();
        }

        return DB::table('patients')
            ->where('is_demo', true)
            ->whereIn('id', $ids)
            ->orderBy('created_at')
            ->get(['id', 'email', 'health_id']);
    }

    private function hasDiabetes(string $patientId): bool
    {
        return DB::table('diagnoses')
            ->where('patient_id', $patientId)
            ->where(fn ($q) => $q->where('code', 'like', 'E11%')->orWhere('display_name', 'ilike', '%diabet%'))
            ->exists();
    }

    /**
     * The user account behind the patient's own login, for the audit trail.
     *
     * `patients` has no user column on this estate; the login is matched by
     * email, exactly as the mobile auth path matches it. Null when the demo
     * patient has no account — the column is nullable for that case and a
     * fabricated author would be worse than none.
     */
    private function patientsOwnUser(?string $email): ?string
    {
        if (! $email) {
            return null;
        }

        $id = DB::table('users')->where('email', $email)->value('id');

        return $id ? (string) $id : null;
    }

    // ── Writing ──────────────────────────────────────────────────────────

    /**
     * One reading.
     *
     * `updateOrInsert` on a derived id, so a re-run refreshes the same row's
     * date relative to the new "now" instead of stacking a second diary on top
     * of the first.
     *
     * @param  array<string,mixed>  $attributes
     */
    private function write(string $patientId, string $key, array $attributes, ?string $enteredBy): int
    {
        $id = DemoStoryIds::derived($patientId, 'passport-vitals:' . $key);

        DB::table('patient_recorded_vitals')->updateOrInsert(
            ['id' => $id],
            $attributes + [
                'patient_id'          => $patientId,
                'measurement_context' => null,
                'entered_by_user_id'  => $enteredBy,
                // A demo reading is a live entry, not a retracted one.
                'voided_at'           => null,
                'void_reason'         => null,
                'created_at'          => $attributes['measured_at'],
                'updated_at'          => Carbon::now(),
            ]
        );

        return 1;
    }
}
