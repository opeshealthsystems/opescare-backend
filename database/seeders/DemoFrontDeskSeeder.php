<?php

namespace Database\Seeders;

use App\Models\FacilityRoleAssignment;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\Support\DemoFacilityResolver;
use Database\Seeders\Support\DemoStoryIds;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Today's appointment book at the demo front desk.
 *
 * A reviewer signing in to the clinician app as demo.receptionist@ should
 * open a desk with work on it: five appointments for the five demo patients
 * (reviewer, patient, boy, girl, grandmother), spread across the demo
 * doctor, nurse and specialist, in every state the desk handles — one
 * awaiting confirmation, one confirmed, one already checked in (with its
 * visit open), one no-show, and one nobody has been assigned to yet, waiting
 * to be routed.
 *
 *   - The facility is wherever demo.receptionist@ is POSTED (her active
 *     facility_role_assignments row, else her primary facility) — never a
 *     hard-coded id, because the two facility registers on this estate do
 *     not agree on ids.
 *   - The demo clinicians are posted at that facility too (an active
 *     assignment on their own role), so GET /desk/providers lists them and
 *     routing to them passes FrontDeskService::isClinicianAt(). Real people
 *     are never touched: every user and patient here must be is_demo.
 *   - Idempotent: row ids derive from the patient id (DemoStoryIds), so a
 *     re-run rewrites the same five rows onto TODAY and never piles up. A
 *     patient this estate does not have (the grandmother comes from
 *     DemoFamilySeeder) is skipped with a note, not invented.
 *   - NOT registered in DatabaseSeeder — run it explicitly, after the demo
 *     accounts exist:
 *         php artisan db:seed --class=DemoFrontDeskSeeder --force
 */
class DemoFrontDeskSeeder extends Seeder
{
    public const RECEPTIONIST_EMAIL = 'demo.receptionist@opescare.test';

    /** Posted at the desk's facility so appointments can be routed to them. */
    public const CLINICIAN_EMAILS = [
        'demo.doctor@opescare.test',
        'demo.nurse@opescare.test',
        'demo.specialist@opescare.test',
    ];

    /**
     * Today's book: [patient email, clinician email|null, HH:MM, status, type, reason].
     *
     * @var list<array{0:string,1:?string,2:string,3:string,4:string,5:string}>
     */
    public const BOOK = [
        ['demo.girl@opescare.test',        'demo.specialist@opescare.test', '08:30', 'no_show',    'consultation',     'Referred for a cardiology opinion'],
        ['demo.reviewer@opescare.test',    'demo.doctor@opescare.test',     '09:00', 'confirmed',  'consultation',     'Quarterly review'],
        ['demo.patient@opescare.test',     'demo.doctor@opescare.test',     '09:30', 'checked_in', 'follow_up',        'Follow-up after the last visit'],
        ['demo.boy@opescare.test',         'demo.nurse@opescare.test',      '10:00', 'scheduled',  'vaccination',      'EPI catch-up dose'],
        ['demo.grandmother@opescare.test', null,                            '11:00', 'scheduled',  'geriatric_review', 'Walked in — waiting to be routed to a clinician'],
    ];

    public function run(): void
    {
        DemoFacilityResolver::flush();

        $receptionist = $this->demoUser(self::RECEPTIONIST_EMAIL);

        if (! $receptionist) {
            $this->command?->warn('DemoFrontDeskSeeder: ' . self::RECEPTIONIST_EMAIL . ' is not a demo account on this estate — run FinderDemoSeeder first. Nothing seeded.');

            return;
        }

        $facilityId = FacilityRoleAssignment::query()
            ->active()
            ->where('user_id', $receptionist->id)
            ->orderByDesc('assigned_at')
            ->value('facility_id')
            ?? $receptionist->primary_facility_id;

        if (! $facilityId) {
            $this->command?->warn('DemoFrontDeskSeeder: the demo receptionist is posted nowhere — nothing seeded.');

            return;
        }

        $facilityId   = (string) $facilityId;
        $facilityName = DB::table('facilities')->where('id', $facilityId)->value('name') ?? $facilityId;
        $this->command?->info("── Front desk at {$facilityName}");

        $clinicianIds = $this->postClinicians($facilityId);

        $today   = now(config('app.timezone'))->startOfDay();
        $written = 0;

        foreach (self::BOOK as [$patientEmail, $clinicianEmail, $time, $status, $type, $reason]) {
            $patient = Patient::withoutGlobalScopes()->where('email', $patientEmail)->first();

            if (! $patient) {
                $this->command?->warn("  skip {$patientEmail}: no patient record on this estate");
                continue;
            }

            if (! $patient->is_demo) {
                $this->command?->warn("  refuse {$patientEmail}: not a demo record — the book is never written onto a real person");
                continue;
            }

            $providerId = $clinicianEmail !== null ? ($clinicianIds[$clinicianEmail] ?? null) : null;

            if ($clinicianEmail !== null && $providerId === null) {
                $this->command?->warn("  {$patientEmail}: {$clinicianEmail} is missing — booked unassigned instead");
            }

            [$hour, $minute] = array_map('intval', explode(':', $time));
            $at = $today->copy()->setTime($hour, $minute);

            $this->writeAppointment($patient, $facilityId, $providerId, $at, $status, $type, $reason, (string) $receptionist->id);
            $written++;

            $this->command?->info(sprintf('  %s  %-32s %-11s %s', $at->format('H:i'), $patientEmail, $status, $clinicianEmail ?? '(unassigned)'));
        }

        $this->command?->info("  {$written} appointment(s) on today's book");
    }

    // ── People ───────────────────────────────────────────────────────────────

    /** A demo login by email, past the demo scope; null for a missing or REAL account. */
    private function demoUser(string $email): ?User
    {
        $user = User::withoutGlobalScopes()->where('email', $email)->first();

        return $user && $user->is_demo ? $user : null;
    }

    /**
     * Post each demo clinician at the desk's facility on their own role, so
     * they are routable. Returns email → user id for the ones this estate has.
     *
     * @return array<string,string>
     */
    private function postClinicians(string $facilityId): array
    {
        $ids = [];

        foreach (self::CLINICIAN_EMAILS as $email) {
            $user = $this->demoUser($email);

            if (! $user || ! $user->role_id) {
                $this->command?->warn("  skip {$email}: no demo account (or no role) on this estate");
                continue;
            }

            FacilityRoleAssignment::updateOrCreate(
                ['user_id' => $user->id, 'facility_id' => $facilityId, 'role_id' => $user->role_id],
                ['is_active' => true, 'expires_at' => null, 'assigned_at' => now()],
            );

            $ids[$email] = (string) $user->id;
        }

        return $ids;
    }

    // ── Rows ─────────────────────────────────────────────────────────────────

    private function writeAppointment(
        Patient $patient,
        string $facilityId,
        ?string $providerId,
        \Illuminate\Support\Carbon $at,
        string $status,
        string $type,
        string $reason,
        string $receptionistId,
    ): void {
        $appointmentId = DemoStoryIds::derived($patient->id, 'demo-front-desk:appointment:today');
        $visitId       = null;

        if ($status === 'checked_in') {
            $visitId = DemoStoryIds::derived($patient->id, 'demo-front-desk:visit:today');

            $this->upsert('visits', $visitId, [
                'patient_id'    => $patient->id,
                'facility_id'   => $facilityId,
                'provider_id'   => $providerId,
                'visit_type'    => $type,
                'status'        => 'open',
                'started_at'    => $at->copy()->subMinutes(10),
                'ended_at'      => null,
                'is_demo'       => true,
                'demo_seed_key' => 'demo-front-desk:visit:today',
            ]);
        }

        $this->upsert('appointments', $appointmentId, [
            'patient_id'            => $patient->id,
            'facility_id'           => $facilityId,
            'provider_id'           => $providerId,
            'appointment_slot_id'   => null,
            'visit_id'              => $visitId,
            'appointment_type'      => $type,
            'status'                => $status,
            'scheduled_at'          => $at,
            'booked_by_type'        => 'staff',
            'booked_by_id'          => $receptionistId,
            'reason'                => $reason,
            'cancellation_reason'   => null,
            'cancelled_by_id'       => null,
            'cancelled_at'          => null,
            'checked_in_at'         => $status === 'checked_in' ? $at->copy()->subMinutes(10) : null,
            'no_show_at'            => $status === 'no_show' ? $at->copy()->addMinutes(30) : null,
            'billing_deferred'      => true,
            'telemedicine_deferred' => true,
        ]);

        // The ledger: how the row got to where it is. Booked by the desk, then
        // (where the status says so) one more step — same shape
        // FrontDeskService::history() writes.
        $this->upsert('appointment_status_histories', DemoStoryIds::derived($patient->id, 'demo-front-desk:history:booked'), [
            'appointment_id'  => $appointmentId,
            'from_status'     => null,
            'to_status'       => 'scheduled',
            'changed_by'      => $receptionistId,
            'changed_by_type' => 'provider',
            'reason'          => 'Appointment booked by the front desk.',
            'metadata'        => json_encode(['channel' => 'demo_seed']),
            'changed_at'      => $at->copy()->subDays(2),
        ]);

        if ($status !== 'scheduled') {
            $this->upsert('appointment_status_histories', DemoStoryIds::derived($patient->id, 'demo-front-desk:history:' . $status), [
                'appointment_id'  => $appointmentId,
                'from_status'     => 'scheduled',
                'to_status'       => $status,
                'changed_by'      => $receptionistId,
                'changed_by_type' => 'provider',
                'reason'          => match ($status) {
                    'confirmed'  => 'Appointment confirmed by the front desk.',
                    'checked_in' => 'Patient checked in by the front desk.',
                    'no_show'    => 'Marked as a no-show by the front desk.',
                    default      => null,
                },
                'metadata'        => json_encode(['channel' => 'demo_seed'] + ($visitId ? ['visit_id' => $visitId] : [])),
                'changed_at'      => match ($status) {
                    'confirmed'  => $at->copy()->subDay(),
                    'checked_in' => $at->copy()->subMinutes(10),
                    'no_show'    => $at->copy()->addMinutes(30),
                    default      => $at,
                },
            ]);
        }
    }

    /** Insert once (created_at stamped), rewrite every run after that. */
    private function upsert(string $table, string $id, array $values): void
    {
        $exists = DB::table($table)->where('id', $id)->exists();

        if ($exists) {
            DB::table($table)->where('id', $id)->update($values + ['updated_at' => now()]);

            return;
        }

        DB::table($table)->insert($values + ['id' => $id, 'created_at' => now(), 'updated_at' => now()]);
    }
}
