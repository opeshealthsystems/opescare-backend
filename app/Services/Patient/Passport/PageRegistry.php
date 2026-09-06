<?php

namespace App\Services\Patient\Passport;

/**
 * Which class builds which page.
 *
 * Pages are being moved out of HealthPassportBuilder one at a time, so this
 * deliberately tolerates a page whose class has not been written yet: an
 * unregistered — or not-yet-existing — page falls back to the builder's own
 * method and reads exactly as it did before. That is what lets the thirty pages
 * be worked on independently without a half-finished one taking the passport
 * down.
 */
final class PageRegistry
{
    /** @var array<string,class-string<PageSource>> */
    private const MAP = [
        'patient-identity'     => Pages\PatientIdentityPage::class,
        'emergency-profile'    => Pages\EmergencyProfilePage::class,
        'medical-summary'      => Pages\MedicalSummaryPage::class,
        'allergies-alerts'     => Pages\AllergiesAlertsPage::class,
        'current-medications'  => Pages\CurrentMedicationsPage::class,
        'conditions'           => Pages\ConditionsPage::class,
        'encounters'           => Pages\EncountersPage::class,
        'hospital-admissions'  => Pages\HospitalAdmissionsPage::class,
        'laboratory'           => Pages\LaboratoryPage::class,
        'radiology'            => Pages\RadiologyPage::class,
        'immunization'         => Pages\ImmunizationPage::class,
        'prescriptions'        => Pages\PrescriptionsPage::class,
        'dispensing'           => Pages\DispensingPage::class,
        'referrals'            => Pages\ReferralsPage::class,
        'medical-certificates' => Pages\MedicalCertificatesPage::class,
        'discharge-summary'    => Pages\DischargeSummaryPage::class,
        'care-providers'       => Pages\CareProvidersPage::class,
        'insurance'            => Pages\InsurancePage::class,
        'consent-sharing'      => Pages\ConsentSharingPage::class,
        'vitals'               => Pages\VitalsPage::class,
        'care-plans'           => Pages\CarePlansPage::class,
        'verification'         => Pages\VerificationPage::class,
    ];

    /** The source for a page, or null when the builder should handle it. */
    public static function for(string $pageId): ?PageSource
    {
        $class = self::MAP[$pageId] ?? null;

        if ($class === null || ! class_exists($class)) {
            return null;
        }

        $source = app($class);

        return $source instanceof PageSource ? $source : null;
    }

    /** Page ids that have a source class on disk — used by the tests. */
    public static function migrated(): array
    {
        return array_keys(array_filter(self::MAP, fn (string $c) => class_exists($c)));
    }
}
