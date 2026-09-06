<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\FamilyLink;
use App\Models\ImmunizationRecord;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Modules\PatientIdentity\Exceptions\DuplicateIdentityFound;
use App\Modules\PatientIdentity\Services\PatientIdentityService;
use App\Services\Identity\HealthIdGeneratorService;
use Carbon\Carbon;
use Database\Seeders\Support\DemoFacilityResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * The family demo: a guardian managing two CHILDREN, and an adult patient
 * managing an OLDER PERSON.
 *
 *   demo.guardian@opescare.test  (Demo Guardian, 1985)      ── parent ──▶ demo.boy@, demo.girl@   full access
 *   demo.reviewer@opescare.test  (existing demo patient)    ── child  ──▶ demo.grandmother@       read only
 *
 * `relationship` is the GUARDIAN's role toward the dependent (FamilyLink
 * docblock): the guardian is the children's `parent`; the reviewer is the
 * grandmother's `child`. The grandmother's link is the invitation flow's end
 * state — created_by `invite_accepted`, `dependent_consented_at` set — so an
 * adult dependent's consent is on file, not implied.
 *
 * demo.guardian@ existed as a user with no Patient row, which is why she could
 * never sign in to the app (login requires a patient by e-mail). She gets one
 * here. Every login uses the Finder demo password `demo1234`. Everything is
 * flagged is_demo and a second run changes nothing.
 *
 *   php artisan db:seed --class=DemoFamilySeeder --force
 */
class DemoFamilySeeder extends Seeder
{
    public const PASSWORD = 'demo1234';

    public const GUARDIAN_EMAIL    = 'demo.guardian@opescare.test';
    public const GRANDMOTHER_EMAIL = 'demo.grandmother@opescare.test';
    public const REVIEWER_EMAIL    = 'demo.reviewer@opescare.test';

    /** @var array<string, mixed> */
    public const GUARDIAN = [
        'email'       => self::GUARDIAN_EMAIL,
        'first_name'  => 'Demo',
        'last_name'   => 'Guardian',
        'sex'         => 'female',
        'dob'         => '1985-06-02',
        'blood_group' => 'O+',
    ];

    /** @var array<string, mixed> */
    public const GRANDMOTHER = [
        'email'       => self::GRANDMOTHER_EMAIL,
        'first_name'  => 'Demo',
        'last_name'   => 'Grandmother',
        'sex'         => 'female',
        'dob'         => '1948-02-20',
        'blood_group' => 'A+',
    ];

    /**
     * Age-appropriate immunisations for a woman of 78 — the adult schedule,
     * not the EPI one: [code, name, dose, years ago, route].
     *
     * @var list<array{0: string, 1: string, 2: int, 3: float, 4: string}>
     */
    private const GRANDMOTHER_SHOTS = [
        ['FLU',    'Seasonal influenza',                 1, 0.9, 'IM'],
        ['TD',     'Tetanus-diphtheria booster',         1, 3.2, 'IM'],
        ['PPSV23', 'Pneumococcal polysaccharide (23v)',  1, 2.1, 'IM'],
    ];

    public function run(): void
    {
        // The resolver memoises facility ids per process; in one test run the
        // database is rebuilt between tests, so a stale id would violate the FK.
        DemoFacilityResolver::flush();

        $patientRole = Role::where('name', 'patient')->first();

        // The children come from their own seeder; make sure they are there.
        if (! $this->demoPatient('demo.boy@opescare.test') || ! $this->demoPatient('demo.girl@opescare.test')) {
            $this->call(DemoChildrenSeeder::class);
        }

        // 1. The guardian gets a Patient row so she can sign in to the app.
        $guardianPatient = $this->ensurePatient(self::GUARDIAN);
        $guardianUser    = $this->ensureUser(self::GUARDIAN, 'Demo Guardian', $guardianPatient, $patientRole?->id, keepRole: true);

        // 2. Parent → two children, full access, registered by the guardian.
        foreach (DemoChildrenSeeder::CHILDREN as $child) {
            $dependent = $this->demoPatient($child['email']);
            if (! $dependent) {
                $this->command?->warn("  skip {$child['email']}: no demo patient");
                continue;
            }
            $link = $this->ensureLink($guardianUser, $dependent, 'parent', FamilyLink::ACCESS_FULL, FamilyLink::CREATED_BY_SELF_REGISTERED);
            $this->command?->info(sprintf('  %-28s ── parent ──▶ %-26s %s', self::GUARDIAN_EMAIL, $child['email'], $link->access_level));
        }

        // 3. The grandmother: her own Health ID, her own login.
        $grandmother = $this->ensurePatient(self::GRANDMOTHER);
        $this->ensureUser(self::GRANDMOTHER, 'Demo Grandmother', $grandmother, $patientRole?->id);
        $this->ensureGrandmotherRecords($grandmother);

        // 4. The reviewer (an adult patient) manages her mother, read only,
        //    exactly as the invitation flow would have left it.
        $reviewer = User::withoutGlobalScopes()->where('email', self::REVIEWER_EMAIL)->first();
        if ($reviewer) {
            $link = $this->ensureLink($reviewer, $grandmother, 'child', FamilyLink::ACCESS_READ_ONLY, FamilyLink::CREATED_BY_INVITE_ACCEPTED, consented: true);
            $this->command?->info(sprintf('  %-28s ── child  ──▶ %-26s %s', self::REVIEWER_EMAIL, self::GRANDMOTHER_EMAIL, $link->access_level));
        } else {
            $this->command?->warn('  skip reviewer → grandmother: ' . self::REVIEWER_EMAIL . ' has no user (run the demo user seeders first)');
        }

        $this->command?->info(sprintf(
            '  logins: %s / %s and %s / %s',
            self::GUARDIAN_EMAIL, self::PASSWORD, self::GRANDMOTHER_EMAIL, self::PASSWORD
        ));
    }

    // ── People ───────────────────────────────────────────────────────────────

    private function demoPatient(string $email): ?Patient
    {
        return Patient::withoutGlobalScopes()->where('email', $email)->first();
    }

    /** @param array<string, mixed> $person */
    private function ensurePatient(array $person): Patient
    {
        $existing = $this->demoPatient($person['email']);

        if (! $existing) {
            $data = [
                'first_name'      => $person['first_name'],
                'last_name'       => $person['last_name'],
                'date_of_birth'   => $person['dob'],
                'sex'             => $person['sex'],
                'identity_status' => 'verified',
                'country_code'    => 'CM',
            ];

            try {
                $existing = app(PatientIdentityService::class)->createPatientCandidate($data);
            } catch (DuplicateIdentityFound) {
                // The MPI saw a look-alike (another demo row). Demo rows are
                // not real people: mint the Health ID directly instead of
                // sending a seeder through the reconciliation desk.
                $existing = app(HealthIdGeneratorService::class)->generate('CM', fn (string $healthId) => Patient::create($data + ['health_id' => $healthId]));
            }
        }

        // Idempotent demo facts, every run.
        $existing->forceFill([
            'email'               => $person['email'],
            'blood_group'         => $person['blood_group'],
            'is_demo'             => true,
            'identity_status'     => 'verified',
            'verification_status' => 'verified',
        ])->save();

        return $existing->refresh();
    }

    /** @param array<string, mixed> $person */
    private function ensureUser(array $person, string $name, Patient $patient, ?string $patientRoleId, bool $keepRole = false): User
    {
        // Past the demo scope: with demo mode off, is_demo users are invisible to
        // a plain query, and a blind create() would hit users_email_unique.
        $user = User::withoutGlobalScopes()->where('email', $person['email'])->first();

        if (! $user) {
            $user = User::create([
                'name'       => $name,
                'email'      => $person['email'],
                'password'   => Hash::make(self::PASSWORD),
                'patient_id' => $patient->id,
            ]);
        }

        $facts = [
            'name'                 => $name,
            'password'             => Hash::make(self::PASSWORD),
            'patient_id'           => $patient->id,
            'is_demo'              => true,
            'email_verified_at'    => $user->email_verified_at ?? now(),
            // The guardian portal's profile gate reads this; without it she
            // is bounced to "complete your profile" on every web page.
            'profile_completed_at' => $user->profile_completed_at ?? now(),
        ];

        // demo.guardian@ was seeded with the `guardian` role; leave a role that
        // is already set alone and only fill an empty one.
        if ($patientRoleId && (! $keepRole || ! $user->role_id)) {
            $facts['role_id'] = $patientRoleId;
        }

        $user->forceFill($facts)->save();

        return $user;
    }

    // ── Links ────────────────────────────────────────────────────────────────

    private function ensureLink(User $guardian, Patient $dependent, string $relationship, string $accessLevel, string $createdBy, bool $consented = false): FamilyLink
    {
        $pair = ['guardian_user_id' => $guardian->id, 'dependent_patient_id' => $dependent->id];

        // Reuse the live row for the pair, else the most recent terminal one
        // (never a second row — the partial unique index would refuse it).
        $link = FamilyLink::query()->where($pair)->whereNotIn('status', FamilyLink::TERMINAL_STATUSES)->first()
            ?? FamilyLink::query()->where($pair)->orderByDesc('created_at')->first()
            ?? new FamilyLink($pair);

        $link->fill([
            'relationship'      => $relationship,
            'access_level'      => $accessLevel,
            'status'            => FamilyLink::STATUS_ACTIVE,
            'created_by'        => $createdBy,
            'invite_token'      => null,
            'invite_expires_at' => null,
        ]);

        if ($consented && ! $link->dependent_consented_at) {
            $link->dependent_consented_at = now();
        }

        $link->save();

        return $link;
    }

    // ── The grandmother's record ─────────────────────────────────────────────

    private function ensureGrandmotherRecords(Patient $grandmother): void
    {
        $facilityId = DemoFacilityResolver::primaryHospital();
        if (! $facilityId) {
            $this->command?->warn('  grandmother records skipped: no demo hospital in the directory');
            return;
        }

        foreach (self::GRANDMOTHER_SHOTS as [$code, $name, $dose, $yearsAgo, $route]) {
            $given = Carbon::now()->subDays((int) round($yearsAgo * 365))->setTime(10, 15);

            ImmunizationRecord::firstOrCreate(
                ['patient_id' => $grandmother->id, 'vaccine_code' => $code, 'dose_number' => $dose],
                [
                    'facility_id'         => $facilityId,
                    'vaccine_system'      => 'WHO-ATC',
                    'vaccine_name'        => $name,
                    'lot_number'          => sprintf('%s-%s-%02d', $code, $given->format('y'), $dose),
                    'administered_at'     => $given,
                    'dose_sequence'       => $code === 'FLU' ? 'seasonal' : 'booster',
                    'route'               => $route,
                    'status'              => 'completed',
                    'verification_status' => 'verified',
                    'is_historical'       => false,
                ]
            );
        }

        // One upcoming review so the detail screen has a next appointment;
        // pushed forward again if a re-run finds it in the past.
        $appointment = Appointment::firstOrCreate(
            ['patient_id' => $grandmother->id, 'appointment_type' => 'geriatric_review', 'status' => 'scheduled'],
            [
                'facility_id'    => $facilityId,
                'scheduled_at'   => Carbon::now()->addDays(12)->setTime(10, 0),
                'booked_by_type' => 'patient',
                'reason'         => 'Quarterly blood-pressure and mobility review',
            ]
        );

        if ($appointment->scheduled_at?->isPast()) {
            $appointment->update(['scheduled_at' => Carbon::now()->addDays(12)->setTime(10, 0)]);
        }
    }
}
