<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\CarePlan;
use App\Models\CarePlanGoal;
use App\Models\CarePlanIntervention;
use App\Models\PatientSurvey;
use App\Models\ReferralCase;
use App\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Demo care pathway for the ONE demo patient (Demo Patient One,
 * 00000000-0000-0000-0000-300000000001) — appointments, referral, care plan
 * and satisfaction surveys.
 *
 * Those four mobile screens render empty states with a clean database. This
 * fills them with a *single coherent history* rather than scattered rows:
 *
 *   2023-02  Type 2 diabetes diagnosed at Hôpital Central de Yaoundé (Metformin)
 *   2024-06  Hypertension added (Amlodipine)
 *   ~5 weeks ago   BP still above target -> cardiology referral to Dr. Ibrahim Sow
 *   ~5 days ago    interim blood-pressure check (completed) -> survey still open
 *   +3 weeks       next quarterly review (the home screen "Upcoming" card)
 *
 * Every appointment/referral/plan/survey below hangs off that timeline, and
 * every date is computed from `now()` at run time so the demo never goes stale.
 *
 * Deliberate boundaries:
 *   - Writes clinical rows for exactly ONE patient (self::PATIENT). If the
 *     patient, the real hospital rows or the demo clinicians are missing the
 *     seeder warns and returns rather than attaching invented history to a
 *     real facility or a real patient.
 *   - Facilities and clinicians are RESOLVED BY QUERY, never invented:
 *       * Hôpital Central de Yaoundé / CHU de Yaoundé come from
 *         `care_facilities.facility_name` -> `care_facilities.facility_id`,
 *         i.e. the internal `facilities` id. That is the same id
 *         MobileAppointmentController::book() stores, so a seeded appointment
 *         is indistinguishable from a booked one and stays linkable from the
 *         directory. Only rows with a non-null `facility_id` are bookable —
 *         both hospitals used here are in that set.
 *       * Dr. Amara Diallo and Dr. Ibrahim Sow are looked up in `users`; the
 *         specialist's title comes from his real `staff_profiles.job_title`
 *         ("Cardiologist"), it is not hard-coded prose.
 *   - Idempotent: every row carries a stable UUID in the reserved
 *     00000000-0000-0000-0a0x- demo space and is upserted, so running twice
 *     writes the same rows (with dates refreshed relative to the new "now").
 *   - Statuses are the values the DB check constraints and the mobile
 *     controllers actually accept — see the tables at each step. There is no
 *     backed enum for appointment / referral / care-plan status in app/Enums,
 *     so these are held as private constants here instead of loose literals.
 *   - NOT registered in DatabaseSeeder — run it explicitly:
 *         php artisan db:seed --class=DemoCarePathwaySeeder
 */
class DemoCarePathwaySeeder extends Seeder
{
    private const PATIENT    = '00000000-0000-0000-0000-300000000001'; // Demo Patient One
    private const DOCTOR     = '00000000-0000-0000-0000-200000000001'; // Dr. Amara Diallo (GP)
    private const SPECIALIST = '00000000-0000-0000-0000-200000000011'; // Dr. Ibrahim Sow (Cardiologist)

    /** Directory names — resolved to internal `facilities` ids at run time. */
    private const HOME_HOSPITAL     = 'Hôpital Central de Yaoundé';
    private const CARDIOLOGY_CENTRE = 'CHU de Yaoundé';

    /**
     * appointments.status — free-form varchar, but MobileAppointmentController
     * splits upcoming/past on exactly these values:
     *   upcoming: booked | confirmed | checked_in   (and scheduled_at >= now)
     *   past:     anything else, or scheduled_at < now
     */
    private const APPT_CONFIRMED = 'confirmed';
    private const APPT_COMPLETED = 'completed';
    private const APPT_CANCELLED = 'cancelled';
    private const APPT_NO_SHOW   = 'no_show';

    /** PatientPortalController::appointmentTypes() is the app-wide vocabulary. */
    private const TYPE_FOLLOW_UP = 'follow_up';

    /** referral_cases.status — draft|sent|accepted|rejected|cancelled|completed|expired */
    private const REFERRAL_ACCEPTED = 'accepted';

    /** care_plans.status CHECK — active|completed|on_hold|cancelled */
    private const PLAN_ACTIVE = 'active';

    /** care_plan_goals.status CHECK — pending|in_progress|achieved|abandoned */
    private const GOAL_PENDING     = 'pending';
    private const GOAL_IN_PROGRESS = 'in_progress';
    private const GOAL_ACHIEVED    = 'achieved';

    /** care_plan_interventions.status CHECK — active|completed|discontinued */
    private const INTERVENTION_ACTIVE    = 'active';
    private const INTERVENTION_COMPLETED = 'completed';

    /** patient_surveys.status CHECK — pending|sent|completed|expired.
     *  MobileSurveyController::index() lists `sent`, so "pending for the
     *  patient" is stored as `sent`, exactly like SurveyService::createAndSend(). */
    private const SURVEY_SENT      = 'sent';
    private const SURVEY_COMPLETED = 'completed';

    /** SurveyService::TEMPLATES key — drives the questions the form renderer draws. */
    private const SURVEY_TEMPLATE = 'post_visit';

    // Stable ids — reserved demo space, one block per table.
    private const APPT_UPCOMING     = '00000000-0000-0000-0a01-000000000001';
    private const APPT_BP_CHECK     = '00000000-0000-0000-0a01-000000000002';
    private const APPT_REVIEW_Q1    = '00000000-0000-0000-0a01-000000000003';
    private const APPT_CANCELLED_ID = '00000000-0000-0000-0a01-000000000004';
    private const APPT_REVIEW_Q2    = '00000000-0000-0000-0a01-000000000005';
    private const APPT_NO_SHOW_ID   = '00000000-0000-0000-0a01-000000000006';

    private const REFERRAL = '00000000-0000-0000-0a02-000000000001';
    private const PLAN     = '00000000-0000-0000-0a03-000000000001';

    private const SURVEY_DONE    = '00000000-0000-0000-0a06-000000000001';
    private const SURVEY_PENDING = '00000000-0000-0000-0a06-000000000002';

    public function run(): void
    {
        if (! DB::table('patients')->where('id', self::PATIENT)->exists()) {
            $this->command?->warn('DemoCarePathwaySeeder skipped: demo patient not present.');
            return;
        }

        $homeFacilityId       = $this->resolveBookableFacilityId(self::HOME_HOSPITAL);
        $cardiologyFacilityId = $this->resolveBookableFacilityId(self::CARDIOLOGY_CENTRE);

        if (! $homeFacilityId || ! $cardiologyFacilityId) {
            $this->command?->warn(
                'DemoCarePathwaySeeder skipped: no bookable facility linked for "'
                . self::HOME_HOSPITAL . '" / "' . self::CARDIOLOGY_CENTRE
                . '". Run BookableFacilitySlotsSeeder first.'
            );
            return;
        }

        $doctorId     = $this->resolveUserId(self::DOCTOR);
        $specialistId = $this->resolveUserId(self::SPECIALIST);

        if (! $doctorId) {
            $this->command?->warn('DemoCarePathwaySeeder skipped: Dr. Amara Diallo not present.');
            return;
        }

        $this->seedAppointments($homeFacilityId, $doctorId);
        $this->seedReferral($homeFacilityId, $cardiologyFacilityId, $doctorId, $specialistId);
        $this->seedCarePlan($homeFacilityId, $doctorId);
        $this->seedSurveys($homeFacilityId);

        $this->command?->info('DemoCarePathwaySeeder: care pathway ready for ' . self::PATIENT . '.');
    }

    // ── Appointments ─────────────────────────────────────────────────────────

    /**
     * Quarterly-review cadence around today, so the home screen always has a
     * next appointment and the list always has a populated history.
     */
    private function seedAppointments(string $facilityId, string $providerId): void
    {
        $upcomingAt = Carbon::now()->addWeeks(3)->setTime(9, 30);

        // The card the home screen renders: facility, provider, date/time, reason.
        $this->upsert(Appointment::class, self::APPT_UPCOMING, [
            'patient_id'       => self::PATIENT,
            'facility_id'      => $facilityId,
            'provider_id'      => $providerId,
            'appointment_type' => self::TYPE_FOLLOW_UP,
            'status'           => self::APPT_CONFIRMED,
            'scheduled_at'     => $upcomingAt,
            'booked_by_type'   => 'staff',
            'booked_by_id'     => $providerId,
            'reason'           => 'Quarterly diabetes and hypertension review — HbA1c, blood pressure and medication check.',
        ]);

        // Interim BP check five days ago. This is the visit the still-open
        // satisfaction survey below belongs to.
        $bpCheckAt = Carbon::now()->subDays(5)->setTime(11, 0);
        $this->upsert(Appointment::class, self::APPT_BP_CHECK, [
            'patient_id'       => self::PATIENT,
            'facility_id'      => $facilityId,
            'provider_id'      => $providerId,
            'appointment_type' => self::TYPE_FOLLOW_UP,
            'status'           => self::APPT_COMPLETED,
            'scheduled_at'     => $bpCheckAt,
            'checked_in_at'    => $bpCheckAt->copy()->subMinutes(12),
            'booked_by_type'   => 'staff',
            'booked_by_id'     => $providerId,
            'reason'           => 'Interim blood-pressure check after the cardiology referral.',
        ]);

        // Last quarterly review — ten weeks ago (three weeks from now completes
        // the ~13-week cadence).
        $lastReviewAt = Carbon::now()->subWeeks(10)->setTime(9, 30);
        $this->upsert(Appointment::class, self::APPT_REVIEW_Q1, [
            'patient_id'       => self::PATIENT,
            'facility_id'      => $facilityId,
            'provider_id'      => $providerId,
            'appointment_type' => self::TYPE_FOLLOW_UP,
            'status'           => self::APPT_COMPLETED,
            'scheduled_at'     => $lastReviewAt,
            'checked_in_at'    => $lastReviewAt->copy()->subMinutes(20),
            'booked_by_type'   => 'staff',
            'booked_by_id'     => $providerId,
            'reason'           => 'Quarterly diabetes and hypertension review — HbA1c 7.8%, blood pressure above target.',
        ]);

        // Cancelled — she was in Douala for work (the one episode of care in
        // Douala in her history).
        $cancelledAt = Carbon::now()->subWeeks(16)->setTime(14, 0);
        $this->upsert(Appointment::class, self::APPT_CANCELLED_ID, [
            'patient_id'          => self::PATIENT,
            'facility_id'         => $facilityId,
            'provider_id'         => $providerId,
            'appointment_type'    => self::TYPE_FOLLOW_UP,
            'status'              => self::APPT_CANCELLED,
            'scheduled_at'        => $cancelledAt,
            'booked_by_type'      => 'patient',
            'booked_by_id'        => self::PATIENT,
            'reason'              => 'Blood-pressure check.',
            'cancellation_reason' => 'Cancelled by the patient — travelling to Douala for work; rescheduled into the next quarterly review.',
            'cancelled_at'        => $cancelledAt->copy()->subDays(2),
            'cancelled_by_id'     => self::PATIENT,
        ]);

        $reviewQ2At = Carbon::now()->subWeeks(23)->setTime(10, 0);
        $this->upsert(Appointment::class, self::APPT_REVIEW_Q2, [
            'patient_id'       => self::PATIENT,
            'facility_id'      => $facilityId,
            'provider_id'      => $providerId,
            'appointment_type' => self::TYPE_FOLLOW_UP,
            'status'           => self::APPT_COMPLETED,
            'scheduled_at'     => $reviewQ2At,
            'checked_in_at'    => $reviewQ2At->copy()->subMinutes(15),
            'booked_by_type'   => 'staff',
            'booked_by_id'     => $providerId,
            'reason'           => 'Quarterly diabetes review — Amlodipine dose reviewed after the hypertension diagnosis.',
        ]);

        // Missed review — exercises the no_show pill.
        $noShowAt = Carbon::now()->subWeeks(36)->setTime(8, 30);
        $this->upsert(Appointment::class, self::APPT_NO_SHOW_ID, [
            'patient_id'       => self::PATIENT,
            'facility_id'      => $facilityId,
            'provider_id'      => $providerId,
            'appointment_type' => self::TYPE_FOLLOW_UP,
            'status'           => self::APPT_NO_SHOW,
            'scheduled_at'     => $noShowAt,
            'no_show_at'       => $noShowAt->copy()->addMinutes(45),
            'booked_by_type'   => 'staff',
            'booked_by_id'     => $providerId,
            'reason'           => 'Quarterly diabetes review — patient did not attend.',
        ]);
    }

    // ── Referral ─────────────────────────────────────────────────────────────

    /**
     * GP -> Cardiology for blood pressure that is still above target, which is
     * also why the interim BP check and the care-plan BP goal exist.
     */
    private function seedReferral(
        string $referringFacilityId,
        string $receivingFacilityId,
        string $referringProviderId,
        ?string $specialistId
    ): void {
        $specialist = $specialistId
            ? DB::table('users')->where('id', $specialistId)->first(['name'])
            : null;

        $specialistProfile = $specialistId
            ? DB::table('staff_profiles')->where('user_id', $specialistId)->first(['job_title', 'department'])
            : null;

        $specialistName = $specialist->name ?? 'Dr. Ibrahim Sow';
        $specialty      = $specialistProfile->department ?? 'Cardiology';

        $referredAt = Carbon::now()->subWeeks(5)->setTime(12, 15);

        $this->upsert(ReferralCase::class, self::REFERRAL, [
            'patient_id'              => self::PATIENT,
            'referring_facility_id'   => $referringFacilityId,
            'referring_provider_id'   => $referringProviderId,
            'receiving_facility_id'   => $receivingFacilityId,
            'receiving_specialty'     => $specialty,
            'receiving_provider_name' => $specialistName
                . ($specialistProfile?->job_title ? ' (' . $specialistProfile->job_title . ')' : ''),
            'urgency'                 => 'routine',
            'status'                  => self::REFERRAL_ACCEPTED,
            'reason'                  => 'Hypertension review — blood pressure persistently above 140/90 mmHg on Amlodipine 5 mg despite good adherence.',
            'clinical_summary'        => '34-year-old woman with Type 2 diabetes (diagnosed February 2023, on Metformin 1000 mg twice daily) and hypertension (diagnosed June 2024, on Amlodipine 5 mg once daily). Last HbA1c 7.8%. Home readings average 146/92 mmHg. Requesting a cardiology opinion on antihypertensive escalation and cardiovascular risk assessment. ALLERGIES: penicillin (severe — anaphylaxis), peanuts (moderate).',
            'included_record_types'   => ['conditions', 'medications', 'allergies', 'lab_results', 'vitals'],
            'expires_at'              => $referredAt->copy()->addDays(90),
            'accepted_at'             => $referredAt->copy()->addDays(4),
            'accepted_by_id'          => $specialistId,
            'created_by_id'           => $referringProviderId,
            // ReferralCase is listed with ->latest(); pin created_at to the
            // referral date so ordering matches the clinical timeline.
            'created_at'              => $referredAt,
        ]);
    }

    // ── Care plan ────────────────────────────────────────────────────────────

    /**
     * MobileCarePlanController::index() -> CarePlanService::getActivePlansForPatient()
     * filters on status = active and eager-loads goals + interventions, so the
     * list screen can compute progress without the {id} round trip. Goals are
     * therefore spread across pending / in_progress / achieved on purpose.
     */
    private function seedCarePlan(string $facilityId, string $createdBy): void
    {
        $startedOn = Carbon::now()->subWeeks(10)->startOfDay();

        $this->upsert(CarePlan::class, self::PLAN, [
            'patient_id'  => self::PATIENT,
            'facility_id' => $facilityId,
            'created_by'  => $createdBy,
            'title'       => 'Glycaemic control and blood-pressure management',
            'description' => 'Twelve-month plan for Type 2 diabetes (diagnosed February 2023) and hypertension (diagnosed June 2024). Targets HbA1c below 7.0% and home blood pressure below 130/80 mmHg through medication, home monitoring, diet and activity. Reviewed every three months at ' . self::HOME_HOSPITAL . '. Penicillin allergy (severe) — no beta-lactams.',
            'start_date'  => $startedOn->toDateString(),
            'end_date'    => $startedOn->copy()->addYear()->toDateString(),
            'status'      => self::PLAN_ACTIVE,
            'created_at'  => $startedOn,
        ]);

        $goals = [
            [
                'id'          => '00000000-0000-0000-0a04-000000000001',
                'goal_text'   => 'Bring HbA1c below 7.0% (7.8% at the last review).',
                'target_date' => Carbon::now()->addWeeks(3)->toDateString(),
                'status'      => self::GOAL_IN_PROGRESS,
                'achieved_at' => null,
                'notes'       => 'Down from 8.4% at diagnosis. Metformin 1000 mg twice daily continued; re-check at the next quarterly review.',
            ],
            [
                'id'          => '00000000-0000-0000-0a04-000000000002',
                'goal_text'   => 'Record a home blood-pressure reading twice weekly in the OpesCare app.',
                'target_date' => Carbon::now()->subWeeks(6)->toDateString(),
                'status'      => self::GOAL_ACHIEVED,
                'achieved_at' => Carbon::now()->subWeeks(5)->setTime(9, 0),
                'notes'       => 'Logging consistently since the referral. Average 146/92 mmHg — readings shared with cardiology.',
            ],
            [
                'id'          => '00000000-0000-0000-0a04-000000000003',
                'goal_text'   => 'Keep home blood pressure below 130/80 mmHg.',
                'target_date' => Carbon::now()->addWeeks(9)->toDateString(),
                'status'      => self::GOAL_IN_PROGRESS,
                'achieved_at' => null,
                'notes'       => 'Still above target on Amlodipine 5 mg. Awaiting the cardiology opinion before escalating.',
            ],
            [
                'id'          => '00000000-0000-0000-0a04-000000000004',
                'goal_text'   => 'Complete a dietary review with the clinic nutritionist.',
                'target_date' => Carbon::now()->addWeeks(6)->toDateString(),
                'status'      => self::GOAL_PENDING,
                'achieved_at' => null,
                'notes'       => 'Low-salt, reduced-carbohydrate plan. Peanut allergy (moderate) must be flagged to the nutritionist.',
            ],
            [
                'id'          => '00000000-0000-0000-0a04-000000000005',
                'goal_text'   => 'Attend the structured diabetes self-management education session.',
                'target_date' => Carbon::now()->subWeeks(7)->toDateString(),
                'status'      => self::GOAL_ACHIEVED,
                'achieved_at' => Carbon::now()->subWeeks(7)->setTime(15, 30),
                'notes'       => 'Group session completed at the outpatient clinic.',
            ],
            [
                'id'          => '00000000-0000-0000-0a04-000000000006',
                'goal_text'   => 'Book the annual diabetic retinal screening.',
                'target_date' => Carbon::now()->addWeeks(20)->toDateString(),
                'status'      => self::GOAL_PENDING,
                'achieved_at' => null,
                'notes'       => 'Due twelve months after the last screening.',
            ],
        ];

        foreach ($goals as $goal) {
            $id = $goal['id'];
            unset($goal['id']);
            $this->upsert(CarePlanGoal::class, $id, $goal + ['care_plan_id' => self::PLAN]);
        }

        $interventions = [
            [
                'id'                => '00000000-0000-0000-0a05-000000000001',
                'intervention_type' => 'medication',
                'description'       => 'Metformin 1000 mg orally twice daily with meals.',
                'frequency'         => 'Twice daily',
                'responsible_party' => 'Dr. Amara Diallo',
                'status'            => self::INTERVENTION_ACTIVE,
            ],
            [
                'id'                => '00000000-0000-0000-0a05-000000000002',
                'intervention_type' => 'medication',
                'description'       => 'Amlodipine 5 mg orally once daily in the morning.',
                'frequency'         => 'Once daily',
                'responsible_party' => 'Dr. Amara Diallo',
                'status'            => self::INTERVENTION_ACTIVE,
            ],
            [
                'id'                => '00000000-0000-0000-0a05-000000000003',
                'intervention_type' => 'monitoring',
                'description'       => 'Home blood-pressure reading, logged in the OpesCare app.',
                'frequency'         => 'Twice weekly',
                'responsible_party' => 'Patient',
                'status'            => self::INTERVENTION_ACTIVE,
            ],
            [
                'id'                => '00000000-0000-0000-0a05-000000000004',
                'intervention_type' => 'monitoring',
                'description'       => 'HbA1c and fasting glucose at each quarterly review.',
                'frequency'         => 'Every 3 months',
                'responsible_party' => 'Outpatient Clinic',
                'status'            => self::INTERVENTION_ACTIVE,
            ],
            [
                'id'                => '00000000-0000-0000-0a05-000000000005',
                'intervention_type' => 'diet',
                'description'       => 'Reduced-carbohydrate, low-salt meal plan. Peanut-free (moderate allergy).',
                'frequency'         => 'Daily',
                'responsible_party' => 'Patient',
                'status'            => self::INTERVENTION_ACTIVE,
            ],
            [
                'id'                => '00000000-0000-0000-0a05-000000000006',
                'intervention_type' => 'exercise',
                'description'       => 'Brisk walking for 30 minutes.',
                'frequency'         => '5 days per week',
                'responsible_party' => 'Patient',
                'status'            => self::INTERVENTION_ACTIVE,
            ],
            [
                'id'                => '00000000-0000-0000-0a05-000000000007',
                'intervention_type' => 'referral',
                'description'       => 'Cardiology opinion on antihypertensive escalation and cardiovascular risk.',
                'frequency'         => 'One-off',
                'responsible_party' => 'Dr. Ibrahim Sow',
                'status'            => self::INTERVENTION_ACTIVE,
            ],
            [
                'id'                => '00000000-0000-0000-0a05-000000000008',
                'intervention_type' => 'education',
                'description'       => 'Structured diabetes self-management education session.',
                'frequency'         => 'One-off',
                'responsible_party' => 'Outpatient Clinic',
                'status'            => self::INTERVENTION_COMPLETED,
            ],
        ];

        foreach ($interventions as $intervention) {
            $id = $intervention['id'];
            unset($intervention['id']);
            $this->upsert(CarePlanIntervention::class, $id, $intervention + ['care_plan_id' => self::PLAN]);
        }
    }

    // ── Surveys ──────────────────────────────────────────────────────────────

    /**
     * One completed and one still-open post-visit survey.
     *
     * Question keys/text/types are taken verbatim from
     * SurveyService::TEMPLATES['post_visit'] — the same array
     * MobileSurveyController::show() returns as `template`, so the form
     * renderer never meets a question type it cannot draw
     * (rating_5 | yes_no | text).
     *
     * The open one is stored with status `sent` (not `pending`): that is what
     * SurveyService::createAndSend() writes and what
     * MobileSurveyController::index() lists. Its expires_at is kept in the
     * future so SurveyService::expirePendingSurveys() cannot close it before
     * the demo is given.
     */
    private function seedSurveys(string $facilityId): void
    {
        // Completed — follows the quarterly review ten weeks ago.
        $doneSentAt = Carbon::now()->subWeeks(10)->setTime(16, 0);
        $this->upsert(PatientSurvey::class, self::SURVEY_DONE, [
            'patient_id'   => self::PATIENT,
            'facility_id'  => $facilityId,
            'template_key' => self::SURVEY_TEMPLATE,
            'status'       => self::SURVEY_COMPLETED,
            'sent_at'      => $doneSentAt,
            'completed_at' => $doneSentAt->copy()->addDay(),
            'expires_at'   => $doneSentAt->copy()->addDays(7),
            'created_at'   => $doneSentAt,
        ]);

        $answers = [
            ['00000000-0000-0000-0a07-000000000001', 'overall_experience',    'rating_5', 5,    null],
            ['00000000-0000-0000-0a07-000000000002', 'wait_time',             'rating_5', 3,    null],
            ['00000000-0000-0000-0a07-000000000003', 'provider_communication', 'rating_5', 5,   null],
            ['00000000-0000-0000-0a07-000000000004', 'would_recommend',       'yes_no',   1,    null],
            [
                '00000000-0000-0000-0a07-000000000005',
                'comments',
                'text',
                null,
                'Dr. Diallo explained my HbA1c result clearly and checked my blood pressure log. The wait was long but the care was good.',
            ],
        ];

        $template = collect(app(\App\Services\Patient\SurveyService::class)->getTemplate(self::SURVEY_TEMPLATE))
            ->keyBy('key');

        foreach ($answers as [$id, $questionKey, $type, $numeric, $text]) {
            $question = $template->get($questionKey);

            $this->upsert(SurveyResponse::class, $id, [
                'patient_survey_id' => self::SURVEY_DONE,
                'question_key'      => $questionKey,
                'question_text'     => $question['text'] ?? $questionKey,
                'response_type'     => $question['type'] ?? $type,
                'numeric_response'  => $numeric,
                'text_response'     => $text,
                'created_at'        => $doneSentAt->copy()->addDay(),
            ]);
        }

        // Still open — sent after the blood-pressure check five days ago, so it
        // is inside the 7-day window and she can still fill it in.
        $pendingSentAt = Carbon::now()->subDays(5)->setTime(12, 30);
        $this->upsert(PatientSurvey::class, self::SURVEY_PENDING, [
            'patient_id'   => self::PATIENT,
            'facility_id'  => $facilityId,
            'template_key' => self::SURVEY_TEMPLATE,
            'status'       => self::SURVEY_SENT,
            'sent_at'      => $pendingSentAt,
            'completed_at' => null,
            'expires_at'   => Carbon::now()->addDays(2)->setTime(12, 30),
            'created_at'   => $pendingSentAt,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Directory name -> internal `facilities` id.
     *
     * Only `care_facilities` rows with a non-null `facility_id` are bookable
     * (17 when this was written, 121 since BookableFacilityNetworkSeeder — do
     * not hardcode the number). Slots and appointments hang off that id, and it
     * is the id MobileAppointmentController::book() writes to
     * `appointments.facility_id`.
     * Returning null for an unlinked facility keeps the seeder from writing an
     * appointment the app cannot link back to the directory.
     */
    private function resolveBookableFacilityId(string $facilityName): ?string
    {
        $facilityId = DB::table('care_facilities')
            ->where('facility_name', $facilityName)
            ->whereNotNull('facility_id')
            ->value('facility_id');

        if (! $facilityId) {
            return null;
        }

        return DB::table('facilities')->where('id', $facilityId)->exists()
            ? (string) $facilityId
            : null;
    }

    private function resolveUserId(string $userId): ?string
    {
        return DB::table('users')->where('id', $userId)->exists() ? $userId : null;
    }

    /**
     * Insert-or-update by fixed primary key, soft-delete aware.
     *
     * forceFill is used deliberately: `created_at` is not fillable on these
     * models but has to be pinned so the seeded history keeps its clinical
     * ordering (ReferralCase and CarePlan are both listed with ->latest()).
     */
    private function upsert(string $modelClass, string $id, array $attributes): Model
    {
        $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($modelClass), true);

        /** @var Model|null $model */
        $model = ($usesSoftDeletes ? $modelClass::withTrashed() : $modelClass::query())->find($id);

        if ($model === null) {
            $model = new $modelClass();
            $model->{$model->getKeyName()} = $id;
        } elseif ($usesSoftDeletes && $model->trashed()) {
            $model->restore();
        }

        $model->forceFill($attributes)->save();

        return $model;
    }
}
