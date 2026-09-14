<?php

namespace Tests\Feature\BloodBank;

use App\Models\AccessLog;
use App\Models\BloodAvailability;
use App\Models\BloodRequest;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\Patient;
use App\Modules\Notifications\Models\NotificationEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Raising a blood request from the staff web desk
 * (BloodRequestDeskController::create / store).
 *
 * Until this surface existed the only writer of `blood_requests` was the
 * patient app, so every clinical dashboard's desk was empty. The web path must
 * be the SAME path: BloodRequestService::request(), with the facility taken
 * from the session context, the patient named by exact Health ID, and the
 * `blood_order_received` notification raised as it is for the app.
 */
class BloodRequestDeskCreateTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private CareFacility $listing;
    private Patient $patient;
    private User $nurse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::factory()->create();
        $this->listing  = $this->listingFor($this->facility, 'Yaounde Central Blood Bank');
        $this->patient  = $this->makePatient('OC-TST-BC-0001-01');
        $this->nurse    = $this->staffUser('nurse', $this->facility);

        $this->reportAvailable($this->listing, 'O+', 'whole_blood');
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

    private function reportAvailable(CareFacility $listing, string $group, string $component): BloodAvailability
    {
        return BloodAvailability::create([
            'facility_id'           => $listing->id,
            'blood_group'           => $group,
            'component_type'        => $component,
            'units_available_range' => '6-20',
            'availability_status'   => 'available',
            'freshness_status'      => 'fresh',
            'source_system'         => 'portal',
            'last_updated_at'       => now(),
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

    private function asStaff(?User $user = null, ?Facility $facility = null): self
    {
        $user     ??= $this->nurse;
        $facility ??= $this->facility;

        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    /** @return array<string, mixed> */
    private function validBody(array $overrides = []): array
    {
        return array_merge([
            'health_id'      => $this->patient->health_id,
            'blood_group'    => 'O+',
            'component_type' => 'whole_blood',
            'quantity'       => 2,
            'urgency'        => 'urgent',
            'indication'     => 'Post-partum haemorrhage, Hb 6.1 g/dL.',
            'contact_phone'  => '+237600000000',
        ], $overrides);
    }

    // -- Happy path ----------------------------------------------------------

    public function test_a_nurse_files_a_request_and_it_lands_on_this_facilitys_desk(): void
    {
        $this->assertSame(0, BloodRequest::count());

        $response = $this->asStaff()->post('/facility/blood-requests', $this->validBody());

        $row = BloodRequest::first();
        $this->assertNotNull($row, 'the request was not written');

        $response->assertRedirect('/facility/blood-requests/' . $row->id);
        $response->assertSessionHas('success');

        $this->assertSame($this->patient->id, $row->patient_id);
        $this->assertSame($this->listing->id, $row->care_facility_id);
        $this->assertSame('O+', $row->blood_group->value);
        $this->assertSame('whole_blood', $row->component_type->value);
        $this->assertSame(2, $row->quantity);
        $this->assertSame('urgent', $row->urgency->value);
        $this->assertSame('pending', $row->status->value);
        $this->assertSame('Post-partum haemorrhage, Hb 6.1 g/dL.', $row->patient_note);
        $this->assertStringStartsWith('OC-BL-', $row->reference);

        // The queue on the desk shows it straight away.
        $this->asStaff()->get('/facility/blood-requests')
            ->assertOk()
            ->assertSee($row->reference);

        // Filing was recorded against the patient before the write.
        $this->assertTrue(
            AccessLog::query()
                ->where('patient_id', $this->patient->id)
                ->where('actor_id', $this->nurse->id)
                ->where('access_type', 'write')
                ->exists(),
            'the create was not access-logged',
        );
    }

    public function test_a_facility_id_in_the_body_is_ignored_the_session_facility_wins(): void
    {
        $other        = Facility::factory()->create();
        $otherListing = $this->listingFor($other, 'Douala Blood Bank');
        $this->reportAvailable($otherListing, 'O+', 'whole_blood');

        $this->asStaff()->post('/facility/blood-requests', $this->validBody([
            'facility_id'      => $other->id,
            'care_facility_id' => $otherListing->id,
            'listing_id'       => $otherListing->id, // not this facility's — refused, never honoured
        ]))->assertSessionHasErrors('listing_id');

        $this->assertSame(0, BloodRequest::count());

        // Without the foreign listing_id the same body files against the session facility.
        $this->asStaff()->post('/facility/blood-requests', $this->validBody([
            'facility_id'      => $other->id,
            'care_facility_id' => $otherListing->id,
        ]))->assertSessionHas('success');

        $row = BloodRequest::sole();
        $this->assertSame($this->listing->id, $row->care_facility_id);
        $this->assertNotSame($otherListing->id, $row->care_facility_id);
    }

    /**
     * KNOWN GAP — recorded, not hidden.
     *
     * BloodRequestService::request() calls Notify::toFacilityStaff() with
     * `$request->care_facility_id`, a `care_facilities.id`. Notify resolves
     * recipients through facility_role_assignments.facility_id and
     * users.primary_facility_id, both constrained to `facilities.id`. The two
     * registers use different uuids, so the lookup matches nobody and no
     * notification_events row is written — from the web desk AND from the
     * patient app. The one-line fix is to pass the listing's tenant bridge,
     * `$facility->facility_id`, instead; that service is outside this change.
     *
     * Until then this test states the truth: the request is written, and the
     * event is not.
     */
    public function test_filing_raises_the_blood_order_received_event(): void
    {
        $this->asStaff()->post('/facility/blood-requests', $this->validBody())->assertSessionHas('success');

        $row = BloodRequest::sole();

        $events = NotificationEvent::query()
            ->where('event_type', 'blood_order_received')
            ->where('related_resource_type', 'BloodRequest')
            ->where('related_resource_id', (string) $row->id)
            ->get();

        // This was markTestIncomplete: BloodRequestService passed care_facilities.id
        // to Notify::toFacilityStaff(), which resolves staff on facilities.id, so
        // no event was ever recorded — from the web desk or the mobile app. It now
        // passes the tenant id behind the listing, and this is a real assertion.
        $this->assertCount(1, $events, 'filing a blood request must record blood_order_received for the facility staff');

        $payload = json_decode((string) $events->first()->payload_json, true);
        $this->assertSame(route('portals.staff.blood_requests.index'), $payload['url'] ?? null);
    }

    // -- Refusals ------------------------------------------------------------

    public function test_an_unknown_health_id_creates_nothing(): void
    {
        $this->asStaff()->post('/facility/blood-requests', $this->validBody([
            'health_id' => 'OC-TST-NOBODY-9999',
        ]))->assertSessionHasErrors('health_id');

        $this->assertSame(0, BloodRequest::count());
        $this->assertSame(0, NotificationEvent::query()->where('event_type', 'blood_order_received')->count());
    }

    public function test_a_group_the_bank_does_not_report_creates_nothing(): void
    {
        $this->asStaff()->post('/facility/blood-requests', $this->validBody([
            'blood_group' => 'AB-',
        ]))->assertSessionHasErrors('blood_group');

        $this->assertSame(0, BloodRequest::count());
    }

    public function test_a_role_without_blood_inventory_is_refused_403(): void
    {
        $pharmacist = $this->staffUser('pharmacist', $this->facility);

        $this->asStaff($pharmacist)->get('/facility/blood-requests/new')->assertForbidden();
        $this->asStaff($pharmacist)->post('/facility/blood-requests', $this->validBody())->assertForbidden();

        $this->assertSame(0, BloodRequest::count());
    }

    public function test_the_form_opens_for_a_nurse_and_the_desk_links_to_it(): void
    {
        $this->asStaff()->get('/facility/blood-requests/new')
            ->assertOk()
            ->assertSee('name="health_id"', false)
            ->assertSee(route('portals.staff.blood_requests.store'));

        $this->asStaff()->get('/facility/blood-requests')
            ->assertOk()
            ->assertSee(route('portals.staff.blood_requests.create'));
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get('/facility/blood-requests/new')->assertRedirect();
        $this->post('/facility/blood-requests', $this->validBody())->assertRedirect();
        $this->assertSame(0, BloodRequest::count());
    }
}
