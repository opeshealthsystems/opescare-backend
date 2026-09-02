<?php

namespace Tests\Feature\CareMap;

use App\Models\CareFacility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /care-map/emergency — the page a person opens when someone is bleeding.
 *
 * WHY THIS MATTERED
 * -----------------
 * CareMapSearchService::searchNearby() narrowed the emergency search with
 * `facility_type = 'hospital' AND emergency_contact != ''`. In SQL,
 * `NULL != ''` is NULL — not true — so every row with an unpopulated
 * emergency_contact was silently dropped. Production held 98 hospitals and
 * zero with that column set, which means the public emergency finder returned
 * an empty list on every single request it had ever served. Somebody looking
 * for the nearest hospital in an emergency was shown "no emergency facilities
 * found" while 98 hospitals sat in the database, each with a working phone
 * number in `phone_primary`.
 *
 * The second half of the contract is the opposite failure. `care_facilities`
 * holds no column recording whether a facility runs a functioning emergency
 * department, so the page must not tell anyone it does. Sending a person past
 * a closer hospital to one this platform falsely labelled "24/7 A&E" is a
 * worse outcome than the empty list was.
 */
class EmergencyFinderTest extends TestCase
{
    use RefreshDatabase;

    /** Douala city centre — the point every search in this file is run from. */
    private const LAT = 4.0511;
    private const LON = 9.7679;

    private function hospital(array $overrides = []): CareFacility
    {
        return CareFacility::create(array_merge([
            'facility_name'       => 'Laquintinie Hospital',
            'facility_type'       => 'hospital',
            'listing_status'      => 'active',
            'verification_status' => 'unverified',
            'country_code'        => 'CMR',
            'region'              => 'Littoral',
            'city'                => 'Douala',
            'address'             => 'Boulevard de la Liberte',
            'latitude'            => self::LAT,
            'longitude'           => self::LON,
            'phone_primary'       => '+237233420000',
            // The production shape: nobody ever filled this in.
            'emergency_contact'   => null,
        ], $overrides));
    }

    /**
     * The defect itself: a hospital with a NULL emergency_contact is still a
     * hospital, and must still be returned.
     */
    public function test_the_emergency_api_returns_a_nearby_hospital_whose_emergency_contact_is_null(): void
    {
        $hospital = $this->hospital();

        $this->assertNull(
            $hospital->fresh()->emergency_contact,
            'fixture guard: this test is only meaningful against a NULL emergency_contact'
        );

        $this->getJson('/api/v1/care-map/emergency?latitude=' . self::LAT . '&longitude=' . self::LON . '&radius=25')
            ->assertOk()
            ->assertJsonFragment(['facility_name' => 'Laquintinie Hospital']);
    }

    /**
     * Same guarantee through the rendered page, which is what a person in an
     * emergency actually looks at.
     */
    public function test_the_public_emergency_page_lists_a_hospital_that_has_no_emergency_contact(): void
    {
        $this->hospital();

        $this->get('/care-map/emergency?latitude=' . self::LAT . '&longitude=' . self::LON . '&radius=25')
            ->assertOk()
            ->assertSee('Laquintinie Hospital', false);
    }

    /**
     * With no coordinates supplied at all the finder must still list hospitals
     * — a browser that refuses geolocation is the common case, not an edge one.
     */
    public function test_the_emergency_page_still_lists_hospitals_when_the_browser_gives_no_location(): void
    {
        $this->hospital();

        $this->get('/care-map/emergency')
            ->assertOk()
            ->assertSee('Laquintinie Hospital', false);
    }

    /**
     * The finder must not fabricate an emergency department. No column in
     * care_facilities records A&E capability, so nothing rendered next to a
     * facility name may assert one.
     */
    public function test_the_emergency_finder_does_not_claim_an_emergency_department_it_has_no_data_for(): void
    {
        $this->hospital();

        $html = $this->get('/care-map/emergency?latitude=' . self::LAT . '&longitude=' . self::LON . '&radius=25')
            ->assertOk()
            ->getContent();

        // '24/7 A&E' was rendered as a per-facility badge for every result,
        // unconditionally. It is an assertion about a physical department this
        // platform holds no data on.
        $this->assertStringNotContainsStringIgnoringCase(
            '24/7 A&amp;E',
            $html,
            'the page labels a facility as a 24/7 A&E on no evidence at all'
        );
        $this->assertStringNotContainsStringIgnoringCase(
            '24/7 Emergency Facilities',
            $html,
            'the results heading asserts 24/7 emergency capability the data cannot support'
        );
    }

    /**
     * A guard on the fix: dropping the broken filter entirely would let
     * pharmacies and labs into an emergency result set.
     */
    public function test_a_pharmacy_is_never_returned_by_the_emergency_finder(): void
    {
        $this->hospital();

        CareFacility::create([
            'facility_name'       => 'Akwa Pharmacy',
            'facility_type'       => 'pharmacy',
            'listing_status'      => 'active',
            'verification_status' => 'unverified',
            'country_code'        => 'CMR',
            'region'              => 'Littoral',
            'city'                => 'Douala',
            'address'             => 'Rue Joss',
            'latitude'            => self::LAT,
            'longitude'           => self::LON,
            'phone_primary'       => '+237233421111',
            'emergency_contact'   => '+237233421111',
        ]);

        $this->getJson('/api/v1/care-map/emergency?latitude=' . self::LAT . '&longitude=' . self::LON . '&radius=25')
            ->assertOk()
            ->assertJsonFragment(['facility_name' => 'Laquintinie Hospital'])
            ->assertJsonMissing(['facility_name' => 'Akwa Pharmacy']);
    }

    /**
     * A guard on the fix in the other direction: the radius must still bound
     * the result set. Yaounde is ~200km from Douala.
     */
    public function test_a_hospital_outside_the_search_radius_is_not_returned(): void
    {
        $this->hospital();

        $this->hospital([
            'facility_name' => 'Yaounde Central Hospital',
            'region'        => 'Centre',
            'city'          => 'Yaounde',
            'latitude'      => 3.8667,
            'longitude'     => 11.5167,
            'phone_primary' => '+237222234000',
        ]);

        $this->getJson('/api/v1/care-map/emergency?latitude=' . self::LAT . '&longitude=' . self::LON . '&radius=25')
            ->assertOk()
            ->assertJsonFragment(['facility_name' => 'Laquintinie Hospital'])
            ->assertJsonMissing(['facility_name' => 'Yaounde Central Hospital']);
    }

    /**
     * A suspended listing is suspended everywhere, including here.
     */
    public function test_a_suspended_listing_is_not_returned_by_the_emergency_finder(): void
    {
        $this->hospital([
            'facility_name'  => 'Closed District Hospital',
            'listing_status' => 'suspended',
        ]);

        $this->getJson('/api/v1/care-map/emergency?latitude=' . self::LAT . '&longitude=' . self::LON . '&radius=25')
            ->assertOk()
            ->assertJsonMissing(['facility_name' => 'Closed District Hospital']);
    }
}
