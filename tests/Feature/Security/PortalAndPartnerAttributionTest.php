<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\RequireFacilityContext;
use App\Models\ConsentGrant;
use App\Models\Facility;
use App\Models\IntegrationClient;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Audit 2026-10-19 (web portal + partner API):
 *  - /admin/certifications was reachable by any facility-tier admin;
 *  - v1/prescriptions had no scope gate and let a partner name any prescriber;
 *  - radiology finalize/distribute/store(reported_by) attributed acts to any user;
 *  - a removed or suspended staff member kept their facility through the session.
 */
class PortalAndPartnerAttributionTest extends TestCase
{
    use RefreshDatabase;

    private function role(string $name): Role
    {
        return Role::firstOrCreate(['name' => $name], ['description' => ucfirst($name)]);
    }

    private function bypassHeaders(): array
    {
        return ['X-Client-ID' => 'test_client_id', 'X-Client-Secret' => 'test_client_secret', 'Accept' => 'application/json'];
    }

    public function test_facility_admin_cannot_reach_certifications(): void
    {
        $facility = Facility::factory()->create();
        $admin = User::factory()->create([
            'role_id' => $this->role('facility_admin')->id,
            'primary_facility_id' => $facility->id,
        ]);

        $res = $this->actingAs($admin)->get('/admin/certifications');
        $this->assertNotSame(200, $res->status());

        $this->actingAs($admin)->post('/admin/certifications/seed-requirements')
            ->assertStatus($res->status() === 302 ? 302 : 403);
    }

    public function test_prescriptions_write_needs_prescription_push_scope(): void
    {
        $facility = Facility::factory()->create();
        $secret = 'secret-' . bin2hex(random_bytes(8));
        $client = IntegrationClient::factory()->create([
            'facility_id' => $facility->id, 'scopes' => ['inventory:read'], 'status' => 'active',
            'client_secret' => hash('sha256', $secret), 'client_secret_argon' => null,
        ]);
        $h = ['X-Client-ID' => $client->client_id, 'X-Client-Secret' => $secret, 'Accept' => 'application/json'];

        $this->postJson('/v1/prescriptions', [], $h)->assertForbidden()
            ->assertJson(['required_scope' => 'prescription:push']);
        $this->getJson('/v1/prescriptions', $h)->assertForbidden()
            ->assertJson(['required_scope' => 'patient:read']);
    }

    public function test_prescriber_must_be_posted_at_the_client_facility(): void
    {
        $mine  = Facility::factory()->create();
        $other = Facility::factory()->create();
        $patient = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
        $outsider = User::factory()->create(['role_id' => $this->role('doctor')->id, 'primary_facility_id' => $other->id]);

        $this->postJson('/v1/prescriptions', [
            'facility_id'   => $mine->id,
            'patient_id'    => $patient->id,
            'prescriber_id' => $outsider->id,
            'items'         => [['drug_name' => 'Amoxicillin', 'dosage' => '500mg', 'frequency' => 'tds']],
        ], $this->bypassHeaders())->assertStatus(422)->assertJsonValidationErrors('prescriber_id');
    }

    public function test_radiology_reported_by_must_be_posted_at_the_client_facility(): void
    {
        $mine  = Facility::factory()->create();
        $other = Facility::factory()->create();
        $patient = Patient::factory()->create(['is_demo' => false]);
        $doctor = User::factory()->create(['role_id' => $this->role('doctor')->id, 'primary_facility_id' => $mine->id]);
        $outsider = User::factory()->create(['role_id' => $this->role('doctor')->id, 'primary_facility_id' => $other->id]);
        ConsentGrant::create([
            'patient_id' => $patient->id, 'facility_id' => $mine->id, 'authorizing_actor' => 'patient',
            'scope' => ['clinical:write'], 'status' => 'active', 'expires_at' => now()->addYear(), 'is_demo' => false,
        ]);

        $this->postJson('/api/v1/radiology/reports', [
            'facility_id' => $mine->id, 'patient_id' => $patient->id,
            'ordered_by' => $doctor->id, 'reported_by' => $outsider->id,
            'modality' => 'xray', 'body_part' => 'Chest', 'study_date' => now()->toDateString(),
            'clinical_indication' => 'Cough', 'findings' => 'Clear.', 'impression' => 'Normal.',
        ], $this->bypassHeaders())->assertStatus(422)->assertJsonValidationErrors('reported_by');
    }

    public function test_session_facility_is_dropped_when_the_posting_is_gone(): void
    {
        $home  = Facility::factory()->create();
        $other = Facility::factory()->create();
        $user = User::factory()->create(['role_id' => $this->role('doctor')->id, 'primary_facility_id' => $home->id]);
        Auth::login($user);

        $request = Request::create('/facility/queue');
        $request->setLaravelSession($this->app['session.store']);
        session(['active_facility_id' => $other->id]);

        app(RequireFacilityContext::class)->handle($request, fn () => response('ok'));

        $this->assertSame($home->id, session('active_facility_id'));
    }

    public function test_suspended_account_session_is_ended(): void
    {
        $home = Facility::factory()->create();
        $user = User::factory()->create([
            'role_id' => $this->role('doctor')->id, 'primary_facility_id' => $home->id, 'status' => 'suspended',
        ]);
        Auth::login($user);

        $request = Request::create('/facility/queue');
        $request->setLaravelSession($this->app['session.store']);
        session(['active_facility_id' => $home->id]);

        $res = app(RequireFacilityContext::class)->handle($request, fn () => response('ok'));

        $this->assertTrue($res->isRedirect());
        $this->assertNull(Auth::user());
    }
}
