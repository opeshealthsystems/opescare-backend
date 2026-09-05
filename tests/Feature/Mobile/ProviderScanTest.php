<?php

namespace Tests\Feature\Mobile;

use App\Models\AccessLog;
use App\Models\Facility;
use App\Models\HealthIdVerificationEvent;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\Identity\QrTokenService;
use App\Services\Portal\ProviderSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A clinician scans a patient's Health ID QR with the app: the code resolves
 * to exactly that patient, the scan is recorded for the patient to see, and
 * a stale or foreign code is refused with a precise reason.
 */
class ProviderScanTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;
    private User $clinician;
    private Facility $facility;
    private string $bearer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility  = Facility::factory()->create();
        // The provider middleware re-checks the role on every request, so the
        // session only works for a user whose role is a professional one.
        DB::table('dashboard_profiles')->updateOrInsert(['key' => 'doctor'], [
            'name' => 'Doctor', 'portal_prefix' => 'staff', 'landing_route' => 'staff.dashboard',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $role = Role::firstOrCreate(['name' => 'doctor'], ['description' => 'Doctor']);
        $role->forceFill(['dashboard_profile_key' => 'doctor'])->save(); // not mass-assignable

        $this->clinician = User::factory()->create([
            'is_demo' => (bool) config('demo.enabled'),
            'role_id' => $role->id,
        ]);
        $this->bearer    = app(ProviderSessionService::class)->mint($this->clinician, $this->facility->id, 'test');

        $this->patient = Patient::factory()->create([
            'health_id'  => 'CM-HID-TEST-SCAN-0001',
            'first_name' => 'Amara',
            'last_name'  => 'Ndi',
            'is_demo'    => (bool) config('demo.enabled'),
        ]);
    }

    private function asClinician(): static
    {
        return $this->withHeaders(['Authorization' => "Bearer {$this->bearer}"]);
    }

    private function presentedCode(?int $minutes = 15): string
    {
        // What Presentation Mode puts in the QR: the public verifier URL.
        return app(QrTokenService::class)->generateToken($this->patient->id, 'temporary_consent_qr', $minutes)['secure_url'];
    }

    public function test_scanning_a_live_code_resolves_the_patient_and_records_the_scan(): void
    {
        $response = $this->asClinician()
            ->postJson('/api/mobile/doctor/patients/scan', ['code' => $this->presentedCode()])
            ->assertOk()
            ->assertJsonPath('data.health_id', 'CM-HID-TEST-SCAN-0001')
            ->assertJsonPath('data.full_name', 'Amara Ndi')
            ->assertJsonPath('consent_granted', false)
            ->assertJsonPath('verification.token_type', 'temporary_consent_qr');

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{10}$/', $response->json('verification.reference'));

        $event = HealthIdVerificationEvent::where('patient_id', $this->patient->id)->first();
        $this->assertNotNull($event);
        $this->assertSame('qr_scan', $event->verification_type);
        $this->assertSame($this->clinician->id, $event->verified_by_user_id);
        $this->assertSame($this->facility->id, $event->verified_by_facility_id);

        $this->assertDatabaseHas('access_logs', [
            'patient_id' => $this->patient->id,
            'actor_id'   => $this->clinician->id,
            'purpose'    => 'identity_verification',
        ]);
    }

    public function test_a_bare_token_is_accepted_too(): void
    {
        $raw = app(QrTokenService::class)->generateToken($this->patient->id, 'temporary_consent_qr', 15)['raw_token'];

        $this->asClinician()
            ->postJson('/api/mobile/doctor/patients/scan', ['code' => $raw])
            ->assertOk()
            ->assertJsonPath('data.id', $this->patient->id);
    }

    public function test_an_expired_code_is_refused_as_expired(): void
    {
        $code = $this->presentedCode(15);
        $this->travel(16)->minutes();

        $this->asClinician()
            ->postJson('/api/mobile/doctor/patients/scan', ['code' => $code])
            ->assertNotFound()
            ->assertJsonPath('error_code', 'qr_expired')
            ->assertJsonMissingPath('data');

        $this->assertSame(0, HealthIdVerificationEvent::count());
    }

    public function test_something_that_is_not_a_health_id_code_is_rejected(): void
    {
        $this->asClinician()
            ->postJson('/api/mobile/doctor/patients/scan', ['code' => 'https://example.com/coupon/1234'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'not_a_health_id_code');

        $this->asClinician()
            ->postJson('/api/mobile/doctor/patients/scan', ['code' => 'qrx_unknown_secret'])
            ->assertNotFound()
            ->assertJsonPath('error_code', 'qr_invalid');
    }

    public function test_a_patient_session_cannot_use_the_clinician_scanner(): void
    {
        $this->postJson('/api/mobile/doctor/patients/scan', ['code' => $this->presentedCode()])
            ->assertUnauthorized();
    }
}
