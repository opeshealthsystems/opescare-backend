<?php

namespace Database\Seeders\Passport;

use Database\Seeders\Support\DemoCareTeam;
use Database\Seeders\Support\DemoFacilityResolver;
use Database\Seeders\Support\DemoStoryIds;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Demo content for passport page 21 — Care Providers.
 *
 * The demo record already names the people who look after this patient: her GP
 * attends six of her seven visits, the cardiologist attends the seventh, a
 * pharmacist dispensed two of her three prescriptions. What it has never held
 * is a single `care_team_members` row — nothing in the platform writes one yet —
 * so page 21 is blank on the demo server while the record it reads plainly
 * knows the answer.
 *
 * ── EVERY MEMBERSHIP IS READ OFF THE RECORD ───────────────────────────────
 *
 * Nothing here decides who looked after her. The clinicians are exactly the
 * providers her own visits name; each membership takes its facility and its
 * start date FROM THE EARLIEST VISIT that clinician attended, and the primary
 * is whoever attended the most — the GP, on this record, without anyone saying
 * so. The pharmacy membership is derived the same way from the prescriptions
 * that were actually dispensed. A clinician who is not on a visit does not
 * become a member.
 *
 * Two members are the seeder's own, and both are named as such:
 *
 *  • THE NURSE. `visits` records one provider, so the nursing half of an
 *    outpatient team is invisible to any derivation. The demo estate's
 *    outpatients nurse joins on the day of the patient's first visit at her
 *    main hospital — a plausible team rather than a discovered one.
 *  • THE MEMBER WHO HAS LEFT. This is the case page 21 must get right, and a
 *    demo record where every member is current would never show whether it
 *    does. It is attached to the one visit in the story that records NO
 *    provider — the single episode of care away from home — so it contradicts
 *    nothing: that clinician joined when that episode opened and left when it
 *    closed, and the page must show him as past rather than as someone to ring.
 *
 * ── BOUNDARIES ────────────────────────────────────────────────────────────
 *
 *  - Demo patients only, and only patients who already have demo VISITS. A real
 *    person is never given a care team by a seeder.
 *  - `care_team_members` has no `is_demo` column and no demo global scope;
 *    isolation here is by attachment — every row hangs off an is_demo patient,
 *    an is_demo visit and an is_demo user.
 *  - Idempotent: each row's id is derived from the patient and the provider, so
 *    a re-run refreshes the same rows rather than adding more. Safe to run
 *    repeatedly on the live demo server.
 *  - A patient who already has a membership for a provider — written by the app,
 *    or by hand — is left alone for that provider. The seeder fills gaps; it
 *    does not overrule a real record.
 *  - Discovered and run automatically by DemoHealthPassportSeeder.
 */
class CareProvidersDemoSeeder extends Seeder
{
    /** The roles `care_team_members.role` documents (see the create migration). */
    private const ATTENDING  = 'attending';
    private const CONSULTING = 'consulting';
    private const NURSING    = 'nursing';
    private const PHARMACY   = 'pharmacy';

    public function run(): void
    {
        if (! Schema::hasTable('care_team_members')) {
            $this->command?->warn('CareProvidersDemoSeeder: no care_team_members table — nothing seeded.');

            return;
        }

        $patients = DB::table('patients')
            ->where('is_demo', true)
            ->whereIn('id', DB::table('visits')->where('is_demo', true)->distinct()->pluck('patient_id'))
            ->pluck('health_id', 'id');

        if ($patients->isEmpty()) {
            $this->command?->warn('CareProvidersDemoSeeder: no demo patient has a visit to read a care team off — nothing seeded.');

            return;
        }

        $written = 0;

        foreach ($patients as $patientId => $healthId) {
            $visits = $this->demoVisits((string) $patientId);

            if ($visits->isEmpty()) {
                continue;
            }

            $written += $this->seedAttendingClinicians((string) $patientId, $visits);
            $written += $this->seedNurse((string) $patientId, $visits);
            $written += $this->seedPharmacy((string) $patientId);
            $written += $this->seedDepartedClinician((string) $patientId, $visits);
        }

        $this->command?->info(sprintf(
            'CareProvidersDemoSeeder: %d care team membership(s) across %d demo patient(s).',
            $written,
            $patients->count()
        ));
    }

    // ── Derived from the record ──────────────────────────────────────────

    /**
     * Everyone her visits actually name.
     *
     * The clinician who attended the most visits is the PRIMARY and attends;
     * everyone else consults. That is the record's own answer to "who holds the
     * thread" — on this story the GP with six visits against the cardiologist's
     * one — and it is arrived at by counting rather than by being told.
     *
     * @param  Collection<int,object>  $visits
     */
    private function seedAttendingClinicians(string $patientId, Collection $visits): int
    {
        $byProvider = $visits
            ->filter(fn (object $v) => (string) $v->provider_id !== '')
            ->groupBy(fn (object $v) => (string) $v->provider_id);

        if ($byProvider->isEmpty()) {
            return 0;
        }

        // Most visits wins; the longest-standing clinician breaks a tie, which
        // is the same question asked a second way.
        $primaryId = (string) $byProvider
            ->sortByDesc(fn (Collection $group) => [$group->count(), -$group->first()->started_at_ts])
            ->keys()
            ->first();

        $written = 0;

        foreach ($byProvider as $providerId => $group) {
            $first = $group->first();

            $written += $this->write($patientId, (string) $providerId, [
                'visit_id'    => null,
                'facility_id' => $first->facility_id,
                // Attending for the clinician who holds the thread, consulting
                // for the specialist brought in alongside her.
                'role'        => (string) $providerId === $primaryId ? self::ATTENDING : self::CONSULTING,
                'is_primary'  => (string) $providerId === $primaryId,
                'is_active'   => true,
                'joined_at'   => $first->started_at,
                'left_at'     => null,
            ]);
        }

        return $written;
    }

    /**
     * The pharmacy that dispenses her medicines.
     *
     * WHO is read off the record — `prescriptions.dispensed_by`, the person who
     * actually handed the medicines over — and so is WHEN: the first time they
     * dispensed anything for her. WHERE is not, and cannot be:
     * `prescriptions.facility_id` is the facility that PRESCRIBED, not the
     * counter that dispensed, and putting a hospital's name against a pharmacy
     * membership would be wrong in a way the page cannot show. The real pharmacy
     * the rest of the demo story uses is named instead.
     */
    private function seedPharmacy(string $patientId): int
    {
        if (! Schema::hasTable('prescriptions')) {
            return 0;
        }

        $dispensed = DB::table('prescriptions')
            ->where('patient_id', $patientId)
            ->whereNotNull('dispensed_by')
            ->whereNotNull('dispensed_at')
            ->orderBy('dispensed_at')
            ->first(['dispensed_by', 'dispensed_at']);

        if (! $dispensed) {
            return 0;
        }

        $pharmacy = DemoFacilityResolver::pharmacy();

        if (! $pharmacy) {
            return 0;
        }

        return $this->write($patientId, (string) $dispensed->dispensed_by, [
            'visit_id'    => null,
            'facility_id' => $pharmacy,
            'role'        => self::PHARMACY,
            'is_primary'  => false,
            'is_active'   => true,
            'joined_at'   => $dispensed->dispensed_at,
            'left_at'     => null,
        ]);
    }

    // ── The seeder's own two members ─────────────────────────────────────

    /**
     * The outpatients nurse at her main hospital.
     *
     * `visits` records exactly one provider, so nursing never appears in any
     * derivation however real it is. Her start is still taken from the record —
     * the first visit at the hospital she is posted to — so the date is not
     * invented even though the membership is.
     *
     * @param  Collection<int,object>  $visits
     */
    private function seedNurse(string $patientId, Collection $visits): int
    {
        $nurse = DemoCareTeam::nurse();

        if (! $nurse) {
            return 0;
        }

        $hospital = DemoFacilityResolver::primaryHospital();

        $first = $hospital
            ? $visits->first(fn (object $v) => (string) $v->facility_id === $hospital)
            : null;

        // No visit at the hospital she works at means no plausible way in.
        if ($first === null) {
            return 0;
        }

        return $this->write($patientId, $nurse, [
            'visit_id'    => null,
            'facility_id' => $hospital,
            'role'        => self::NURSING,
            'is_primary'  => false,
            'is_active'   => true,
            'joined_at'   => $first->started_at,
            'left_at'     => null,
        ]);
    }

    /**
     * The clinician who looked after her away from home, and has since left.
     *
     * The membership is scoped to the one visit in the story that names no
     * provider: it opens when that episode opens and closes when it closes,
     * which is what an episode-scoped care team member IS. `is_active` false
     * AND `left_at` set, because page 21 treats either as an ending and both
     * should be exercised.
     *
     * Without a row like this the demo record would show a team on which
     * nobody has ever left, and the one thing page 21 must not get wrong —
     * printing a clinician who handed over as the person to ring today — would
     * go unproven on the demo server.
     *
     * @param  Collection<int,object>  $visits
     */
    private function seedDepartedClinician(string $patientId, Collection $visits): int
    {
        $visit = $visits->first(fn (object $v) => (string) $v->provider_id === '');

        if ($visit === null) {
            return 0;
        }

        $doctor = DB::table('users')
            ->where('email', 'demo.multi.doctor@opescare.test')
            ->where('is_demo', true)
            ->value('id');

        if (! $doctor) {
            return 0;
        }

        return $this->write($patientId, (string) $doctor, [
            'visit_id'    => $visit->id,
            'facility_id' => $visit->facility_id,
            'role'        => self::ATTENDING,
            'is_primary'  => false,
            'is_active'   => false,
            'joined_at'   => $visit->started_at,
            // The episode's own end, never a date of this seeder's choosing.
            'left_at'     => $visit->ended_at ?: $visit->started_at,
        ]);
    }

    // ── Writing ──────────────────────────────────────────────────────────

    /**
     * One membership, keyed on the patient and the provider so a re-run
     * refreshes it rather than adding another.
     *
     * A membership this seeder does not own — one the app wrote, or a real
     * record — is never touched: if the pair already has a row under a
     * different id, the gap is not a gap and the seeder leaves it alone.
     */
    private function write(string $patientId, string $providerId, array $attributes): int
    {
        if ($providerId === '') {
            return 0;
        }

        $id = DemoStoryIds::derived($patientId, 'passport-care-providers:member:' . $providerId);

        $foreign = DB::table('care_team_members')
            ->where('patient_id', $patientId)
            ->where('provider_id', $providerId)
            ->where('id', '!=', $id)
            ->exists();

        if ($foreign) {
            return 0;
        }

        DB::table('care_team_members')->updateOrInsert(['id' => $id], $attributes + [
            'patient_id' => $patientId,
            'provider_id' => $providerId,
            // The membership's own start, never now(): a three-year-old care
            // team created "today" files itself under this month everywhere
            // the platform orders by created_at.
            'created_at' => $attributes['joined_at'],
            'updated_at' => $attributes['left_at'] ?: $attributes['joined_at'],
        ]);

        return 1;
    }

    // ── Reading the record ───────────────────────────────────────────────

    /**
     * The patient's demo visits, oldest first, each carrying a sortable
     * timestamp so the tie-break above does not re-parse dates.
     *
     * @return Collection<int,object>
     */
    private function demoVisits(string $patientId): Collection
    {
        return DB::table('visits')
            ->where('patient_id', $patientId)
            ->where('is_demo', true)
            ->orderBy('started_at')
            ->get(['id', 'facility_id', 'provider_id', 'started_at', 'ended_at'])
            ->map(function (object $visit) {
                $visit->started_at_ts = $visit->started_at ? strtotime((string) $visit->started_at) : 0;

                return $visit;
            });
    }
}
