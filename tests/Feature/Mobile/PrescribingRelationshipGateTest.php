<?php

namespace Tests\Feature\Mobile;

use App\Models\ConsentGrant;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Role;
use App\Models\User;
use App\Services\Portal\ProviderSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Prescribing needs a care relationship — because prescribing CREATES one.
 *
 * PrescriptionService stamps `prescribed_by`, and ClinicalRelationshipGate
 * reads that as the `treating_clinician` basis. So an ungated prescribe route
 * is a way to mint your own authority over a stranger's chart: write one line
 * for any Health ID, then open the whole record on the strength of having
 * written it. These tests hold that door shut on both surfaces, and hold the
 * refusal to its promises — a status the app already knows how to act on, a
 * way forward, and not one word about whether the record exists.
 */
class PrescribingRelationshipGateTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private Patient $known;
    private Patient $stranger;
    private array $users = [];
    private array $bearer = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::factory()->create(['name' => 'Limbe District Hospital']);

        foreach (['doctor'] as $role) {
            $this->users[$role]  = $this->professional($role);
            $this->bearer[$role] = app(ProviderSessionService::class)->mint($this->users[$role], $this->facility->id, 'test');
        }

        // A patient who granted this facility access — the ordinary standing.
        $this->known = Patient::factory()->create([
            'health_id' => 'CM-HID-TEST-PRESC-0001',
            'is_demo'   => (bool) config('demo.enabled'),
        ]);
        ConsentGrant::create([
            'patient_id'        => $this->known->id,
            'facility_id'       => $this->facility->id,
            'authorizing_actor' => 'patient',
            'scope'             => ['patient_identity', 'prescriptions'],
            'status'            => 'active',
            'expires_at'        => now()->addDay(),
        ]);

        // Someone this clinician and this facility have nothing to do with.
        $this->stranger = Patient::factory()->create([
            'health_id' => 'CM-HID-TEST-PRESC-0002',
            'is_demo'   => (bool) config('demo.enabled'),
        ]);
    }

    public function test_a_clinician_prescribes_for_a_patient_who_granted_them_access(): void
    {
        $this->prescribe($this->known->id)
            ->assertCreated()
            ->assertJsonPath('data.patient.id', $this->known->id);

        $this->assertDatabaseHas('prescriptions', [
            'patient_id'    => $this->known->id,
            'facility_id'   => $this->facility->id,
            'prescribed_by' => $this->users['doctor']->id,
        ]);
    }

    public function test_prescribing_for_a_stranger_is_refused_and_writes_nothing(): void
    {
        $this->prescribe($this->stranger->id)
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'NO_CLINICAL_RELATIONSHIP')
            ->assertJsonPath('required_action', 'request_consent');

        $this->assertSame(0, Prescription::where('patient_id', $this->stranger->id)->count());
    }

    /**
     * The bypass this gate exists to close: a refused prescription must not
     * leave behind the very relationship it was refused for.
     */
    public function test_a_refused_prescription_mints_no_relationship(): void
    {
        $this->prescribe($this->stranger->id)->assertStatus(403);

        // Still a stranger — the chart stays shut too.
        $this->withHeaders(['Authorization' => "Bearer {$this->bearer['doctor']}"])
            ->getJson("/api/mobile/doctor/patients/{$this->stranger->id}")
            ->assertStatus(403);
    }

    public function test_the_refusal_does_not_reveal_whether_the_patient_exists(): void
    {
        $refused = $this->prescribe($this->stranger->id)->assertStatus(403);

        $body = $refused->json();
        $this->assertSame(__('api.prescription_no_relationship'), $body['message']);
        $this->assertArrayNotHasKey('patient', $body);
        $this->assertStringNotContainsString($this->stranger->health_id, json_encode($body));
        $this->assertStringNotContainsString((string) $this->stranger->first_name, json_encode($body));
    }

    public function test_the_denial_is_written_to_the_patients_access_log(): void
    {
        $this->prescribe($this->stranger->id)->assertStatus(403);

        $this->assertDatabaseHas('access_logs', [
            'patient_id'  => $this->stranger->id,
            'actor_id'    => $this->users['doctor']->id,
            'access_type' => 'denied',
        ]);
    }

    private function prescribe(string $patientId): TestResponse
    {
        return $this->withHeaders(['Authorization' => "Bearer {$this->bearer['doctor']}"])
            ->postJson('/api/mobile/doctor/prescriptions', [
                'patient_id' => $patientId,
                'items'      => [[
                    'drug_name' => 'Amoxicillin 500mg',
                    'dose'      => '1 capsule',
                    'frequency' => '3 times daily',
                    'duration_days' => 5,
                    'quantity'  => 15,
                ]],
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
}
