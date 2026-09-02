<?php

namespace Tests\Feature;

use App\Models\AppointmentSlot;
use App\Models\Facility;
use App\Models\FacilityQueue;
use App\Models\FacilitySchedule;
use App\Models\OfficialDocument;
use App\Models\Patient;
use App\Models\ProviderAvailability;
use App\Models\User;
use App\Modules\Notifications\Models\NotificationEvent;
use App\Modules\OperationalFlow\Services\PatientJourneyService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalJourneyFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_journey_links_appointment_queue_billing_receipt_document_notification_and_audit()
    {
        [$patient, $facility, $provider, $slot] = $this->journeyActors();

        $result = app(PatientJourneyService::class)->runPilotJourney([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'appointment_slot_id' => $slot->id,
            'appointment_type' => 'outpatient',
            'queue_name' => 'triage',
            'consultation' => [
                'history_of_present_illness' => 'Fever and headache',
                'examination_findings' => 'Stable, febrile',
                'treatment_plan' => 'Malaria test and antipyretic',
            ],
            'lab_result_summary' => 'Malaria rapid test released',
            'invoice_items' => [
                ['description' => 'Consultation fee', 'service_code' => 'CONSULT', 'quantity' => 1, 'unit_price' => 5000],
                ['description' => 'Malaria RDT', 'service_code' => 'LAB-MALARIA', 'quantity' => 1, 'unit_price' => 1500],
            ],
            'payment' => [
                'amount' => 6500,
                'method' => 'cash',
            ],
            'actor_id' => $provider->id,
        ]);

        $this->assertEquals('checked_in', $result['appointment']->status);
        $this->assertEquals($result['visit']->id, $result['appointment']->visit_id);
        $this->assertEquals('waiting', $result['queue_ticket']->status);
        $this->assertEquals($result['visit']->id, $result['queue_ticket']->visit_id);
        $this->assertEquals('signed', $result['clinical_note']->status);
        $this->assertEquals('paid', $result['invoice']->status);
        $this->assertEquals(0, (int) $result['invoice']->balance_amount);
        $this->assertEquals('successful', $result['payment']->status);
        $this->assertEquals($result['payment']->id, $result['receipt']->payment_id);
        $this->assertEquals('issued', $result['document']->status);
        $this->assertEquals('INV', $result['document']->document_type);
        $this->assertEquals($result['invoice']->id, $result['document']->payload_json['invoice_id']);

        $this->assertDatabaseHas('notification_events', [
            'event_type' => 'invoice_document_ready',
            'recipient_user_id' => $patient->id,
        ]);
        $this->assertDatabaseHas('audit_events', ['resource_type' => 'operational_journey', 'action_type' => 'complete']);
    }

    public function test_e2e_api_returns_document_and_receipt_references()
    {
        [$patient, $facility, $provider, $slot] = $this->journeyActors();

        $response = $this->withHeaders([
            'X-Client-ID'     => 'test_client_id',
            'X-Client-Secret' => 'test_client_secret',
        ])->postJson('/api/v1/operational-flow/patient-journey', [
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'appointment_slot_id' => $slot->id,
            'appointment_type' => 'outpatient',
            'queue_name' => 'triage',
            'consultation' => [
                'history_of_present_illness' => 'Cough',
                'examination_findings' => 'Normal oxygen saturation',
                'treatment_plan' => 'Review if symptoms persist',
            ],
            'lab_result_summary' => 'No lab ordered',
            'invoice_items' => [
                ['description' => 'Consultation fee', 'service_code' => 'CONSULT', 'quantity' => 1, 'unit_price' => 5000],
            ],
            'payment' => ['amount' => 5000, 'method' => 'cash'],
            'actor_id' => $provider->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.invoice.status', 'paid')
            ->assertJsonPath('data.payment.status', 'successful')
            ->assertJsonStructure([
                'data' => [
                    'appointment' => ['id', 'status', 'visit_id'],
                    'queue_ticket' => ['id', 'queue_number', 'status'],
                    'invoice' => ['id', 'status', 'balance_amount'],
                    'payment' => ['id', 'status'],
                    'receipt' => ['id', 'receipt_number'],
                    'document' => ['id', 'document_number', 'verification_code'],
                ],
            ]);

        $this->assertSame(1, OfficialDocument::where('document_type', 'INV')->count());
        $this->assertSame(1, NotificationEvent::where('event_type', 'invoice_document_ready')->count());
    }

    private function journeyActors(): array
    {
        $patient = Patient::create(['health_id' => 'OC-E2E-001', 'first_name' => 'Amina', 'last_name' => 'Journey']);
        $facility = Facility::create(['name' => 'Journey Clinic', 'type' => 'clinic', 'status' => 'active', 'license_number' => 'LIC-E2E']);
        $provider = User::create(['name' => 'Dr Journey', 'email' => 'journey@test.com', 'password' => 'password', 'primary_facility_id' => $facility->id]);
        $start = CarbonImmutable::parse('2026-06-01 09:00:00');

        FacilitySchedule::create([
            'facility_id' => $facility->id,
            'day_of_week' => 1,
            'opens_at' => '08:00',
            'closes_at' => '17:00',
            'is_active' => true,
        ]);
        ProviderAvailability::create([
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '17:00',
            'is_active' => true,
        ]);
        FacilityQueue::create([
            'facility_id' => $facility->id,
            'name' => 'triage',
            'display_name' => 'Triage',
            'is_active' => true,
        ]);
        $slot = AppointmentSlot::create([
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'starts_at' => $start,
            'ends_at' => $start->addMinutes(30),
            'capacity' => 1,
            'booked_count' => 0,
            'status' => 'open',
        ]);

        return [$patient, $facility, $provider, $slot];
    }
}
