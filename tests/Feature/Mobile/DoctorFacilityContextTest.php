<?php

namespace Tests\Feature\Mobile;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\Portal\ProviderSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The clinician session's facility — where it is read from, what happens
 * when the token names none, and that a real facility keeps its name while
 * the deployment is showing the demo partition.
 *
 *   GET  /api/mobile/doctor/me                       facility_id + facility_name
 *   GET  /api/mobile/doctor/home                     needs a facility
 *   POST /api/mobile/doctor/patients/{id}/follow-up  needs a facility
 */
class DoctorFacilityContextTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::factory()->create(['name' => 'Hôpital Régional de Bertoua', 'is_demo' => false]);

        DB::table('dashboard_profiles')->updateOrInsert(['key' => 'doctor'], [
            'name' => 'Doctor', 'portal_prefix' => 'staff', 'landing_route' => 'staff.dashboard',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $role = Role::firstOrCreate(['name' => 'doctor'], ['description' => 'Doctor']);
        $role->forceFill(['dashboard_profile_key' => 'doctor'])->save(); // not mass-assignable

        $this->patient = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
    }

    /** A doctor in the given partition, with a provider session at $this->facility. */
    private function doctor(bool $isDemo): array
    {
        $user = User::factory()->create([
            'name'    => 'Dr Aïcha Bello',
            'is_demo' => $isDemo,
            'role_id' => Role::where('name', 'doctor')->value('id'),
        ]);

        return [$user, app(ProviderSessionService::class)->mint($user, $this->facility->id, 'test')];
    }

    private function hit(string $bearer, string $method, string $path, array $body = []): TestResponse
    {
        return $this->withHeaders(['Authorization' => "Bearer {$bearer}"])->json($method, $path, $body);
    }

    /** The recipe for a session that names no facility: the token row loses it. */
    private function detachFacility(User $user): void
    {
        DB::table('provider_access_tokens')->where('user_id', $user->id)->update(['facility_id' => null]);
    }

    public function test_me_names_the_token_facility(): void
    {
        [$doctor, $bearer] = $this->doctor((bool) config('demo.enabled'));

        $this->hit($bearer, 'GET', '/api/mobile/doctor/me')
            ->assertOk()
            ->assertJsonPath('data.id', $doctor->id)
            ->assertJsonPath('data.facility_id', $this->facility->id)
            ->assertJsonPath('data.facility_name', 'Hôpital Régional de Bertoua');
    }

    public function test_a_real_facility_keeps_its_name_while_demo_mode_is_on(): void
    {
        // A demo clinician (visible in demo mode) signed in to a REAL facility.
        // The facility row is is_demo=false, so the demo-isolation scope would
        // hide it — and the app would show a nameless facility. It must not.
        [$doctor, $bearer] = $this->doctor(true);

        config(['demo.enabled' => true]);

        $this->hit($bearer, 'GET', '/api/mobile/doctor/me')
            ->assertOk()
            ->assertJsonPath('data.id', $doctor->id)
            ->assertJsonPath('data.facility_id', $this->facility->id)
            ->assertJsonPath('data.facility_name', 'Hôpital Régional de Bertoua');
    }

    public function test_a_token_without_a_facility_is_refused_with_the_one_shape(): void
    {
        [$doctor, $bearer] = $this->doctor((bool) config('demo.enabled'));
        $this->detachFacility($doctor);

        // /me still answers — it describes the session, facility or not.
        $this->hit($bearer, 'GET', '/api/mobile/doctor/me')
            ->assertOk()
            ->assertJsonPath('data.facility_id', null)
            ->assertJsonPath('data.facility_name', null);

        $home = $this->hit($bearer, 'GET', '/api/mobile/doctor/home')
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'facility_required')
            ->assertJsonPath('message', __('doctor_schedule.facility_required'));
        $this->assertSame(['error_code', 'message'], array_keys($home->json()));

        $followUp = $this->hit($bearer, 'POST', "/api/mobile/doctor/patients/{$this->patient->id}/follow-up", [
            'scheduled_at' => now()->addDay()->toIso8601String(),
        ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'facility_required');
        $this->assertSame(['error_code', 'message'], array_keys($followUp->json()));

        $this->assertDatabaseCount('appointments', 0);
    }
}
