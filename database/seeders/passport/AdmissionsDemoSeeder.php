<?php

namespace Database\Seeders\Passport;

use Database\Seeders\Support\DemoCareTeam;
use Database\Seeders\Support\DemoFacilityResolver;
use Database\Seeders\Support\DemoStoryIds;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The demo patient's inpatient history — page 09 (Hospital Admissions) and
 * page 19 (Discharge Summary).
 *
 * WHY THIS EXISTS. No demo patient had a single row in `admissions`, so both
 * pages read as "nothing recorded". That is an honest sentence about the record
 * but it tells a reviewer nothing about whether the pages work, and the two
 * pages differ in exactly the way an empty table cannot show: page 09 lists
 * every stay, page 19 lists only the ones that concluded.
 *
 * THE STORY IT FITS. DemoPatientStorySeeder / DemoClinicalCoreSeeder already
 * tell one coherent history (DEMO_DATA_BRIEF.md): type 2 diabetes diagnosed
 * three years ago at Hôpital Central de Yaoundé, essential hypertension
 * eighteen months ago, quarterly reviews since, a severe penicillin allergy.
 * The two stays seeded here are the inpatient half of that same history and
 * nothing else:
 *
 *   -18 months  Presented to the clinic with severely raised blood pressure and
 *               was admitted for control. This is the day the record already
 *               carries her hypertension diagnosis, so the stay is linked to
 *               that visit where the visit can be found.
 *   -14 months  Admitted with hyperglycaemia and dehydration — her diabetes,
 *               the condition the record already holds. Discharged home four
 *               days later onto the revised oral regimen the quarterly reviews
 *               then follow up.
 *
 * Both stays are DISCHARGED, deliberately. A "currently admitted" demo row is
 * plausible on the day it is written and implausible a month later, when it
 * shows a patient who has been an inpatient since last spring; a concluded stay
 * stays true however long it is left. Nothing else in this application reads
 * `admissions` (no controller, no view), so an open stay would buy the demo one
 * green pill at the cost of a record that decays into a false one.
 *
 * Deliberate boundaries:
 *   - Demo patients only, resolved by email exactly as DemoPatientStorySeeder
 *     resolves them (DEMO_STORY_EMAILS overrides). A patient that is not
 *     `is_demo` is refused by name and never written to.
 *   - Real institutions only, through DemoFacilityResolver. The ward and the
 *     two beds are the one piece of scaffolding this seeder does invent, and
 *     only because `admissions.bed_id` is NOT NULL with a foreign key onto
 *     `beds` — there is no way to record a stay without them.
 *   - Idempotent: every row is keyed on a UUID derived from the patient id, so
 *     a second run rewrites the same rows and inserts nothing new. It is run
 *     repeatedly on the live demo server.
 *   - `admissions`, `wards` and `beds` have no `is_demo` column and no demo
 *     global scope; the isolation here is the patient, which is why the
 *     is_demo check on the patient is not optional.
 */
class AdmissionsDemoSeeder extends Seeder
{
    /** The demo logins the story is told for — the same list the story seeder uses. */
    private const DEFAULT_EMAILS = [
        'demo.patient@opescare.test',
        'demo.reviewer@opescare.test',
    ];

    /** The ward the stays happened on. One per facility, shared by every demo patient. */
    private const WARD_NAME = 'Médecine Interne';

    public function run(): void
    {
        $facility = DemoFacilityResolver::primaryHospital();

        if (! $facility) {
            $this->command?->warn('AdmissionsDemoSeeder skipped: "' . DemoFacilityResolver::PRIMARY_HOSPITAL . '" is not in the care_facilities registry.');

            return;
        }

        DemoCareTeam::ensure($this->command);
        $doctor = DemoCareTeam::doctor();

        if (! $doctor) {
            $this->command?->warn('AdmissionsDemoSeeder skipped: no demo clinician (admissions.admitted_by is NOT NULL).');

            return;
        }

        $wardId = $this->ward($facility);
        $seeded = 0;

        foreach ($this->emails() as $email) {
            $patient = DB::table('patients')->where('email', $email)->first(['id', 'health_id', 'is_demo']);

            if (! $patient) {
                $this->command?->warn("  skip {$email}: no patient record");

                continue;
            }

            if (! $patient->is_demo) {
                $this->command?->warn("  refuse {$email}: not a demo record — an inpatient stay is never written onto a real person");

                continue;
            }

            $this->seedStays((string) $patient->id, $facility, $wardId, $doctor);
            $seeded++;

            $this->command?->info(sprintf(
                '  %s  admissions=%d (discharged=%d)',
                $patient->health_id,
                DB::table('admissions')->where('patient_id', $patient->id)->count(),
                DB::table('admissions')->where('patient_id', $patient->id)->whereNotNull('discharged_at')->count()
            ));
        }

        // Declared capacity must match the beds that actually exist, or the
        // ward's occupancy rate is computed against a number nothing backs.
        DB::table('wards')->where('id', $wardId)->update([
            'total_beds' => DB::table('beds')->where('ward_id', $wardId)->count(),
            'updated_at' => now(),
        ]);

        if ($seeded === 0) {
            $this->command?->warn('AdmissionsDemoSeeder: no demo patient matched — nothing seeded.');
        }
    }

    /** @return list<string> */
    private function emails(): array
    {
        $raw = (string) (env('DEMO_STORY_EMAILS') ?: implode(',', self::DEFAULT_EMAILS));

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * The ward the stays happened on, created once per facility.
     *
     * A ward and a bed are the only invented rows here. They exist because
     * `admissions.bed_id` is NOT NULL and carries a foreign key onto `beds`:
     * a stay cannot be recorded without one. Floor, building and head nurse are
     * left null rather than guessed — this is a real hospital and its internal
     * geography is not something a seeder knows.
     */
    private function ward(string $facilityId): string
    {
        $id = DemoStoryIds::derived($facilityId, 'passport:ward:internal-medicine');

        DB::table('wards')->updateOrInsert(['id' => $id], [
            'facility_id' => $facilityId,
            'name'        => self::WARD_NAME,
            'ward_type'   => 'general',
            'is_active'   => true,
            'updated_at'  => now(),
            'created_at'  => now(),
        ]);

        return $id;
    }

    /**
     * The two stays, and the bed each was on.
     *
     * Bed numbers are derived from the patient id rather than from the loop
     * position, so two demo patients never end up sharing one bed on the same
     * dates however the email list is ordered or extended.
     */
    private function seedStays(string $patientId, string $facilityId, string $wardId, string $doctor): void
    {
        $slot   = (hexdec(substr(sha1($patientId), 0, 6)) % 90) + 1;
        $anchor = $this->hypertensionVisit($patientId, $facilityId);

        // The clinic visit the stay came out of, or — where the record holds no
        // such visit — the same moment in the story told in relative time.
        $admittedHypertension = $anchor
            ? Carbon::parse($anchor->started_at)->setTime(12, 15)
            : Carbon::now()->subMonthsNoOverflow(18)->setTime(12, 15);

        // Four months later, well inside the gap the record leaves between the
        // hypertension diagnosis and the first quarterly review.
        $admittedGlycaemia = $admittedHypertension->copy()->addMonthsNoOverflow(4)->setTime(16, 20);

        $stays = [
            [
                'key'         => 'passport:admission:hyperglycaemia',
                'bed'         => sprintf('MI-%02dB', $slot),
                'visit_id'    => null,
                'admitted_at' => $admittedGlycaemia,
                // Four nights: long enough to be a real stay, short enough to
                // be the episode the next quarterly review follows up.
                'discharged_at'         => $admittedGlycaemia->copy()->addDays(4)->setTime(11, 0),
                'admission_reason'      => 'Hyperglycaemia with dehydration — type 2 diabetes',
                'discharge_reason'      => 'Blood glucose stabilised on oral therapy; hydration corrected',
                'discharge_destination' => 'home',
            ],
            [
                'key'         => 'passport:admission:hypertensive-urgency',
                'bed'         => sprintf('MI-%02dA', $slot),
                'visit_id'    => $anchor?->visit_id,
                'admitted_at' => $admittedHypertension,
                // Two nights, on the day the record already carries her
                // hypertension diagnosis: seen in clinic, admitted for control.
                'discharged_at'         => $admittedHypertension->copy()->addDays(2)->setTime(10, 30),
                'admission_reason'      => 'Severely raised blood pressure at clinic review',
                'discharge_reason'      => 'Blood pressure controlled on oral therapy; essential hypertension confirmed',
                'discharge_destination' => 'home',
            ],
        ];

        foreach ($stays as $stay) {
            $bedId = $this->bed($wardId, $patientId, $stay['bed']);

            DB::table('admissions')->updateOrInsert(
                ['id' => DemoStoryIds::derived($patientId, $stay['key'])],
                [
                    'facility_id'            => $facilityId,
                    'patient_id'             => $patientId,
                    'bed_id'                 => $bedId,
                    'visit_id'               => $stay['visit_id'],
                    'admitted_by'            => $doctor,
                    'attending_physician_id' => $doctor,
                    'status'                 => 'discharged',
                    'admission_reason'       => $stay['admission_reason'],
                    'discharge_reason'       => $stay['discharge_reason'],
                    'discharge_destination'  => $stay['discharge_destination'],
                    'admitted_at'            => $stay['admitted_at'],
                    'discharged_at'          => $stay['discharged_at'],
                    // The stay's own dates, not now(): anything ordering by
                    // created_at would otherwise file a stay from last year
                    // under this week.
                    'created_at'             => $stay['admitted_at'],
                    'updated_at'             => $stay['discharged_at'],
                ]
            );
        }
    }

    /**
     * The visit at which the record already carries the hypertension diagnosis.
     *
     * Anchoring on it rather than on `now()->subMonths(18)` matters: the story
     * seeders write their dates relative to the day THEY were run, so a stay
     * computed from today's date lands a day or two off the visit it is meant
     * to have come out of, and the link is then either wrong or missing. The
     * diagnosis is the fixed point; the stay hangs off it.
     */
    private function hypertensionVisit(string $patientId, string $facilityId): ?object
    {
        return DB::table('diagnoses')
            ->join('visits', 'visits.id', '=', 'diagnoses.visit_id')
            ->where('diagnoses.patient_id', $patientId)
            ->where('diagnoses.code', 'I10')
            ->where('visits.facility_id', $facilityId)
            ->orderBy('visits.started_at')
            ->first(['visits.id as visit_id', 'visits.started_at']);
    }

    /** The bed a stay was on. Free again afterwards — both stays ended. */
    private function bed(string $wardId, string $patientId, string $number): string
    {
        $id = DemoStoryIds::derived($patientId, 'passport:bed:' . $number);

        DB::table('beds')->updateOrInsert(['id' => $id], [
            'ward_id'     => $wardId,
            'bed_number'  => $number,
            'status'      => 'available',
            'bed_type'    => 'standard',
            'has_oxygen'  => true,
            'has_monitor' => false,
            'updated_at'  => now(),
            'created_at'  => now(),
        ]);

        return $id;
    }
}
