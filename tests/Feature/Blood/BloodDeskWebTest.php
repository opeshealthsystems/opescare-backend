<?php

namespace Tests\Feature\Blood;

use App\Enums\BloodRequestStatus;
use App\Models\AccessLog;
use App\Models\BloodAvailability;
use App\Models\BloodInventory;
use App\Models\BloodRequest;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The blood-bank staff desk in the browser
 * (App\Http\Controllers\MedicalId\BloodRequestDeskController).
 *
 * The browser twin of the provider-app desk, and the same boundaries apply: the
 * capability gate, facility scoping that answers 404 rather than 403, the
 * enum's forward-only moves — and answering a request still moves NO stock.
 *
 * Two things are specific to this surface: the action buttons on the page are
 * drawn from BloodRequestStatus::facilityTransitions(), so a closed request
 * offers none; and a patient-linked disclosure that cannot be access-logged is
 * refused rather than served.
 */
class BloodDeskWebTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private CareFacility $listing;
    private Patient $patient;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::factory()->create();
        $this->listing  = $this->listingFor($this->facility, 'Yaounde Central Blood Bank');
        $this->patient  = $this->makePatient('OC-TST-BW-0001-01');
        $this->staff    = $this->staffUser('doctor', $this->facility);
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

    private function staffUser(string $role, Facility $facility): User
    {
        DB::table('dashboard_profiles')->updateOrInsert(['key' => $role], [
            'name' => ucfirst($role), 'portal_prefix' => 'staff', 'landing_route' => 'staff.dashboard',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $model = Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)]);
        $model->forceFill(['dashboard_profile_key' => $role])->save();

        $user = User::factory()->create([
            'is_demo'             => (bool) config('demo.enabled'),
            'primary_facility_id' => $facility->id,
        ]);
        $user->role_id = $model->id;
        $user->save();

        return $user;
    }

    private function makeRequest(CareFacility $listing, Patient $patient, string $status = 'pending'): BloodRequest
    {
        return BloodRequest::create([
            'care_facility_id' => $listing->id,
            'reference'        => 'OC-BL-' . Str::upper(Str::random(8)),
            'patient_id'       => $patient->id,
            'blood_group'      => 'O+',
            'component_type'   => 'whole_blood',
            'quantity'         => 2,
            'urgency'          => 'routine',
            'status'           => $status,
            'contact_phone'    => '+237600000000',
            'expires_at'       => now()->addHours(24),
        ]);
    }

    /** The staff session, with the facility context the portal middleware sets. */
    private function asStaff(?User $user = null, ?Facility $facility = null): self
    {
        $user     ??= $this->staff;
        $facility ??= $this->facility;

        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    /**
     * Make the access-log write fail.
     *
     * The failure is raised on the model, not by removing the table: on
     * Postgres a failed INSERT poisons the surrounding transaction, so the
     * refusal path would never be reached. This is the same condition the
     * controller catches — AccessLogService::log() throwing.
     */
    private function breakTheAccessLog(): void
    {
        AccessLog::saving(function (): void {
            throw new \RuntimeException('access log unavailable');
        });
    }

    // -- The role gate -------------------------------------------------------

    public function test_a_role_without_blood_inventory_is_refused_403_never_404(): void
    {
        $pharmacist = $this->staffUser('pharmacist', $this->facility);
        $request    = $this->makeRequest($this->listing, $this->patient);

        $this->asStaff($pharmacist)->get('/facility/blood-requests')->assertForbidden();
        $this->asStaff($pharmacist)->get('/facility/blood-requests/' . $request->id)->assertForbidden();
        $this->asStaff($pharmacist)->post('/facility/blood-requests/' . $request->id . '/decide', ['decision' => 'confirmed'])->assertForbidden();
        $this->asStaff($pharmacist)->post('/facility/blood-requests/' . $request->id . '/ready')->assertForbidden();
        $this->asStaff($pharmacist)->post('/facility/blood-requests/' . $request->id . '/fulfil')->assertForbidden();

        $this->assertSame('pending', $request->refresh()->status->value);
    }

    public function test_the_desk_opens_for_a_role_that_holds_blood_inventory(): void
    {
        $request = $this->makeRequest($this->listing, $this->patient);

        $this->asStaff()->get('/facility/blood-requests')
            ->assertOk()
            ->assertSee($request->reference);

        $this->asStaff()->get('/facility/blood-requests/' . $request->id)
            ->assertOk()
            ->assertSee($request->reference);
    }

    public function test_a_guest_is_sent_to_login_rather_than_shown_the_queue(): void
    {
        $this->get('/facility/blood-requests')->assertRedirect();
    }

    // -- Facility scoping ----------------------------------------------------

    public function test_another_blood_banks_request_is_404_not_403(): void
    {
        $otherFacility = Facility::factory()->create();
        $otherListing  = $this->listingFor($otherFacility, 'Douala Blood Bank');
        $elsewhere     = $this->makeRequest($otherListing, $this->makePatient('OC-TST-BW-0002-01'));

        // A 403 would confirm the request exists, i.e. that a patient asked for
        // blood elsewhere.
        $this->asStaff()->get('/facility/blood-requests/' . $elsewhere->id)->assertNotFound();
        $this->asStaff()->post('/facility/blood-requests/' . $elsewhere->id . '/decide', ['decision' => 'confirmed'])->assertNotFound();
        $this->asStaff()->post('/facility/blood-requests/' . $elsewhere->id . '/ready')->assertNotFound();
        $this->asStaff()->post('/facility/blood-requests/' . $elsewhere->id . '/fulfil')->assertNotFound();

        $this->assertSame('pending', $elsewhere->refresh()->status->value);
    }

    public function test_the_queue_holds_only_this_facilitys_requests(): void
    {
        $mine = $this->makeRequest($this->listing, $this->patient);

        $otherFacility = Facility::factory()->create();
        $otherListing  = $this->listingFor($otherFacility, 'Douala Blood Bank');
        $stranger      = $this->makePatient('OC-TST-BW-0003-01');
        $elsewhere     = $this->makeRequest($otherListing, $stranger);

        $this->asStaff()->get('/facility/blood-requests')
            ->assertOk()
            ->assertSee($mine->reference)
            ->assertDontSee($elsewhere->reference)
            ->assertDontSee($stranger->health_id);
    }

    public function test_facility_id_in_the_query_string_cannot_widen_the_desk(): void
    {
        $otherFacility = Facility::factory()->create();
        $otherListing  = $this->listingFor($otherFacility, 'Douala Blood Bank');
        $elsewhere     = $this->makeRequest($otherListing, $this->makePatient('OC-TST-BW-0004-01'));
        $mine          = $this->makeRequest($this->listing, $this->patient);

        $this->asStaff()->get('/facility/blood-requests?facility_id=' . $otherFacility->id)
            ->assertOk()
            ->assertSee($mine->reference)
            ->assertDontSee($elsewhere->reference);

        $this->asStaff()->get('/facility/blood-requests/' . $elsewhere->id . '?facility_id=' . $otherFacility->id)
            ->assertNotFound();

        $this->asStaff()->post('/facility/blood-requests/' . $elsewhere->id . '/decide', [
            'decision'    => 'confirmed',
            'facility_id' => $otherFacility->id,
        ])->assertNotFound();

        $this->assertSame('pending', $elsewhere->refresh()->status->value);
    }

    public function test_a_desk_with_no_public_listing_sees_an_empty_queue(): void
    {
        $bare  = Facility::factory()->create();
        $nurse = $this->staffUser('nurse', $bare);

        $elsewhere = $this->makeRequest($this->listing, $this->patient);

        $this->asStaff($nurse, $bare)->get('/facility/blood-requests')
            ->assertOk()
            ->assertDontSee($elsewhere->reference)
            ->assertDontSee($this->patient->health_id);
    }

    // -- Action buttons come from the enum ------------------------------------

    public function test_the_actions_offered_are_exactly_the_enums_legal_moves(): void
    {
        foreach (BloodRequestStatus::cases() as $case) {
            $request = $this->makeRequest($this->listing, $this->patient, $case->value);
            $page    = $this->asStaff()->get('/facility/blood-requests/' . $request->id)->assertOk();

            $moves     = array_map(static fn (BloodRequestStatus $s) => $s->value, $case->facilityTransitions());
            $submitted = [
                'confirmed' => 'value="confirmed"',
                'rejected'  => 'value="rejected"',
                'ready'     => route('portals.staff.blood_requests.ready', $request->id),
                'fulfilled' => route('portals.staff.blood_requests.fulfil', $request->id),
            ];

            foreach ($submitted as $move => $marker) {
                if (in_array($move, $moves, true)) {
                    $page->assertSee($marker, false);
                } else {
                    $page->assertDontSee($marker, false);
                }
            }
        }
    }

    public function test_a_closed_request_offers_no_action_at_all(): void
    {
        foreach (['fulfilled', 'rejected', 'cancelled', 'expired'] as $status) {
            $request = $this->makeRequest($this->listing, $this->patient, $status);

            $this->asStaff()->get('/facility/blood-requests/' . $request->id)
                ->assertOk()
                ->assertSee(__('blood_desk.no_moves'))
                ->assertDontSee(route('portals.staff.blood_requests.decide', $request->id), false)
                ->assertDontSee(route('portals.staff.blood_requests.ready', $request->id), false)
                ->assertDontSee(route('portals.staff.blood_requests.fulfil', $request->id), false);
        }
    }

    // -- The lifecycle -------------------------------------------------------

    public function test_the_forward_lifecycle_runs_pending_to_fulfilled(): void
    {
        $request = $this->makeRequest($this->listing, $this->patient);
        $show    = route('portals.staff.blood_requests.show', $request->id);

        $this->asStaff()->post('/facility/blood-requests/' . $request->id . '/decide', [
            'decision' => 'confirmed',
            'note'     => 'Two units set aside, come today.',
        ])->assertRedirect($show)->assertSessionHas('success');

        $this->assertSame('confirmed', $request->refresh()->status->value);
        $this->assertSame('Two units set aside, come today.', $request->facility_note);
        $this->assertNotNull($request->confirmed_at);

        $this->asStaff()->post('/facility/blood-requests/' . $request->id . '/ready')
            ->assertRedirect($show)->assertSessionHas('success');
        $this->assertSame('ready', $request->refresh()->status->value);

        $this->asStaff()->post('/facility/blood-requests/' . $request->id . '/fulfil')
            ->assertRedirect($show)->assertSessionHas('success');

        $fresh = $request->refresh();
        $this->assertSame('fulfilled', $fresh->status->value);
        $this->assertNotNull($fresh->fulfilled_at);
        $this->assertNotNull($fresh->decided_by);
        $this->assertNotNull($fresh->decided_at);
    }

    public function test_every_illegal_move_is_refused_and_changes_nothing(): void
    {
        $verbs = [
            'confirmed' => fn (string $id) => $this->asStaff()->post("/facility/blood-requests/{$id}/decide", ['decision' => 'confirmed']),
            'rejected'  => fn (string $id) => $this->asStaff()->post("/facility/blood-requests/{$id}/decide", ['decision' => 'rejected']),
            'ready'     => fn (string $id) => $this->asStaff()->post("/facility/blood-requests/{$id}/ready"),
            'fulfilled' => fn (string $id) => $this->asStaff()->post("/facility/blood-requests/{$id}/fulfil"),
        ];

        $checked = 0;

        foreach (BloodRequestStatus::cases() as $from) {
            foreach ($verbs as $targetValue => $call) {
                if ($from->canTransitionTo(BloodRequestStatus::from($targetValue))) {
                    continue;
                }

                $request = $this->makeRequest($this->listing, $this->patient, $from->value);

                $call($request->id)
                    ->assertRedirect(route('portals.staff.blood_requests.show', $request->id))
                    ->assertSessionHas('error', __('blood_desk.transition_not_allowed'));

                $fresh = $request->refresh();
                $this->assertSame($from->value, $fresh->status->value);
                $this->assertNull($fresh->decided_at);

                $checked++;
            }
        }

        $this->assertGreaterThan(0, $checked);
    }

    public function test_the_decide_verb_refuses_to_set_a_counter_status(): void
    {
        $request = $this->makeRequest($this->listing, $this->patient);

        foreach (['ready', 'fulfilled', 'cancelled', 'expired', 'pending', 'nonsense'] as $decision) {
            $this->asStaff()
                ->post('/facility/blood-requests/' . $request->id . '/decide', ['decision' => $decision])
                ->assertSessionHasErrors('decision');
        }

        $this->assertSame('pending', $request->refresh()->status->value);
    }

    public function test_nothing_on_this_surface_deletes_a_request(): void
    {
        $request = $this->makeRequest($this->listing, $this->patient);

        $this->asStaff()->post('/facility/blood-requests/' . $request->id . '/decide', ['decision' => 'rejected'])
            ->assertRedirect();

        $this->assertDatabaseHas('blood_requests', ['id' => $request->id, 'status' => 'rejected']);
        $this->assertSame(1, BloodRequest::query()->count());

        // No DELETE route exists on this surface.
        $this->asStaff()->delete('/facility/blood-requests/' . $request->id)->assertStatus(405);
        $this->assertDatabaseHas('blood_requests', ['id' => $request->id]);
    }

    // -- A disclosure that cannot be recorded does not happen -----------------

    public function test_a_read_that_cannot_be_access_logged_is_refused(): void
    {
        $request = $this->makeRequest($this->listing, $this->patient);

        $this->breakTheAccessLog();

        $this->asStaff()->get('/facility/blood-requests/' . $request->id)->assertStatus(503);

        // The queue does not fall back to showing the rows unlogged either.
        $this->asStaff()->get('/facility/blood-requests')
            ->assertOk()
            ->assertDontSee($request->reference)
            ->assertDontSee($this->patient->health_id);
    }

    public function test_a_write_that_cannot_be_access_logged_is_refused_and_changes_nothing(): void
    {
        $request = $this->makeRequest($this->listing, $this->patient);

        $this->breakTheAccessLog();

        $this->asStaff()->post('/facility/blood-requests/' . $request->id . '/decide', ['decision' => 'confirmed'])
            ->assertRedirect(route('portals.staff.blood_requests.show', $request->id))
            ->assertSessionHas('error', __('blood_desk.audit_unavailable'));

        $fresh = $request->refresh();
        $this->assertSame('pending', $fresh->status->value);
        $this->assertNull($fresh->decided_at);
        $this->assertNull($fresh->confirmed_at);
    }

    // -- THE invariant: answering moves no stock -----------------------------

    public function test_confirming_ready_and_fulfilling_never_touch_stock(): void
    {
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

        $this->asStaff()->post('/facility/blood-requests/' . $request->id . '/decide', ['decision' => 'confirmed'])->assertRedirect();
        $this->asStaff()->post('/facility/blood-requests/' . $request->id . '/ready')->assertRedirect();
        $this->asStaff()->post('/facility/blood-requests/' . $request->id . '/fulfil')->assertRedirect();

        $this->assertSame('fulfilled', $request->refresh()->status->value);

        $this->assertSame($availabilityBefore, DB::table('blood_availability')->orderBy('id')->get()->toJson());
        $this->assertSame($inventoryBefore, DB::table('blood_inventories')->orderBy('id')->get()->toJson());

        $this->assertSame(8, (int) $inventory->refresh()->available_units);
        $this->assertSame('6-10', $availability->refresh()->units_available_range);
    }
}
