<?php

namespace Tests\Feature\Mobile;

use App\Models\FamilyLink;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Inviting an adult (the older-person path): exact lookups with a neutral
 * refusal, the invitee's bell, and the invitee's own list / accept / decline.
 */
class FamilyInvitationsTest extends FamilyTestCase
{
    private array $g;

    protected function setUp(): void
    {
        parent::setUp();
        $this->g = $this->makeGuardian(['health_id' => 'CM-HID-GUAR-0002-AAAA']);
    }

    private function makeElder(array $attrs = []): Patient
    {
        return $this->makePatient(array_merge([
            'health_id'     => 'CM-HID-ELDR-0001-AB12',
            'first_name'    => 'Demo',
            'last_name'     => 'Grandmother',
            'sex'           => 'female',
            'date_of_birth' => '1948-02-20',
            'email'         => 'elder@example.test',
            'phone_number'  => '+237699000111',
        ], $attrs));
    }

    public function test_invite_by_exact_health_id_creates_a_pending_read_only_link_and_rings_the_invitees_bell(): void
    {
        $elder = $this->makeElder();

        $response = $this->as($this->g['headers'])
            ->postJson('/api/mobile/family/invitations', ['health_id' => 'cm-hid-eldr-0001-ab12', 'relationship' => 'child'])
            ->assertCreated()
            ->assertJsonPath('data.relationship', 'child')
            ->assertJsonPath('data.access_level', 'read_only')
            ->assertJsonPath('data.status', 'pending_invite')
            ->assertJsonPath('data.contact', 'cm-hid-eldr-0001-ab12')
            ->assertJsonStructure(['message', 'data' => ['id', 'contact', 'relationship', 'access_level', 'status', 'sent_at', 'expires_at']]);

        $link = FamilyLink::findOrFail($response->json('data.id'));
        $this->assertSame($elder->id, $link->dependent_patient_id);
        $this->assertSame($this->g['user']->id, $link->guardian_user_id);
        $this->assertSame('guardian_invited', $link->created_by);
        $this->assertNotNull($link->invite_token);
        $this->assertNotNull($link->invite_expires_at);
        $this->assertNull($link->dependent_consented_at);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => 'patient',
            'notifiable_id'   => $elder->id,
            'type'            => 'family_invitation',
        ]);

        // …and the app's bell, read as the elder, shows it.
        $bell = $this->as($this->headersFor($elder))->getJson('/api/mobile/notifications')->assertOk()->json('data');
        $this->assertCount(1, $bell);
        $this->assertSame('family_invitation', $bell[0]['type']);
        $this->assertSame('/family', $bell[0]['action_url']);
        $this->assertStringContainsString('Marie Guardian', $bell[0]['title']);
    }

    public function test_a_partial_or_unknown_health_id_is_refused_without_saying_whether_anyone_exists(): void
    {
        $this->makeElder();

        foreach (['CM-HID-ELDR', 'CM-HID-ELDR-0001', 'CM-HID-ZZZZ-9999-ZZ99', '%'] as $probe) {
            $this->as($this->g['headers'])
                ->postJson('/api/mobile/family/invitations', ['health_id' => $probe, 'relationship' => 'child'])
                ->assertUnprocessable()
                ->assertJsonPath('error_code', 'invitation_not_sent')
                ->assertJsonPath('message', __('family_api.invitation_not_sent'));
        }

        $this->as($this->g['headers'])
            ->postJson('/api/mobile/family/invitations', ['email' => 'nobody@example.test', 'relationship' => 'child'])
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'invitation_not_sent');

        $this->as($this->g['headers'])
            ->postJson('/api/mobile/family/invitations', ['phone' => '+237600000000', 'relationship' => 'child'])
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'invitation_not_sent');

        $this->assertSame(0, FamilyLink::count());
    }

    public function test_invite_by_email_and_by_phone_are_exact_lookups(): void
    {
        $byEmail = $this->makeElder();
        $byPhone = $this->makeElder(['health_id' => 'CM-HID-ELDR-0002-CD34', 'first_name' => 'Papa', 'email' => 'papa@example.test', 'phone_number' => '+237699000222']);

        $this->as($this->g['headers'])
            ->postJson('/api/mobile/family/invitations', ['email' => 'Elder@Example.test', 'relationship' => 'child'])
            ->assertCreated();
        $this->assertDatabaseHas('family_links', ['dependent_patient_id' => $byEmail->id, 'status' => 'pending_invite']);

        $this->as($this->g['headers'])
            ->postJson('/api/mobile/family/invitations', ['phone' => '+237699000222', 'relationship' => 'child', 'access_level' => 'full'])
            ->assertCreated()
            ->assertJsonPath('data.access_level', 'full');
        $this->assertDatabaseHas('family_links', ['dependent_patient_id' => $byPhone->id, 'access_level' => 'full']);
    }

    public function test_the_legacy_contact_field_and_legacy_access_levels_are_still_understood(): void
    {
        $a = $this->makeElder();
        $b = $this->makeElder(['health_id' => 'CM-HID-ELDR-0003-EF56', 'first_name' => 'Papa', 'email' => 'papa@example.test', 'phone_number' => '+237699000333']);

        $this->as($this->g['headers'])
            ->postJson('/api/mobile/family/invitations', ['contact' => 'CM-HID-ELDR-0001-AB12', 'relationship' => 'caregiver', 'access_level' => 'view_only'])
            ->assertCreated()
            ->assertJsonPath('data.access_level', 'read_only');
        $this->assertDatabaseHas('family_links', ['dependent_patient_id' => $a->id, 'access_level' => 'read_only']);

        $this->as($this->g['headers'])
            ->postJson('/api/mobile/family/invitations', ['contact' => 'papa@example.test', 'relationship' => 'caregiver', 'access_level' => 'guardian'])
            ->assertCreated()
            ->assertJsonPath('data.access_level', 'full');
        $this->assertDatabaseHas('family_links', ['dependent_patient_id' => $b->id, 'access_level' => 'full']);
    }

    public function test_relationship_and_access_level_are_validated_and_a_contact_is_required(): void
    {
        $this->makeElder();

        $this->as($this->g['headers'])
            ->postJson('/api/mobile/family/invitations', ['health_id' => 'CM-HID-ELDR-0001-AB12', 'relationship' => 'uncle'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['relationship']);

        $this->as($this->g['headers'])
            ->postJson('/api/mobile/family/invitations', ['health_id' => 'CM-HID-ELDR-0001-AB12', 'relationship' => 'child', 'access_level' => 'admin'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['access_level']);

        $this->as($this->g['headers'])
            ->postJson('/api/mobile/family/invitations', ['relationship' => 'child'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['health_id', 'email', 'phone', 'contact']);
    }

    public function test_self_and_an_existing_link_are_refused(): void
    {
        $this->as($this->g['headers'])
            ->postJson('/api/mobile/family/invitations', ['health_id' => 'CM-HID-GUAR-0002-AAAA', 'relationship' => 'other'])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('api.cannot_link_self'));

        $this->makeElder();
        $this->as($this->g['headers'])->postJson('/api/mobile/family/invitations', ['health_id' => 'CM-HID-ELDR-0001-AB12', 'relationship' => 'child'])->assertCreated();
        $this->as($this->g['headers'])->postJson('/api/mobile/family/invitations', ['health_id' => 'CM-HID-ELDR-0001-AB12', 'relationship' => 'child'])->assertConflict();
    }

    public function test_the_invitee_sees_the_invitation_and_can_accept_it(): void
    {
        $elder = $this->makeElder();
        $this->makeUserFor($elder, ['name' => 'Demo Grandmother']);
        $elderHeaders = $this->headersFor($elder);

        $linkId = $this->as($this->g['headers'])
            ->postJson('/api/mobile/family/invitations', ['health_id' => 'CM-HID-ELDR-0001-AB12', 'relationship' => 'child'])
            ->assertCreated()->json('data.id');

        $incoming = $this->as($elderHeaders)->getJson('/api/mobile/family/invitations/incoming')->assertOk();
        $incoming->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $linkId)
            ->assertJsonPath('data.0.guardian_name', 'Marie Guardian')
            ->assertJsonPath('data.0.relationship', 'child')
            ->assertJsonPath('data.0.access_level', 'read_only')
            ->assertJsonStructure(['data' => [['id', 'guardian_name', 'relationship', 'access_level', 'created_at', 'expires_at']]]);
        $this->assertNotNull($incoming->json('data.0.created_at'));
        $this->assertNotNull($incoming->json('data.0.expires_at'));

        // Somebody else sees nothing addressed to them.
        $this->as($this->headersFor($this->makePatient()))->getJson('/api/mobile/family/invitations/incoming')->assertOk()->assertJsonCount(0, 'data');

        $accepted = $this->as($elderHeaders)
            ->postJson("/api/mobile/family/invitations/{$linkId}/accept")
            ->assertOk()
            ->assertJsonPath('data.id', $linkId)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.guardian_name', 'Marie Guardian')
            ->assertJsonPath('message', __('family_api.invitation_accepted', ['name' => 'Marie Guardian']));
        $this->assertNotNull($accepted->json('data.dependent_consented_at'));

        $link = FamilyLink::findOrFail($linkId);
        $this->assertSame('active', $link->status);
        $this->assertSame('invite_accepted', $link->created_by);
        $this->assertNotNull($link->dependent_consented_at);
        $this->assertNull($link->invite_token);
        $this->assertNull($link->invite_expires_at);

        // Gone from the invitee's inbox, present in the guardian's family.
        $this->as($elderHeaders)->getJson('/api/mobile/family/invitations/incoming')->assertOk()->assertJsonCount(0, 'data');
        $this->as($this->g['headers'])->getJson('/api/mobile/family')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $linkId)
            ->assertJsonPath('data.0.status', 'active')
            ->assertJsonPath('data.0.patient.id', $elder->id)
            ->assertJsonPath('data.0.patient.dob', '1948-02-20')
            ->assertJsonPath('data.0.patient.sex', 'female');
        $this->assertNotNull($this->as($this->g['headers'])->getJson('/api/mobile/family')->json('data.0.dependent_consented_at'));

        // The guardian's own bell hears the answer.
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => (new User)->getMorphClass(),
            'notifiable_id'   => $this->g['user']->id,
            'type'            => 'family_invitation_accepted',
        ]);
    }

    public function test_the_invitee_can_decline_and_the_guardian_can_invite_again(): void
    {
        $elder = $this->makeElder();
        $this->makeUserFor($elder);
        $elderHeaders = $this->headersFor($elder);

        $linkId = $this->as($this->g['headers'])
            ->postJson('/api/mobile/family/invitations', ['health_id' => 'CM-HID-ELDR-0001-AB12', 'relationship' => 'child'])
            ->assertCreated()->json('data.id');

        $this->as($elderHeaders)
            ->postJson("/api/mobile/family/invitations/{$linkId}/decline")
            ->assertOk()
            ->assertJsonPath('data.status', 'declined')
            ->assertJsonPath('message', __('family_api.invitation_declined'));

        $this->assertDatabaseHas('family_links', ['id' => $linkId, 'status' => 'declined', 'invite_token' => null]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->g['user']->id, 'type' => 'family_invitation_declined']);

        // Declined is terminal: the pair may be invited again (partial unique index).
        $this->as($this->g['headers'])
            ->postJson('/api/mobile/family/invitations', ['health_id' => 'CM-HID-ELDR-0001-AB12', 'relationship' => 'child'])
            ->assertCreated();

        // Declining twice, or accepting after declining, finds nothing.
        $this->as($elderHeaders)->postJson("/api/mobile/family/invitations/{$linkId}/accept")->assertNotFound();
    }

    public function test_only_the_invitee_can_answer(): void
    {
        $elder = $this->makeElder();
        $linkId = $this->as($this->g['headers'])
            ->postJson('/api/mobile/family/invitations', ['health_id' => 'CM-HID-ELDR-0001-AB12', 'relationship' => 'child'])
            ->assertCreated()->json('data.id');

        // A bystander, and the guardian who sent it.
        $this->as($this->headersFor($this->makePatient()))
            ->postJson("/api/mobile/family/invitations/{$linkId}/accept")
            ->assertNotFound()
            ->assertJsonPath('error_code', 'invitation_not_found');
        $this->as($this->g['headers'])
            ->postJson("/api/mobile/family/invitations/{$linkId}/accept")
            ->assertNotFound();

        $this->assertDatabaseHas('family_links', ['id' => $linkId, 'status' => 'pending_invite']);
    }

    public function test_an_expired_invitation_is_neither_listed_nor_acceptable(): void
    {
        $elder = $this->makeElder();
        $link  = $this->link($this->g['user'], $elder, [
            'status'            => FamilyLink::STATUS_PENDING_INVITE,
            'created_by'        => FamilyLink::CREATED_BY_GUARDIAN_INVITED,
            'invite_token'      => hash('sha256', 'x'),
            'invite_expires_at' => now()->subHour(),
        ]);
        $elderHeaders = $this->headersFor($elder);

        $this->as($elderHeaders)->getJson('/api/mobile/family/invitations/incoming')->assertOk()->assertJsonCount(0, 'data');
        $this->as($elderHeaders)->postJson("/api/mobile/family/invitations/{$link->id}/accept")->assertNotFound();
    }

    public function test_cancel_then_reinvite_does_not_trip_the_unique_index(): void
    {
        $this->makeElder();

        $first = $this->as($this->g['headers'])
            ->postJson('/api/mobile/family/invitations', ['health_id' => 'CM-HID-ELDR-0001-AB12', 'relationship' => 'child'])
            ->assertCreated()->json('data.id');

        $this->as($this->g['headers'])->deleteJson("/api/mobile/family/invitations/{$first}")->assertOk();
        $this->assertDatabaseHas('family_links', ['id' => $first, 'status' => 'cancelled']);

        $second = $this->as($this->g['headers'])
            ->postJson('/api/mobile/family/invitations', ['health_id' => 'CM-HID-ELDR-0001-AB12', 'relationship' => 'child'])
            ->assertCreated()->json('data.id');

        $this->assertNotSame($first, $second);
        $this->assertSame(2, DB::table('family_links')->count());
    }

    public function test_sent_invitations_show_the_invitee_by_short_name_not_by_identifier(): void
    {
        $this->makeElder();
        $this->as($this->g['headers'])
            ->postJson('/api/mobile/family/invitations', ['phone' => '+237699000111', 'relationship' => 'child'])
            ->assertCreated();

        $this->as($this->g['headers'])->getJson('/api/mobile/family/invitations')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.contact', 'Demo G.')
            ->assertJsonPath('data.0.access_level', 'read_only')
            ->assertJsonPath('data.0.status', 'pending_invite')
            ->assertJsonStructure(['data' => [['id', 'contact', 'relationship', 'access_level', 'status', 'method', 'sent_at', 'expires_at']]]);
    }
}
