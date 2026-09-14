<?php

namespace Tests\Feature\Portal;

use App\Models\Facility;
use App\Models\HealthIdQrToken;
use App\Models\Patient;
use App\Models\ReferralCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * D-B3: PatientPortalController::referrals() and reportLostCard() existed
 * with no route, so neither was reachable. These hold the two routes, the
 * sidebar link, and the one safety property of a lost-card report — the
 * Health ID itself is never touched, only the QR tokens.
 */
class PatientReferralsAndLostCardTest extends TestCase
{
    use RefreshDatabase;

    private function patientUser(): array
    {
        $patient = Patient::factory()->create(['health_id' => 'OC-REF-001', 'is_demo' => false]);
        $user = User::factory()->create(['patient_id' => $patient->id]);

        return [$patient, $user];
    }

    public function test_referrals_page_lists_only_this_patients_referrals(): void
    {
        [$patient, $user] = $this->patientUser();
        $other = Patient::factory()->create(['health_id' => 'OC-REF-002', 'is_demo' => false]);
        $from = Facility::factory()->create(['name' => 'Hôpital Laquintinie']);
        $to = Facility::factory()->create(['name' => 'Hôpital Général de Douala']);

        ReferralCase::create([
            'patient_id' => $patient->id,
            'referring_facility_id' => $from->id,
            'receiving_facility_id' => $to->id,
            'urgency' => 'urgent',
            'status' => 'sent',
            'reason' => 'Cardiology review',
        ]);
        ReferralCase::create([
            'patient_id' => $other->id,
            'referring_facility_id' => $from->id,
            'receiving_facility_id' => $to->id,
            'urgency' => 'routine',
            'status' => 'sent',
            'reason' => 'NOT-MINE-MARKER',
        ]);

        $this->actingAs($user)
            ->get(route('portals.patient.referrals'))
            ->assertOk()
            ->assertSee('Cardiology review')
            ->assertSee('Hôpital Général de Douala')
            ->assertSee(route('portals.patient.referrals'), false) // sidebar link
            ->assertDontSee('NOT-MINE-MARKER');
    }

    public function test_referrals_page_is_empty_state_without_referrals(): void
    {
        [, $user] = $this->patientUser();

        $this->actingAs($user)
            ->get(route('portals.patient.referrals'))
            ->assertOk()
            ->assertSee(__('public.pat_referral_empty_title'));
    }

    public function test_referrals_requires_auth(): void
    {
        $this->get(route('portals.patient.referrals'))->assertRedirect(route('login'));
    }

    public function test_settings_page_offers_the_lost_card_form(): void
    {
        [, $user] = $this->patientUser();

        $this->actingAs($user)
            ->get(route('portals.patient.settings'))
            ->assertOk()
            ->assertSee(route('portals.patient.lost-card'), false);
    }

    public function test_lost_card_report_revokes_qr_tokens_but_keeps_the_health_id(): void
    {
        [$patient, $user] = $this->patientUser();
        $this->actingAs($user);

        // Mint a real token through the same endpoint the portal uses.
        $this->postJson(route('portals.patient.qr'))->assertOk();
        $this->assertSame(1, HealthIdQrToken::where('patient_id', $patient->id)->where('status', 'active')->count());

        $this->from(route('portals.patient.settings'))
            ->post(route('portals.patient.lost-card'), ['report_reason' => 'Wallet stolen in Akwa'])
            ->assertRedirect(route('portals.patient.settings'))
            ->assertSessionHas('success');

        $this->assertSame(0, HealthIdQrToken::where('patient_id', $patient->id)->where('status', 'active')->count());
        $this->assertSame('OC-REF-001', $patient->fresh()->health_id);
    }

    public function test_lost_card_report_requires_a_reason(): void
    {
        [, $user] = $this->patientUser();

        $this->actingAs($user)
            ->from(route('portals.patient.settings'))
            ->post(route('portals.patient.lost-card'), [])
            ->assertRedirect(route('portals.patient.settings'))
            ->assertSessionHasErrors('report_reason');
    }
}
