<?php

namespace Tests\Feature\Referral;

use App\Models\ConsentGrant;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Patient;
use App\Models\ReferralCase;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The web referral desk (C-3 / HC-3). The lifecycle itself is
 * ReferralDeskService's and is already covered through the clinician app by
 * tests/Feature/Mobile/DoctorReferralTest; these tests hold the web door:
 * exact Health ID, facility scope from the session, party rules, reasons and
 * the patient being told.
 */
class ReferralDeskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{0: User, 1: Facility} */
    private function staff(string $roleName = 'doctor', ?Facility $facility = null, ?string $name = null): array
    {
        $role     = Role::where('name', $roleName)->firstOrFail();
        $facility ??= Facility::factory()->create(array_filter(['type' => 'hospital', 'name' => $name]));

        $user = User::factory()->create([
            'role_id'             => $role->id,
            'primary_facility_id' => $facility->id,
            'status'              => 'active',
        ]);

        FacilityRoleAssignment::create([
            'user_id'     => $user->id,
            'facility_id' => $facility->id,
            'role_id'     => $role->id,
            'is_active'   => true,
            'assigned_at' => now(),
        ]);

        return [$user, $facility];
    }

    private function as(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    /** A patient this facility holds a full consent grant for. */
    private function patientWithGrant(Facility $facility): Patient
    {
        $patient = Patient::factory()->create([
            'privacy_preferences' => ['require_consent_for_full_record' => false],
        ]);

        ConsentGrant::create([
            'patient_id'        => $patient->id,
            'facility_id'       => $facility->id,
            'authorizing_actor' => 'patient',
            'scope'             => ['full'],
            'status'            => 'active',
            'expires_at'        => now()->addMonth(),
        ]);

        return $patient;
    }

    /** @return array{0: User, 1: Facility, 2: User, 3: Facility, 4: Patient} */
    private function twoHospitals(): array
    {
        [$doctor, $csi]       = $this->staff('nurse', null, 'CSI Nkolbisson');
        [$specialist, $district] = $this->staff('doctor', null, 'Hopital de District de Biyem-Assi');

        return [$doctor, $csi, $specialist, $district, $this->patientWithGrant($csi)];
    }

    private function draft(User $user, Facility $facility, Patient $patient, Facility $to): ReferralCase
    {
        // Two drafts in the same second tie on created_at, so "latest" could
        // return the earlier one. Return the row this call actually created.
        $before = ReferralCase::where('patient_id', $patient->id)->pluck('id')->all();

        $this->as($user, $facility)->post('/facility/referrals', [
            'health_id'               => $patient->health_id,
            'receiving_facility_name' => $to->name,
            'urgency'                 => 'urgent',
            'reason'                  => 'Suspected appendicitis',
            'clinical_summary'        => 'RLQ pain 12h',
        ])->assertRedirect();

        return ReferralCase::where('patient_id', $patient->id)->whereNotIn('id', $before)->firstOrFail();
    }

    public function test_a_referral_is_raised_by_exact_health_id_and_a_name_finds_nothing(): void
    {
        [$nurse, $csi, , $district, $patient] = $this->twoHospitals();

        $this->as($nurse, $csi)->get('/facility/referrals/new')->assertOk();

        // A name — or a partial Health ID — never resolves a patient.
        foreach ([$patient->first_name . ' ' . $patient->last_name, substr($patient->health_id, 0, 8)] as $attempt) {
            $this->as($nurse, $csi)->post('/facility/referrals', [
                'health_id' => $attempt, 'receiving_facility_name' => $district->name,
                'urgency' => 'routine', 'reason' => 'x',
            ])->assertSessionHasErrors('health_id');
        }
        $this->assertSame(0, ReferralCase::count());

        // A body facility id is not read: the referring facility is the session's.
        $this->as($nurse, $csi)->post('/facility/referrals', [
            'health_id'               => strtolower($patient->health_id),
            'receiving_facility_name' => strtoupper($district->name),
            'referring_facility_id'   => $district->id,
            'urgency'                 => 'urgent',
            'reason'                  => 'Suspected appendicitis',
        ])->assertRedirect();

        $referral = ReferralCase::firstOrFail();
        $this->assertSame($csi->id, $referral->referring_facility_id);
        $this->assertSame($district->id, $referral->receiving_facility_id);
        $this->assertSame((string) $nurse->id, (string) $referral->referring_provider_id);
        $this->assertSame('draft', $referral->status);

        $this->as($nurse, $csi)->get('/facility/referrals?tab=sent')->assertOk()->assertSee($patient->health_id);
    }

    public function test_a_patient_with_no_basis_cannot_be_referred_and_the_refusal_is_logged(): void
    {
        [$nurse, $csi, , $district] = $this->twoHospitals();
        $stranger = Patient::factory()->create(['privacy_preferences' => ['require_consent_for_full_record' => false]]);

        $this->as($nurse, $csi)->post('/facility/referrals', [
            'health_id' => $stranger->health_id, 'receiving_facility_name' => $district->name,
            'urgency' => 'routine', 'reason' => 'x',
        ])->assertSessionHasErrors('health_id');

        $this->assertSame(0, ReferralCase::count());
        $this->assertDatabaseHas('access_logs', [
            'patient_id' => $stranger->id, 'actor_id' => $nurse->id, 'access_type' => 'denied',
        ]);
    }

    public function test_sent_referral_reaches_the_receiving_facility_which_accepts_and_completes_and_the_patient_is_told(): void
    {
        [$nurse, $csi, $specialist, $district, $patient] = $this->twoHospitals();
        $referral = $this->draft($nurse, $csi, $patient, $district);

        // A draft is invisible to the receiving side's actions; the receiver cannot send.
        $this->as($specialist, $district)->post("/facility/referrals/{$referral->id}/send")->assertForbidden();

        $this->as($nurse, $csi)->post("/facility/referrals/{$referral->id}/send")
            ->assertRedirect("/facility/referrals/{$referral->id}")
            ->assertSessionHas('record_access');
        $this->assertSame('sent', $referral->fresh()->status);

        $this->as($specialist, $district)->get('/facility/referrals?tab=received')
            ->assertOk()->assertSee($patient->health_id);
        $this->as($specialist, $district)->get("/facility/referrals/{$referral->id}")
            ->assertOk()->assertSee(__('referral_desk.action.accept'));

        // The referring side may not accept its own referral.
        $this->as($nurse, $csi)->post("/facility/referrals/{$referral->id}/accept")->assertForbidden();

        $this->as($specialist, $district)->post("/facility/referrals/{$referral->id}/accept")->assertRedirect();
        $this->assertSame('accepted', $referral->fresh()->status);

        $this->as($specialist, $district)->post("/facility/referrals/{$referral->id}/complete", ['feedback' => 'Seen, discharged'])
            ->assertRedirect();
        $this->assertSame('completed', $referral->fresh()->status);

        foreach (['referral_sent', 'referral_accepted', 'referral_completed'] as $type) {
            $this->assertTrue(
                DB::table('notifications')->where('notifiable_id', $patient->id)->where('type', $type)->exists(),
                "patient was not notified: {$type}"
            );
        }
    }

    public function test_reject_and_cancel_need_a_reason(): void
    {
        [$nurse, $csi, $specialist, $district, $patient] = $this->twoHospitals();
        $referral = $this->draft($nurse, $csi, $patient, $district);
        $this->as($nurse, $csi)->post("/facility/referrals/{$referral->id}/send");

        $this->as($specialist, $district)->post("/facility/referrals/{$referral->id}/reject", [])
            ->assertSessionHasErrors('reason');
        $this->assertSame('sent', $referral->fresh()->status);

        $this->as($specialist, $district)->post("/facility/referrals/{$referral->id}/reject", ['reason' => 'No surgeon on call'])
            ->assertRedirect();
        $fresh = $referral->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame('No surgeon on call', $fresh->rejection_reason);
        $this->assertTrue(DB::table('notifications')->where('notifiable_id', $patient->id)->where('type', 'referral_rejected')->exists());

        // Terminal: nothing more moves, and nothing was deleted.
        $this->as($nurse, $csi)->post("/facility/referrals/{$referral->id}/cancel", ['reason' => 'late'])
            ->assertSessionHas('error');
        $this->assertSame('rejected', $referral->fresh()->status);

        $second = $this->draft($nurse, $csi, $patient, $district);
        $this->as($nurse, $csi)->post("/facility/referrals/{$second->id}/cancel", [])->assertSessionHasErrors('reason');
        $this->as($nurse, $csi)->post("/facility/referrals/{$second->id}/cancel", ['reason' => 'Patient improved'])->assertRedirect();
        $this->assertSame('cancelled', $second->fresh()->status);
    }

    public function test_a_referral_between_two_other_facilities_is_not_found_and_a_pharmacist_is_refused(): void
    {
        [$nurse, $csi, , $district, $patient] = $this->twoHospitals();
        $referral = $this->draft($nurse, $csi, $patient, $district);

        [$outsider, $elsewhere] = $this->staff('doctor');

        $this->as($outsider, $elsewhere)->get("/facility/referrals/{$referral->id}")->assertNotFound();
        $this->as($outsider, $elsewhere)->post("/facility/referrals/{$referral->id}/cancel", ['reason' => 'x'])->assertNotFound();
        $this->as($outsider, $elsewhere)->get('/facility/referrals?tab=sent')->assertOk()->assertDontSee($patient->health_id);
        $this->assertSame('draft', $referral->fresh()->status);

        [$pharmacist, $pharmacy] = $this->staff('pharmacist');
        $this->as($pharmacist, $pharmacy)->get('/facility/referrals')->assertForbidden();
    }
}
