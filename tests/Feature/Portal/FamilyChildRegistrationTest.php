<?php
namespace Tests\Feature\Portal;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Registering a child: through PatientIdentityService (the platform's one
 * front door for a new Health ID), on the guardian's side of the demo
 * partition, and only for a minor — an adult is invited, never registered.
 */
class FamilyChildRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private array $session = ['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'];

    private function childPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name'    => 'Kidd',
            'last_name'     => 'Registered',
            'date_of_birth' => now()->subYears(6)->toDateString(),
            'sex'           => 'female',
            'relationship'  => 'parent',
            'access_level'  => 'full',
        ], $overrides);
    }

    public function test_child_is_created_through_the_identity_service(): void
    {
        $own      = Patient::factory()->create(['is_demo' => false, 'country_code' => 'CM']);
        $guardian = User::factory()->create(['patient_id' => $own->id]);

        $this->actingAs($guardian)->withSession($this->session)
            ->post(route('portals.patient.family.store'), $this->childPayload())
            ->assertRedirect(route('portals.patient.family'));

        $child = Patient::where('first_name', 'Kidd')->where('last_name', 'Registered')->first();
        $this->assertNotNull($child);
        $this->assertStringStartsWith('CM-HID-', $child->health_id);
        $this->assertSame('CM', $child->country_code);
        $this->assertSame('provisional', $child->identity_status instanceof \BackedEnum ? $child->identity_status->value : $child->identity_status);

        // PatientIdentityService writes the identity-creation audit row; a bare
        // Patient::create() never does.
        $this->assertDatabaseHas('audit_events', [
            'patient_id'    => $child->id,
            'action_type'   => 'create',
            'source_system' => 'identity',
            'actor_id'      => $guardian->id,
        ]);
        $this->assertDatabaseHas('family_links', [
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $child->id,
            'status'               => 'active',
            'created_by'           => 'self_registered',
            'relationship'         => 'parent',
        ]);
    }

    public function test_child_inherits_the_guardians_demo_flag_on_a_demo_host(): void
    {
        // The demo host shows only is_demo = true rows. A child hard-coded to
        // is_demo = false vanished from its own parent the moment it was made.
        config(['demo.enabled' => true]);

        $own      = Patient::factory()->create(['is_demo' => true]);
        $guardian = User::factory()->create(['patient_id' => $own->id, 'is_demo' => true]);

        $this->actingAs($guardian)->withSession($this->session)
            ->post(route('portals.patient.family.store'), $this->childPayload(['first_name' => 'Demo', 'last_name' => 'Kid']))
            ->assertRedirect(route('portals.patient.family'));

        $child = Patient::withoutGlobalScopes()->where('first_name', 'Demo')->where('last_name', 'Kid')->first();
        $this->assertNotNull($child);
        $this->assertTrue((bool) $child->is_demo, 'A child registered from a demo account is demo data.');

        // ...and therefore visible to the guardian under the demo scope.
        $this->assertTrue(Patient::where('id', $child->id)->exists());
        $this->actingAs($guardian)->withSession($this->session)
            ->get(route('portals.patient.family'))
            ->assertOk()
            ->assertSee('Demo Kid');
    }

    public function test_child_of_a_real_guardian_is_never_demo_data(): void
    {
        config(['demo.enabled' => false]);

        $own      = Patient::factory()->create(['is_demo' => false]);
        $guardian = User::factory()->create(['patient_id' => $own->id, 'is_demo' => false]);

        $this->actingAs($guardian)->withSession($this->session)
            ->post(route('portals.patient.family.store'), $this->childPayload(['first_name' => 'Real', 'last_name' => 'Kid']));

        $child = Patient::withoutGlobalScopes()->where('first_name', 'Real')->where('last_name', 'Kid')->first();
        $this->assertNotNull($child);
        $this->assertFalse((bool) $child->is_demo);
    }

    public function test_an_adult_cannot_be_registered_and_is_redirected_to_the_invitation(): void
    {
        $guardian = User::factory()->create();

        $response = $this->actingAs($guardian)->withSession($this->session)
            ->post(route('portals.patient.family.store'), $this->childPayload([
                'first_name'    => 'Grand',
                'last_name'     => 'Mother',
                'date_of_birth' => '1948-02-20',
                'relationship'  => 'child',
                'access_level'  => 'read_only',
            ]));

        $response->assertRedirect(route('portals.patient.family.invite'));
        $response->assertSessionHas('error');
        // The choices already made travel to the invitation form.
        $response->assertSessionHasInput('relationship', 'child');
        $response->assertSessionHasInput('access_level', 'read_only');

        $this->assertDatabaseMissing('patients', ['first_name' => 'Grand', 'last_name' => 'Mother']);
        $this->assertDatabaseMissing('family_links', ['guardian_user_id' => $guardian->id]);
    }

    public function test_someone_who_turned_eighteen_today_is_an_adult(): void
    {
        $guardian = User::factory()->create();

        $this->actingAs($guardian)->withSession($this->session)
            ->post(route('portals.patient.family.store'), $this->childPayload([
                'first_name'    => 'Birthday',
                'date_of_birth' => now()->subYears(18)->toDateString(),
            ]))
            ->assertRedirect(route('portals.patient.family.invite'));

        $this->assertDatabaseMissing('patients', ['first_name' => 'Birthday']);
    }

    public function test_relationship_child_is_accepted_and_legacy_access_levels_are_not(): void
    {
        $guardian = User::factory()->create();

        $this->actingAs($guardian)->withSession($this->session)
            ->post(route('portals.patient.family.store'), $this->childPayload(['relationship' => 'child']))
            ->assertRedirect(route('portals.patient.family'));

        $this->actingAs($guardian)->withSession($this->session)
            ->post(route('portals.patient.family.store'), $this->childPayload(['first_name' => 'Other', 'access_level' => 'view_only']))
            ->assertSessionHasErrors('access_level');
    }

    public function test_add_form_explains_who_may_be_registered(): void
    {
        $guardian = User::factory()->create();

        $this->actingAs($guardian)->withSession($this->session)
            ->get(route('portals.patient.family.add'))
            ->assertOk()
            ->assertSee('data-section="child-only-note"', false)
            ->assertSee('Full access:')
            ->assertSee('Read only:')
            ->assertSee('<option value="child"', false);
    }
}
