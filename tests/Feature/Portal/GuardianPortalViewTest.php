<?php
namespace Tests\Feature\Portal;

use App\Models\FamilyLink;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardianPortalViewTest extends TestCase
{
    use RefreshDatabase;

    private function guardianSession(string $patientId): array
    {
        return [
            'active_facility_id'          => '7e57fac1-0000-4000-8000-000000000001',
            'guardian_viewing_patient_id' => $patientId,
        ];
    }

    public function test_guardian_can_view_dependent_lab_results(): void
    {
        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create(['is_demo' => false]);
        FamilyLink::factory()->create([
            'guardian_user_id'    => $guardian->id,
            'dependent_patient_id'=> $dependent->id,
            'access_level'        => 'read_only',
            'status'              => 'active',
        ]);

        $response = $this->actingAs($guardian)
            ->withSession($this->guardianSession($dependent->id))
            ->get(route('portals.patient.labs'));

        $response->assertStatus(200);
    }

    public function test_read_only_guardian_cannot_update_profile(): void
    {
        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create(['is_demo' => false]);
        FamilyLink::factory()->create([
            'guardian_user_id'    => $guardian->id,
            'dependent_patient_id'=> $dependent->id,
            'access_level'        => 'read_only',
            'status'              => 'active',
        ]);

        $response = $this->actingAs($guardian)
            ->withSession($this->guardianSession($dependent->id))
            ->post(route('portals.patient.profile.update'), ['phone_number' => '123']);

        $response->assertStatus(403);
    }

    public function test_full_access_guardian_can_update_profile(): void
    {
        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create(['is_demo' => false, 'phone_number' => '000']);
        FamilyLink::factory()->create([
            'guardian_user_id'    => $guardian->id,
            'dependent_patient_id'=> $dependent->id,
            'access_level'        => 'full',
            'status'              => 'active',
        ]);

        $response = $this->actingAs($guardian)
            ->withSession($this->guardianSession($dependent->id))
            ->post(route('portals.patient.profile.update'), ['phone_number' => '555-1234']);

        $response->assertRedirect(route('portals.patient.profile'));
        // phone_number is now encrypted in the DB — verify via model decryption
        $this->assertEquals('555-1234', $dependent->fresh()->phone_number);
    }

    public function test_guardian_views_dependent_data_not_own_data(): void
    {
        $guardian   = User::factory()->create();
        $ownPatient = Patient::factory()->create(['is_demo' => false]);
        $guardian->update(['patient_id' => $ownPatient->id]);

        $dependent = Patient::factory()->create(['is_demo' => false]);
        FamilyLink::factory()->create([
            'guardian_user_id'    => $guardian->id,
            'dependent_patient_id'=> $dependent->id,
            'access_level'        => 'full',
            'status'              => 'active',
        ]);

        $response = $this->actingAs($guardian)
            ->withSession($this->guardianSession($dependent->id))
            ->get(route('portals.patient.labs'));

        $response->assertStatus(200);
        $response->assertViewHas('patient', fn($p) => $p->id === $dependent->id);
    }

    public function test_dependent_can_see_and_deny_pending_guardian_consent(): void
    {
        // Dependent has a user account
        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create(['is_demo' => false]);
        $depUser   = User::factory()->create(['patient_id' => $dependent->id]);

        $link = \App\Models\FamilyLink::factory()->create([
            'guardian_user_id'          => $guardian->id,
            'dependent_patient_id'      => $dependent->id,
            'status'                    => 'active',
            'age_transition_expires_at' => now()->addDays(20),
        ]);

        // Family index page for the DEPENDENT (not the guardian)
        $response = $this->actingAs($depUser)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->get(route('portals.patient.family'));

        $response->assertStatus(200);
        $response->assertSee('guardian-consent'); // the data-section attribute

        // Deny action
        $deny = $this->actingAs($depUser)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->post(route('portals.patient.family.guardian_consent.deny', $link->id));

        $deny->assertRedirect(route('portals.patient'));
        $link->refresh();
        $this->assertEquals('revoked', $link->status);
    }
}
