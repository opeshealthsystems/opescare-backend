<?php

namespace Tests\Feature\Blood;

use App\Enums\BloodRequestStatus;
use App\Models\BloodAvailability;
use App\Models\BloodInventory;
use App\Models\BloodRequest;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\Patient;
use App\Modules\CareMap\Services\BloodAvailabilityProjector;
use App\Modules\CareMap\Services\BloodRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * Blood, end to end: one source of truth, and a request that gets an answer.
 *
 * Two defects are pinned here.
 *
 * 1. Blood was recorded twice with no arrow between the tables.
 *    `blood_inventories` (units + safety flags, keyed on `facilities.id`) is
 *    the operational record; `blood_availability` (a public band, keyed on
 *    `care_facilities.id`) is what the patient sees. Nothing derived the second
 *    from the first, so they disagreed: 5 units on the shelf, "20+" in the app.
 *
 * 2. The request loop had no receiver. `confirmed`, `ready`, `fulfilled` and
 *    `rejected` were declared and unreachable — a patient's request could only
 *    ever be cancelled by the patient or expired by the sweep.
 */
class BloodBankCoherenceTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    /** The integration-client test bypass baked into VerifyIntegrationClient. */
    private const CLIENT_HEADERS = [
        'X-Client-ID'     => 'test_client_id',
        'X-Client-Secret' => 'test_client_secret',
    ];

    private Facility $tenant;
    private Facility $otherTenant;
    private CareFacility $listing;
    private CareFacility $otherListing;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Facility::create([
            'name'   => 'Yaounde Central Blood Bank',
            'type'   => 'hospital',
            'status' => 'active',
        ]);

        $this->otherTenant = Facility::create([
            'name'   => 'Douala Rival Blood Bank',
            'type'   => 'hospital',
            'status' => 'active',
        ]);

        $this->listing = CareFacility::create([
            'facility_id'    => $this->tenant->id,
            'facility_name'  => 'Yaounde Central Blood Bank',
            'facility_type'  => 'blood_bank',
            'listing_status' => 'active',
            'city'           => 'Yaounde',
            'region'         => 'Centre',
            'country_code'   => 'CM',
            'address'        => 'Rue de la Reunification',
            'latitude'       => 3.8667,
            'longitude'      => 11.5167,
            'phone_primary'  => '+237222234000',
        ]);

        $this->otherListing = CareFacility::create([
            'facility_id'    => $this->otherTenant->id,
            'facility_name'  => 'Douala Rival Blood Bank',
            'facility_type'  => 'blood_bank',
            'listing_status' => 'active',
            'city'           => 'Douala',
            'region'         => 'Littoral',
            'country_code'   => 'CM',
            'address'        => 'Boulevard de la Liberte',
            'phone_primary'  => '+237233420000',
        ]);

        $this->patient = Patient::create([
            'health_id'     => 'OC-TST-7300-0001-01',
            'first_name'    => 'Aline',
            'last_name'     => 'Receveur',
            'sex'           => 'female',
            'date_of_birth' => '1992-06-11',
            'is_demo'       => false,
        ]);
    }

    // ── 1. One source of truth ───────────────────────────────────────────────

    public function test_availability_is_projected_from_the_operational_inventory(): void
    {
        $this->stock('O+', 'whole_blood', 5);
        $this->stock('A+', 'packed_red_cells', 30);

        $this->projector()->projectFacility($this->tenant->id);

        $wholeBlood = $this->availability('O+', 'whole_blood');
        $this->assertSame('available', $wholeBlood->availability_status);
        $this->assertSame('1-5', $wholeBlood->units_available_range);

        // The operational spelling `packed_red_cells` publishes as `red_cells`:
        // the finder searches one vocabulary, the shelf uses another.
        $redCells = $this->availability('A+', 'red_cells');
        $this->assertSame('20+', $redCells->units_available_range);
    }

    public function test_the_patient_endpoint_answers_with_the_operational_number(): void
    {
        // The exact contradiction found in the live data: the shelf holds five
        // units of O+ whole blood while availability advertised "20+".
        $stale = BloodAvailability::create([
            'facility_id'           => $this->listing->id,
            'blood_group'           => 'O+',
            'component_type'        => 'whole_blood',
            'units_available_range' => '20+',
            'availability_status'   => 'available',
            'freshness_status'      => 'fresh',
            // A published row is one somebody claimed. Unattributed rows are
            // withheld by BloodAvailability::scopeReportedByRealSource(), so an
            // unstamped fixture is invisible to the very search this asserts on.
            'source_system'         => 'portal',
            'last_updated_at'       => now(),
        ]);

        $this->stock('O+', 'whole_blood', 5);
        $this->projector()->projectFacility($this->tenant->id);

        $response = $this->mobileGetJson($this->patient, '/api/mobile/blood/search?blood_group=' . urlencode('O+'));

        $response->assertStatus(200);
        $this->assertSame('1-5', $response->json('data.0.availability.units_range'));

        // Projected onto the row that was already there, not a second one.
        $this->assertSame(1, BloodAvailability::query()->where('facility_id', $this->listing->id)->count());
        $this->assertSame('1-5', $stale->refresh()->units_available_range);
    }

    public function test_flagged_units_are_never_advertised(): void
    {
        $unit = $this->stock('AB-', 'platelets', 12);
        $this->projector()->projectFacility($this->tenant->id);

        $this->assertSame('available', $this->availability('AB-', 'platelets')->availability_status);

        $unit->update(['is_unsafe' => true]);
        $this->projector()->projectFacility($this->tenant->id);

        $row = $this->availability('AB-', 'platelets');
        $this->assertSame('unavailable', $row->availability_status);
        $this->assertSame('0', $row->units_available_range);
    }

    public function test_availability_the_inventory_does_not_back_is_retired_not_deleted(): void
    {
        $orphan = BloodAvailability::create([
            'facility_id'           => $this->listing->id,
            'blood_group'           => 'B-',
            'component_type'        => 'plasma',
            'units_available_range' => '20+',
            'availability_status'   => 'available',
            'freshness_status'      => 'fresh',
            // Previously published by the facility itself — a real source, so
            // it is a row patients could see before the projector retires it.
            'source_system'         => 'portal',
            'last_updated_at'       => now(),
        ]);

        $this->stock('O+', 'whole_blood', 8);
        $this->projector()->projectFacility($this->tenant->id);

        $orphan->refresh();
        $this->assertNotNull($orphan, 'A retired availability row must never be deleted');
        $this->assertSame('unavailable', $orphan->availability_status);
    }

    public function test_a_facility_with_no_operational_record_keeps_its_self_reported_availability(): void
    {
        $selfReported = BloodAvailability::create([
            'facility_id'           => $this->otherListing->id,
            'blood_group'           => 'O-',
            'component_type'        => 'whole_blood',
            'units_available_range' => '6-20',
            'availability_status'   => 'available',
            'freshness_status'      => 'fresh',
            // Self-reported by a facility with no operational record — still a
            // real source, and the row this test proves the projector leaves be.
            'source_system'         => 'portal',
            'last_updated_at'       => now(),
        ]);

        $this->projector()->projectFacility($this->otherTenant->id);

        $this->assertSame('6-20', $selfReported->refresh()->units_available_range);
    }

    public function test_projection_is_idempotent(): void
    {
        $this->stock('O+', 'whole_blood', 9);

        $this->projector()->projectFacility($this->tenant->id);
        $first = $this->availability('O+', 'whole_blood')->only(['availability_status', 'units_available_range']);

        $this->projector()->projectFacility($this->tenant->id);
        $second = $this->availability('O+', 'whole_blood')->only(['availability_status', 'units_available_range']);

        $this->assertSame($first, $second);
        $this->assertSame(1, BloodAvailability::query()->count());
    }

    public function test_a_staff_stock_adjustment_republishes_availability(): void
    {
        $unit = $this->stock('O+', 'whole_blood', 30);
        $this->projector()->projectFacility($this->tenant->id);
        $this->assertSame('20+', $this->availability('O+', 'whole_blood')->units_available_range);

        // The staff API, not the projector — the write path must publish itself.
        $this->withHeaders(self::CLIENT_HEADERS)
            ->postJson('/api/v1/inventory/blood/' . $unit->id . '/adjust', [
                'facility_id' => $this->tenant->id,   // test-bypass facility scope
                'delta'       => 28,
                'direction'   => 'subtract',
            ])
            ->assertStatus(200);

        $this->assertSame('1-5', $this->availability('O+', 'whole_blood')->units_available_range);
    }

    public function test_one_blood_bank_cannot_adjust_another_banks_units(): void
    {
        $unit = $this->stock('O+', 'whole_blood', 10);

        $this->withHeaders(self::CLIENT_HEADERS)
            ->postJson('/api/v1/inventory/blood/' . $unit->id . '/adjust', [
                'facility_id' => $this->otherTenant->id,
                'delta'       => 10,
                'direction'   => 'subtract',
            ])
            ->assertStatus(404);

        $this->assertSame(10, $unit->refresh()->available_units);
    }

    // ── 2. The receiver ──────────────────────────────────────────────────────

    public function test_the_blood_bank_sees_requests_raised_against_its_own_listings_only(): void
    {
        $mine    = $this->pendingRequest($this->listing);
        $notMine = $this->pendingRequest($this->otherListing, 'A+');

        $response = $this->withHeaders(self::CLIENT_HEADERS)
            ->getJson('/api/v1/blood-bank/requests?facility_id=' . $this->tenant->id);

        $response->assertStatus(200);
        $ids = array_column($response->json('data'), 'id');

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($notMine->id, $ids);
    }

    public function test_a_pending_request_can_be_confirmed_by_the_blood_bank(): void
    {
        $request = $this->pendingRequest($this->listing);

        $response = $this->withHeaders(self::CLIENT_HEADERS)
            ->postJson('/api/v1/blood-bank/requests/' . $request->id . '/decision', [
                'facility_id' => $this->tenant->id,
                'decision'    => BloodRequestStatus::Confirmed->value,
                'note'        => 'Two units held at the counter until 18:00.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', BloodRequestStatus::Confirmed->value);

        $request->refresh();
        $this->assertSame(BloodRequestStatus::Confirmed, $request->status);
        $this->assertNotNull($request->confirmed_at, 'A decision must carry a timestamp');
        $this->assertSame('test_client_id', $request->decided_by, 'A decision must carry an actor');
        $this->assertSame('Two units held at the counter until 18:00.', $request->facility_note);
    }

    public function test_the_patient_sees_the_status_the_blood_bank_set(): void
    {
        $request = $this->pendingRequest($this->listing);

        $this->withHeaders(self::CLIENT_HEADERS)
            ->postJson('/api/v1/blood-bank/requests/' . $request->id . '/decision', [
                'facility_id' => $this->tenant->id,
                'decision'    => BloodRequestStatus::Ready->value,
                'note'        => 'Ready for collection.',
            ])
            ->assertStatus(200);

        $patientView = $this->mobileGetJson($this->patient, '/api/mobile/blood/requests');

        $patientView->assertStatus(200)
            ->assertJsonPath('data.0.status', BloodRequestStatus::Ready->value)
            ->assertJsonPath('data.0.facility_note', 'Ready for collection.');
    }

    public function test_transitions_are_forward_only(): void
    {
        $request = $this->pendingRequest($this->listing);

        foreach ([BloodRequestStatus::Confirmed, BloodRequestStatus::Ready, BloodRequestStatus::Fulfilled] as $step) {
            $this->withHeaders(self::CLIENT_HEADERS)
                ->postJson('/api/v1/blood-bank/requests/' . $request->id . '/decision', [
                    'facility_id' => $this->tenant->id,
                    'decision'    => $step->value,
                ])
                ->assertStatus(200);
        }

        // Fulfilled is terminal — it can never step back to confirmed.
        $this->withHeaders(self::CLIENT_HEADERS)
            ->postJson('/api/v1/blood-bank/requests/' . $request->id . '/decision', [
                'facility_id' => $this->tenant->id,
                'decision'    => BloodRequestStatus::Confirmed->value,
            ])
            ->assertStatus(409)
            ->assertJsonPath('error_code', BloodRequestService::ERR_BAD_TRANSITION);

        $this->assertSame(BloodRequestStatus::Fulfilled, $request->refresh()->status);
    }

    public function test_a_rejected_request_is_kept_not_deleted(): void
    {
        $request = $this->pendingRequest($this->listing);

        $this->withHeaders(self::CLIENT_HEADERS)
            ->postJson('/api/v1/blood-bank/requests/' . $request->id . '/decision', [
                'facility_id' => $this->tenant->id,
                'decision'    => BloodRequestStatus::Rejected->value,
                'note'        => 'Units reserved for an emergency admission.',
            ])
            ->assertStatus(200);

        $row = BloodRequest::find($request->id);
        $this->assertNotNull($row, 'A rejected request must never be deleted');
        $this->assertSame(BloodRequestStatus::Rejected, $row->status);
        $this->assertFalse($row->status->isOpen());
        $this->assertNotNull($row->decided_at);
    }

    public function test_a_blood_bank_cannot_decide_another_banks_request(): void
    {
        $request = $this->pendingRequest($this->listing);

        $this->withHeaders(self::CLIENT_HEADERS)
            ->postJson('/api/v1/blood-bank/requests/' . $request->id . '/decision', [
                'facility_id' => $this->otherTenant->id,
                'decision'    => BloodRequestStatus::Confirmed->value,
            ])
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'REQUEST_NOT_FOUND');

        $this->assertSame(BloodRequestStatus::Pending, $request->refresh()->status);
    }

    public function test_the_queue_requires_integration_client_credentials(): void
    {
        $this->getJson('/api/v1/blood-bank/requests')->assertStatus(401);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function projector(): BloodAvailabilityProjector
    {
        return app(BloodAvailabilityProjector::class);
    }

    private function stock(string $group, string $component, int $units): BloodInventory
    {
        return BloodInventory::create([
            'facility_id'       => $this->tenant->id,
            'blood_group'       => $group,
            'component'         => $component,
            'available_units'   => $units,
            'is_expired'        => false,
            'is_quarantined'    => false,
            'is_unsafe'         => false,
            'last_stock_update' => now(),
        ]);
    }

    private function availability(string $group, string $component): BloodAvailability
    {
        return BloodAvailability::query()
            ->where('facility_id', $this->listing->id)
            ->where('blood_group', $group)
            ->where('component_type', $component)
            ->firstOrFail();
    }

    private function pendingRequest(CareFacility $listing, string $group = 'O+'): BloodRequest
    {
        $availability = BloodAvailability::create([
            'facility_id'           => $listing->id,
            'blood_group'           => $group,
            'component_type'        => 'whole_blood',
            'units_available_range' => '6-20',
            'availability_status'   => 'available',
            'freshness_status'      => 'fresh',
            // BloodRequestService::request() reads through
            // reportedByRealSource(), so an unattributed row cannot be ordered
            // against at all — this fixture has to be a publishable one or the
            // tests using it exercise the ERR_NOT_AVAILABLE path instead.
            'source_system'         => 'portal',
            'last_updated_at'       => now(),
        ]);

        return BloodRequest::create([
            'reference'             => 'OC-BL-' . strtoupper(substr(md5((string) mt_rand()), 0, 8)),
            'patient_id'            => $this->patient->id,
            'care_facility_id'      => $listing->id,
            'blood_availability_id' => $availability->id,
            'blood_group'           => $group,
            'component_type'        => 'whole_blood',
            'quantity'              => 2,
            'urgency'               => 'urgent',
            'status'                => BloodRequestStatus::Pending->value,
            'contact_phone'         => '+237699000222',
            'expires_at'            => now()->addHours(BloodRequestService::HOLD_HOURS),
        ]);
    }
}
