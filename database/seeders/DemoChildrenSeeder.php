<?php

namespace Database\Seeders;

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
 * Two paediatric demo patients with their own logins — a boy and a girl — so
 * the child variants of the Health ID's anatomical hologram can be seen on a
 * real credential, and so a reviewer can walk a child's record.
 *
 * Each child gets a real CM-HID Health ID through PatientIdentityService (the
 * only sanctioned way to create a patient), a verified identity, a login user
 * (password `demo1234`, like every Finder demo account) and an age-appropriate
 * EPI immunisation history at the demo hospital. Everything is flagged
 * is_demo, and a second run changes nothing.
 *
 *   php artisan db:seed --class=DemoChildrenSeeder --force
 */
class DemoChildrenSeeder extends Seeder
{
    public const PASSWORD = 'demo1234';

    /** @var list<array<string, mixed>> */
    public const CHILDREN = [
        [
            'email'       => 'demo.boy@opescare.test',
            'first_name'  => 'Demo',
            'last_name'   => 'Boy',
            'sex'         => 'male',
            'dob'         => '2017-03-12',
            'blood_group' => 'B+',
            'hpv'         => false,
        ],
        [
            'email'       => 'demo.girl@opescare.test',
            'first_name'  => 'Demo',
            'last_name'   => 'Girl',
            'sex'         => 'female',
            'dob'         => '2013-11-05',
            'blood_group' => 'A-',
            'hpv'         => true, // Cameroon EPI: HPV for girls from age 9
        ],
    ];

    /**
     * Cameroon EPI schedule, as weeks (or months) after birth.
     * [code, name, dose_number, weeks_after_birth, route]
     *
     * @var list<array{0: string, 1: string, 2: int, 3: int, 4: string}>
     */
    private const SCHEDULE = [
        ['BCG',    'BCG',                              1, 0,  'intradermal'],
        ['OPV',    'Oral polio vaccine',               0, 0,  'oral'],
        ['PENTA',  'Pentavalent (DTP-HepB-Hib)',       1, 6,  'IM'],
        ['PCV13',  'Pneumococcal conjugate',           1, 6,  'IM'],
        ['ROTA',   'Rotavirus',                        1, 6,  'oral'],
        ['PENTA',  'Pentavalent (DTP-HepB-Hib)',       2, 10, 'IM'],
        ['PCV13',  'Pneumococcal conjugate',           2, 10, 'IM'],
        ['ROTA',   'Rotavirus',                        2, 10, 'oral'],
        ['PENTA',  'Pentavalent (DTP-HepB-Hib)',       3, 14, 'IM'],
        ['PCV13',  'Pneumococcal conjugate',           3, 14, 'IM'],
        ['IPV',    'Inactivated polio vaccine',        1, 14, 'IM'],
        ['MR',     'Measles-Rubella',                  1, 39, 'SC'],
        ['YF',     'Yellow fever',                     1, 39, 'SC'],
        ['MR',     'Measles-Rubella',                  2, 65, 'SC'],
    ];

    public function run(): void
    {
        // The resolver memoises facility ids per process; in one test run the
        // database is rebuilt between tests, so a stale id would violate the FK.
        DemoFacilityResolver::flush();

        $role       = Role::where('name', 'patient')->first();
        $facilityId = DemoFacilityResolver::primaryHospital();

        foreach (self::CHILDREN as $child) {
            $patient = $this->ensurePatient($child);
            $user    = $this->ensureUser($child, $patient, $role?->id);
            $shots   = $facilityId ? $this->ensureImmunisations($patient, $child, $facilityId) : 0;

            $this->command?->info(sprintf(
                '%-26s %s  %s %s  %s  user=%s  immunisations=%s',
                $child['email'],
                $patient->health_id,
                $child['sex'],
                $child['dob'],
                $patient->verification_status?->value ?? '-',
                $user->id,
                $facilityId ? $shots : 'skipped (no demo hospital)'
            ));
        }
    }

    /** @param array<string, mixed> $child */
    private function ensurePatient(array $child): Patient
    {
        $existing = Patient::withoutGlobalScopes()->where('email', $child['email'])->first();

        if (! $existing) {
            $data = [
                'first_name'      => $child['first_name'],
                'last_name'       => $child['last_name'],
                'date_of_birth'   => $child['dob'],
                'sex'             => $child['sex'],
                'identity_status' => 'verified',
                'country_code'    => 'CM',
            ];

            try {
                $existing = app(PatientIdentityService::class)->createPatientCandidate($data);
            } catch (DuplicateIdentityFound) {
                // The MPI saw a look-alike (another demo child). Demo rows are
                // not real people: mint the Health ID directly instead of
                // sending a seeder through the reconciliation desk.
                $existing = app(HealthIdGeneratorService::class)->generate('CM', fn (string $healthId) => Patient::create($data + ['health_id' => $healthId]));
            }
        }

        // Idempotent demo facts, every run.
        $existing->forceFill([
            'email'               => $child['email'],
            'blood_group'         => $child['blood_group'],
            'is_demo'             => true,
            'identity_status'     => 'verified',
            'verification_status' => 'verified',
        ])->save();

        return $existing->refresh();
    }

    /** @param array<string, mixed> $child */
    private function ensureUser(array $child, Patient $patient, ?string $roleId): User
    {
        // Past the demo scope: with demo mode off, is_demo users are invisible to
        // a plain query, and a blind create() would hit users_email_unique.
        $user = User::withoutGlobalScopes()->where('email', $child['email'])->first();

        if (! $user) {
            $user = User::create([
                'name'       => trim($child['first_name'] . ' ' . $child['last_name']),
                'email'      => $child['email'],
                'password'   => Hash::make(self::PASSWORD),
                'patient_id' => $patient->id,
            ]);
        }

        $user->forceFill(array_filter([
            'password'          => Hash::make(self::PASSWORD),
            'patient_id'        => $patient->id,
            'is_demo'           => true,
            'role_id'           => $roleId,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ], fn ($v) => $v !== null))->save();

        return $user;
    }

    /** @param array<string, mixed> $child */
    private function ensureImmunisations(Patient $patient, array $child, string $facilityId): int
    {
        $birth    = Carbon::parse($child['dob']);
        $schedule = self::SCHEDULE;

        if ($child['hpv']) {
            $schedule[] = ['HPV', 'Human papillomavirus', 1, 9 * 52 + 20, 'IM'];
        }

        $count = 0;

        foreach ($schedule as [$code, $name, $dose, $weeks, $route]) {
            $given = $birth->copy()->addWeeks($weeks)->addDays($weeks === 0 ? 1 : 3)->setTime(9, 30);

            if ($given->isFuture()) {
                continue;
            }

            ImmunizationRecord::firstOrCreate(
                ['patient_id' => $patient->id, 'vaccine_code' => $code, 'dose_number' => $dose],
                [
                    'facility_id'         => $facilityId,
                    'vaccine_system'      => 'WHO-EPI',
                    'vaccine_name'        => $name,
                    'lot_number'          => sprintf('%s-%s-%02d', $code, $given->format('y'), $dose),
                    'administered_at'     => $given,
                    'dose_sequence'       => $dose === 0 ? 'birth' : "primary_{$dose}",
                    'route'               => $route,
                    'status'              => 'completed',
                    'verification_status' => 'verified',
                    'is_historical'       => false,
                ]
            );
            $count++;
        }

        return $count;
    }
}
