<?php
namespace Tests\Feature\Portal;

use App\Models\FamilyLink;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Inviting an adult: exact Health ID, email or phone — and the invited person
 * answers from THEIR portal ("Invitations for me"), so a working mailbox is
 * no longer the only way a link ever becomes active.
 */
class FamilyInvitationsTest extends TestCase
{
    use RefreshDatabase;

    private array $session = ['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'];

    private function invite(User $guardian, string $contact, array $overrides = [])
    {
        return $this->actingAs($guardian)->withSession($this->session)
            ->post(route('portals.patient.family.invite.send'), array_merge([
                'health_id_or_email' => $contact,
                'relationship'       => 'child',
                'access_level'       => 'read_only',
            ], $overrides));
    }

    public function test_invite_by_exact_phone_number(): void
    {
        $guardian = User::factory()->create();
        $elder    = Patient::factory()->create(['is_demo' => false, 'phone_number' => '+237690000001']);

        $this->invite($guardian, '+237690000001')->assertRedirect(route('portals.patient.family'));

        $this->assertDatabaseHas('family_links', [
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $elder->id,
            'status'               => 'pending_invite',
            'relationship'         => 'child',
            'access_level'         => 'read_only',
        ]);
    }

    public function test_invite_by_exact_email_and_health_id(): void
    {
        $guardian = User::factory()->create();
        $byMail   = Patient::factory()->create(['is_demo' => false, 'email' => 'elder@example.test']);
        $byId     = Patient::factory()->create(['is_demo' => false]);

        $this->invite($guardian, 'elder@example.test')->assertRedirect(route('portals.patient.family'));
        $this->invite($guardian, $byId->health_id)->assertRedirect(route('portals.patient.family'));

        $this->assertDatabaseHas('family_links', ['guardian_user_id' => $guardian->id, 'dependent_patient_id' => $byMail->id]);
        $this->assertDatabaseHas('family_links', ['guardian_user_id' => $guardian->id, 'dependent_patient_id' => $byId->id]);
    }

    public function test_partial_or_unknown_contact_finds_nobody(): void
    {
        $guardian = User::factory()->create();
        Patient::factory()->create(['is_demo' => false, 'email' => 'elder@example.test', 'first_name' => 'Mireille']);

        // No LIKE: a prefix of the email, a name, a partial phone — none resolve.
        foreach (['elder@example', 'Mireille', '+23769', 'nobody@example.test'] as $contact) {
            $this->invite($guardian, $contact)->assertSessionHasErrors('health_id_or_email');
        }
        $this->assertDatabaseMissing('family_links', ['guardian_user_id' => $guardian->id]);
    }

    public function test_invited_adult_sees_and_accepts_the_invitation_from_their_portal(): void
    {
        $guardian = User::factory()->create(['name' => 'Son Reviewer']);
        $elder    = Patient::factory()->create(['is_demo' => false]);
        $elderUser = User::factory()->create(['patient_id' => $elder->id]);

        $this->invite($guardian, $elder->health_id);
        $link = FamilyLink::where('dependent_patient_id', $elder->id)->firstOrFail();

        // Listed on the family page and on the dedicated page.
        $this->actingAs($elderUser)->withSession($this->session)
            ->get(route('portals.patient.family'))
            ->assertOk()
            ->assertSee('data-section="incoming-invitations"', false)
            ->assertSee('Son Reviewer');

        $this->actingAs($elderUser)->withSession($this->session)
            ->get(route('portals.patient.family.invitations'))
            ->assertOk()
            ->assertSee('data-invitation="' . $link->id . '"', false)
            ->assertSee(route('portals.patient.family.invitations.accept', $link->id));

        // Accept.
        $this->actingAs($elderUser)->withSession($this->session)
            ->post(route('portals.patient.family.invitations.accept', $link->id))
            ->assertRedirect(route('portals.patient.family.invitations'))
            ->assertSessionHas('success');

        $link->refresh();
        $this->assertSame('active', $link->status);
        $this->assertSame('invite_accepted', $link->created_by);
        $this->assertNull($link->invite_token);
        $this->assertDatabaseHas('audit_events', ['patient_id' => $elder->id, 'action_type' => 'guardian_link_accepted', 'actor_id' => $elderUser->id]);

        // And the guardian can now open the elder's record.
        $this->actingAs($guardian)->withSession($this->session)
            ->post(route('portals.patient.family.switch', $elder->id))
            ->assertRedirect(route('portals.patient'));
    }

    public function test_invited_adult_can_decline_and_be_invited_again_without_a_crash(): void
    {
        $guardian  = User::factory()->create();
        $elder     = Patient::factory()->create(['is_demo' => false]);
        $elderUser = User::factory()->create(['patient_id' => $elder->id]);

        $this->invite($guardian, $elder->health_id);
        $link = FamilyLink::where('dependent_patient_id', $elder->id)->firstOrFail();

        $this->actingAs($elderUser)->withSession($this->session)
            ->post(route('portals.patient.family.invitations.decline', $link->id))
            ->assertRedirect(route('portals.patient.family.invitations'))
            ->assertSessionHas('success');

        $link->refresh();
        $this->assertSame('declined', $link->status);
        $this->assertNull($link->invite_token);

        // Gone from the list...
        $this->actingAs($elderUser)->withSession($this->session)
            ->get(route('portals.patient.family.invitations'))
            ->assertOk()
            ->assertDontSee('data-invitation="' . $link->id . '"', false);

        // ...and a second invitation re-uses the row: the partial unique index
        // on (guardian, dependent) would otherwise turn it into a 23505 → 500.
        $this->invite($guardian, $elder->health_id, ['access_level' => 'full'])
            ->assertRedirect(route('portals.patient.family'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, FamilyLink::where('guardian_user_id', $guardian->id)->where('dependent_patient_id', $elder->id)->count());
        $link->refresh();
        $this->assertSame('pending_invite', $link->status);
        $this->assertSame('full', $link->access_level);
        $this->assertNotNull($link->invite_token);
    }

    public function test_only_the_invited_person_can_answer(): void
    {
        $guardian  = User::factory()->create();
        $elder     = Patient::factory()->create(['is_demo' => false]);
        $stranger  = User::factory()->create(['patient_id' => Patient::factory()->create(['is_demo' => false])->id]);
        $noPatient = User::factory()->create(['patient_id' => null]);

        $this->invite($guardian, $elder->health_id);
        $link = FamilyLink::where('dependent_patient_id', $elder->id)->firstOrFail();

        foreach ([$stranger, $noPatient] as $other) {
            $this->actingAs($other)->withSession($this->session)
                ->post(route('portals.patient.family.invitations.accept', $link->id))
                ->assertRedirect(route('portals.patient.family.invitations'))
                ->assertSessionHas('error');
            $this->assertDatabaseHas('family_links', ['id' => $link->id, 'status' => 'pending_invite']);
        }

        // Nothing to see for the stranger either.
        $this->actingAs($stranger)->withSession($this->session)
            ->get(route('portals.patient.family.invitations'))
            ->assertOk()
            ->assertDontSee('data-invitation="', false);
    }

    public function test_expired_invitations_are_not_offered(): void
    {
        $guardian  = User::factory()->create();
        $elder     = Patient::factory()->create(['is_demo' => false]);
        $elderUser = User::factory()->create(['patient_id' => $elder->id]);
        $link = FamilyLink::factory()->create([
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $elder->id,
            'status'               => 'pending_invite',
            'created_by'           => 'guardian_invited',
            'invite_token'         => hash('sha256', 'expired'),
            'invite_expires_at'    => now()->subHour(),
        ]);

        $this->actingAs($elderUser)->withSession($this->session)
            ->get(route('portals.patient.family.invitations'))
            ->assertOk()
            ->assertDontSee('data-invitation="' . $link->id . '"', false);

        $this->actingAs($elderUser)->withSession($this->session)
            ->post(route('portals.patient.family.invitations.accept', $link->id))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('family_links', ['id' => $link->id, 'status' => 'pending_invite']);
    }

    public function test_invite_form_explains_exact_match_and_access_levels(): void
    {
        $guardian = User::factory()->create();

        $this->actingAs($guardian)->withSession($this->session)
            ->get(route('portals.patient.family.invite'))
            ->assertOk()
            ->assertSee('Exact match only')
            ->assertSee('Full access:')
            ->assertSee('Read only:')
            ->assertSee('<option value="child"', false);
    }

    public function test_edit_form_shows_a_legacy_access_value_as_read_only_never_full(): void
    {
        // `view_only` came from early mobile builds. Whether the model
        // normalises it on write (it does now) or a raw row still carries it,
        // the edit form must pre-select read-only — saving the form used to
        // silently promote such a link to full access.
        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create(['is_demo' => false]);
        $link = FamilyLink::factory()->create([
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $dependent->id,
            'access_level'         => 'view_only',
            'status'               => 'active',
        ]);

        $this->assertNotSame('full', $link->fresh()->access_level);

        $this->actingAs($guardian)->withSession($this->session)
            ->get(route('portals.patient.family.edit', $link->id))
            ->assertOk()
            ->assertSee('<option value="read_only" selected>', false)
            ->assertDontSee('<option value="full" selected>', false);

        // And the update endpoint refuses the legacy word outright.
        $this->actingAs($guardian)->withSession($this->session)
            ->post(route('portals.patient.family.update', $link->id), ['relationship' => 'parent', 'access_level' => 'view_only'])
            ->assertSessionHasErrors('access_level');
    }
}
