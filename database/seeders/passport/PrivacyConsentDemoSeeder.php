<?php

namespace Database\Seeders\Passport;

use App\Models\ConsentGrant;
use App\Models\Patient;
use App\Models\StaffProfile;
use App\Models\User;
use Database\Seeders\Support\DemoStoryIds;
use Illuminate\Database\Seeder;

/**
 * Demo consent, so the privacy switch can be SEEN working.
 *
 * `patients.privacy_preferences.require_consent_for_full_record` defaults to
 * on, so a clinician with only an implicit basis — an appointment, a shared
 * facility — now sees identity, allergies and current medicines and must ask
 * for the rest. That is the intended behaviour, but with no consent grant
 * anywhere in the demo data it is also the ONLY behaviour a reviewer would
 * ever see, and a chart that is masked for every patient reads as broken
 * rather than as careful.
 *
 * So this grants consent for the FIRST demo story patient and deliberately
 * leaves the second without one. A reviewer signing in as the demo doctor then
 * sees both halves of the feature: one patient whose record opens because they
 * said yes, and one who has not, where the chart offers to ask them.
 *
 * Nothing here is written for a patient that is not `is_demo`.
 */
class PrivacyConsentDemoSeeder extends Seeder
{
    /** Demo patients that should have granted consent, in order of preference. */
    private const CONSENTING_EMAILS = ['demo.patient@opescare.test'];

    /** Clinicians whose facility the consent is granted to. */
    private const CLINICIAN_EMAILS = [
        'demo.doctor@opescare.test',
        'demo.nurse@opescare.test',
        'demo.labtech@opescare.test',
        'demo.pharmacist@opescare.test',
    ];

    public function run(): void
    {
        $facilityIds = $this->clinicianFacilities();

        if ($facilityIds === []) {
            $this->command?->warn('PrivacyConsentDemoSeeder: no demo clinician has a facility; nothing to grant to.');

            return;
        }

        $granted = 0;

        foreach (self::CONSENTING_EMAILS as $email) {
            $patient = $this->demoPatientFor($email);

            if (! $patient) {
                continue;
            }

            foreach ($facilityIds as $facilityId) {
                ConsentGrant::withoutGlobalScopes()->updateOrCreate(
                    ['id' => DemoStoryIds::derived($patient->id, 'privacy-consent-' . $facilityId)],
                    [
                        'patient_id'        => $patient->id,
                        'facility_id'       => $facilityId,
                        'authorizing_actor' => 'patient',
                        // What the patient agreed to share, not everything the
                        // platform could show.
                        'scope'             => ['patient_identity', 'allergies', 'medications', 'diagnoses', 'encounters', 'vitals', 'orders'],
                        'status'            => 'active',
                        'expires_at'        => now()->addMonths(6),
                    ]
                );

                $granted++;
            }
        }

        $this->command?->info(sprintf(
            'PrivacyConsentDemoSeeder: %d grant(s) across %d facility(ies); the other demo patients stay masked on purpose.',
            $granted,
            count($facilityIds)
        ));
    }

    /** @return list<string> */
    private function clinicianFacilities(): array
    {
        $userIds = User::withoutGlobalScopes()
            ->whereIn('email', self::CLINICIAN_EMAILS)
            ->pluck('id');

        return StaffProfile::withoutGlobalScopes()
            ->whereIn('user_id', $userIds)
            ->whereNotNull('facility_id')
            ->pluck('facility_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * The demo patient behind a demo login — and never anybody else. A consent
     * grant written against a real patient would be this platform forging the
     * one thing it exists to protect.
     */
    private function demoPatientFor(string $email): ?Patient
    {
        // `patients` carries no user_id in this schema; the demo account and
        // the demo record are linked by the address they share.
        $patient = Patient::withoutGlobalScopes()->where('email', $email)->first();

        if (! $patient || ! $patient->is_demo) {
            return null;
        }

        return $patient;
    }
}
