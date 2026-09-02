<?php
namespace Tests\Feature\Portal;

use App\Models\FamilyLink;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardianAccessMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function makeGuardianWithDependent(string $accessLevel = 'full', string $status = 'active'): array
    {
        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create(['is_demo' => false]);
        $link = FamilyLink::factory()->create([
            'guardian_user_id'    => $guardian->id,
            'dependent_patient_id'=> $dependent->id,
            'access_level'        => $accessLevel,
            'status'              => $status,
        ]);
        return [$guardian, $dependent, $link];
    }

    public function test_middleware_passes_through_when_no_guardian_session(): void
    {
        [$guardian] = $this->makeGuardianWithDependent();

        $response = $this->actingAs($guardian)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->get(route('portals.patient.appointments'));

        $response->assertStatus(200);
    }

    public function test_middleware_binds_dependent_when_guardian_session_active(): void
    {
        [$guardian, $dependent] = $this->makeGuardianWithDependent();

        $response = $this->actingAs($guardian)
            ->withSession([
                'active_facility_id'           => '7e57fac1-0000-4000-8000-000000000001',
                'guardian_viewing_patient_id'  => $dependent->id,
            ])
            ->get(route('portals.patient.appointments'));

        $response->assertStatus(200);
    }

    public function test_middleware_clears_session_and_redirects_for_revoked_link(): void
    {
        [$guardian, $dependent] = $this->makeGuardianWithDependent('full', 'revoked');

        $response = $this->actingAs($guardian)
            ->withSession([
                'active_facility_id'           => '7e57fac1-0000-4000-8000-000000000001',
                'guardian_viewing_patient_id'  => $dependent->id,
            ])
            ->get(route('portals.patient.appointments'));

        $response->assertRedirect(route('portals.patient'));
    }

    public function test_middleware_rejects_expired_age_transition_link(): void
    {
        [$guardian, $dependent, $link] = $this->makeGuardianWithDependent();
        $link->update(['age_transition_expires_at' => now()->subDay()]);

        $response = $this->actingAs($guardian)
            ->withSession([
                'active_facility_id'           => '7e57fac1-0000-4000-8000-000000000001',
                'guardian_viewing_patient_id'  => $dependent->id,
            ])
            ->get(route('portals.patient.appointments'));

        $response->assertRedirect(route('portals.patient'));
    }

    public function test_guardian_cannot_view_unlinked_patient(): void
    {
        // Guardian A has a link to dependentA
        [$guardianA] = $this->makeGuardianWithDependent();
        // Guardian B has a link to dependentB — different dependent
        [$guardianB, $dependentOfB] = $this->makeGuardianWithDependent();

        // Guardian A sets session to dependentB's ID (injection attempt)
        $response = $this->actingAs($guardianA)
            ->withSession([
                'active_facility_id'          => '7e57fac1-0000-4000-8000-000000000001',
                'guardian_viewing_patient_id' => $dependentOfB->id,
            ])
            ->get(route('portals.patient.appointments'));

        // No link exists for guardianA → dependentB, so must redirect
        $response->assertRedirect(route('portals.patient'));
    }

    public function test_guardian_with_stale_session_for_nonexistent_patient_is_redirected(): void
    {
        $guardian = User::factory()->create();
        $fakePatientId = (string) \Illuminate\Support\Str::uuid();

        $response = $this->actingAs($guardian)
            ->withSession([
                'active_facility_id'          => '7e57fac1-0000-4000-8000-000000000001',
                'guardian_viewing_patient_id' => $fakePatientId,
            ])
            ->get(route('portals.patient.appointments'));

        $response->assertRedirect(route('portals.patient'));
    }
}
