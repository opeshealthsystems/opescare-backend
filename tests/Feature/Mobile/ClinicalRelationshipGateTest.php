<?php

namespace Tests\Feature\Mobile;

use App\Models\Appointment;
use App\Models\CareTeamMember;
use App\Models\ConsentGrant;
use App\Models\Facility;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\User;
use App\Support\ClinicalRelationshipGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The ONE care-relationship rule (App\Support\ClinicalRelationshipGate): on
 * what basis a clinician may open, write to, or order against a patient's
 * record — and when none exists.
 *
 * The chart, the clinician's messaging and the lab order all call this class;
 * their own tests prove the wire (403 NO_CLINICAL_RELATIONSHIP and so on).
 * This file proves the rule itself, basis by basis.
 */
class ClinicalRelationshipGateTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private Facility $otherFacility;
    private User $clinician;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility      = Facility::factory()->create(['name' => 'Bafoussam Regional Hospital']);
        $this->otherFacility = Facility::factory()->create();
        $this->clinician     = User::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
        $this->patient       = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
    }

    private function evaluate(?string $facilityId = null, ?string $clinicianId = null): ClinicalRelationshipGate
    {
        return ClinicalRelationshipGate::evaluate(
            $clinicianId ?? $this->clinician->id,
            $facilityId ?? $this->facility->id,
            $this->patient->id,
        );
    }

    private function grant(Facility $facility, string $status = 'active', ?\DateTimeInterface $expiresAt = null): ConsentGrant
    {
        return ConsentGrant::create([
            'patient_id'        => $this->patient->id,
            'facility_id'       => $facility->id,
            'authorizing_actor' => 'patient',
            'scope'             => ['patient_identity', 'orders'],
            'status'            => $status,
            'expires_at'        => $expiresAt ?? now()->addDay(),
        ]);
    }

    // ── basis 4: an episode at the clinician's own facility ───────────────

    public function test_a_live_episode_at_the_token_facility_grants_access(): void
    {
        // A front-desk booking with no clinician assigned: nothing ties the
        // patient to THIS clinician, only to the facility.
        Appointment::factory()->create([
            'patient_id'   => $this->patient->id,
            'facility_id'  => $this->facility->id,
            'provider_id'  => null,
            'scheduled_at' => now()->addDays(2),
        ]);

        $verdict = $this->evaluate();

        $this->assertTrue($verdict->holds());
        $this->assertSame(ClinicalRelationshipGate::FACILITY_EPISODE, $verdict->basis);
        $this->assertSame('treatment:facility_episode', $verdict->purpose());
    }

    public function test_an_episode_at_another_facility_does_not_count(): void
    {
        Appointment::factory()->create([
            'patient_id'   => $this->patient->id,
            'facility_id'  => $this->otherFacility->id,
            'provider_id'  => null,
            'scheduled_at' => now()->addDays(2),
        ]);

        $this->assertFalse($this->evaluate()->holds());
        $this->assertTrue(ClinicalRelationshipGate::hasFacilityEpisode($this->patient->id, $this->otherFacility->id));
        $this->assertFalse(ClinicalRelationshipGate::hasFacilityEpisode($this->patient->id, $this->facility->id));
    }

    public function test_an_episode_older_than_the_lookback_is_history_not_care(): void
    {
        Appointment::factory()->create([
            'patient_id'   => $this->patient->id,
            'facility_id'  => $this->facility->id,
            'provider_id'  => null,
            'scheduled_at' => now()->subDays(ClinicalRelationshipGate::FACILITY_EPISODE_LOOKBACK_DAYS + 1),
        ]);

        $this->assertFalse($this->evaluate()->holds());
    }

    // ── basis 5: a consent grant to the facility ──────────────────────────

    public function test_an_active_consent_grant_to_the_token_facility_grants_access(): void
    {
        $this->grant($this->facility);

        $verdict = $this->evaluate();

        $this->assertTrue($verdict->holds());
        $this->assertSame(ClinicalRelationshipGate::CONSENT_GRANT, $verdict->basis);
        $this->assertSame('treatment:consent_grant', $verdict->purpose());
        $this->assertTrue(ClinicalRelationshipGate::hasActiveConsentGrant($this->patient->id, $this->facility->id));
    }

    public function test_an_expired_consent_grant_refuses(): void
    {
        $this->grant($this->facility, 'active', now()->subMinute());

        $verdict = $this->evaluate();

        $this->assertFalse($verdict->holds());
        $this->assertNull($verdict->basis);
        $this->assertFalse(ClinicalRelationshipGate::hasActiveConsentGrant($this->patient->id, $this->facility->id));
    }

    public function test_a_revoked_or_foreign_consent_grant_refuses(): void
    {
        $this->grant($this->facility, 'revoked');
        $this->grant($this->otherFacility);

        $this->assertFalse($this->evaluate()->holds());
        $this->assertFalse(ClinicalRelationshipGate::hasActiveConsentGrant($this->patient->id, $this->facility->id));
        $this->assertFalse(ClinicalRelationshipGate::hasActiveConsentGrant($this->patient->id, null));
    }

    // ── nothing at all ────────────────────────────────────────────────────

    public function test_neither_an_episode_nor_a_grant_refuses(): void
    {
        $verdict = $this->evaluate();

        $this->assertFalse($verdict->holds());
        $this->assertNull($verdict->basis);
        $this->assertSame(ClinicalRelationshipGate::NO_RELATIONSHIP_PURPOSE, $verdict->purpose());
        $this->assertSame('treatment:no_relationship', $verdict->purpose());
    }

    public function test_no_clinician_never_holds_even_with_a_grant_in_place(): void
    {
        $this->grant($this->facility);

        $this->assertFalse(ClinicalRelationshipGate::evaluate(null, $this->facility->id, $this->patient->id)->holds());
        $this->assertFalse(ClinicalRelationshipGate::evaluate('', $this->facility->id, $this->patient->id)->holds());
    }

    public function test_no_facility_leaves_only_the_personal_bases(): void
    {
        // A grant and an episode both exist — but the token names no facility.
        $this->grant($this->facility);
        Appointment::factory()->create([
            'patient_id'   => $this->patient->id,
            'facility_id'  => $this->facility->id,
            'provider_id'  => null,
            'scheduled_at' => now()->addDay(),
        ]);

        $this->assertFalse(ClinicalRelationshipGate::evaluate($this->clinician->id, null, $this->patient->id)->holds());

        // A personal act still does.
        LabOrder::factory()->create([
            'patient_id'  => $this->patient->id,
            'facility_id' => $this->facility->id,
            'ordered_by'  => $this->clinician->id,
        ]);

        $this->assertSame(
            ClinicalRelationshipGate::TREATING_CLINICIAN,
            ClinicalRelationshipGate::evaluate($this->clinician->id, null, $this->patient->id)->basis
        );
    }

    // ── the personal bases, and their precedence ──────────────────────────

    public function test_the_first_basis_that_holds_wins_in_the_chart_order(): void
    {
        // Facility episode + consent grant + treating clinician + care team + appointment.
        $this->grant($this->facility);
        LabOrder::factory()->create(['patient_id' => $this->patient->id, 'facility_id' => $this->facility->id, 'ordered_by' => $this->clinician->id]);
        $this->assertSame(ClinicalRelationshipGate::TREATING_CLINICIAN, $this->evaluate()->basis);

        CareTeamMember::create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->clinician->id,
            'role'        => 'primary_care',
            'is_active'   => true,
            'joined_at'   => now(),
        ]);
        $this->assertSame(ClinicalRelationshipGate::CARE_TEAM, $this->evaluate()->basis);

        Appointment::factory()->create([
            'patient_id'  => $this->patient->id,
            'facility_id' => $this->otherFacility->id,
            'provider_id' => $this->clinician->id,
        ]);
        $this->assertSame(ClinicalRelationshipGate::APPOINTMENT, $this->evaluate()->basis);
        $this->assertSame('treatment:appointment', $this->evaluate()->purpose());
    }

    public function test_a_care_team_membership_that_ended_does_not_count(): void
    {
        CareTeamMember::create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->clinician->id,
            'role'        => 'primary_care',
            'is_active'   => false,
            'joined_at'   => now()->subYear(),
            'left_at'     => now()->subMonth(),
        ]);

        $this->assertFalse($this->evaluate()->holds());
    }

    public function test_the_five_bases_are_named_in_the_order_they_are_checked(): void
    {
        $this->assertSame(
            ['appointment', 'care_team', 'treating_clinician', 'facility_episode', 'consent_grant'],
            ClinicalRelationshipGate::BASES
        );
    }
}
