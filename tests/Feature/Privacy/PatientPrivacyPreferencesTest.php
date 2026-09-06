<?php

namespace Tests\Feature\Privacy;

use App\Models\Appointment;
use App\Models\ConsentGrant;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Modules\Governance\Services\EmergencyAccessService;
use App\Support\ClinicalRelationshipGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The two switches on the patient's privacy page have to mean something.
 *
 * They were written by the profile form and read by nobody — not the
 * relationship gate, not the consent service, not break-glass. The page told
 * the patient "Providers can only see a masked preview without your explicit
 * consent" and "Permit audited break-glass access", and both were promises the
 * platform did not keep. A privacy control that does nothing is worse than no
 * control at all, because the patient stops asking.
 *
 * Both default to ON, matching the switches the portal shows checked. A
 * preference the patient sees as on must behave as on.
 */
class PatientPrivacyPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private User $clinician;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility  = Facility::factory()->create();
        $this->clinician = User::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
        $this->patient   = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);

        // An implicit basis: the patient is on this clinician's own schedule.
        Appointment::factory()->create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->clinician->id,
            'facility_id' => $this->facility->id,
        ]);
    }

    private function evaluate(): ClinicalRelationshipGate
    {
        return ClinicalRelationshipGate::evaluate(
            $this->clinician->id,
            $this->facility->id,
            $this->patient->id,
        );
    }

    private function prefer(array $preferences): void
    {
        $this->patient->forceFill(['privacy_preferences' => $preferences])->save();
        $this->patient->refresh();
    }

    // ── Require consent for the full record ──────────────────────────────

    public function test_an_implicit_basis_opens_only_the_preview_by_default(): void
    {
        $verdict = $this->evaluate();

        $this->assertTrue($verdict->holds(), 'the appointment is still a care relationship');
        $this->assertSame(ClinicalRelationshipGate::APPOINTMENT, $verdict->basis);
        $this->assertFalse(
            $verdict->coversFullRecord(),
            'an appointment is not the patient saying yes, so it must not open the whole record'
        );
    }

    public function test_an_explicit_consent_grant_opens_the_full_record(): void
    {
        ConsentGrant::create([
            'patient_id'        => $this->patient->id,
            'facility_id'       => $this->facility->id,
            'authorizing_actor' => 'patient',
            'scope'             => ['patient_identity'],
            'status'            => 'active',
            'expires_at'        => now()->addMonth(),
        ]);

        // The grant must win even though the appointment is checked first, so
        // the verdict is re-evaluated with the appointment removed as well.
        Appointment::where('patient_id', $this->patient->id)->delete();

        $verdict = $this->evaluate();

        $this->assertSame(ClinicalRelationshipGate::CONSENT_GRANT, $verdict->basis);
        $this->assertTrue($verdict->coversFullRecord(), 'an explicit grant IS the consent the switch asks for');
    }

    public function test_a_patient_who_turns_the_switch_off_is_read_in_full(): void
    {
        $this->prefer(['require_consent_for_full_record' => false]);

        $verdict = $this->evaluate();

        $this->assertTrue($verdict->holds());
        $this->assertTrue(
            $verdict->coversFullRecord(),
            'a patient who has said they do not require consent must not be masked'
        );
    }

    public function test_the_switch_defaults_to_on_when_nothing_is_recorded(): void
    {
        $this->prefer([]);

        $this->assertTrue($this->patient->requiresConsentForFullRecord());
        $this->assertFalse($this->evaluate()->coversFullRecord());
    }

    public function test_no_relationship_still_covers_nothing(): void
    {
        Appointment::where('patient_id', $this->patient->id)->delete();

        $verdict = $this->evaluate();

        $this->assertFalse($verdict->holds());
        $this->assertFalse($verdict->coversFullRecord(), 'a refusal can never cover the record');
    }

    // ── Allow emergency access ───────────────────────────────────────────

    public function test_break_glass_is_refused_when_the_patient_turned_it_off(): void
    {
        $this->prefer(['emergency_access_allowed' => false]);

        $result = app(EmergencyAccessService::class)->openBreakGlass(
            $this->patient,
            $this->facility->id,
            $this->clinician->id,
            'Unconscious in resus',
        );

        $this->assertSame(EmergencyAccessService::STATUS_WITHHELD, $result['status']);
        $this->assertNull($result['event'], 'no grant may be minted against a patient who said no');
    }

    public function test_break_glass_still_works_when_the_patient_allows_it(): void
    {
        $this->prefer(['emergency_access_allowed' => true]);

        $result = app(EmergencyAccessService::class)->openBreakGlass(
            $this->patient,
            $this->facility->id,
            $this->clinician->id,
            'Unconscious in resus',
        );

        $this->assertNotSame(EmergencyAccessService::STATUS_WITHHELD, $result['status']);
        $this->assertNotNull($result['event']);
    }

    public function test_emergency_access_defaults_to_allowed(): void
    {
        $this->assertTrue($this->patient->allowsEmergencyAccess());

        $result = app(EmergencyAccessService::class)->openBreakGlass(
            $this->patient,
            $this->facility->id,
            $this->clinician->id,
            'Unconscious in resus',
        );

        $this->assertNotSame(EmergencyAccessService::STATUS_WITHHELD, $result['status']);
    }

    public function test_a_grant_survives_an_earlier_basis_winning_the_race(): void
    {
        // The gate returns the FIRST basis that holds, and the appointment is
        // checked before the consent grant. The scope must not depend on that
        // ordering: a clinician who has been given consent does not lose the
        // record the moment they also acquire a nearer reason to act.
        ConsentGrant::create([
            'patient_id'        => $this->patient->id,
            'facility_id'       => $this->facility->id,
            'authorizing_actor' => 'patient',
            'scope'             => ['patient_identity'],
            'status'            => 'active',
            'expires_at'        => now()->addMonth(),
        ]);

        $verdict = $this->evaluate();

        $this->assertSame(ClinicalRelationshipGate::APPOINTMENT, $verdict->basis, 'the nearer basis still wins');
        $this->assertTrue($verdict->coversFullRecord(), 'but the consent the patient gave still stands');
    }

}
