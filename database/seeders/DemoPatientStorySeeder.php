<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Modules\Governance\Services\AccessLogService;
use App\Services\Clinical\PrescriptionService;
use Database\Seeders\Support\DemoCareTeam;
use Database\Seeders\Support\DemoFacilityResolver;
use Database\Seeders\Support\DemoStoryIds;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The whole demo story, for every demo patient login.
 *
 * A reviewer signing in as demo.patient@… or demo.reviewer@… should be able to
 * open every screen of the app and find it populated with ONE coherent history
 * (DEMO_DATA_BRIEF.md): Type 2 diabetes and hypertension managed at Hôpital
 * Central de Yaoundé, the penicillin allergy that makes every medication screen
 * safe, quarterly reviews, a cardiology referral, a care plan, labs that prove
 * the honest paths, a live prescription, messages with the GP, a pending consent
 * request, an access log, an invoice paid and one due, and a bell with news.
 *
 * It runs the existing story seeders once per patient (they take a target via
 * `forPatient()` and derive their own stable ids — see Support\DemoStoryIds), and
 * adds here what no seeder covered: prescriptions (through PrescriptionService,
 * the only writer of clinical prescriptions), a message thread, consents, the
 * access log and invoices.
 *
 * Deliberate boundaries:
 *   - Demo patients only. A login whose patient row is not `is_demo` is refused
 *     by name, never written to.
 *   - Idempotent: every row is keyed on a stable, patient-derived id or marker,
 *     so a second run writes nothing new and refreshes the relative dates.
 *   - Real institutions and the demo care team only (DemoFacilityResolver,
 *     DemoCareTeam). Nothing invented.
 *   - NOT registered in DatabaseSeeder — run it explicitly:
 *         php artisan db:seed --class=DemoPatientStorySeeder
 *     Optionally DEMO_STORY_EMAILS="a@x,b@y" to target other demo logins.
 */
class DemoPatientStorySeeder extends Seeder
{
    public const DEFAULT_EMAILS = [
        'demo.patient@opescare.test',
        'demo.reviewer@opescare.test',
    ];

    /** Marker written into prescriptions.notes so a re-run finds its own rows. */
    private const RX_MARKER = '[demo-story:%s]';

    public function run(): void
    {
        $emails = array_values(array_filter(array_map('trim', explode(',', (string) (env('DEMO_STORY_EMAILS') ?: implode(',', self::DEFAULT_EMAILS))))));

        DemoCareTeam::ensure($this->command);

        $doctor = DemoCareTeam::doctor();
        if (! $doctor) {
            $this->command?->error('DemoPatientStorySeeder: no demo clinician could be resolved or created — nothing seeded.');
            return;
        }

        $hospital = DemoFacilityResolver::primaryHospital();
        if (! $hospital) {
            $this->command?->error('DemoPatientStorySeeder: "' . DemoFacilityResolver::PRIMARY_HOSPITAL . '" is not in the care_facilities registry — nothing seeded.');
            return;
        }

        foreach ($emails as $email) {
            $patient = Patient::query()->withoutGlobalScopes()->where('email', $email)->first();

            if (! $patient) {
                $this->command?->warn("  skip {$email}: no patient record");
                continue;
            }
            if (! $patient->is_demo) {
                $this->command?->warn("  refuse {$email}: not a demo record — the story is never written onto a real person");
                continue;
            }

            $this->command?->info("── {$patient->health_id}  ({$email})");

            foreach ([
                DemoClinicalCoreSeeder::class,
                DemoPatientVitalsSeeder::class,
                DemoLabResultsSeeder::class,
                DemoCarePathwaySeeder::class,
            ] as $class) {
                $this->resolve($class)->forPatient($patient->id)->run();
            }

            $this->seedPrescriptions($patient, $doctor, $hospital);
            $this->seedMessages($patient, $doctor);
            $this->seedConsents($patient, $doctor, $hospital);
            $this->seedAccessLog($patient, $doctor, $hospital);
            $this->seedInvoices($patient, $hospital);

            $this->report($patient);
        }

        $this->call(DemoNotificationsSeeder::class);
    }

    // ── Prescriptions ────────────────────────────────────────────────────────

    /**
     * Three prescriptions through PrescriptionService — the same path the
     * prescriber desk uses, so items, the RX document and the audit trail all
     * come out exactly as a real one: the current quarterly repeat (active),
     * the previous quarter's (dispensed), and the acute episode in Douala
     * (dispensed). No beta-lactam anywhere: the penicillin allergy is severe.
     */
    private function seedPrescriptions(Patient $patient, string $doctor, string $hospital): void
    {
        $service   = app(PrescriptionService::class);
        $douala    = DemoFacilityResolver::secondaryHospital() ?? $hospital;
        $dispenser = DB::table('users')->where('email', 'demo.pharmacist@opescare.test')->value('id') ?? $doctor;

        $chronic = [
            ['drug_name' => 'Metformin 1000 mg tablet', 'dose' => '1000 mg', 'frequency' => 'Twice daily with meals', 'route' => 'oral', 'duration_days' => 90, 'quantity' => 180],
            ['drug_name' => 'Amlodipine 5 mg tablet',   'dose' => '5 mg',    'frequency' => 'Once daily, morning',    'route' => 'oral', 'duration_days' => 90, 'quantity' => 90],
        ];

        $specs = [
            ['key' => 'rx_current',  'facility' => $hospital, 'at' => now()->subDays(18)->setTime(10, 5),           'dispensed' => false, 'items' => $chronic,
             'notes' => 'Quarterly review — repeat prescription. ALLERGY: penicillin (severe), peanuts (moderate).'],
            ['key' => 'rx_previous', 'facility' => $hospital, 'at' => now()->subWeeks(10)->setTime(10, 20),         'dispensed' => true,  'items' => $chronic,
             'notes' => 'Quarterly review — repeat prescription. ALLERGY: penicillin (severe).'],
            ['key' => 'rx_douala',   'facility' => $douala,   'at' => now()->subMonthsNoOverflow(22)->setTime(11, 40), 'dispensed' => true,
             'items' => [
                 ['drug_name' => 'Oral rehydration salts (ORS) sachet', 'dose' => '1 sachet in 1 L water', 'frequency' => 'After each loose stool', 'route' => 'oral', 'duration_days' => 3, 'quantity' => 10],
                 ['drug_name' => 'Paracetamol 500 mg tablet',            'dose' => '1000 mg',                'frequency' => 'Three times daily if needed', 'route' => 'oral', 'duration_days' => 5, 'quantity' => 30],
             ],
             'notes' => 'Acute gastroenteritis — episode of care while travelling. ALLERGY: penicillin (severe) — no beta-lactams.'],
        ];

        foreach ($specs as $spec) {
            $marker = sprintf(self::RX_MARKER, $spec['key']);

            $exists = Prescription::query()->withoutGlobalScopes()
                ->where('patient_id', $patient->id)
                ->where('notes', 'like', '%' . $marker . '%')
                ->exists();
            if ($exists) {
                continue;
            }

            $rx = $service->issue([
                'patient_id'    => $patient->id,
                'facility_id'   => $spec['facility'],
                'prescribed_by' => $doctor,
                'notes'         => $spec['notes'] . ' ' . $marker,
                'validity_days' => 90,
                'items'         => $spec['items'],
            ], $doctor);

            /** @var Carbon $at */
            $at = $spec['at'];

            if ($spec['dispensed']) {
                $rx = $service->dispense($rx, $dispenser);
                $dispensedAt = $at->copy()->addHours(3);
                $rx->forceFill(['dispensed_at' => $dispensedAt])->saveQuietly();
                DB::table('prescription_items')->where('prescription_id', $rx->id)
                    ->update(['dispensed_at' => $dispensedAt, 'created_at' => $at, 'updated_at' => $dispensedAt]);
            } else {
                DB::table('prescription_items')->where('prescription_id', $rx->id)
                    ->update(['created_at' => $at, 'updated_at' => $at]);
            }

            // The service stamps now(); the story happened when it happened.
            $rx->forceFill([
                'prescribed_at' => $at,
                'expires_at'    => $at->copy()->addDays(90),
                'created_at'    => $at,
                'updated_at'    => $spec['dispensed'] ? $at->copy()->addHours(3) : $at,
            ])->saveQuietly();

            DB::table('official_documents')
                ->where('patient_id', $patient->id)
                ->where('document_type', 'RX')
                ->where('payload_json->prescription_id', $rx->id)
                ->update(['issued_at' => $at, 'created_at' => $at, 'updated_at' => $at]);
        }
    }

    // ── Messages ─────────────────────────────────────────────────────────────

    /**
     * One thread with the GP about home blood-pressure readings — the exact
     * shape MobileMessagingController::createThread() writes: the patient is a
     * participant under patient_id (what the app keys on) AND under the linked
     * users row (what the staff inbox keys on); the doctor is the provider.
     * The last message is the doctor's and unread, so the badge has something
     * to count.
     */
    private function seedMessages(Patient $patient, string $doctor): void
    {
        $threadUuid = DemoStoryIds::derived($patient->id, 'demo-story:thread:bp-readings');

        if (DB::table('message_threads')->where('uuid', $threadUuid)->exists()) {
            return;
        }

        $patientUserId = User::query()->where('patient_id', $patient->id)->value('id')
            ?? User::query()->where('email', $patient->email)->value('id');

        $t0 = now()->subDays(4)->setTime(9, 12);
        $reviewDate = now()->addWeeks(3)->translatedFormat('j F');

        $messages = [
            [$patient->id, $t0,                                  'Good morning Doctor. My home readings this week were 142/90, 138/88 and 145/91. Should I change anything before the review?'],
            [$doctor,      $t0->copy()->addHours(3),             "Thank you for logging them. Keep Amlodipine 5 mg exactly as prescribed and bring the log to the review on {$reviewDate}. If you get a headache or a reading above 160/100, come in the same day."],
            [$patient->id, $t0->copy()->addDay()->setTime(8, 5), 'Understood, thank you. I have also booked the fasting sample for the lipid panel.'],
            [$doctor,      $t0->copy()->addDay()->setTime(10, 40), 'Perfect — nothing to eat after 22:00 the night before. See you at the review.'],
        ];
        $lastAt = end($messages)[1];

        $threadId = DB::table('message_threads')->insertGetId([
            'uuid'         => $threadUuid,
            'thread_type'  => 'patient_provider',
            'context_type' => 'appointment',
            'context_id'   => DemoStoryIds::uid($patient->id, DemoCarePathwaySeeder::APPT_UPCOMING),
            'title'        => 'Blood-pressure readings before the next review',
            'priority'     => 'normal',
            'status'       => 'open',
            'created_by'   => $patient->id,
            'created_at'   => $t0,
            'updated_at'   => $lastAt,
        ]);

        $participants = [
            [$patient->id, 'patient', $messages[2][1]],   // has read up to their own last message
            [$doctor, 'provider', $lastAt],
        ];
        if ($patientUserId && $patientUserId !== $patient->id) {
            $participants[] = [$patientUserId, 'patient', $messages[2][1]];
        }

        foreach ($participants as [$userId, $role, $lastRead]) {
            DB::table('message_thread_participants')->insert([
                'thread_id'      => $threadId,
                'user_id'        => $userId,
                'role_in_thread' => $role,
                'status'         => 'active',
                'last_read_at'   => $lastRead,
                'created_at'     => $t0,
                'updated_at'     => $t0,
            ]);
        }

        foreach ($messages as $i => [$sender, $at, $body]) {
            DB::table('messages')->insert([
                'uuid'         => DemoStoryIds::derived($patient->id, 'demo-story:message:' . $i),
                'thread_id'    => $threadId,
                'sender_id'    => $sender,
                'message_type' => 'text',
                'body'         => $body,
                'status'       => 'sent',
                'created_at'   => $at,
                'updated_at'   => $at,
            ]);
        }
    }

    // ── Consents ─────────────────────────────────────────────────────────────

    /**
     * An approved request from the home hospital with its active grant, and a
     * pending one from the cardiology centre — so the consent screen shows both
     * a decision to make and a decision already made.
     */
    private function seedConsents(Patient $patient, string $doctor, string $hospital): void
    {
        $cardiology = DemoFacilityResolver::resolve('CHU de Yaoundé') ?? $hospital;
        $specialist = DemoCareTeam::specialist() ?? $doctor;
        $scope      = ['conditions', 'medications', 'allergies', 'lab_results', 'vitals'];

        $approvedId = DemoStoryIds::derived($patient->id, 'demo-story:consent-request:approved');
        $grantId    = DemoStoryIds::derived($patient->id, 'demo-story:consent-grant:home');
        $pendingId  = DemoStoryIds::derived($patient->id, 'demo-story:consent-request:pending');

        $approvedAt = now()->subWeeks(10)->setTime(9, 0);
        $pendingAt  = now()->subDays(2)->setTime(15, 20);

        $demoFlag = fn (string $table) => Schema::hasColumn($table, 'is_demo') ? ['is_demo' => true] : [];

        DB::table('consent_requests')->updateOrInsert(['id' => $approvedId], [
            'patient_id'             => $patient->id,
            'requesting_facility_id' => $hospital,
            'requesting_user_id'     => $doctor,
            'purpose'                => 'treatment',
            'requested_scope'        => json_encode($scope),
            'duration_minutes'       => 60 * 24 * 90,
            'status'                 => 'approved',
            'created_at'             => $approvedAt,
            'updated_at'             => $approvedAt->copy()->addMinutes(12),
        ] + $demoFlag('consent_requests'));

        DB::table('consent_grants')->updateOrInsert(['id' => $grantId], [
            'patient_id'         => $patient->id,
            'facility_id'        => $hospital,
            'consent_request_id' => $approvedId,
            'authorizing_actor'  => 'patient',
            'scope'              => json_encode($scope),
            'status'             => 'active',
            'expires_at'         => $approvedAt->copy()->addDays(90),
            'created_at'         => $approvedAt->copy()->addMinutes(12),
            'updated_at'         => $approvedAt->copy()->addMinutes(12),
        ] + $demoFlag('consent_grants'));

        DB::table('consent_requests')->updateOrInsert(['id' => $pendingId], [
            'patient_id'             => $patient->id,
            'requesting_facility_id' => $cardiology,
            'requesting_user_id'     => $specialist,
            'purpose'                => 'treatment',
            'requested_scope'        => json_encode(['conditions', 'medications', 'vitals', 'lab_results']),
            'duration_minutes'       => 60 * 24,
            'status'                 => 'pending_patient_approval',
            'created_at'             => $pendingAt,
            'updated_at'             => $pendingAt,
        ] + $demoFlag('consent_requests'));
    }

    // ── Access log ───────────────────────────────────────────────────────────

    /**
     * What the GP read at the last quarterly review, written through
     * AccessLogService like every real access, then dated to that visit. The
     * purpose/category vocabulary is the one the treating-clinician surface
     * already writes ("treatment:treating_clinician", allergies, medications…).
     */
    private function seedAccessLog(Patient $patient, string $doctor, string $hospital): void
    {
        $already = DB::table('access_logs')
            ->where('patient_id', $patient->id)
            ->where('actor_id', $doctor)
            ->count();
        if ($already >= 5) {
            return;
        }

        $reviewAt = now()->subDays(18)->setTime(10, 2);
        $reads = [
            ['patient_identity', 'Patient',       $patient->id],
            ['allergies',        'AllergyRecord', DemoStoryIds::uid($patient->id, '00000000-0000-0000-0c03-000000000001')],
            ['problem_list',     'ProblemList',   DemoStoryIds::uid($patient->id, '00000000-0000-0000-0c02-000000000001')],
            ['medications',      'Prescription',  null],
            ['vitals',           'VitalSign',     DemoStoryIds::uid($patient->id, '00000000-0000-0000-0015-100000000001')],
        ];

        foreach ($reads as $i => [$category, $resourceType, $resourceId]) {
            try {
                $log = AccessLogService::log(
                    patientId: $patient->id,
                    actorId: $doctor,
                    actorType: 'user',
                    organizationId: null,
                    facilityId: $hospital,
                    purpose: 'treatment:treating_clinician',
                    dataCategory: $category,
                    resourceType: $resourceType,
                    resourceId: $resourceId,
                    accessType: 'read',
                );
                // access_logs is append-only and has no updated_at; only the
                // clock is moved, to the visit the read belongs to.
                $at = $reviewAt->copy()->addMinutes($i * 2);
                DB::table('access_logs')->where('id', $log->id)->update(['created_at' => $at]);
            } catch (\Throwable $e) {
                $this->command?->warn('  access log entry skipped: ' . $e->getMessage());
            }
        }
    }

    // ── Invoices ─────────────────────────────────────────────────────────────

    /**
     * One consultation fee paid at the last review, one due for the upcoming
     * one — so the payments screen shows a receipt and something to pay
     * (MTN MoMo / Orange Money), in XAF.
     */
    private function seedInvoices(Patient $patient, string $hospital): void
    {
        $short = strtoupper(substr(str_replace('-', '', $patient->id), 0, 6));
        $rows = [
            ['ref' => "OC-DEMO-{$short}-1", 'appt' => DemoCarePathwaySeeder::APPT_REVIEW_Q1, 'status' => 'paid',
             'description' => 'Consultation fee — quarterly review', 'amount' => 5000, 'at' => now()->subWeeks(10)->setTime(9, 5), 'paid_at' => now()->subWeeks(10)->setTime(9, 12), 'expires_at' => null],
            ['ref' => "OC-DEMO-{$short}-2", 'appt' => DemoCarePathwaySeeder::APPT_UPCOMING,  'status' => 'pending',
             'description' => 'Consultation fee — upcoming review', 'amount' => 5000, 'at' => now()->subDays(2)->setTime(8, 0), 'paid_at' => null, 'expires_at' => now()->addWeeks(3)->setTime(9, 30)],
        ];

        foreach ($rows as $row) {
            $values = [
                'patient_id'   => $patient->id,
                'facility_id'  => $hospital,
                'subject_type' => 'appointment',
                'subject_id'   => DemoStoryIds::uid($patient->id, $row['appt']),
                'description'  => $row['description'],
                'amount_minor' => $row['amount'],
                'currency'     => 'XAF',
                'status'       => $row['status'],
                'expires_at'   => $row['expires_at'],
                'paid_at'      => $row['paid_at'],
                'created_at'   => $row['at'],
                'updated_at'   => $row['paid_at'] ?? $row['at'],
            ];
            if (Schema::hasColumn('mobile_invoices', 'is_demo')) {
                $values['is_demo'] = true;
            }
            if (! DB::table('mobile_invoices')->where('reference', $row['ref'])->exists()) {
                $values['id'] = (string) Str::orderedUuid();
            }

            DB::table('mobile_invoices')->updateOrInsert(['reference' => $row['ref']], $values);
        }
    }

    // ── Reporting ────────────────────────────────────────────────────────────

    private function report(Patient $patient): void
    {
        $counts = [];
        foreach ([
            'visits', 'diagnoses', 'allergy_records', 'immunization_records', 'triage_vital_signs',
            'lab_orders', 'lab_results', 'prescriptions', 'appointments', 'referral_cases', 'care_plans',
            'patient_surveys', 'consent_requests', 'consent_grants', 'access_logs', 'mobile_invoices', 'official_documents',
        ] as $table) {
            if (Schema::hasTable($table)) {
                $counts[] = $table . '=' . DB::table($table)->where('patient_id', $patient->id)->count();
            }
        }
        $threads = DB::table('message_thread_participants')->where('user_id', $patient->id)->count();
        $this->command?->info('   ' . implode(' · ', $counts) . " · message_threads={$threads}");
    }
}
