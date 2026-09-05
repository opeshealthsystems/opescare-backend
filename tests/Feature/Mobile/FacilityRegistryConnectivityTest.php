<?php

namespace Tests\Feature\Mobile;

use App\Models\AppointmentSlot;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * The two facility registers, as the mobile directory now reports them.
 *
 *   care_facilities — the public directory. A row means "findable".
 *   facilities      — the operational tenant. A row means "care happens here".
 *   care_facilities.facility_id — the one link between them. Only a linked
 *   listing can publish a clinician, open a slot, or take a booking.
 *
 * In the live dev database that link exists on 468 of 2 728 active listings,
 * so 2 260 listings are findable but can never be a place where care happens.
 * The directory endpoint used to return all of them in NO order, which is how
 * "Find Provider" came to read as broken.
 *
 * These tests pin the fix: the link is reported per row, it can be filtered
 * on, and connected listings come first by default. Nothing here asserts that
 * an unlinked listing is hidden — it is not, and must not be.
 */
class FacilityRegistryConnectivityTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    private Patient $patient;
    private Facility $tenant;
    private CareFacility $connected;
    private CareFacility $connectedNoSlots;
    private CareFacility $unlinked;

    protected function setUp(): void
    {
        parent::setUp();

        // Registry totals are cached; each test must measure its own fixtures.
        Cache::flush();

        $this->patient = Patient::create([
            'health_id'     => 'OC-TST-7777-0001-01',
            'first_name'    => 'Alice',
            'last_name'     => 'Patient',
            'sex'           => 'female',
            'date_of_birth' => '1990-01-01',
            'is_demo'       => false,
        ]);

        $this->tenant = Facility::create([
            'name'   => 'Zenith Teaching Hospital',
            'type'   => 'hospital',
            'status' => 'active',
        ]);

        $quietTenant = Facility::create([
            'name'   => 'Beacon Clinic',
            'type'   => 'clinic',
            'status' => 'active',
        ]);

        // Linked AND has an open future slot — the fully usable case.
        // Named last alphabetically on purpose, so if it still sorts first the
        // sort is genuinely by connectivity and not by name.
        $this->connected = CareFacility::create([
            'facility_name'  => 'Zenith Teaching Hospital',
            'facility_type'  => 'hospital',
            'listing_status' => 'active',
            'city'           => 'Yaounde',
            'country_code'   => 'CM',
            'address'        => '1 Zenith Road',
            'phone_primary'  => '+237600000101',
            'facility_id'    => $this->tenant->id,
        ]);

        AppointmentSlot::create([
            'facility_id'  => $this->tenant->id,
            'provider_id'  => User::factory()->create()->id,
            'starts_at'    => now()->addDay(),
            'ends_at'      => now()->addDay()->addMinutes(30),
            'capacity'     => 2,
            'booked_count' => 0,
            'status'       => 'open',
        ]);

        // Linked but with no calendar and no roster.
        $this->connectedNoSlots = CareFacility::create([
            'facility_name'  => 'Beacon Clinic',
            'facility_type'  => 'clinic',
            'listing_status' => 'active',
            'city'           => 'Douala',
            'country_code'   => 'CM',
            'address'        => '2 Beacon Street',
            'phone_primary'  => '+237600000102',
            'facility_id'    => $quietTenant->id,
        ]);

        // Directory only — the 2 260 case.
        $this->unlinked = CareFacility::create([
            'facility_name'  => 'Avenue Health Post',
            'facility_type'  => 'health_center',
            'listing_status' => 'active',
            'city'           => 'Douala',
            'country_code'   => 'CM',
            'address'        => '3 Avenue Road',
            'phone_primary'  => '+237600000103',
        ]);
    }

    public function test_directory_rows_report_the_register_link(): void
    {
        $response = $this->mobileGetJson($this->patient, '/api/mobile/facilities');
        $response->assertStatus(200);

        $rows = collect($response->json('data'))->keyBy('id');

        $this->assertTrue($rows[$this->connected->id]['connected']);
        $this->assertSame($this->tenant->id, $rows[$this->connected->id]['facility_id']);
        $this->assertSame(1, $rows[$this->connected->id]['open_slot_count']);

        $this->assertFalse($rows[$this->unlinked->id]['connected']);
        $this->assertNull($rows[$this->unlinked->id]['facility_id']);
        $this->assertSame(0, $rows[$this->unlinked->id]['open_slot_count']);
    }

    public function test_connected_listings_come_first_by_default(): void
    {
        $names = collect($this->mobileGetJson($this->patient, '/api/mobile/facilities')->json('data'))
            ->pluck('facility_name')
            ->all();

        // Alphabetically "Avenue Health Post" leads; by connectivity it must
        // not. Both linked listings precede the unlinked one.
        $this->assertSame(
            ['Beacon Clinic', 'Zenith Teaching Hospital', 'Avenue Health Post'],
            $names,
        );
    }

    public function test_sort_by_name_is_still_available(): void
    {
        $names = collect($this->mobileGetJson($this->patient, '/api/mobile/facilities?sort=name')->json('data'))
            ->pluck('facility_name')
            ->all();

        $this->assertSame(
            ['Avenue Health Post', 'Beacon Clinic', 'Zenith Teaching Hospital'],
            $names,
        );
    }

    public function test_connected_filter_narrows_to_linked_listings(): void
    {
        $ids = collect($this->mobileGetJson($this->patient, '/api/mobile/facilities?connected=1')->json('data'))
            ->pluck('id')
            ->all();

        $this->assertContains($this->connected->id, $ids);
        $this->assertContains($this->connectedNoSlots->id, $ids);
        $this->assertNotContains($this->unlinked->id, $ids);
    }

    public function test_connected_zero_returns_only_directory_only_listings(): void
    {
        $ids = collect($this->mobileGetJson($this->patient, '/api/mobile/facilities?connected=0')->json('data'))
            ->pluck('id')
            ->all();

        $this->assertSame([$this->unlinked->id], $ids);
    }

    public function test_bookable_filter_requires_an_open_future_slot(): void
    {
        $ids = collect($this->mobileGetJson($this->patient, '/api/mobile/facilities?bookable=1')->json('data'))
            ->pluck('id')
            ->all();

        // Linked is not enough — Beacon Clinic has a tenant but no calendar.
        $this->assertSame([$this->connected->id], $ids);
    }

    public function test_with_providers_filter_requires_an_active_clinical_profile(): void
    {
        $this->assertSame(
            [],
            collect($this->mobileGetJson($this->patient, '/api/mobile/facilities?with_providers=1')->json('data'))
                ->pluck('id')
                ->all(),
            'No facility publishes a clinician yet, and the filter must say so rather than fall back to "connected".',
        );

        StaffProfile::create([
            'user_id'         => User::factory()->create()->id,
            'facility_id'     => $this->tenant->id,
            'employee_number' => 'EMP-REG-001',
            'first_name'      => 'Ada',
            'last_name'       => 'Nkeng',
            'job_title'       => 'Paediatrician',
            'staff_category'  => 'clinical',
            'status'          => 'active',
        ]);

        $ids = collect($this->mobileGetJson($this->patient, '/api/mobile/facilities?with_providers=1')->json('data'))
            ->pluck('id')
            ->all();

        $this->assertSame([$this->connected->id], $ids);
    }

    public function test_registry_block_reports_honest_totals(): void
    {
        $registry = $this->mobileGetJson($this->patient, '/api/mobile/facilities')->json('registry');

        $this->assertSame(3, $registry['directory_total']);
        $this->assertSame(2, $registry['connected_total']);
        $this->assertSame(1, $registry['bookable_total']);
        $this->assertSame(0, $registry['with_providers_total']);
    }

    public function test_facility_detail_states_its_connectivity(): void
    {
        $linked = $this->mobileGetJson($this->patient, '/api/mobile/facilities/' . $this->connected->id)
            ->json('data');

        $this->assertTrue($linked['connected']);
        $this->assertSame($this->tenant->id, $linked['facility_id']);
        $this->assertSame(1, $linked['open_slot_count']);
        $this->assertSame(__('facility_registry.roster.slots_without_roster'), $linked['connectivity_note']);

        $directoryOnly = $this->mobileGetJson($this->patient, '/api/mobile/facilities/' . $this->unlinked->id)
            ->json('data');

        $this->assertFalse($directoryOnly['connected']);
        $this->assertNull($directoryOnly['facility_id']);
        $this->assertSame(__('facility_registry.roster.not_connected'), $directoryOnly['connectivity_note']);
    }

    public function test_provider_roster_distinguishes_unlinked_from_unpublished(): void
    {
        $unlinked = $this->mobileGetJson(
            $this->patient,
            '/api/mobile/facilities/' . $this->unlinked->id . '/providers',
        )->json();

        $this->assertFalse($unlinked['connected']);
        $this->assertSame('not_connected', $unlinked['roster_status']);

        $connected = $this->mobileGetJson(
            $this->patient,
            '/api/mobile/facilities/' . $this->connected->id . '/providers',
        )->json();

        $this->assertTrue($connected['connected']);
        $this->assertSame('none_published', $connected['roster_status']);
        $this->assertSame([], $connected['data'], 'An empty roster stays empty — no clinician is invented.');
        // Bookable without a named clinician: a slot belongs to a facility.
        $this->assertSame(1, $connected['open_slot_count']);
        $this->assertSame(__('facility_registry.roster.slots_without_roster'), $connected['message']);
    }

    public function test_directory_search_is_case_insensitive(): void
    {
        $names = collect($this->mobileGetJson($this->patient, '/api/mobile/facilities?q=zenith')->json('data'))
            ->pluck('facility_name')
            ->all();

        $this->assertSame(['Zenith Teaching Hospital'], $names);
    }

    /**
     * 793 of the 2 728 live listings are in "Yaoundé" and 61 in "Ngaoundéré".
     * A patient types "Yaounde". Before accent folding that search returned
     * one row — the single listing without the accent.
     */
    public function test_directory_search_ignores_accents(): void
    {
        CareFacility::create([
            'facility_name'  => 'Hôpital Général de Yaoundé',
            'facility_type'  => 'hospital',
            'listing_status' => 'active',
            'city'           => 'Yaoundé',
            'country_code'   => 'CM',
            'address'        => '4 Rue de la Réunification',
            'phone_primary'  => '+237600000104',
        ]);

        // "Zenith Teaching Hospital" is seeded in the unaccented "Yaounde", so
        // each spelling must find BOTH — folding has to work in both
        // directions, not just strip accents from the needle.
        foreach (['Yaounde', 'yaounde', 'Yaoundé', 'YAOUNDE'] as $typed) {
            $names = collect(
                $this->mobileGetJson($this->patient, '/api/mobile/facilities?city=' . urlencode($typed))->json('data')
            )->pluck('facility_name')->sort()->values()->all();

            $this->assertSame(
                ['Hôpital Général de Yaoundé', 'Zenith Teaching Hospital'],
                $names,
                "Searching the city as \"{$typed}\" must find the accented listing.",
            );
        }

        // The same folding on the free-text search, and on the name as well as
        // the city.
        $this->assertSame(
            ['Hôpital Général de Yaoundé'],
            collect($this->mobileGetJson($this->patient, '/api/mobile/facilities?q=hopital+general')->json('data'))
                ->pluck('facility_name')
                ->all(),
        );
    }

    public function test_directory_still_lists_unlinked_listings(): void
    {
        // The point is ordering and labelling, never suppression: a patient must
        // still be able to find and phone a facility that has not signed up.
        $ids = collect($this->mobileGetJson($this->patient, '/api/mobile/facilities')->json('data'))
            ->pluck('id')
            ->all();

        $this->assertContains($this->unlinked->id, $ids);
    }
}
