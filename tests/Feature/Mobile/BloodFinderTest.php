<?php

namespace Tests\Feature\Mobile;

use App\Enums\BloodComponentType;
use App\Enums\BloodGroup;
use App\Enums\BloodRequestStatus;
use App\Models\BloodAvailability;
use App\Models\BloodRequest;
use App\Models\CareFacility;
use App\Models\Patient;
use App\Modules\CareMap\Services\BloodRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * Patient-facing Blood Finder — /api/mobile/blood/*.
 *
 * Covers the two things that can silently break this feature: the route prefix
 * (a route registered at `mobile/...` instead of `api/mobile/...` 404s every
 * client call) and patient scoping on the request records.
 */
class BloodFinderTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    // Douala, Akwa — the search origin used throughout these tests.
    private const ORIGIN_LAT = 4.0511;
    private const ORIGIN_LNG = 9.7679;

    private Patient $patient;
    private Patient $otherPatient;
    private CareFacility $nearBank;
    private CareFacility $farBank;
    private BloodAvailability $nearAvailability;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = Patient::create([
            'health_id'     => 'OC-TST-7200-0001-01',
            'first_name'    => 'Ines',
            'last_name'     => 'Donor',
            'sex'           => 'female',
            'date_of_birth' => '1990-02-03',
            'is_demo'       => false,
        ]);

        $this->otherPatient = Patient::create([
            'health_id'     => 'OC-TST-7200-0002-01',
            'first_name'    => 'Paul',
            'last_name'     => 'Autre',
            'sex'           => 'male',
            'date_of_birth' => '1988-09-14',
            'is_demo'       => false,
        ]);

        // ~1.2 km from the origin.
        $this->nearBank = CareFacility::create([
            'facility_name'  => 'Douala Central Blood Bank',
            'facility_type'  => 'blood_bank',
            'listing_status' => 'active',
            'city'           => 'Douala',
            'region'         => 'Littoral',
            'country_code'   => 'CM',
            'address'        => 'Boulevard de la Liberte, Akwa',
            'latitude'       => 4.0611,
            'longitude'      => 9.7710,
            'phone_primary'  => '+237233421501',
        ]);

        // ~40 km away — outside a 5km search.
        $this->farBank = CareFacility::create([
            'facility_name'  => 'Edea Regional Hospital Blood Unit',
            'facility_type'  => 'hospital',
            'listing_status' => 'active',
            'city'           => 'Edea',
            'region'         => 'Littoral',
            'country_code'   => 'CM',
            'address'        => 'Centre ville, Edea',
            'latitude'       => 3.8000,
            'longitude'      => 10.1333,
            'phone_primary'  => '+237233421599',
        ]);

        // `source_system` is required for a row to be PUBLIC. The finder
        // withholds seeded and unattributed availability
        // (BloodAvailability::scopeReportedByRealSource()), so an unstamped
        // fixture would make every assertion below measure the empty set.
        // 'portal' is what the staff blood screen stamps.
        $this->nearAvailability = BloodAvailability::create([
            'facility_id'           => $this->nearBank->id,
            'blood_group'           => BloodGroup::ONegative->value,
            'component_type'        => BloodComponentType::WholeBlood->value,
            'units_available_range' => '5-10',
            'availability_status'   => 'available',
            'freshness_status'      => 'fresh',
            'emergency_contact'     => '+237699000111',
            'source_system'         => 'portal',
            'last_updated_at'       => now(),
        ]);

        BloodAvailability::create([
            'facility_id'           => $this->farBank->id,
            'blood_group'           => BloodGroup::ONegative->value,
            'component_type'        => BloodComponentType::WholeBlood->value,
            'units_available_range' => '1-4',
            'availability_status'   => 'available',
            'freshness_status'      => 'recent',
            'source_system'         => 'portal',
            'last_updated_at'       => now(),
        ]);
    }

    // ── Routing + auth ───────────────────────────────────────────────────────

    public function test_blood_search_requires_authentication(): void
    {
        $this->getJson('/api/mobile/blood/search?blood_group=O-')->assertStatus(401);
    }

    public function test_blood_routes_are_registered_under_the_api_prefix(): void
    {
        // A route file loaded from AppServiceProvider does NOT inherit the
        // automatic /api prefix — this asserts the prefix is spelled out.
        $this->mobileGetJson($this->patient, '/api/mobile/blood/options')->assertStatus(200);
        $this->getJson('/mobile/blood/options')->assertStatus(404);
    }

    // ── Options ──────────────────────────────────────────────────────────────

    public function test_options_lists_every_group_with_availability_counts(): void
    {
        $response = $this->mobileGetJson($this->patient, '/api/mobile/blood/options');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'blood_groups'    => [['value', 'label', 'can_receive_from', 'facility_count']],
                    'component_types' => [['value', 'label', 'icon', 'facility_count']],
                    'urgencies'       => [['value', 'label']],
                    'max_units',
                ],
            ]);

        $groups = collect($response->json('data.blood_groups'))->keyBy('value');

        $this->assertCount(8, $groups);
        $this->assertSame(2, $groups[BloodGroup::ONegative->value]['facility_count']);
        $this->assertSame(0, $groups[BloodGroup::ABPositive->value]['facility_count']);
    }

    // ── Search ───────────────────────────────────────────────────────────────

    public function test_search_rejects_an_unknown_blood_group(): void
    {
        $this->mobileGetJson($this->patient, '/api/mobile/blood/search?blood_group=Z%2B')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['blood_group']);
    }

    public function test_search_excludes_facilities_outside_the_radius(): void
    {
        $response = $this->mobileGetJson($this->patient, '/api/mobile/blood/search?' . http_build_query([
            'blood_group' => BloodGroup::ONegative->value,
            'lat'         => self::ORIGIN_LAT,
            'lng'         => self::ORIGIN_LNG,
            'radius_km'   => 5,
        ]));

        $response->assertStatus(200);
        $names = array_column($response->json('data'), 'name');

        $this->assertContains('Douala Central Blood Bank', $names);
        $this->assertNotContains(
            'Edea Regional Hospital Blood Unit',
            $names,
            'A blood bank 40km away must not appear in a 5km search',
        );
    }

    public function test_search_returns_availability_details_and_distance(): void
    {
        $response = $this->mobileGetJson($this->patient, '/api/mobile/blood/search?' . http_build_query([
            'blood_group'    => BloodGroup::ONegative->value,
            'component_type' => BloodComponentType::WholeBlood->value,
            'lat'            => self::ORIGIN_LAT,
            'lng'            => self::ORIGIN_LNG,
            'radius_km'      => 10,
        ]));

        $response->assertStatus(200);
        $row = $response->json('data.0');

        $this->assertSame('Douala Central Blood Bank', $row['name']);
        $this->assertSame('5-10', $row['availability']['units_range']);
        $this->assertSame('fresh', $row['availability']['freshness']);
        $this->assertSame('+237699000111', $row['emergency_contact']);
        $this->assertNotNull($row['distance_km']);
        $this->assertLessThan(5, $row['distance_km']);
    }

    public function test_search_does_not_return_a_group_the_facility_has_not_reported(): void
    {
        $response = $this->mobileGetJson($this->patient, '/api/mobile/blood/search?' . http_build_query([
            'blood_group' => BloodGroup::ABNegative->value,
            'lat'         => self::ORIGIN_LAT,
            'lng'         => self::ORIGIN_LNG,
        ]));

        $response->assertStatus(200);
        $this->assertSame([], $response->json('data'));
    }

    public function test_search_hides_facilities_that_are_not_publicly_listed(): void
    {
        $this->nearBank->update(['listing_status' => 'suspended']);

        $response = $this->mobileGetJson($this->patient, '/api/mobile/blood/search?' . http_build_query([
            'blood_group' => BloodGroup::ONegative->value,
            'lat'         => self::ORIGIN_LAT,
            'lng'         => self::ORIGIN_LNG,
            'radius_km'   => 5,
        ]));

        $response->assertStatus(200);
        $this->assertSame([], $response->json('data'));
    }

    // ── Requests ─────────────────────────────────────────────────────────────

    public function test_a_patient_can_request_units_at_a_facility_reporting_availability(): void
    {
        $response = $this->mobilePostJson($this->patient, '/api/mobile/blood/requests', [
            'care_facility_id' => $this->nearBank->id,
            'blood_group'      => BloodGroup::ONegative->value,
            'component_type'   => BloodComponentType::WholeBlood->value,
            'quantity'         => 2,
            'urgency'          => 'urgent',
            'contact_phone'    => '+237699123456',
            'note'             => 'Surgery scheduled Thursday morning.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', BloodRequestStatus::Pending->value)
            ->assertJsonPath('data.quantity', 2)
            ->assertJsonPath('data.blood_group', 'O-')
            ->assertJsonPath('data.facility.name', 'Douala Central Blood Bank');

        $this->assertStringStartsWith('OC-BL-', $response->json('data.reference'));

        $row = BloodRequest::first();
        $this->assertSame($this->patient->id, $row->patient_id);
        $this->assertSame($this->nearAvailability->id, $row->blood_availability_id);
        $this->assertNotNull($row->expires_at);
    }

    public function test_a_request_is_refused_when_the_facility_does_not_report_that_group(): void
    {
        $this->mobilePostJson($this->patient, '/api/mobile/blood/requests', [
            'care_facility_id' => $this->nearBank->id,
            'blood_group'      => BloodGroup::ABNegative->value,
        ])
            ->assertStatus(409)
            ->assertJsonPath('error_code', BloodRequestService::ERR_NOT_AVAILABLE);
    }

    public function test_a_second_open_request_for_the_same_group_at_the_same_facility_is_refused(): void
    {
        $payload = [
            'care_facility_id' => $this->nearBank->id,
            'blood_group'      => BloodGroup::ONegative->value,
        ];

        $this->mobilePostJson($this->patient, '/api/mobile/blood/requests', $payload)->assertStatus(201);

        $this->mobilePostJson($this->patient, '/api/mobile/blood/requests', $payload)
            ->assertStatus(409)
            ->assertJsonPath('error_code', BloodRequestService::ERR_DUPLICATE);
    }

    public function test_requests_are_scoped_to_the_authenticated_patient(): void
    {
        $this->mobilePostJson($this->patient, '/api/mobile/blood/requests', [
            'care_facility_id' => $this->nearBank->id,
            'blood_group'      => BloodGroup::ONegative->value,
        ])->assertStatus(201);

        $mine = $this->mobileGetJson($this->patient, '/api/mobile/blood/requests');
        $mine->assertStatus(200);
        $this->assertCount(1, $mine->json('data'));

        $theirs = $this->mobileGetJson($this->otherPatient, '/api/mobile/blood/requests');
        $theirs->assertStatus(200);
        $this->assertSame([], $theirs->json('data'));
    }

    public function test_another_patient_cannot_cancel_a_request_they_do_not_own(): void
    {
        $created = $this->mobilePostJson($this->patient, '/api/mobile/blood/requests', [
            'care_facility_id' => $this->nearBank->id,
            'blood_group'      => BloodGroup::ONegative->value,
        ])->json('data');

        $this->mobilePostJson(
            $this->otherPatient,
            '/api/mobile/blood/requests/' . $created['id'] . '/cancel',
        )
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'REQUEST_NOT_FOUND');

        $this->assertSame(
            BloodRequestStatus::Pending,
            BloodRequest::find($created['id'])->status,
        );
    }

    public function test_cancelling_moves_the_request_to_a_terminal_status_without_deleting_it(): void
    {
        $created = $this->mobilePostJson($this->patient, '/api/mobile/blood/requests', [
            'care_facility_id' => $this->nearBank->id,
            'blood_group'      => BloodGroup::ONegative->value,
        ])->json('data');

        $this->mobilePostJson(
            $this->patient,
            '/api/mobile/blood/requests/' . $created['id'] . '/cancel',
            ['reason' => 'Found units elsewhere'],
        )
            ->assertStatus(200)
            ->assertJsonPath('data.status', BloodRequestStatus::Cancelled->value)
            ->assertJsonPath('data.is_open', false);

        $row = BloodRequest::find($created['id']);
        $this->assertNotNull($row, 'A cancelled request must never be deleted');
        $this->assertSame('Found units elsewhere', $row->cancelled_reason);

        // A second cancel is refused rather than silently re-applied.
        $this->mobilePostJson($this->patient, '/api/mobile/blood/requests/' . $created['id'] . '/cancel')
            ->assertStatus(409)
            ->assertJsonPath('error_code', BloodRequestService::ERR_NOT_CANCELLABLE);
    }

    public function test_the_open_request_limit_is_enforced(): void
    {
        // Fill the patient's quota at distinct facilities.
        for ($i = 0; $i < BloodRequestService::MAX_OPEN_REQUESTS; $i++) {
            $facility = CareFacility::create([
                'facility_name'  => "Quota Blood Bank {$i}",
                'facility_type'  => 'blood_bank',
                'listing_status' => 'active',
                'city'           => 'Douala',
                'region'         => 'Littoral',
                'country_code'   => 'CM',
                'address'        => "Rue {$i}, Akwa",
                'latitude'       => 4.06 + ($i / 1000),
                'longitude'      => 9.77,
                'phone_primary'  => '+23723342160' . $i,
            ]);

            BloodAvailability::create([
                'facility_id'         => $facility->id,
                'blood_group'         => BloodGroup::ONegative->value,
                'component_type'      => BloodComponentType::WholeBlood->value,
                'availability_status' => 'available',
                'freshness_status'    => 'fresh',
                'last_updated_at'     => now(),
                // Provenance, as a real blood-bank report carries it. Ordering
                // is gated by the same scope the finder reads, so an unstamped
                // row is neither findable nor orderable — which is the point.
                'source_system'       => 'portal',
            ]);

            $this->mobilePostJson($this->patient, '/api/mobile/blood/requests', [
                'care_facility_id' => $facility->id,
                'blood_group'      => BloodGroup::ONegative->value,
            ])->assertStatus(201);
        }

        $this->mobilePostJson($this->patient, '/api/mobile/blood/requests', [
            'care_facility_id' => $this->nearBank->id,
            'blood_group'      => BloodGroup::ONegative->value,
        ])
            ->assertStatus(429)
            ->assertJsonPath('error_code', BloodRequestService::ERR_TOO_MANY_OPEN);
    }

    /**
     * The lockout regression.
     *
     * `expires_at` was written on every request and read by nothing, so an
     * unanswered hold stayed `pending` for ever and kept counting against
     * MAX_OPEN_REQUESTS. Five of them and the patient could never use the Blood
     * Finder again — the first real user session bricked itself.
     */
    public function test_a_lapsed_hold_stops_counting_against_the_open_limit(): void
    {
        $this->fillOpenRequestQuota();

        $this->mobilePostJson($this->patient, '/api/mobile/blood/requests', [
            'care_facility_id' => $this->nearBank->id,
            'blood_group'      => BloodGroup::ONegative->value,
        ])
            ->assertStatus(429)
            ->assertJsonPath('error_code', BloodRequestService::ERR_TOO_MANY_OPEN);

        // The hold window runs out with the blood bank never having answered.
        BloodRequest::query()->where('patient_id', $this->patient->id)->update([
            'expires_at' => now()->subHour(),
        ]);

        $this->mobilePostJson($this->patient, '/api/mobile/blood/requests', [
            'care_facility_id' => $this->nearBank->id,
            'blood_group'      => BloodGroup::ONegative->value,
        ])->assertStatus(201);

        // Lapsed holds are retired forward, never deleted.
        $this->assertSame(5, BloodRequest::query()
            ->where('patient_id', $this->patient->id)
            ->where('status', BloodRequestStatus::Expired->value)
            ->count());
        $this->assertSame(6, BloodRequest::query()
            ->where('patient_id', $this->patient->id)
            ->count());
    }

    public function test_the_expiry_command_sweeps_lapsed_requests(): void
    {
        $this->fillOpenRequestQuota();

        BloodRequest::query()->where('patient_id', $this->patient->id)->update([
            'expires_at' => now()->subHour(),
        ]);

        $this->artisan('blood:expire-requests', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(0, BloodRequest::query()
            ->where('status', BloodRequestStatus::Expired->value)->count(),
            'A dry run must not write');

        $this->artisan('blood:expire-requests')->assertSuccessful();
        $this->assertSame(
            BloodRequestService::MAX_OPEN_REQUESTS,
            BloodRequest::query()->where('status', BloodRequestStatus::Expired->value)->count(),
        );

        // Terminal rows are never re-touched, and a second sweep is a no-op.
        $this->artisan('blood:expire-requests')->assertSuccessful();
        $this->assertSame(0, BloodRequest::query()->open()->count());
    }

    /** Fills the patient's open-request quota at distinct facilities. */
    private function fillOpenRequestQuota(): void
    {
        for ($i = 0; $i < BloodRequestService::MAX_OPEN_REQUESTS; $i++) {
            $facility = CareFacility::create([
                'facility_name'  => "Lapse Blood Bank {$i}",
                'facility_type'  => 'blood_bank',
                'listing_status' => 'active',
                'city'           => 'Douala',
                'region'         => 'Littoral',
                'country_code'   => 'CM',
                'address'        => "Avenue {$i}, Bonanjo",
                'latitude'       => 4.05 + ($i / 1000),
                'longitude'      => 9.76,
                'phone_primary'  => '+23723342170' . $i,
            ]);

            BloodAvailability::create([
                'facility_id'         => $facility->id,
                'blood_group'         => BloodGroup::ONegative->value,
                'component_type'      => BloodComponentType::WholeBlood->value,
                'availability_status' => 'available',
                'freshness_status'    => 'fresh',
                'last_updated_at'     => now(),
                // Provenance, as a real blood-bank report carries it. Ordering
                // is gated by the same scope the finder reads, so an unstamped
                // row is neither findable nor orderable — which is the point.
                'source_system'       => 'portal',
            ]);

            $this->mobilePostJson($this->patient, '/api/mobile/blood/requests', [
                'care_facility_id' => $facility->id,
                'blood_group'      => BloodGroup::ONegative->value,
            ])->assertStatus(201);
        }
    }

    public function test_quantity_above_the_ceiling_is_rejected(): void
    {
        $this->mobilePostJson($this->patient, '/api/mobile/blood/requests', [
            'care_facility_id' => $this->nearBank->id,
            'blood_group'      => BloodGroup::ONegative->value,
            'quantity'         => BloodRequestService::MAX_UNITS + 1,
        ])->assertStatus(422)->assertJsonValidationErrors(['quantity']);
    }
}
