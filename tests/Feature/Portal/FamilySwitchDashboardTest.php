<?php
namespace Tests\Feature\Portal;

use App\Models\FamilyLink;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The dashboard (Health ID card + QR) is declared outside the guardian.context
 * route group, so it applies the switch itself. While a guardian views a
 * dependent it must show THAT person's card, mint QRs for THAT person, and
 * refuse to mint anything for a read-only guardian — with a message, never a 500.
 */
class FamilySwitchDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function portalSession(?string $viewing = null): array
    {
        $s = ['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'];
        if ($viewing) {
            $s['guardian_viewing_patient_id'] = $viewing;
        }
        return $s;
    }

    /** @return array{0: User, 1: Patient, 2: Patient, 3: FamilyLink} guardian, own patient, dependent, link */
    private function guardianWithOwnRecordAnd(string $accessLevel): array
    {
        $own       = Patient::factory()->create(['is_demo' => false, 'first_name' => 'Guardian', 'last_name' => 'Self']);
        $guardian  = User::factory()->create(['patient_id' => $own->id]);
        $dependent = Patient::factory()->create(['is_demo' => false, 'first_name' => 'Demo', 'last_name' => 'Child', 'date_of_birth' => '2017-03-12']);
        $link      = FamilyLink::factory()->create([
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $dependent->id,
            'access_level'         => $accessLevel,
            'status'               => 'active',
        ]);
        return [$guardian, $own, $dependent, $link];
    }

    public function test_dashboard_shows_the_dependents_card_and_mints_their_qr_while_switched(): void
    {
        [$guardian, $own, $dependent] = $this->guardianWithOwnRecordAnd('full');

        $response = $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->get(route('portals.patient'));

        $response->assertOk();
        $response->assertViewHas('patient', fn ($p) => $p->id === $dependent->id);
        $response->assertSee($dependent->health_id);
        $response->assertDontSee($own->health_id);
        $response->assertSee('You are viewing Demo Child');
        $response->assertSee(route('portals.patient.family.switch.back'));

        // The static card QR belongs to the dependent, not to the guardian.
        $this->assertDatabaseHas('health_id_qr_tokens', ['patient_id' => $dependent->id, 'token_type' => 'static_identity_qr']);
        $this->assertDatabaseMissing('health_id_qr_tokens', ['patient_id' => $own->id]);
    }

    public function test_read_only_guardian_sees_the_card_but_no_qr_is_minted(): void
    {
        [$guardian, , $dependent] = $this->guardianWithOwnRecordAnd('read_only');

        $response = $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->get(route('portals.patient'));

        $response->assertOk();
        $response->assertSee($dependent->health_id);
        $response->assertSee('Read-only access');
        $response->assertSee('data-section="read-only-note"', false);
        $this->assertDatabaseMissing('health_id_qr_tokens', ['patient_id' => $dependent->id]);
    }

    public function test_read_only_guardian_cannot_mint_a_temporary_qr(): void
    {
        [$guardian, , $dependent] = $this->guardianWithOwnRecordAnd('read_only');

        $response = $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->postJson(route('portals.patient.qr'));

        $response->assertStatus(403);
        $response->assertJsonStructure(['message']);
        $this->assertDatabaseMissing('health_id_qr_tokens', ['patient_id' => $dependent->id]);
    }

    public function test_full_access_guardian_mints_a_temporary_qr_for_the_dependent(): void
    {
        [$guardian, $own, $dependent] = $this->guardianWithOwnRecordAnd('full');

        $response = $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->postJson(route('portals.patient.qr'));

        $response->assertOk()->assertJsonStructure(['url', 'expires_in', 'token_id']);
        $this->assertNotNull($response->json('token_id'), 'token_id must identify the minted token (it is the row uuid).');
        $this->assertDatabaseHas('health_id_qr_tokens', ['uuid' => $response->json('token_id'), 'patient_id' => $dependent->id, 'token_type' => 'temporary_consent_qr']);
        $this->assertDatabaseMissing('health_id_qr_tokens', ['patient_id' => $own->id, 'token_type' => 'temporary_consent_qr']);

        // ...and the same guardian can revoke it by that id — a read-only one gets a flash instead.
        $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->post(route('portals.patient.qr.revoke', $response->json('token_id')))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertDatabaseHas('health_id_qr_tokens', ['uuid' => $response->json('token_id'), 'status' => 'revoked']);
    }

    public function test_read_only_guardian_revoking_qr_tokens_gets_a_flash_not_an_error_page(): void
    {
        [$guardian, , $dependent] = $this->guardianWithOwnRecordAnd('read_only');

        $response = $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->post(route('portals.patient.qr.revoke-all'));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_read_only_guardian_cannot_print_the_dependents_card(): void
    {
        [$guardian, , $dependent] = $this->guardianWithOwnRecordAnd('read_only');

        $response = $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->get(route('portals.patient.health-id-card'));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('health_id_qr_tokens', ['patient_id' => $dependent->id]);
    }

    public function test_full_access_guardian_prints_the_dependents_card(): void
    {
        [$guardian, , $dependent] = $this->guardianWithOwnRecordAnd('full');

        $response = $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->get(route('portals.patient.health-id-card'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertDatabaseHas('health_id_qr_tokens', ['patient_id' => $dependent->id, 'token_type' => 'card_qr']);
    }

    public function test_stale_switch_on_the_dashboard_is_cleared_and_explained(): void
    {
        [$guardian, , $dependent, $link] = $this->guardianWithOwnRecordAnd('full');
        $link->update(['status' => 'revoked']);

        $response = $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->get(route('portals.patient'));

        $response->assertRedirect(route('portals.patient'));
        $response->assertSessionHas('error');
        $response->assertSessionMissing('guardian_viewing_patient_id');
    }

    public function test_user_who_only_manages_dependents_is_sent_to_the_dependents_dashboard(): void
    {
        $guardian  = User::factory()->create(['patient_id' => null]);
        $dependent = Patient::factory()->create(['is_demo' => false]);
        FamilyLink::factory()->create([
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $dependent->id,
            'status'               => 'active',
        ]);

        $this->actingAs($guardian)
            ->withSession($this->portalSession())
            ->get(route('portals.patient'))
            ->assertRedirect(route('portals.guardian.dashboard'));
    }

    public function test_switch_lands_on_the_dashboard_or_on_a_requested_record_page(): void
    {
        [$guardian, , $dependent] = $this->guardianWithOwnRecordAnd('full');

        $this->actingAs($guardian)->withSession($this->portalSession())
            ->post(route('portals.patient.family.switch', $dependent->id))
            ->assertRedirect(route('portals.patient'));

        $this->actingAs($guardian)->withSession($this->portalSession())
            ->post(route('portals.patient.family.switch', $dependent->id), ['next' => 'immunizations'])
            ->assertRedirect(route('portals.patient.immunizations'));

        // Anything not on the allow-list falls back to the dashboard — a URL in the body is never followed.
        $this->actingAs($guardian)->withSession($this->portalSession())
            ->post(route('portals.patient.family.switch', $dependent->id), ['next' => 'https://evil.example'])
            ->assertRedirect(route('portals.patient'));
    }

    public function test_family_pages_show_the_banner_while_switched(): void
    {
        [$guardian, , $dependent] = $this->guardianWithOwnRecordAnd('full');

        $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->get(route('portals.patient.family'))
            ->assertOk()
            ->assertSee('You are viewing Demo Child')
            ->assertSee('Currently open');
    }
}
