<?php
namespace Tests\Feature\Portal;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_requires_auth(): void
    {
        $this->get(route('portals.patient.profile'))->assertRedirect();
    }

    public function test_profile_page_shows_patient_data(): void
    {
        $patient = Patient::factory()->create(['is_demo' => false, 'phone_number' => '+1234567890']);
        $user = User::factory()->create(['patient_id' => $patient->id]);

        $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->get(route('portals.patient.profile'))
            ->assertStatus(200)
            ->assertSee('+1234567890');
    }

    public function test_patient_can_update_contact_details(): void
    {
        $patient = Patient::factory()->create(['is_demo' => false]);
        $user = User::factory()->create(['patient_id' => $patient->id]);

        $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->post(route('portals.patient.profile.update'), [
                'phone_number' => '+9876543210',
                'email'        => 'newemail@example.com',
                'address'      => '123 Health Street',
            ])
            ->assertRedirect(route('portals.patient.profile'));

        $this->assertEquals('+9876543210', $patient->fresh()->phone_number);
        $this->assertEquals('newemail@example.com', $patient->fresh()->email);
    }

    public function test_profile_update_rejects_invalid_email(): void
    {
        $patient = Patient::factory()->create(['is_demo' => false]);
        $user = User::factory()->create(['patient_id' => $patient->id]);

        $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->post(route('portals.patient.profile.update'), [
                'email' => 'not-an-email',
            ])
            ->assertSessionHasErrors(['email']);
    }

    public function test_patient_can_save_privacy_preferences(): void
    {
        $patient = Patient::factory()->create(['is_demo' => false]);
        $user = User::factory()->create(['patient_id' => $patient->id]);

        $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->post(route('portals.patient.profile.update'), [
                'privacy_require_consent'   => '1',
                'privacy_emergency_access'  => '1',
            ])
            ->assertRedirect(route('portals.patient.profile'));

        $prefs = $patient->fresh()->privacy_preferences;
        $this->assertTrue($prefs['require_consent_for_full_record']);
        $this->assertTrue($prefs['emergency_access_allowed']);
    }

    public function test_patient_can_disable_privacy_preference(): void
    {
        $patient = \App\Models\Patient::factory()->create([
            'privacy_preferences' => ['require_consent_for_full_record' => true],
        ]);
        $user = \App\Models\User::factory()->create(['patient_id' => $patient->id]);

        $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->post(route('portals.patient.profile.update'), [
                'privacy_require_consent' => '0',
            ])
            ->assertRedirect();

        $patient->refresh();
        $prefs = $patient->privacy_preferences ?? [];
        $this->assertFalse((bool) ($prefs['require_consent_for_full_record'] ?? true));
    }

    public function test_unlinked_user_sees_no_profile_state(): void
    {
        $user = User::factory()->create(['patient_id' => null]);

        $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->get(route('portals.patient.profile'))
            ->assertStatus(200)
            ->assertViewHas('patient', null);
    }
}
