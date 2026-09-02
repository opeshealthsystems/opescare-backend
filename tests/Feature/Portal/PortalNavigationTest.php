<?php
namespace Tests\Feature\Portal;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_links_to_all_portal_sections(): void
    {
        $patient = Patient::factory()->create(['is_demo' => false]);
        $user = User::factory()->create(['patient_id' => $patient->id]);

        $response = $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->get(route('portals.patient'));

        $response->assertStatus(200);
        $response->assertSee(route('portals.patient.labs'));
        $response->assertSee(route('portals.patient.prescriptions'));
        $response->assertSee(route('portals.patient.consent'));
        $response->assertSee(route('portals.patient.documents'));
        $response->assertSee(route('portals.patient.profile'));
        $response->assertSee(route('portals.patient.appointments'));
        $response->assertSee(route('portals.patient.logs'));
        $response->assertSee(route('public.care-map'));
    }

    public function test_dashboard_does_not_show_demo_banner_for_real_user(): void
    {
        $patient = Patient::factory()->create(['is_demo' => false]);
        $user = User::factory()->create(['patient_id' => $patient->id, 'is_demo' => false]);

        $response = $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->get(route('portals.patient'));

        $response->assertDontSee('Demo Mode');
        $response->assertDontSee('sample data');
    }

    public function test_dashboard_does_not_show_privacy_checkboxes_inline(): void
    {
        $patient = Patient::factory()->create(['is_demo' => false]);
        $user = User::factory()->create(['patient_id' => $patient->id]);

        $response = $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->get(route('portals.patient'));

        // Privacy settings moved to profile page — should not be inline checkboxes
        $response->assertDontSee('privacy_require_consent');
        $response->assertDontSee('privacy_emergency_access');
    }
}
