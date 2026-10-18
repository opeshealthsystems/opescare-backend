<?php

namespace Tests\Feature\Documents;

use App\Models\AccessLog;
use App\Models\AllergyRecord;
use App\Models\Appointment;
use App\Models\ConsentGrant;
use App\Models\DocumentVerification;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Patient-side documents from the missing-documents pass (specs 24-26, 31, 32).
 * The holder is always the signed-in patient; another patient's appointment
 * answers 404 and a staff account without a patient profile is refused.
 */
class NewPatientDocumentsTest extends TestCase
{
    use NewDocumentSupport;
    use RefreshDatabase;

    private Patient $holder;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBasics();

        $this->holder = $this->patient();
        $this->user   = User::factory()->create([
            'status' => 'active', 'is_demo' => (bool) config('demo.enabled'),
            'role_id' => Role::where('name', 'patient')->value('id'),
        ]);
        $this->user->forceFill(['patient_id' => $this->holder->id])->save();
    }

    private function asHolder(): static
    {
        return $this->actingAs($this->user)->withSession(['mfa.verified' => true]);
    }

    private function sealed(string $type): void
    {
        $row = DocumentVerification::where('patient_id', $this->holder->id)->where('document_type', $type)->first();
        $this->assertNotNull($row, "{$type}: no verification row");
        $this->assertSame(64, strlen((string) $row->sha256));
    }

    public function test_health_passport_and_emergency_summary(): void
    {
        AllergyRecord::create(['patient_id' => $this->holder->id, 'provider_id' => $this->user->id, 'substance' => 'Penicillin', 'severity' => 'severe', 'status' => 'active']);

        $this->capture('documents.new.health-passport');
        $this->assertPdfResponse($this->asHolder()->get(route('documents.new.passport')));
        $html = $this->renderedHtml('documents.new.health-passport');
        $this->assertStringContainsString('Penicillin', $html);
        $this->assertStringContainsString((string) $this->holder->health_id, $html);
        $this->sealed('health_passport');

        $this->capture('documents.new.emergency-summary');
        $this->assertPdfResponse($this->asHolder()->get(route('documents.new.emergency')));
        $html = $this->renderedHtml('documents.new.emergency-summary');
        $this->assertStringContainsString('Penicillin', $html);
        $this->sealed('emergency_summary');
    }

    public function test_appointment_slip_is_the_holders_only(): void
    {
        $facility = Facility::factory()->create(['type' => 'hospital', 'name' => 'Hopital Laquintinie']);
        $mine = Appointment::create(['patient_id' => $this->holder->id, 'facility_id' => $facility->id,
            'appointment_type' => 'consultation', 'status' => 'confirmed', 'scheduled_at' => now()->addDays(2),
            'reason' => 'Diabetes review', 'booked_by_type' => 'patient']);
        $theirs = Appointment::create(['patient_id' => $this->patient()->id, 'facility_id' => $facility->id,
            'appointment_type' => 'consultation', 'status' => 'confirmed', 'scheduled_at' => now()->addDays(2),
            'booked_by_type' => 'patient']);

        $this->capture('documents.new.appointment-slip');
        $this->assertPdfResponse($this->asHolder()->get(route('documents.new.appointment', $mine->id)));
        $html = $this->renderedHtml('documents.new.appointment-slip');
        $this->assertStringContainsString('Diabetes review', $html);
        $this->assertStringContainsString('Hopital Laquintinie', $html);
        $this->sealed('appointment_slip');

        $this->asHolder()->get(route('documents.new.appointment', $theirs->id))->assertNotFound();
    }

    public function test_consent_record_and_access_log(): void
    {
        $facility = Facility::factory()->create(['type' => 'hospital', 'name' => 'CSI Nkolbisson']);
        ConsentGrant::create(['patient_id' => $this->holder->id, 'facility_id' => $facility->id, 'authorizing_actor' => 'patient',
            'scope' => ['full'], 'status' => 'active', 'expires_at' => now()->addMonth()]);
        AccessLog::create(['patient_id' => $this->holder->id, 'actor_id' => $this->user->id, 'actor_type' => 'provider',
            'facility_id' => $facility->id, 'purpose' => 'treatment:referral', 'data_category' => 'referral',
            'resource_type' => 'ReferralCase', 'access_type' => 'read', 'ip_address' => '10.9.8.7']);

        $this->capture('documents.new.consent-record');
        $this->assertPdfResponse($this->asHolder()->get(route('documents.new.consents')));
        $this->assertStringContainsString('CSI Nkolbisson', $this->renderedHtml('documents.new.consent-record'));
        $this->sealed('consent_record');

        $this->capture('documents.new.access-log');
        $this->assertPdfResponse($this->asHolder()->get(route('documents.new.access_log')));
        $html = $this->renderedHtml('documents.new.access-log');
        $this->assertStringContainsString('treatment:referral', $html);
        $this->assertStringNotContainsString('10.9.8.7', $html, 'IP must not be printed');
        $this->sealed('access_log');
    }

    public function test_staff_without_a_patient_profile_is_refused(): void
    {
        [$doctor, $facility] = $this->staff('doctor');
        $response = $this->at($doctor, $facility)->get(route('documents.new.passport'));
        $this->assertContains($response->getStatusCode(), [302, 403], 'a staff account must not get a passport');
    }
}
