<?php

namespace Database\Seeders\Passport;

use Database\Seeders\Support\DemoStoryIds;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Demo content for passport page 08 — Visits & Encounters.
 *
 * The demo record already holds a coherent visit history: DemoClinicalCoreSeeder
 * writes seven `visits` (the two diagnosis appointments, one episode of care in
 * Douala and four quarterly reviews) and DemoFrontDeskSeeder opens one more at
 * the desk this morning. What it does NOT hold is a single `encounters` row for
 * any of them — there is no `Encounter::create()` anywhere in the platform yet —
 * so page 08 is blank on the demo server while the record it reads plainly is
 * not.
 *
 * ── WHY THIS DERIVES INSTEAD OF INVENTING ─────────────────────────────────
 *
 * Every encounter written here hangs off a visit that already exists, and takes
 * its facility, its attending clinician, its start and its end FROM THAT VISIT.
 * Nothing about where or when is decided in this file. A visit that is not
 * there is skipped rather than created, so this seeder can never conjure a
 * hospital attendance that the rest of the demo story does not already tell.
 *
 * The inpatient encounters are derived further still: they are read whole off
 * the `admissions` rows page 09 already shows, so a hospital stay appears in
 * the patient's encounter history rather than only on the admissions page, and
 * `encounters.admission_id` carries the link between the two.
 *
 * The one thing it does supply is the clinical narrative for the OUTPATIENT
 * encounters — why the patient came and how it concluded — and each line agrees
 * with what the record already says happened around it: the diagnosis at that
 * visit (E11 at the first, I10 at the third), the allergies recorded the same
 * day, the prescription issued at the fourth quarterly review, the raised home
 * readings that the accepted cardiology referral cites. A reader who compares
 * this page with pages 4, 5, 6, 7 and 15 finds one story, not five.
 *
 * ── BOUNDARIES ────────────────────────────────────────────────────────────
 *
 *  - Demo patients only, and only where the underlying visit is itself a demo
 *    row. A real person's visit is never given an encounter by a seeder.
 *  - `encounters` has no `is_demo` column and no demo global scope; isolation
 *    here is by attachment — every row hangs off an is_demo patient and an
 *    is_demo visit.
 *  - Idempotent: each encounter id is derived from the patient and a reserved
 *    id (the 0c08 block — "clinical core, page 08"), so a re-run refreshes the
 *    same rows rather than adding more. It is safe to run repeatedly on the
 *    live demo server.
 *  - Discovered and run automatically by DemoHealthPassportSeeder.
 */
class EncountersDemoSeeder extends Seeder
{
    /**
     * The story, keyed on the reserved visit id DemoClinicalCoreSeeder writes.
     *
     *   [reserved encounter id, reason for the encounter, how it concluded]
     *
     * The order here is the order of the visits in the demo history: diabetes
     * diagnosed three years ago, one episode of care away from home, hypertension
     * added eighteen months ago, then four quarterly reviews.
     */
    private const STORY = [
        // Type 2 diabetes diagnosed — Hôpital Central de Yaoundé, -3 years.
        '00000000-0000-0000-0c01-000000000001' => [
            '00000000-0000-0000-0c08-000000000001',
            'Three months of thirst, passing urine at night and weight loss.',
            'Type 2 diabetes confirmed on fasting glucose. Metformin started, dietary advice given and quarterly review arranged. Penicillin allergy recorded.',
        ],
        // The one episode of care away from home — Hôpital Laquintinie de Douala, -22 months.
        '00000000-0000-0000-0c01-000000000002' => [
            '00000000-0000-0000-0c08-000000000002',
            'Acute diarrhoea and vomiting for two days while travelling.',
            'Rehydrated and discharged home with oral rehydration salts and paracetamol. No beta-lactams given — penicillin allergy is severe.',
        ],
        // Hypertension diagnosed — Hôpital Central de Yaoundé, -18 months.
        '00000000-0000-0000-0c01-000000000003' => [
            '00000000-0000-0000-0c08-000000000003',
            'Headaches and a raised blood pressure reading at a routine check.',
            'Essential hypertension confirmed over three readings. Amlodipine started; peanut allergy added to the record.',
        ],
        // Quarterly review — -9 months.
        '00000000-0000-0000-0c01-000000000004' => [
            '00000000-0000-0000-0c08-000000000004',
            'Quarterly diabetes and hypertension review.',
            'Both conditions stable. Metformin and Amlodipine continued; review in three months.',
        ],
        // Quarterly review seen by the cardiologist — -6 months.
        '00000000-0000-0000-0c01-000000000005' => [
            '00000000-0000-0000-0c08-000000000005',
            'Quarterly review with cardiovascular risk assessment.',
            'Blood pressure above target on Amlodipine 5 mg. Home monitoring started; medicines unchanged pending the readings.',
        ],
        // Quarterly review — -3 months.
        '00000000-0000-0000-0c01-000000000006' => [
            '00000000-0000-0000-0c08-000000000006',
            'Quarterly diabetes and hypertension review.',
            'HbA1c 7.8% and blood pressure still above target. Cardiology opinion to be requested.',
        ],
        // Quarterly review, the most recent — -18 days.
        '00000000-0000-0000-0c01-000000000007' => [
            '00000000-0000-0000-0c08-000000000007',
            'Quarterly diabetes and hypertension review.',
            'Repeat prescription issued for 90 days. Awaiting the cardiology appointment; review in three months.',
        ],
    ];

    /**
     * The visit the front desk opened this morning, which
     * DemoFrontDeskSeeder keys by name rather than by a reserved id. It is
     * still open, so its encounter is `active` and records no conclusion —
     * which is exactly what page 08 should show for care in progress.
     */
    private const FRONT_DESK_VISIT_KEY = 'demo-front-desk:visit:today';

    private const FRONT_DESK_ENCOUNTER = '00000000-0000-0000-0c08-000000000008';

    public function run(): void
    {
        $patients = DB::table('patients')
            ->where('is_demo', true)
            ->whereIn('id', DB::table('visits')->where('is_demo', true)->distinct()->pluck('patient_id'))
            ->pluck('health_id', 'id');

        if ($patients->isEmpty()) {
            $this->command?->warn('EncountersDemoSeeder: no demo patient has a visit to hang an encounter off — nothing seeded.');

            return;
        }

        $written = 0;

        foreach ($patients as $patientId => $healthId) {
            $written += $this->seedStory((string) $patientId);
            $written += $this->seedOpenVisit((string) $patientId);
            $written += $this->seedAdmissions((string) $patientId);
        }

        $this->command?->info(sprintf(
            'EncountersDemoSeeder: %d encounter(s) across %d demo patient(s).',
            $written,
            $patients->count()
        ));
    }

    /** The seven visits of the demo clinical story. */
    private function seedStory(string $patientId): int
    {
        $written = 0;

        foreach (self::STORY as $reservedVisitId => [$reservedEncounterId, $reason, $disposition]) {
            $visit = $this->demoVisit(DemoStoryIds::uid($patientId, $reservedVisitId), $patientId);

            if ($visit === null) {
                continue;
            }

            $this->writeEncounter(
                DemoStoryIds::uid($patientId, $reservedEncounterId),
                $patientId,
                $visit,
                $reason,
                $disposition
            );

            $written++;
        }

        return $written;
    }

    /**
     * The visit still open at the desk. Its reason is the one the front desk
     * booked it under — read from the appointment rather than restated here, so
     * the two can never drift apart.
     */
    private function seedOpenVisit(string $patientId): int
    {
        $visit = $this->demoVisit(DemoStoryIds::derived($patientId, self::FRONT_DESK_VISIT_KEY), $patientId);

        if ($visit === null) {
            return 0;
        }

        $reason = (string) (DB::table('appointments')
            ->where('visit_id', $visit->id)
            ->orderBy('scheduled_at')
            ->value('reason') ?? '');

        $this->writeEncounter(
            DemoStoryIds::uid($patientId, self::FRONT_DESK_ENCOUNTER),
            $patientId,
            $visit,
            $reason !== '' ? $reason : null,
            null
        );

        return 1;
    }

    /**
     * The inpatient stays page 09 already shows.
     *
     * `admissions` is written by AdmissionsDemoSeeder and is the record of the
     * stay itself; an admitted patient is also HAVING AN ENCOUNTER, and with no
     * row here page 08 would list a patient's outpatient visits while page 09
     * showed hospital stays that appear nowhere in her encounter history. Every
     * word of these rows is read off the admission — its reason, its discharge
     * reason, its dates, its attending physician — so this adds a cross-reference
     * the record supports and no narrative of its own. `admission_id` carries
     * the link back, which is what that column is for.
     *
     * An admission with no `visit_id` is SKIPPED rather than given an invented
     * visit: `encounters.visit_id` is NOT NULL, and manufacturing a visit to
     * satisfy a foreign key would put an attendance in the patient's timeline
     * that nobody recorded.
     */
    private function seedAdmissions(string $patientId): int
    {
        if (! Schema::hasTable('admissions')) {
            return 0;
        }

        $admissions = DB::table('admissions')
            ->where('patient_id', $patientId)
            ->whereNotNull('visit_id')
            ->orderBy('admitted_at')
            ->get();

        $written = 0;

        foreach ($admissions as $admission) {
            $visit = $this->demoVisit((string) $admission->visit_id, $patientId);

            if ($visit === null) {
                continue;
            }

            $status = $this->admissionStatus((string) $admission->status);

            $this->upsert(
                DemoStoryIds::derived($patientId, 'passport-encounters:admission:' . $admission->id),
                [
                    'visit_id'              => $admission->visit_id,
                    'patient_id'            => $patientId,
                    'facility_id'           => $admission->facility_id,
                    'encounter_type'        => 'inpatient',
                    'encounter_class'       => 'IMP',
                    'status'                => $status,
                    'attending_provider_id' => $admission->attending_physician_id ?: $admission->admitted_by,
                    'admission_id'          => $admission->id,
                    'reason_for_encounter'  => $admission->admission_reason,
                    'discharge_disposition' => $status === 'completed' ? $admission->discharge_reason : null,
                    'started_at'            => $admission->admitted_at,
                    'ended_at'              => $admission->discharged_at,
                    'created_at'            => $admission->admitted_at,
                    'updated_at'            => $admission->discharged_at ?: $admission->admitted_at,
                ]
            );

            $written++;
        }

        return $written;
    }

    /**
     * A demo visit belonging to this patient, or null.
     *
     * Both conditions matter. The patient check keeps a derived id from ever
     * reaching across to somebody else's row; the `is_demo` check means a real
     * visit can never acquire a seeded encounter, whatever else is true.
     */
    private function demoVisit(string $visitId, string $patientId): ?object
    {
        return DB::table('visits')
            ->where('id', $visitId)
            ->where('patient_id', $patientId)
            ->where('is_demo', true)
            ->first(['id', 'facility_id', 'provider_id', 'visit_type', 'status', 'started_at', 'ended_at']);
    }

    /** Write the encounter, taking everything factual from the visit itself. */
    private function writeEncounter(string $encounterId, string $patientId, object $visit, ?string $reason, ?string $disposition): void
    {
        $startedAt = $visit->started_at;
        $status    = $this->encounterStatus((string) $visit->status);

        $this->upsert($encounterId, [
            'visit_id'              => $visit->id,
            'patient_id'            => $patientId,
            'facility_id'           => $visit->facility_id,
            'encounter_type'        => $this->encounterType((string) $visit->visit_type),
            'encounter_class'       => $this->encounterClass((string) $visit->visit_type),
            'status'                => $status,
            'attending_provider_id' => $visit->provider_id,
            'admission_id'          => null,
            'reason_for_encounter'  => $reason,
            // An encounter that has not ended has not concluded either.
            'discharge_disposition' => $status === 'completed' ? $disposition : null,
            'started_at'            => $startedAt,
            'ended_at'              => $visit->ended_at,
            // The visit's own date, never now(): a three-year-old encounter
            // created "today" files itself under this month everywhere the
            // platform orders by created_at.
            'created_at'            => $startedAt,
            'updated_at'            => $visit->ended_at ?: $startedAt,
        ]);
    }

    /**
     * `updateOrInsert` rather than an existence check: the front-desk visit is
     * re-dated to "today" on every run of its own seeder, and an encounter still
     * carrying last week's start time would put a live consultation at the wrong
     * end of the page.
     */
    private function upsert(string $encounterId, array $attributes): void
    {
        DB::table('encounters')->updateOrInsert(['id' => $encounterId], $attributes);
    }

    // ── Reading the visit's vocabulary into the encounter's ──────────────

    /**
     * `visits.visit_type` is free text (outpatient, follow_up, lab-only …);
     * `encounters.encounter_type` is the FHIR-facing set the Encounter model
     * documents. Anything ambulatory reads as outpatient — which is what it is.
     */
    private function encounterType(string $visitType): string
    {
        return match (strtolower(trim($visitType))) {
            'emergency'                   => 'emergency',
            'inpatient', 'admission'      => 'inpatient',
            'telemedicine', 'telehealth'  => 'telemedicine',
            'referral'                    => 'referral',
            default                       => 'outpatient',
        };
    }

    /** The FHIR R4 Encounter.class code the model's fhirClass() maps back. */
    private function encounterClass(string $visitType): string
    {
        return match ($this->encounterType($visitType)) {
            'emergency'    => 'EMER',
            'inpatient'    => 'IMP',
            'telemedicine' => 'VR',
            default        => 'AMB',
        };
    }

    /** visits: open|in_progress|closed|completed|cancelled → encounters: active|completed|cancelled. */
    private function encounterStatus(string $visitStatus): string
    {
        return match (strtolower(trim($visitStatus))) {
            'completed', 'closed' => 'completed',
            'cancelled'           => 'cancelled',
            default               => 'active',
        };
    }

    /** admissions: admitted|discharged|transferred|cancelled → the same three. */
    private function admissionStatus(string $admissionStatus): string
    {
        return match (strtolower(trim($admissionStatus))) {
            'discharged', 'completed' => 'completed',
            'cancelled'               => 'cancelled',
            default                   => 'active',
        };
    }
}
