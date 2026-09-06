<?php

namespace Database\Seeders\Passport;

use Database\Seeders\CameroonInsurancePlansSeeder;
use Database\Seeders\CameroonInsuranceSeeder;
use Database\Seeders\Support\DemoStoryIds;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Demonstration cover for page 22 of the Health Passport.
 *
 * No demo patient holds a policy, so Insurance & Coverage is blank on the
 * deployed demo — which tells a reviewer nothing about whether the page works,
 * and in particular nothing about the one behaviour on it that matters.
 *
 * ── REAL CAMEROONIAN INSURERS, NOT INVENTED ONES ─────────────────────────
 *
 * `CameroonInsuranceSeeder` already holds fifteen real Cameroonian insurers
 * from the CIMA/MINFI registry, and `CameroonInsurancePlansSeeder` holds their
 * real products with real XAF premiums. This seeder invents neither: it
 * resolves three of those plans by code and attaches demo patients to them.
 * Where the reference data has not been seeded on a host — the plans seeder is
 * not wired into DatabaseSeeder — it runs the seeder that owns that data
 * rather than writing a private copy of it that would drift.
 *
 *   CNAMGS-BASE   CNAMGS Régime de Base — the statutory scheme, in force.
 *   ACTIVA-GOLD   Activa Gold Santé — private complementary cover, in force.
 *   GAN-GROUPE    GAN Santé Groupe Entreprise — a previous employer's group
 *                 scheme, which ran out two years ago.
 *
 * ── THE LAPSED POLICY IS THE POINT ───────────────────────────────────────
 *
 * The third policy is written with `status = 'active'` and an `expiry_date`
 * two years in the past, and that is deliberate rather than sloppy: it is
 * exactly what real policy rows look like, because nobody logs in on the
 * morning cover lapses to flip a status column. It is there so the demo proves
 * the page refuses to print the stored word — page 22 reads that row against
 * the calendar and shows it as Expired, in the danger pill, and leaves its
 * benefits out of the coverage block. A reviewer can see the guardrail work
 * instead of taking it on trust.
 *
 * ── BOUNDARIES ───────────────────────────────────────────────────────────
 *
 *  - DEMO PATIENTS ONLY. `patient_insurance_policies` has no `is_demo` column
 *    in this schema, so the patient the row hangs off is the only isolation
 *    there is: the target set is demo patients that already carry a demo visit
 *    — the accounts with a care story a policy would have been paying for —
 *    and every write re-checks `patients.is_demo` before it touches a row.
 *  - Idempotent. Every policy is keyed on a stable UUID from the reserved 0d22
 *    block (page 22), derived per patient by DemoStoryIds, so a re-run rewrites
 *    the same three rows with their dates refreshed relative to the new "now".
 *  - Nothing invented. Policy and member numbers are derived deterministically
 *    from the patient id so they are stable across runs and unique per patient.
 *  - Discovered and run by DemoHealthPassportSeeder; registered nowhere.
 */
class InsuranceDemoSeeder extends Seeder
{
    /** Reserved id block — 0d22 = passport page 22. */
    private const POLICY_STATUTORY = '00000000-0000-0000-0d22-000000000001';

    private const POLICY_PRIVATE   = '00000000-0000-0000-0d22-000000000002';

    private const POLICY_LAPSED    = '00000000-0000-0000-0d22-000000000003';

    /** The three real plans this story uses, as provider code => plan code. */
    private const PLANS = [
        'statutory' => ['CNAMGS', 'CNAMGS-BASE'],
        'private'   => ['ACTIVA-CM', 'ACTIVA-GOLD'],
        'lapsed'    => ['GAN-CM', 'GAN-GROUPE'],
    ];

    public function run(): void
    {
        $plans = $this->plans();

        if ($plans === null) {
            return;
        }

        $patients = $this->patients();

        if ($patients->isEmpty()) {
            $this->command?->warn('InsuranceDemoSeeder: no demo patient has a visit to hang a policy off — nothing seeded.');

            return;
        }

        $now     = Carbon::now();
        $written = 0;

        foreach ($patients->keys() as $patientId) {
            $written += $this->statutory((string) $patientId, $plans['statutory'], $now);
            $written += $this->complementary((string) $patientId, $plans['private'], $now);
            $written += $this->lapsed((string) $patientId, $plans['lapsed'], $now);
        }

        $this->command?->info("InsuranceDemoSeeder: {$written} polic(ies) for {$patients->count()} demo patient(s).");
    }

    // ── The three policies ───────────────────────────────────────────────

    /**
     * The statutory scheme. Effective from the start of the year she was first
     * seen for her chronic conditions, and renewed to the end of next year —
     * so it is unambiguously in force whenever the demo is looked at.
     */
    private function statutory(string $patientId, string $planId, Carbon $now): int
    {
        $token = $this->token($patientId);

        return $this->write($patientId, self::POLICY_STATUTORY, [
            'insurance_plan_id'       => $planId,
            'policy_number'           => 'CNAMGS-BASE-' . $token,
            'member_id'               => 'AMU-237-' . $token,
            'group_number'            => null,
            'relationship_to_primary' => 'self',
            'primary_member_name'     => null,
            'effective_date'          => $now->copy()->subYears(3)->startOfYear()->toDateString(),
            'expiry_date'             => $now->copy()->addYear()->endOfYear()->toDateString(),
            'status'                  => 'active',
            'verified_by'             => 'CNAMGS eligibility check',
            'verified_at'             => $now->copy()->subMonthsNoOverflow(2),
            'notes'                   => 'Régime de base — cotisation à jour.',
        ]);
    }

    /** Private complementary cover on top of the statutory scheme. */
    private function complementary(string $patientId, string $planId, Carbon $now): int
    {
        $token = $this->token($patientId);

        return $this->write($patientId, self::POLICY_PRIVATE, [
            'insurance_plan_id'       => $planId,
            'policy_number'           => 'ACT-CM-' . $token,
            'member_id'               => 'ACTIVA-' . $token,
            'group_number'            => null,
            'relationship_to_primary' => 'self',
            'primary_member_name'     => null,
            'effective_date'          => $now->copy()->subYear()->startOfYear()->toDateString(),
            'expiry_date'             => $now->copy()->endOfYear()->toDateString(),
            'status'                  => 'active',
            'verified_by'             => null,
            'verified_at'             => null,
            'notes'                   => 'Complémentaire santé — tiers payant.',
        ]);
    }

    /**
     * The previous employer's group scheme, which ended two years ago and whose
     * row nobody ever came back to close. See the class docblock: the stale
     * `active` status is the demonstration.
     */
    private function lapsed(string $patientId, string $planId, Carbon $now): int
    {
        $token = $this->token($patientId);

        return $this->write($patientId, self::POLICY_LAPSED, [
            'insurance_plan_id'       => $planId,
            'policy_number'           => 'GAN-GRP-' . $token,
            'member_id'               => null,
            'group_number'            => 'GRP-YDE-0042',
            'relationship_to_primary' => 'self',
            'primary_member_name'     => null,
            'effective_date'          => $now->copy()->subYears(5)->startOfYear()->toDateString(),
            'expiry_date'             => $now->copy()->subYears(2)->endOfYear()->toDateString(),
            // Left as the record would really hold it: nobody closed the row
            // when the employment ended. Page 22 reads the date, not this word.
            'status'                  => 'active',
            'verified_by'             => null,
            'verified_at'             => null,
            'notes'                   => "Couverture collective de l'ancien employeur.",
        ]);
    }

    // ── Who, and against which plans ─────────────────────────────────────

    /**
     * The demo patients this story belongs to: an is_demo patient that already
     * carries a demo visit, which is the same population the other passport
     * demo seeders write for. A patient with no recorded care has nothing for
     * a policy to have been paying for.
     *
     * @return \Illuminate\Support\Collection<string,string>
     */
    private function patients(): \Illuminate\Support\Collection
    {
        return DB::table('patients')
            ->where('is_demo', true)
            ->whereIn('id', DB::table('visits')->where('is_demo', true)->distinct()->pluck('patient_id'))
            ->orderBy('created_at')
            ->pluck('health_id', 'id');
    }

    /**
     * The three real plans, resolved by provider code and plan code.
     *
     * `CameroonInsurancePlansSeeder` is not registered in DatabaseSeeder, so a
     * host can have the fifteen insurers and none of their products. Rather
     * than copy plan definitions here — a second source of truth that would
     * quietly drift from the first — the reference seeders that own that data
     * are run once, and only when what is needed is genuinely absent.
     *
     * @return array{statutory:string,private:string,lapsed:string}|null
     */
    private function plans(): ?array
    {
        $found = $this->resolvePlans();

        if ($found !== null) {
            return $found;
        }

        $this->command?->info('InsuranceDemoSeeder: the Cameroonian insurer plans are not seeded on this host — seeding the reference data first.');

        $this->call([
            CameroonInsuranceSeeder::class,
            CameroonInsurancePlansSeeder::class,
        ]);

        $found = $this->resolvePlans();

        if ($found === null) {
            $this->command?->warn('InsuranceDemoSeeder: CNAMGS / Activa / GAN plans are still not in insurance_plans — nothing seeded, rather than inventing an insurer.');
        }

        return $found;
    }

    /** @return array{statutory:string,private:string,lapsed:string}|null */
    private function resolvePlans(): ?array
    {
        $resolved = [];

        foreach (self::PLANS as $role => [$providerCode, $planCode]) {
            $id = DB::table('insurance_plans')
                ->join('insurance_providers', 'insurance_providers.id', '=', 'insurance_plans.insurance_provider_id')
                ->where('insurance_providers.code', $providerCode)
                ->where('insurance_plans.plan_code', $planCode)
                ->value('insurance_plans.id');

            if (! $id) {
                return null;
            }

            $resolved[$role] = (string) $id;
        }

        return $resolved;
    }

    // ── Writing ──────────────────────────────────────────────────────────

    /**
     * One policy, written straight to the table.
     *
     * `updateOrInsert` rather than the model, so a re-run refreshes the row's
     * relative dates in place instead of colliding with its own primary key.
     * The is_demo check is repeated here and not only at selection time: this
     * table has no demo column of its own, so the patient is the boundary and
     * it is worth being certain of at the point of the write.
     *
     * @param  array<string,mixed>  $attributes
     */
    private function write(string $patientId, string $reservedId, array $attributes): int
    {
        if (! DB::table('patients')->where('id', $patientId)->where('is_demo', true)->exists()) {
            return 0;
        }

        DB::table('patient_insurance_policies')->updateOrInsert(
            ['id' => DemoStoryIds::uid($patientId, $reservedId)],
            $attributes + [
                'patient_id' => $patientId,
                'created_at' => $attributes['effective_date'],
                'updated_at' => Carbon::now(),
            ]
        );

        return 1;
    }

    /**
     * A stable, patient-unique token for a policy number. Derived from the
     * patient id, so the same patient keeps the same numbers across every run
     * and two demo patients never share one.
     */
    private function token(string $patientId): string
    {
        return strtoupper(substr(md5($patientId), 0, 8));
    }
}
