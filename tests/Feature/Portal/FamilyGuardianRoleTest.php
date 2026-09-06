<?php
namespace Tests\Feature\Portal;

use App\Models\FamilyLink;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A guardian-role account must not be a dead end.
 *
 * RequireCompleteGuardianProfile used to bounce every portal URL to
 * complete-profile → pending forever, with nothing that ever turned "pending"
 * into a FamilyLink. Now: a guardian who owns a link (active or pending) or a
 * patient record passes; one who has submitted the profile can reach the two
 * pages that create a link; and the dashboard lands them on their dependents.
 */
class FamilyGuardianRoleTest extends TestCase
{
    use RefreshDatabase;

    private array $session = ['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'];

    private function guardianUser(array $attrs = []): User
    {
        $role = Role::firstOrCreate(['name' => 'guardian'], ['description' => 'Guardian / Parent Account']);

        return User::factory()->create(array_merge(['role_id' => $role->id, 'patient_id' => null], $attrs));
    }

    public function test_guardian_with_no_profile_and_no_links_is_sent_to_complete_profile(): void
    {
        $guardian = $this->guardianUser();

        $this->actingAs($guardian)->withSession($this->session)
            ->get(route('portals.patient'))
            ->assertRedirect(route('portals.guardian.complete-profile'));

        // The family pages are not an escape hatch before the profile exists.
        $this->actingAs($guardian)->withSession($this->session)
            ->get(route('portals.patient.family.add'))
            ->assertRedirect(route('portals.guardian.complete-profile'));
    }

    public function test_guardian_with_submitted_profile_but_no_links_can_reach_the_pages_that_create_one(): void
    {
        $guardian = $this->guardianUser(['profile_completed_at' => now()]);

        // Dashboard still waits...
        $this->actingAs($guardian)->withSession($this->session)
            ->get(route('portals.patient'))
            ->assertRedirect(route('portals.guardian.pending'));

        // ...but the pending page now offers the way out,
        $this->actingAs($guardian)->withSession($this->session)
            ->get(route('portals.guardian.pending'))
            ->assertOk()
            ->assertSee('data-section="guardian-next-steps"', false)
            ->assertSee(route('portals.patient.family.add'))
            ->assertSee(route('portals.patient.family.invite'));

        // and both of those pages open.
        $this->actingAs($guardian)->withSession($this->session)
            ->get(route('portals.patient.family.add'))->assertOk();
        $this->actingAs($guardian)->withSession($this->session)
            ->get(route('portals.patient.family.invite'))->assertOk();
        $this->actingAs($guardian)->withSession($this->session)
            ->get(route('portals.guardian.dashboard'))->assertOk()
            ->assertSee('data-section="dependents-empty"', false);
    }

    public function test_guardian_with_an_active_link_lands_on_the_dependents_dashboard(): void
    {
        $guardian  = $this->guardianUser();
        $dependent = Patient::factory()->create(['is_demo' => false, 'first_name' => 'Amara', 'last_name' => 'Ndip', 'date_of_birth' => now()->subYears(9)->toDateString()]);
        FamilyLink::factory()->create([
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $dependent->id,
            'access_level'         => 'full',
            'status'               => 'active',
        ]);

        // The patient dashboard has nothing of the guardian's own to show — it
        // hands over to the dependents dashboard instead of an empty record.
        $this->actingAs($guardian)->withSession($this->session)
            ->get(route('portals.patient'))
            ->assertRedirect(route('portals.guardian.dashboard'));

        $page = $this->actingAs($guardian)->withSession($this->session)
            ->get(route('portals.guardian.dashboard'))
            ->assertOk()
            ->assertSee('Amara Ndip')
            ->assertSee($dependent->health_id)
            ->assertSee('Open record')
            ->assertSee(route('portals.patient.family.switch', $dependent->id));

        // The page carries the navigation the role needs (the sidebar is owned elsewhere).
        foreach (['portals.patient.family', 'portals.patient.appointments', 'portals.patient.immunizations', 'portals.patient.timeline', 'portals.patient.documents', 'portals.patient.family.add', 'portals.patient.family.invite'] as $name) {
            $page->assertSee(route($name));
        }
    }

    public function test_guardian_with_only_a_pending_invite_is_let_through(): void
    {
        $guardian  = $this->guardianUser();
        $dependent = Patient::factory()->create(['is_demo' => false]);
        FamilyLink::factory()->create([
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $dependent->id,
            'status'               => 'pending_invite',
            'created_by'           => 'guardian_invited',
            'invite_token'         => hash('sha256', 'x'),
            'invite_expires_at'    => now()->addDay(),
        ]);

        $this->actingAs($guardian)->withSession($this->session)
            ->get(route('portals.guardian.dashboard'))
            ->assertOk()
            ->assertSee('Waiting for them to accept');
    }

    public function test_guardian_who_is_also_a_patient_sees_their_own_dashboard(): void
    {
        $own      = Patient::factory()->create(['is_demo' => false]);
        $guardian = $this->guardianUser(['patient_id' => $own->id]);

        $this->actingAs($guardian)->withSession($this->session)
            ->get(route('portals.patient'))
            ->assertOk()
            ->assertSee($own->health_id);
    }

    public function test_dependents_dashboard_shows_age_transition_copy_for_a_child_about_to_turn_eighteen(): void
    {
        $guardian  = $this->guardianUser();
        $dependent = Patient::factory()->create([
            'is_demo'       => false,
            'first_name'    => 'Teen',
            'date_of_birth' => now()->subYears(18)->addDays(20)->toDateString(),
        ]);
        FamilyLink::factory()->create([
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $dependent->id,
            'status'               => 'active',
        ]);

        $this->actingAs($guardian)->withSession($this->session)
            ->get(route('portals.guardian.dashboard'))
            ->assertOk()
            ->assertSee('data-age-transition', false)
            ->assertSee('Teen turns 18 on');
    }
}
