<?php

namespace Tests\Feature\CareMap;

use App\Models\CareFacility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * "Verified" is a claim about the facility directory. It has to be true.
 *
 * WHY THIS MATTERED
 * -----------------
 * All 903 rows in `care_facilities` carry `verification_status = 'unverified'`.
 * Not one has a `license_number` on file, and not one has a `last_verified_at`
 * timestamp. No verification process has ever been run against a single
 * facility in the directory.
 *
 * Meanwhile the public site and /llms.txt describe the directory as verified:
 * "a directory of verified healthcare facilities", "verified facility
 * directory", "Only pharmacies with a licence on record ... can publish stock.
 * An unverified outlet never appears in a result."
 *
 * That is the claim that turns a listing into a recommendation. A person
 * choosing between two clinics on this map is being told OpesCare checked one
 * of them. It did not check any of them. /llms.txt is the worst surface for it
 * because generative engines quote it verbatim, so the false claim propagates
 * into answers given to people who never visited the site.
 *
 * CONTRACT: while `care_facilities` contains no row with
 * `verification_status != 'unverified'`, no public page and not /llms.txt may
 * describe the facility directory as verified.
 *
 * WHAT THIS FILE DELIBERATELY DOES NOT ASSERT
 * -------------------------------------------
 * Not the bare substring "verified". Patient identity verification, Health ID
 * verification, an audit trail being "verifiable", and a per-facility badge on
 * a facility that genuinely is verified are all legitimate and are not
 * regressions. Each test below names the specific sentence that asserts a
 * verified FACILITY DIRECTORY, and the last test proves the honest use of the
 * word still renders.
 *
 * The contract is conditional, so it can be satisfied either by rewording the
 * copy permanently or by gating it on real verification data. Nothing here
 * pins which.
 */
class VerifiedDirectoryClaimTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Claims on /llms.txt that describe the directory itself as verified.
     * These are the sentences an AI assistant will quote back to a patient.
     */
    private const LLMS_DIRECTORY_CLAIMS = [
        'verified facility directory',
        'directory of verified healthcare facilities',
        'search verified pharmacies',
        'search verified hospitals and blood banks',
    ];

    /**
     * Production's actual shape: listings exist, none has ever been verified.
     */
    private function seedTheUnverifiedDirectory(): void
    {
        foreach ([
            ['Laquintinie Hospital', 'hospital',   'Douala',  4.0511, 9.7679],
            ['Bonanjo Pharmacy',     'pharmacy',   'Douala',  4.0470, 9.6920],
            ['Centre Pasteur Lab',   'laboratory', 'Yaounde', 3.8667, 11.5167],
        ] as [$name, $type, $city, $lat, $lon]) {
            CareFacility::create([
                'facility_name'       => $name,
                'facility_type'       => $type,
                'listing_status'      => 'active',
                'verification_status' => 'unverified',
                'license_number'      => null,
                'last_verified_at'    => null,
                'country_code'        => 'CMR',
                'region'              => $city === 'Yaounde' ? 'Centre' : 'Littoral',
                'city'                => $city,
                'address'             => '1 Rue Principale',
                'latitude'            => $lat,
                'longitude'           => $lon,
                'phone_primary'       => '+237233000000',
            ]);
        }

        $this->assertSame(
            0,
            CareFacility::where('verification_status', '!=', 'unverified')->count(),
            'precondition: this test only applies while nothing in the directory is verified'
        );
    }

    /**
     * @param  list<string>  $claims
     */
    private function assertMakesNoVerifiedDirectoryClaim(string $body, array $claims, string $surface): void
    {
        foreach ($claims as $claim) {
            $this->assertStringNotContainsStringIgnoringCase(
                $claim,
                $body,
                "{$surface} tells the reader the facility directory is verified (\"{$claim}\"), "
                . 'but not one facility in it has ever been verified'
            );
        }
    }

    /**
     * /llms.txt is quoted verbatim by generative engines. A false claim here
     * reaches people who never open the site.
     */
    public function test_llms_txt_does_not_describe_the_facility_directory_as_verified(): void
    {
        $this->seedTheUnverifiedDirectory();

        $body = $this->get('/llms.txt')->assertOk()->getContent();

        $this->assertMakesNoVerifiedDirectoryClaim($body, self::LLMS_DIRECTORY_CLAIMS, '/llms.txt');
    }

    /**
     * The directory page's own meta description — the sentence that appears
     * under the result in a search engine.
     */
    public function test_the_care_map_page_does_not_advertise_a_verified_directory(): void
    {
        $this->seedTheUnverifiedDirectory();

        $body = $this->get('/care-map')->assertOk()->getContent();

        $this->assertMakesNoVerifiedDirectoryClaim($body, [
            'Find verified hospitals',
        ], '/care-map');
    }

    /**
     * The Medicine Finder page states a gatekeeping rule that does not exist.
     * "An unverified outlet never appears in a result" is the reverse of the
     * truth: every outlet in the directory is unverified.
     */
    public function test_the_medicine_finder_page_does_not_claim_a_verified_pharmacy_gate(): void
    {
        $this->seedTheUnverifiedDirectory();

        $body = $this->get('/network/medicine-finder')->assertOk()->getContent();

        $this->assertMakesNoVerifiedDirectoryClaim($body, [
            'Verified pharmacies only',
            'An unverified outlet never appears in a result',
            'Find which verified pharmacies hold a medicine',
        ], '/network/medicine-finder');
    }

    /**
     * The same false gate on the page a clinician looking for blood reads.
     */
    public function test_the_blood_finder_page_does_not_claim_a_verified_facility_gate(): void
    {
        $this->seedTheUnverifiedDirectory();

        $body = $this->get('/network/blood-finder')->assertOk()->getContent();

        $this->assertMakesNoVerifiedDirectoryClaim($body, [
            'Verified hospitals and blood banks only',
            'no informal or unverified supply in these results',
            'across verified hospitals and blood banks',
        ], '/network/blood-finder');
    }

    /**
     * The public status page names the service "Verified Care Map" — the claim
     * is in the product name itself.
     */
    public function test_the_status_page_does_not_name_the_directory_a_verified_care_map(): void
    {
        $this->seedTheUnverifiedDirectory();

        $body = $this->get('/status')->assertOk()->getContent();

        $this->assertMakesNoVerifiedDirectoryClaim($body, [
            'Verified Care Map',
        ], '/status');
    }

    /**
     * The French copy already says "enregistrées"/"connectées" (registered /
     * connected) rather than "vérifiées" — it never made the claim the English
     * copy made. This pins it there: EN and FR must stay 1:1, and the fix must
     * not import the false claim into French while removing it from English.
     */
    public function test_the_french_finder_pages_make_no_verified_directory_claim_either(): void
    {
        $this->seedTheUnverifiedDirectory();

        foreach (['/network/medicine-finder', '/network/blood-finder', '/care-map'] as $path) {
            $body = $this->get($path . '?lang=fr')->assertOk()->getContent();

            $this->assertMakesNoVerifiedDirectoryClaim($body, [
                'pharmacies vérifiées',
                'établissements vérifiés',
                'annuaire vérifié',
                'hôpitaux et banques de sang vérifiés',
            ], $path . ' (fr)');
        }
    }

    /**
     * THE BOUNDARY. The word is not banned — the false claim is. A facility
     * that has actually been license-verified must still carry its badge,
     * otherwise the fix has thrown away the only honest use of the word and
     * with it the reason to ever verify a facility.
     *
     * This test should pass both before and after the fix.
     */
    public function test_a_facility_that_really_is_verified_still_shows_its_verified_badge(): void
    {
        CareFacility::create([
            'facility_name'       => 'Douala General Hospital',
            'facility_type'       => 'hospital',
            'listing_status'      => 'active',
            'verification_status' => 'license_verified',
            'license_number'      => 'CM-LIT-000123',
            'last_verified_at'    => Carbon::now()->subMonth(),
            'country_code'        => 'CMR',
            'region'              => 'Littoral',
            'city'                => 'Douala',
            'address'             => 'Rue de la Reunification',
            'latitude'            => 4.0511,
            'longitude'           => 9.7679,
            'phone_primary'       => '+237233370000',
        ]);

        $body = $this->get('/care-map')->assertOk()->getContent();

        $this->assertStringContainsString(
            'License Verified',
            $body,
            'a facility whose licence really was verified must still be shown as verified — '
            . 'this test exists so the fix removes the false claim, not the true one'
        );
    }
}
