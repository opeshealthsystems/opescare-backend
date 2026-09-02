<?php
namespace Tests\Feature\Portal;

use App\Models\FamilyLink;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $session = ['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'];

    public function test_family_dashboard_shows_active_links(): void
    {
        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create(['is_demo' => false, 'first_name' => 'Anna']);
        FamilyLink::factory()->create([
            'guardian_user_id'    => $guardian->id,
            'dependent_patient_id'=> $dependent->id,
            'status'              => 'active',
        ]);

        $response = $this->actingAs($guardian)
            ->withSession($this->session)
            ->get(route('portals.patient.family'));

        $response->assertStatus(200);
        $response->assertViewHas('links');
    }

    public function test_store_creates_patient_and_family_link(): void
    {
        $guardian = User::factory()->create();

        $response = $this->actingAs($guardian)
            ->withSession($this->session)
            ->post(route('portals.patient.family.store'), [
                'first_name'   => 'Child',
                'last_name'    => 'Test',
                'date_of_birth'=> '2015-06-01',
                'sex'          => 'male',
                'relationship' => 'parent',
                'access_level' => 'full',
            ]);

        $response->assertRedirect(route('portals.patient.family'));
        $this->assertDatabaseHas('patients', ['first_name' => 'Child', 'last_name' => 'Test']);
        $this->assertDatabaseHas('family_links', [
            'guardian_user_id' => $guardian->id,
            'relationship'     => 'parent',
            'status'           => 'active',
            'created_by'       => 'self_registered',
        ]);
    }

    public function test_store_does_not_create_user_account_for_dependent(): void
    {
        $guardian = User::factory()->create();
        $userCountBefore = User::count();

        $this->actingAs($guardian)
            ->withSession($this->session)
            ->post(route('portals.patient.family.store'), [
                'first_name'   => 'Baby',
                'last_name'    => 'Doe',
                'date_of_birth'=> '2020-01-01',
                'sex'          => 'female',
                'relationship' => 'parent',
                'access_level' => 'full',
            ]);

        $this->assertEquals($userCountBefore, User::count());
    }

    public function test_switch_sets_guardian_viewing_session(): void
    {
        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create(['is_demo' => false]);
        FamilyLink::factory()->create([
            'guardian_user_id'    => $guardian->id,
            'dependent_patient_id'=> $dependent->id,
            'status'              => 'active',
        ]);

        $response = $this->actingAs($guardian)
            ->withSession($this->session)
            ->post(route('portals.patient.family.switch', $dependent->id));

        $response->assertRedirect();
        $response->assertSessionHas('guardian_viewing_patient_id', $dependent->id);
    }

    public function test_switch_back_clears_guardian_session(): void
    {
        $guardian = User::factory()->create();

        $response = $this->actingAs($guardian)
            ->withSession(array_merge($this->session, ['guardian_viewing_patient_id' => 'some-id']))
            ->post(route('portals.patient.family.switch.back'));

        $response->assertRedirect(route('portals.patient'));
        $response->assertSessionMissing('guardian_viewing_patient_id');
    }

    public function test_revoke_sets_link_status_to_revoked(): void
    {
        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create(['is_demo' => false]);
        $link = FamilyLink::factory()->create([
            'guardian_user_id'    => $guardian->id,
            'dependent_patient_id'=> $dependent->id,
            'status'              => 'active',
        ]);

        $this->actingAs($guardian)
            ->withSession($this->session)
            ->post(route('portals.patient.family.revoke', $link->id));

        $this->assertDatabaseHas('family_links', ['id' => $link->id, 'status' => 'revoked']);
    }

    public function test_cannot_revoke_another_users_link(): void
    {
        $guardian  = User::factory()->create();
        $other     = User::factory()->create();
        $dependent = Patient::factory()->create(['is_demo' => false]);
        $link = FamilyLink::factory()->create([
            'guardian_user_id'    => $other->id,
            'dependent_patient_id'=> $dependent->id,
        ]);

        $response = $this->actingAs($guardian)
            ->withSession($this->session)
            ->post(route('portals.patient.family.revoke', $link->id));

        $response->assertStatus(403);
    }

    public function test_send_invite_cannot_link_demo_patient_by_health_id(): void
    {
        $guardian = User::factory()->create();
        $demo     = Patient::factory()->create(['is_demo' => true, 'email' => null]);

        $response = $this->actingAs($guardian)
            ->withSession($this->session)
            ->post(route('portals.patient.family.invite.send'), [
                'health_id_or_email' => $demo->health_id,
                'relationship'       => 'parent',
                'access_level'       => 'full',
            ]);

        $response->assertSessionHasErrors('health_id_or_email');
        $this->assertDatabaseMissing('family_links', ['guardian_user_id' => $guardian->id]);
    }

    public function test_send_invite_cannot_link_self(): void
    {
        $guardian = User::factory()->create();
        $patient  = Patient::factory()->create(['is_demo' => false]);
        $guardian->update(['patient_id' => $patient->id]);

        $response = $this->actingAs($guardian)
            ->withSession($this->session)
            ->post(route('portals.patient.family.invite.send'), [
                'health_id_or_email' => $patient->health_id,
                'relationship'       => 'parent',
                'access_level'       => 'full',
            ]);

        $response->assertSessionHasErrors('health_id_or_email');
        $this->assertDatabaseMissing('family_links', ['guardian_user_id' => $guardian->id]);
    }

    public function test_send_invite_sets_created_by_guardian_invited(): void
    {
        $guardian = User::factory()->create();
        $patient  = Patient::factory()->create(['is_demo' => false]);

        $this->actingAs($guardian)
            ->withSession($this->session)
            ->post(route('portals.patient.family.invite.send'), [
                'health_id_or_email' => $patient->health_id,
                'relationship'       => 'parent',
                'access_level'       => 'full',
            ]);

        $this->assertDatabaseHas('family_links', [
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $patient->id,
            'created_by'           => 'guardian_invited',
        ]);
    }

    public function test_unauthenticated_user_cannot_confirm_invite(): void
    {
        $guardian  = User::factory()->create();
        $patient   = Patient::factory()->create(['is_demo' => false]);
        $rawToken  = \Illuminate\Support\Str::random(64);
        \App\Models\FamilyLink::factory()->create([
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $patient->id,
            'status'               => 'pending_invite',
            'invite_token'         => hash('sha256', $rawToken),
            'invite_expires_at'    => now()->addHours(24),
            'created_by'           => 'guardian_invited',
        ]);

        // POST without being logged in
        $response = $this->post(route('portals.patient.family.invite.confirm', $rawToken));

        // Must redirect (to login), not activate the link
        $response->assertRedirect();
        $this->assertDatabaseHas('family_links', [
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $patient->id,
            'status'               => 'pending_invite', // must remain pending
        ]);
    }

    public function test_wrong_user_cannot_confirm_invite_for_another_patient(): void
    {
        $guardian  = User::factory()->create();
        $patient   = Patient::factory()->create(['is_demo' => false]);
        $wrongUser = User::factory()->create(); // has no patient_id link
        $rawToken  = \Illuminate\Support\Str::random(64);
        \App\Models\FamilyLink::factory()->create([
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $patient->id,
            'status'               => 'pending_invite',
            'invite_token'         => hash('sha256', $rawToken),
            'invite_expires_at'    => now()->addHours(24),
            'created_by'           => 'guardian_invited',
        ]);

        // POST as a different (wrong) user who is not the patient
        $response = $this->actingAs($wrongUser)
            ->post(route('portals.patient.family.invite.confirm', $rawToken));

        $response->assertStatus(403);
        $this->assertDatabaseHas('family_links', [
            'status' => 'pending_invite', // must remain pending
        ]);
    }

    public function test_user_with_no_patient_record_cannot_confirm_invite(): void
    {
        $guardian   = User::factory()->create();
        $patient    = Patient::factory()->create(['is_demo' => false]);
        $noPatient  = User::factory()->create(['patient_id' => null]); // no patient linked
        $rawToken   = \Illuminate\Support\Str::random(64);
        \App\Models\FamilyLink::factory()->create([
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $patient->id,
            'status'               => 'pending_invite',
            'invite_token'         => hash('sha256', $rawToken),
            'invite_expires_at'    => now()->addHours(24),
            'created_by'           => 'guardian_invited',
        ]);

        $response = $this->actingAs($noPatient)
            ->post(route('portals.patient.family.invite.confirm', $rawToken));

        $response->assertStatus(403);
        $this->assertDatabaseHas('family_links', ['status' => 'pending_invite']);
    }

    public function test_appointment_updated_listener_checks_wasChanged_not_isDirty(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create(['is_demo' => false]);
        FamilyLink::factory()->create([
            'guardian_user_id'    => $guardian->id,
            'dependent_patient_id'=> $dependent->id,
            'status'              => 'active',
        ]);

        $listener = new \App\Listeners\NotifyGuardiansOfPatientEvent();

        // Create a mock appointment object that has wasChanged method
        $appointment = new class {
            public string $patient_id = '';
            public bool $statusChanged = false;

            public function wasChanged(string $attr): bool
            {
                return $attr === 'status' && $this->statusChanged;
            }
        };
        $appointment->patient_id = $dependent->id;

        // Test 1: When status was NOT changed, no notification should be sent
        $appointment->statusChanged = false;
        $listener->handleAppointmentUpdated($appointment);
        \Illuminate\Support\Facades\Notification::assertNothingSent();

        // Test 2: When status WAS changed, notification should be sent
        $appointment->statusChanged = true;
        $listener->handleAppointmentUpdated($appointment);
        \Illuminate\Support\Facades\Notification::assertSentTo(
            $guardian,
            \App\Notifications\FamilyEventNotification::class
        );
    }

    public function test_store_dependent_creates_audit_log_entry(): void
    {
        $guardian = User::factory()->create();
        // Give guardian a patient record so country_code resolution works
        $guardianPatient = Patient::factory()->create(['is_demo' => false]);
        $guardian->update(['patient_id' => $guardianPatient->id]);

        $this->actingAs($guardian)
            ->withSession($this->session)
            ->post(route('portals.patient.family.store'), [
                'first_name'   => 'Junior',
                'last_name'    => 'Test',
                'date_of_birth'=> '2015-01-01',
                'sex'          => 'male',
                'relationship' => 'parent',
                'access_level' => 'full',
            ]);

        $this->assertDatabaseHas('audit_events', [
            'actor_id'    => $guardian->id,
            'action_type' => 'guardian_link_created',
        ]);
    }

    public function test_guardian_switch_creates_audit_log_entry(): void
    {
        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create(['is_demo' => false]);
        \App\Models\FamilyLink::factory()->create([
            'guardian_user_id'    => $guardian->id,
            'dependent_patient_id'=> $dependent->id,
            'status'              => 'active',
        ]);

        $this->actingAs($guardian)
            ->withSession($this->session)
            ->post(route('portals.patient.family.switch', $dependent->id));

        $this->assertDatabaseHas('audit_events', [
            'patient_id'  => $dependent->id,
            'action_type' => 'guardian_switch_to',
        ]);
    }

    public function test_revoke_creates_audit_log_entry(): void
    {
        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create(['is_demo' => false]);
        $link = \App\Models\FamilyLink::factory()->create([
            'guardian_user_id'    => $guardian->id,
            'dependent_patient_id'=> $dependent->id,
            'status'              => 'active',
        ]);

        $this->actingAs($guardian)
            ->withSession($this->session)
            ->post(route('portals.patient.family.revoke', $link->id));

        $this->assertDatabaseHas('audit_events', [
            'patient_id'  => $dependent->id,
            'action_type' => 'guardian_link_revoked',
        ]);
    }

    public function test_update_saves_false_for_unchecked_notification_channels(): void
    {
        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create(['is_demo' => false]);
        $link = \App\Models\FamilyLink::factory()->create([
            'guardian_user_id'    => $guardian->id,
            'dependent_patient_id'=> $dependent->id,
            'status'              => 'active',
            'notification_prefs'  => [],
        ]);

        // Submit with all lab_result channels absent (unchecked)
        // appointment portal checked, email/sms unchecked
        $this->actingAs($guardian)
            ->withSession($this->session)
            ->post(route('portals.patient.family.update', $link->id), [
                'relationship'       => 'parent',
                'access_level'       => 'full',
                'notification_prefs' => [
                    'appointment' => ['portal' => '1'],
                    // lab_result entirely absent (all unchecked)
                    // appointment email/sms absent (unchecked)
                ],
            ]);

        $link->refresh();
        $prefs = $link->notification_prefs;

        // lab_result: all channels should be stored as false (not missing)
        $this->assertFalse((bool) ($prefs['lab_result']['portal'] ?? 'MISSING'), 'lab_result portal should be false');
        $this->assertFalse((bool) ($prefs['lab_result']['email']  ?? 'MISSING'), 'lab_result email should be false');
        $this->assertFalse((bool) ($prefs['lab_result']['sms']    ?? 'MISSING'), 'lab_result sms should be false');

        // appointment portal: checked → true
        $this->assertTrue((bool) ($prefs['appointment']['portal'] ?? false), 'appointment portal should be true');

        // appointment email/sms: unchecked → false (not missing)
        $this->assertFalse((bool) ($prefs['appointment']['email'] ?? 'MISSING'), 'appointment email should be false');
        $this->assertFalse((bool) ($prefs['appointment']['sms']   ?? 'MISSING'), 'appointment sms should be false');
    }
}
