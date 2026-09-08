<?php

namespace Tests\Feature\Admin;

use App\Models\AllergyRecord;
use App\Models\AuditEvent;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The admin "remove" buttons must never destroy a record.
 *
 * `users` and `facilities` carry no SoftDeletes trait, and PostgreSQL cascades
 * from both: 47 tables from users (allergy_records, clinical_notes, diagnoses,
 * problem_lists, imaging_orders and access_logs among them) and 61 from
 * facilities (visits, prescriptions, lab_orders, consent_grants). A hard DELETE
 * on either therefore erased clinical records and the audit trail proving who
 * had opened them.
 *
 * These tests pin the property, not the implementation: after the admin removes
 * a provider or a facility, the clinical rows and the access log are STILL
 * THERE, and the removed thing is merely inactive.
 */
class AdminRemovalNeverDestroysRecordsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'super_admin'], ['description' => 'Super Admin'])->id,
            'status'  => 'active',
        ]);
    }

    private function accessLogFor(string $actorId, string $patientId): string
    {
        $id = (string) Str::uuid();

        DB::table('access_logs')->insert([
            'id'            => $id,
            'patient_id'    => $patientId,
            'actor_id'      => $actorId,
            'actor_type'    => 'user',
            'purpose'       => 'treatment',
            'data_category' => 'clinical',
            'resource_type' => 'AllergyRecord',
            'access_type'   => 'read',
            'created_at'    => now(),
        ]);

        return $id;
    }

    // -- Removing a provider ---------------------------------------------

    public function test_removing_a_provider_keeps_their_patients_allergy_records_and_the_access_log(): void
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create(['status' => 'active']);

        $allergy = AllergyRecord::factory()->create([
            'patient_id'  => $patient->id,
            'provider_id' => $provider->id,
            'substance'   => 'Penicillin',
            'severity'    => 'high',
        ]);

        $logId = $this->accessLogFor($provider->id, $patient->id);

        $this->actingAs($this->admin())
            ->withSession(['mfa.verified' => true])
            ->delete(route('admin.users.destroy', $provider->id))
            ->assertRedirect(route('admin.users.index'));

        // The record of a life-threatening allergy is still there.
        $this->assertDatabaseHas('allergy_records', [
            'id'          => $allergy->id,
            'provider_id' => $provider->id,
            'substance'   => 'Penicillin',
        ]);

        // So is the evidence of who opened the chart.
        $this->assertDatabaseHas('access_logs', ['id' => $logId, 'actor_id' => $provider->id]);

        // And the account itself: present, but inactive.
        $this->assertDatabaseHas('users', ['id' => $provider->id, 'status' => 'suspended']);
        $this->assertNotNull(User::find($provider->id));
    }

    public function test_a_removed_provider_can_no_longer_authenticate(): void
    {
        $provider = User::factory()->create([
            'status'   => 'active',
            'email'    => 'removed.provider@example.test',
            'password' => bcrypt('correct-horse-battery'),
        ]);

        $this->actingAs($this->admin())->withSession(['mfa.verified' => true])->delete(route('admin.users.destroy', $provider->id));

        $this->flushSession();
        auth()->logout();

        $this->post(route('login.submit'), [
            'email'    => 'removed.provider@example.test',
            'password' => 'correct-horse-battery',
        ])->assertRedirect(route('account.suspended'));

        $this->assertGuest();
    }

    public function test_removing_a_provider_writes_an_audit_event_whose_actor_is_a_uuid(): void
    {
        $admin    = $this->admin();
        $provider = User::factory()->create(['status' => 'active']);

        $this->actingAs($admin)->withSession(['mfa.verified' => true])->delete(route('admin.users.destroy', $provider->id));

        $event = AuditEvent::where('action_type', 'admin_user_deactivated')
            ->where('resource_id', $provider->id)
            ->first();

        $this->assertNotNull($event, 'deactivating a user must be audited');
        // audit_events.actor_id is a uuid column -- a non-uuid string here has
        // broken this codebase before.
        $this->assertTrue(Str::isUuid($event->actor_id));
        $this->assertSame($admin->id, $event->actor_id);
    }

    public function test_an_admin_cannot_remove_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->withSession(['mfa.verified' => true])->delete(route('admin.users.destroy', $admin->id));

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'status' => 'active']);
    }

    // -- Removing a facility ---------------------------------------------

    public function test_removing_a_facility_keeps_its_visits_prescriptions_and_the_patients_access(): void
    {
        $facility = Facility::factory()->create(['status' => 'active']);
        $patient  = Patient::factory()->create(['facility_id' => $facility->id]);
        $provider = User::factory()->create();

        $visitId = (string) Str::uuid();
        DB::table('visits')->insert([
            'id'          => $visitId,
            'patient_id'  => $patient->id,
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'visit_type'  => 'outpatient',
            'status'      => 'closed',
            'started_at'  => now()->subDay(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $prescription = Prescription::factory()->create([
            'patient_id'    => $patient->id,
            'facility_id'   => $facility->id,
            'prescribed_by' => $provider->id,
            'status'        => 'dispensed',
        ]);

        $logId = $this->accessLogFor($provider->id, $patient->id);

        $this->actingAs($this->admin())
            ->withSession(['mfa.verified' => true])
            ->delete(route('admin.facilities.destroy', $facility->id), ['reason' => 'Closed by MINSANTE'])
            ->assertRedirect(route('admin.facilities.index'));

        // Nothing the facility recorded is gone, and every row keeps its
        // facility_id so the provenance of the care is still readable.
        $this->assertDatabaseHas('visits', ['id' => $visitId, 'facility_id' => $facility->id]);
        $this->assertDatabaseHas('prescriptions', ['id' => $prescription->id, 'facility_id' => $facility->id]);
        $this->assertDatabaseHas('access_logs', ['id' => $logId]);

        // The patient -- and their record -- survive the facility entirely.
        $this->assertDatabaseHas('patients', ['id' => $patient->id]);

        $this->assertDatabaseHas('facilities', ['id' => $facility->id, 'status' => 'suspended']);
    }

    public function test_removing_a_facility_with_patients_is_allowed_and_audited(): void
    {
        $facility = Facility::factory()->create(['status' => 'active']);
        Patient::factory()->create(['facility_id' => $facility->id]);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->withSession(['mfa.verified' => true])
            ->delete(route('admin.facilities.destroy', $facility->id), ['reason' => 'Licence revoked'])
            ->assertRedirect(route('admin.facilities.index'));

        $this->assertDatabaseHas('facilities', ['id' => $facility->id, 'status' => 'suspended']);

        $event = AuditEvent::where('action_type', 'admin_facility_deactivated')
            ->where('resource_id', $facility->id)
            ->first();

        $this->assertNotNull($event);
        $this->assertTrue($event->actor_id === null || Str::isUuid($event->actor_id));
        $this->assertSame('Licence revoked', $event->reason);
    }
}
