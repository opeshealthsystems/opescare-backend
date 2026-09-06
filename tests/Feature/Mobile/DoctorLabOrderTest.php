<?php

namespace Tests\Feature\Mobile;

use App\Models\ConsentGrant;
use App\Models\Facility;
use App\Models\LabOrder;
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
 * A clinician orders a laboratory test from the phone.
 *
 * POST /api/mobile/doctor/patients/{patient}/lab-orders
 *
 * The order is placed by the token's clinician at the token's facility, as
 * `ordered`, behind the chart's role gate and the chart's care-relationship
 * rule; the patient is told a test has been ordered for them.
 */
class DoctorLabOrderTest extends TestCase
{
    use RefreshDatabase;

    private const HEALTH_ID = 'CM-HID-TEST-ORDER-0001';

    private Facility $facility;
    private Facility $otherFacility;
    private Patient $patient;
    private Patient $otherFacilityPatient;

    /** @var array<string, User> */
    private array $users = [];

    /** @var array<string, string> */
    private array $bearer = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility      = Facility::factory()->create(['name' => 'Limbe District Hospital']);
        $this->otherFacility = Facility::factory()->create();

        foreach (['doctor', 'receptionist'] as $role) {
            $this->users[$role]  = $this->professional($role);
            $this->bearer[$role] = app(ProviderSessionService::class)->mint($this->users[$role], $this->facility->id, 'test');
        }

        $this->patient = Patient::factory()->create([
            'health_id'  => self::HEALTH_ID,
            'first_name' => 'Grace',
            'last_name'  => 'Njoya',
            'is_demo'    => (bool) config('demo.enabled'),
        ]);

        // Basis 5: the patient has granted THIS facility access.
        $this->grant($this->patient, $this->facility);

        // A patient who granted a DIFFERENT facility access, and nothing else
        // ties them to this clinician or this facility.
        $this->otherFacilityPatient = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
        $this->grant($this->otherFacilityPatient, $this->otherFacility);
    }

    private function grant(Patient $patient, Facility $facility): void
    {
        ConsentGrant::create([
            'patient_id'        => $patient->id,
            'facility_id'       => $facility->id,
            'authorizing_actor' => 'patient',
            'scope'             => ['patient_identity', 'orders'],
            'status'            => 'active',
            'expires_at'        => now()->addDay(),
        ]);
    }

    /** Same recipe as ProviderRoleMatrixTest. */
    private function professional(string $role): User
    {
        DB::table('dashboard_profiles')->updateOrInsert(['key' => $role], [
            'name' => ucfirst($role), 'portal_prefix' => 'staff', 'landing_route' => 'staff.dashboard',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $model = Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)]);
        $model->forceFill(['dashboard_profile_key' => $role])->save();

        return User::factory()->create([
            'is_demo' => (bool) config('demo.enabled'),
            'role_id' => $model->id,
        ]);
    }

    private function order(string $role, string $patient, array $body): TestResponse
    {
        return $this->withHeaders(['Authorization' => "Bearer {$this->bearer[$role]}"])
            ->postJson("/api/mobile/doctor/patients/{$patient}/lab-orders", $body);
    }

    private function goodBody(): array
    {
        return [
            'test_name'           => 'HbA1c',
            'test_code'           => 'HBA1C',
            'urgency'             => 'urgent',
            'clinical_indication' => 'Impaired fasting glucose on two occasions; assess glycaemic control.',
        ];
    }

    public function test_a_clinician_orders_a_test_for_a_patient_their_facility_may_treat(): void
    {
        $response = $this->order('doctor', $this->patient->id, $this->goodBody())->assertCreated();

        $response
            ->assertJsonPath('data.order_type', 'lab')
            ->assertJsonPath('data.name', 'HbA1c')
            ->assertJsonPath('data.test_name', 'HbA1c')
            ->assertJsonPath('data.code', 'HBA1C')
            ->assertJsonPath('data.priority', 'urgent')
            ->assertJsonPath('data.urgency', 'urgent')
            ->assertJsonPath('data.status', 'ordered')
            ->assertJsonPath('data.patient_id', $this->patient->id)
            ->assertJsonPath('data.facility_id', $this->facility->id)
            ->assertJsonPath('data.ordered_by', $this->users['doctor']->id)
            ->assertJsonPath('data.clinical_indication', $this->goodBody()['clinical_indication'])
            ->assertJsonPath('data.result_summary', null);

        $this->assertNotNull($response->json('data.ordered_at'));

        $order = LabOrder::findOrFail($response->json('data.id'));
        $this->assertSame($this->users['doctor']->id, $order->ordered_by);
        $this->assertSame($this->facility->id, $order->facility_id);
        $this->assertSame('ordered', $order->status);
        $this->assertSame('urgent', $order->urgency);
        $this->assertFalse((bool) $order->is_demo);
        $this->assertNotNull($order->ordered_at);
        $this->assertNull($order->resulted_at);

        // The patient is told — the test and the facility, not the indication.
        $bells = DB::table('notifications')->where('notifiable_id', $this->patient->id)->get();
        $this->assertCount(1, $bells);
        $bell = json_decode($bells->first()->data, true);
        $this->assertSame('lab_test_ordered', $bell['type']);
        $this->assertSame('/labs/' . $order->id, $bell['action_url']);
        $this->assertStringContainsString('HbA1c', $bell['title']);
        $this->assertStringContainsString('Limbe District Hospital', $bell['message']);
        $this->assertStringNotContainsString('Impaired fasting glucose', $bell['title'] . $bell['message']);

        // The write is on the trail, with the authority it was placed on.
        $this->assertDatabaseHas('access_logs', [
            'patient_id'  => $this->patient->id,
            'actor_id'    => $this->users['doctor']->id,
            'purpose'     => 'treatment:consent_grant',
            'access_type' => 'write',
        ]);

        // And the chart lists it on the next read.
        $chart = $this->withHeaders(['Authorization' => "Bearer {$this->bearer['doctor']}"])
            ->getJson("/api/mobile/doctor/patients/{$this->patient->id}/orders?type=lab")
            ->assertOk();
        $this->assertStringContainsString($order->id, $chart->getContent());
    }

    public function test_the_health_id_form_of_the_url_works_too(): void
    {
        $this->order('doctor', self::HEALTH_ID, $this->goodBody())
            ->assertCreated()
            ->assertJsonPath('data.patient_id', $this->patient->id);
    }

    public function test_a_blank_test_code_is_stored_as_none(): void
    {
        $body = $this->goodBody();
        $body['test_code'] = '  ';

        $this->order('doctor', $this->patient->id, $body)
            ->assertCreated()
            ->assertJsonPath('data.code', null);
    }

    public function test_a_receptionist_is_refused_by_role_before_anything_is_written(): void
    {
        $this->order('receptionist', $this->patient->id, $this->goodBody())
            ->assertForbidden()
            ->assertJsonPath('error_code', 'role_forbidden');

        $this->assertSame(0, LabOrder::count());
    }

    public function test_a_patient_of_another_facility_is_refused(): void
    {
        $this->order('doctor', $this->otherFacilityPatient->id, $this->goodBody())
            ->assertForbidden()
            ->assertJsonPath('error_code', 'NO_CLINICAL_RELATIONSHIP')
            ->assertJsonPath('required_action', 'request_consent');

        $this->assertSame(0, LabOrder::count());
        $this->assertSame(0, DB::table('notifications')->count());

        $this->assertDatabaseHas('access_logs', [
            'patient_id'  => $this->otherFacilityPatient->id,
            'actor_id'    => $this->users['doctor']->id,
            'access_type' => 'denied',
        ]);
    }

    public function test_an_unknown_patient_is_not_found(): void
    {
        $this->order('doctor', (string) Str::uuid(), $this->goodBody())->assertNotFound();
        $this->order('doctor', 'CM-HID-NOBODY-0000', $this->goodBody())->assertNotFound();

        $this->assertSame(0, LabOrder::count());
    }

    public function test_the_body_is_validated(): void
    {
        $bad = $this->goodBody();
        $bad['urgency'] = 'asap';
        $this->order('doctor', $this->patient->id, $bad)->assertStatus(422);

        $bad = $this->goodBody();
        unset($bad['clinical_indication']);
        $this->order('doctor', $this->patient->id, $bad)->assertStatus(422);

        $bad = $this->goodBody();
        $bad['test_name'] = str_repeat('t', 161);
        $this->order('doctor', $this->patient->id, $bad)->assertStatus(422);

        $this->assertSame(0, LabOrder::count());
    }

    public function test_a_patient_session_cannot_place_an_order(): void
    {
        $this->postJson("/api/mobile/doctor/patients/{$this->patient->id}/lab-orders", $this->goodBody())
            ->assertUnauthorized();
    }
}
