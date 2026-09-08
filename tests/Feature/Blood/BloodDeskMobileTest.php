<?php

namespace Tests\Feature\Blood;

use App\Enums\BloodRequestStatus;
use App\Models\BloodAvailability;
use App\Models\BloodInventory;
use App\Models\BloodRequest;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\Portal\ProviderSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The blood-bank staff desk on the provider app
 * (App\Http\Controllers\Api\Mobile\MobileBloodDeskController).
 *
 * Everything asserted here is a boundary, not a feature: who may open the desk,
 * whose requests it can see, which moves it may make — and, above all, that
 * answering a request moves NO stock. BloodRequestService deliberately never
 * writes to `blood_availability`, and the patient-facing copy was corrected to
 * stop claiming units are held; a desk that quietly decremented would put that
 * lie back.
 */
class BloodDeskMobileTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private CareFacility $listing;
    private Patient $patient;
    private string $bearer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::factory()->create();
        $this->listing  = $this->listingFor($this->facility, 'Yaounde Central Blood Bank');
        $this->patient  = $this->makePatient('OC-TST-BD-0001-01');
        $this->bearer   = $this->providerBearer('doctor', $this->facility);
    }

    // -- Fixtures ------------------------------------------------------------

    private function listingFor(Facility $facility, string $name): CareFacility
    {
        return CareFacility::create([
            'facility_id'    => $facility->id,
            'facility_name'  => $name,
            'facility_type'  => 'blood_bank',
            'listing_status' => 'active',
            'country_code'   => 'CM',
            'region'         => 'Centre',
            'city'           => 'Yaounde',
            'address'        => 'Rue de la Reunification',
            'phone_primary'  => '+237222234000',
        ]);
    }

    private function makePatient(string $healthId): Patient
    {
        return Patient::create([
            'health_id'     => $healthId,
            'first_name'    => 'Amina',
            'last_name'     => 'Njoya',
            'sex'           => 'female',
            'date_of_birth' => '1991-03-04',
            'is_demo'       => (bool) config('demo.enabled'),
        ]);
    }

    /** A professional whose global role carries (or lacks) blood_inventory. */
    private function providerBearer(string $role, Facility $facility): string
    {
        DB::table('dashboard_profiles')->updateOrInsert(['key' => $role], [
            'name' => ucfirst($role), 'portal_prefix' => 'staff', 'landing_route' => 'staff.dashboard',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $model = Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)]);
        $model->forceFill(['dashboard_profile_key' => $role])->save();

        $user = User::factory()->create([
            'is_demo' => (bool) config('demo.enabled'),
            'role_id' => $model->id,
        ]);

        return app(ProviderSessionService::class)->mint($user, $facility->id, 'blood-desk-test');
    }

    private function makeRequest(CareFacility $listing, Patient $patient, string $status = 'pending', string $urgency = 'routine'): BloodRequest
    {
        return BloodRequest::create([
            'care_facility_id' => $listing->id,
            'reference'        => 'OC-BL-' . Str::upper(Str::random(8)),
            'patient_id'       => $patient->id,
            'blood_group'      => 'O+',
            'component_type'   => 'whole_blood',
            'quantity'         => 2,
            'urgency'          => $urgency,
            'status'           => $status,
            'contact_phone'    => '+237600000000',
            'expires_at'       => now()->addHours(24),
        ]);
    }

    private function hit(string $method, string $path, array $body = [], ?string $bearer = null): TestResponse
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . ($bearer ?? $this->bearer)])
            ->json($method, $path, $body);
    }

    // -- The role gate -------------------------------------------------------

    public function test_a_role_without_blood_inventory_is_refused_403_never_404(): void
    {
        // A pharmacist holds `dispense` and `pharmacy_stock`, never
        // `blood_inventory` (config/staff_capabilities.php).
        $pharmacist = $this->providerBearer('pharmacist', $this->facility);
        $request    = $this->makeRequest($this->listing, $this->patient);

        $paths = [
            ['GET',  '/api/mobile/blood-desk/summary'],
            ['GET',  '/api/mobile/blood-desk/requests'],
            ['GET',  '/api/mobile/blood-desk/requests/' . $request->id],
            ['POST', '/api/mobile/blood-desk/requests/' . $request->id . '/decide'],
            ['POST', '/api/mobile/blood-desk/requests/' . $request->id . '/ready'],
            ['POST', '/api/mobile/blood-desk/requests/' . $request->id . '/fulfil'],
        ];

        foreach ($paths as [$method, $path]) {
            $this->hit($method, $path, ['decision' => 'confirmed'], $pharmacist)
                ->assertStatus(403)
                ->assertJsonPath('error_code', 'role_forbidden');
        }

        // A refused role changed nothing.
        $this->assertSame('pending', $request->refresh()->status->value);
    }

    public function test_the_desk_opens_for_a_role_that_holds_blood_inventory(): void
    {
        $this->makeRequest($this->listing, $this->patient);

        $this->hit('GET', '/api/mobile/blood-desk/summary')
            ->assertOk()
            ->assertJsonPath('data.facility_id', $this->facility->id)
            ->assertJsonPath('data.open_total', 1)
            ->assertJsonPath('data.by_status.pending', 1)
            ->assertJsonPath('data.has_listing', true);

        $this->hit('GET', '/api/mobile/blood-desk/requests')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_an_unauthenticated_caller_gets_401(): void
    {
        $this->getJson('/api/mobile/blood-desk/requests')->assertStatus(401);
    }

    // -- Facility scoping ----------------------------------------------------

    public function test_another_blood_banks_request_is_404_not_403(): void
    {
        $otherFacility = Facility::factory()->create();
        $otherListing  = $this->listingFor($otherFacility, 'Douala Blood Bank');
        $elsewhere     = $this->makeRequest($otherListing, $this->makePatient('OC-TST-BD-0002-01'));

        // 403 would confirm the id exists - i.e. that a patient asked for blood
        // somewhere else. It must be 404.
        $this->hit('GET', '/api/mobile/blood-desk/requests/' . $elsewhere->id)
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'REQUEST_NOT_FOUND');

        $this->hit('POST', '/api/mobile/blood-desk/requests/' . $elsewhere->id . '/decide', ['decision' => 'confirmed'])
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'REQUEST_NOT_FOUND');

        $this->hit('POST', '/api/mobile/blood-desk/requests/' . $elsewhere->id . '/ready')->assertStatus(404);
        $this->hit('POST', '/api/mobile/blood-desk/requests/' . $elsewhere->id . '/fulfil')->assertStatus(404);

        $this->assertSame('pending', $elsewhere->refresh()->status->value, 'A 404 must also be a no-op.');
    }

    public function test_the_queue_holds_only_this_facilitys_requests(): void
    {
        $mine = $this->makeRequest($this->listing, $this->patient);

        $otherFacility = Facility::factory()->create();
        $otherListing  = $this->listingFor($otherFacility, 'Douala Blood Bank');
        $stranger      = $this->makePatient('OC-TST-BD-0003-01');
        $elsewhere     = $this->makeRequest($otherListing, $stranger);

        $response = $this->hit('GET', '/api/mobile/blood-desk/requests')->assertOk();

        $ids = array_column($response->json('data'), 'id');
        $this->assertSame([$mine->id], $ids);

        // The other bank's patient is nowhere in the payload - no Health ID,
        // no reference.
        $body = $response->getContent();
        $this->assertStringNotContainsString($stranger->health_id, $body);
        $this->assertStringNotContainsString($elsewhere->reference, $body);

        $this->hit('GET', '/api/mobile/blood-desk/summary')
            ->assertOk()
            ->assertJsonPath('data.by_status.pending', 1);
    }

    public function test_facility_id_in_the_body_or_query_string_cannot_widen_the_desk(): void
    {
        $otherFacility = Facility::factory()->create();
        $otherListing  = $this->listingFor($otherFacility, 'Douala Blood Bank');
        $elsewhere     = $this->makeRequest($otherListing, $this->makePatient('OC-TST-BD-0004-01'));

        // Naming somebody else's facility must not return their queue.
        $this->hit('GET', '/api/mobile/blood-desk/requests?facility_id=' . $otherFacility->id)
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.facility_id', $this->facility->id);

        $this->hit('GET', '/api/mobile/blood-desk/summary?facility_id=' . $otherFacility->id)
            ->assertOk()
            ->assertJsonPath('data.facility_id', $this->facility->id);

        // Nor let a body parameter reach one of their requests.
        $this->hit('POST', '/api/mobile/blood-desk/requests/' . $elsewhere->id . '/decide', [
            'decision'    => 'confirmed',
            'facility_id' => $otherFacility->id,
        ])->assertStatus(404);

        $this->hit('GET', '/api/mobile/blood-desk/requests/' . $elsewhere->id . '?facility_id=' . $otherFacility->id)
            ->assertStatus(404);

        $this->assertSame('pending', $elsewhere->refresh()->status->value);
    }

    public function test_a_desk_with_no_public_listing_sees_an_empty_queue_and_is_told_why(): void
    {
        $bare   = Facility::factory()->create();
        $bearer = $this->providerBearer('nurse', $bare);

        // A request exists - just not against any listing this facility speaks for.
        $this->makeRequest($this->listing, $this->patient);

        $this->hit('GET', '/api/mobile/blood-desk/summary', [], $bearer)
            ->assertOk()
            ->assertJsonPath('data.has_listing', false)
            ->assertJsonPath('data.open_total', 0);

        $this->hit('GET', '/api/mobile/blood-desk/requests', [], $bearer)
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // -- No enumeration ------------------------------------------------------

    public function test_there_is_no_patient_search_on_this_surface(): void
    {
        $mine = $this->makeRequest($this->listing, $this->patient);

        $otherFacility = Facility::factory()->create();
        $otherListing  = $this->listingFor($otherFacility, 'Douala Blood Bank');
        $hidden        = $this->makePatient('OC-TST-BD-0005-01');
        $this->makeRequest($otherListing, $hidden);

        // Every shape a search could take is either ignored or refused; none of
        // them may surface a patient who did not ask THIS facility.
        foreach ([
            'q=Njoya',
            'search=' . $hidden->health_id,
            'health_id=' . $hidden->health_id,
            'patient_id=' . $hidden->id,
        ] as $query) {
            $response = $this->hit('GET', '/api/mobile/blood-desk/requests?' . $query);

            $this->assertContains($response->status(), [200, 422], "?{$query} should be ignored or refused.");

            if ($response->status() === 200) {
                $this->assertSame([$mine->id], array_column($response->json('data'), 'id'));
                $this->assertStringNotContainsString($hidden->health_id, $response->getContent());
            }
        }
    }

    public function test_the_payload_carries_the_health_id_but_no_chart(): void
    {
        $request = $this->makeRequest($this->listing, $this->patient);

        $response = $this->hit('GET', '/api/mobile/blood-desk/requests/' . $request->id)->assertOk();

        // The counter has to match the person in front of it to the request.
        $response->assertJsonPath('data.patient.health_id', $this->patient->health_id);

        // ...and nothing else about them.
        $patient = $response->json('data.patient');
        $this->assertSame(['id', 'health_id', 'name'], array_keys($patient));
    }

    // -- The lifecycle -------------------------------------------------------

    public function test_the_forward_lifecycle_runs_pending_to_fulfilled(): void
    {
        $request = $this->makeRequest($this->listing, $this->patient);

        $this->hit('POST', '/api/mobile/blood-desk/requests/' . $request->id . '/decide', [
            'decision' => 'confirmed',
            'note'     => 'Two units set aside on the shelf, come today.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.facility_note', 'Two units set aside on the shelf, come today.');

        $this->assertNotNull($request->refresh()->confirmed_at);

        $this->hit('POST', '/api/mobile/blood-desk/requests/' . $request->id . '/ready')
            ->assertOk()
            ->assertJsonPath('data.status', 'ready');

        $this->hit('POST', '/api/mobile/blood-desk/requests/' . $request->id . '/fulfil')
            ->assertOk()
            ->assertJsonPath('data.status', 'fulfilled')
            ->assertJsonPath('data.is_open', false)
            ->assertJsonPath('data.next_decisions', []);

        $this->assertNotNull($request->refresh()->fulfilled_at);
    }

    public function test_next_decisions_are_the_enums_own_legal_moves(): void
    {
        foreach (BloodRequestStatus::cases() as $case) {
            $request  = $this->makeRequest($this->listing, $this->patient, $case->value);
            $expected = array_map(
                static fn (BloodRequestStatus $s) => $s->value,
                $case->facilityTransitions(),
            );

            $this->hit('GET', '/api/mobile/blood-desk/requests/' . $request->id)
                ->assertOk()
                ->assertJsonPath('data.next_decisions', $expected);
        }
    }

    public function test_every_illegal_move_is_409_and_reports_what_is_allowed(): void
    {
        $verbs = [
            'confirmed' => fn (string $id) => $this->hit('POST', "/api/mobile/blood-desk/requests/{$id}/decide", ['decision' => 'confirmed']),
            'rejected'  => fn (string $id) => $this->hit('POST', "/api/mobile/blood-desk/requests/{$id}/decide", ['decision' => 'rejected']),
            'ready'     => fn (string $id) => $this->hit('POST', "/api/mobile/blood-desk/requests/{$id}/ready"),
            'fulfilled' => fn (string $id) => $this->hit('POST', "/api/mobile/blood-desk/requests/{$id}/fulfil"),
        ];

        $checked = 0;

        foreach (BloodRequestStatus::cases() as $from) {
            foreach ($verbs as $targetValue => $call) {
                $target = BloodRequestStatus::from($targetValue);

                // The enum is the oracle - the test asserts nothing of its own
                // about which moves are legal.
                if ($from->canTransitionTo($target)) {
                    continue;
                }

                $request  = $this->makeRequest($this->listing, $this->patient, $from->value);
                $response = $call($request->id);

                $response->assertStatus(409)
                    ->assertJsonPath('error_code', 'REQUEST_TRANSITION_NOT_ALLOWED')
                    ->assertJsonPath('from', $from->value)
                    ->assertJsonPath('to', $targetValue)
                    ->assertJsonPath('allowed', array_map(
                        static fn (BloodRequestStatus $s) => $s->value,
                        $from->facilityTransitions(),
                    ));

                // A refused move changed nothing at all.
                $fresh = $request->refresh();
                $this->assertSame($from->value, $fresh->status->value);
                $this->assertNull($fresh->decided_at);

                $checked++;
            }
        }

        $this->assertGreaterThan(0, $checked);
    }

    public function test_a_decision_records_who_made_it_and_when(): void
    {
        $request = $this->makeRequest($this->listing, $this->patient);

        $this->hit('POST', '/api/mobile/blood-desk/requests/' . $request->id . '/decide', ['decision' => 'rejected'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $fresh = $request->refresh();
        $this->assertNotNull($fresh->decided_by);
        $this->assertNotNull($fresh->decided_at);
    }

    public function test_the_decide_verb_refuses_to_set_a_counter_status(): void
    {
        $request = $this->makeRequest($this->listing, $this->patient);

        foreach (['ready', 'fulfilled', 'cancelled', 'expired', 'pending', 'nonsense'] as $decision) {
            $this->hit('POST', '/api/mobile/blood-desk/requests/' . $request->id . '/decide', ['decision' => $decision])
                ->assertStatus(422);
        }

        $this->assertSame('pending', $request->refresh()->status->value);
    }

    public function test_nothing_on_this_surface_deletes_a_request(): void
    {
        $request = $this->makeRequest($this->listing, $this->patient);

        $this->hit('POST', '/api/mobile/blood-desk/requests/' . $request->id . '/decide', ['decision' => 'rejected'])->assertOk();

        $this->assertDatabaseHas('blood_requests', ['id' => $request->id, 'status' => 'rejected']);
        $this->assertSame(1, BloodRequest::query()->count());

        // There is no DELETE verb here at all.
        $this->hit('DELETE', '/api/mobile/blood-desk/requests/' . $request->id)->assertStatus(405);
        $this->assertDatabaseHas('blood_requests', ['id' => $request->id]);
    }

    // -- THE invariant: answering moves no stock -----------------------------

    public function test_confirming_ready_and_fulfilling_never_touch_stock(): void
    {
        // Real reported stock, exactly as a blood bank would publish it. The
        // request below is NOT created through it - the point is only that
        // these rows are byte-identical afterwards.
        $availability = BloodAvailability::create([
            'facility_id'           => $this->listing->id,
            'blood_group'           => 'O+',
            'component_type'        => 'whole_blood',
            'units_available_range' => '6-10',
            'availability_status'   => 'available',
            'freshness_status'      => 'fresh',
            'source_system'         => 'facility_portal',
            'last_updated_at'       => now(),
        ]);

        $inventory = BloodInventory::create([
            'facility_id'       => $this->facility->id,
            'blood_group'       => 'O+',
            'component'         => 'whole_blood',
            'available_units'   => 8,
            'last_stock_update' => now(),
        ]);

        $availabilityBefore = DB::table('blood_availability')->orderBy('id')->get()->toJson();
        $inventoryBefore    = DB::table('blood_inventories')->orderBy('id')->get()->toJson();

        $request = $this->makeRequest($this->listing, $this->patient);

        $this->hit('POST', '/api/mobile/blood-desk/requests/' . $request->id . '/decide', ['decision' => 'confirmed'])->assertOk();
        $this->hit('POST', '/api/mobile/blood-desk/requests/' . $request->id . '/ready')->assertOk();
        $this->hit('POST', '/api/mobile/blood-desk/requests/' . $request->id . '/fulfil')->assertOk();

        $this->assertSame('fulfilled', $request->refresh()->status->value);

        // Confirming is the blood bank saying "yes, come" - not a hold. Every
        // column of both tables, including updated_at, is unchanged.
        $this->assertSame($availabilityBefore, DB::table('blood_availability')->orderBy('id')->get()->toJson());
        $this->assertSame($inventoryBefore, DB::table('blood_inventories')->orderBy('id')->get()->toJson());

        $this->assertSame(8, (int) $inventory->refresh()->available_units);
        $this->assertSame('6-10', $availability->refresh()->units_available_range);
        $this->assertSame('available', $availability->availability_status);
    }
}
