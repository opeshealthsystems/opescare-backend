<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityQueue;
use App\Models\Patient;
use App\Models\QueueTicket;
use App\Models\User;
use App\Modules\Queue\Services\QueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueuePatientFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_walk_in_check_in_creates_visit_queue_ticket_and_audit()
    {
        [$patient, $facility, $staff] = $this->queueActors();

        $ticket = app(QueueService::class)->checkInWalkIn([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'destination_queue' => 'triage',
            'visit_type' => 'outpatient',
            'actor_id' => $staff->id,
        ]);

        $this->assertEquals('waiting', $ticket->status);
        $this->assertEquals('triage', $ticket->current_queue);
        $this->assertNotNull($ticket->visit_id);
        $this->assertStringStartsWith('TRI-', $ticket->queue_number);
        $this->assertDatabaseHas('patient_flow_events', [
            'queue_ticket_id' => $ticket->id,
            'event_type' => 'checked_in',
            'to_queue' => 'triage',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'resource_type' => 'queue_ticket',
            'resource_id' => $ticket->id,
            'action_type' => 'create',
        ]);
    }

    public function test_emergency_priority_is_called_before_normal_waiting_ticket()
    {
        [$patient, $facility, $staff] = $this->queueActors();
        $secondPatient = Patient::create(['health_id' => 'OC-QUEUE-002', 'first_name' => 'Bola', 'last_name' => 'Normal']);
        $service = app(QueueService::class);

        $normal = $service->checkInWalkIn([
            'patient_id' => $secondPatient->id,
            'facility_id' => $facility->id,
            'destination_queue' => 'triage',
            'visit_type' => 'outpatient',
            'actor_id' => $staff->id,
        ]);
        $emergency = $service->checkInWalkIn([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'destination_queue' => 'triage',
            'visit_type' => 'emergency',
            'actor_id' => $staff->id,
        ]);
        $service->prioritize($emergency, 'emergency_priority', 'Severe bleeding', $staff->id);

        $called = $service->callNext($facility->id, 'triage', $staff->id);

        $this->assertEquals($emergency->id, $called->id);
        $this->assertEquals('called', $called->status);
        $this->assertEquals('waiting', $normal->fresh()->status);
    }

    public function test_ticket_can_transfer_between_care_queues_with_flow_event()
    {
        [$patient, $facility, $staff] = $this->queueActors();
        $ticket = app(QueueService::class)->checkInWalkIn([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'destination_queue' => 'triage',
            'visit_type' => 'outpatient',
            'actor_id' => $staff->id,
        ]);

        $transferred = app(QueueService::class)->transfer($ticket, 'consultation', 'Vitals complete', $staff->id);

        $this->assertEquals('waiting', $transferred->status);
        $this->assertEquals('consultation', $transferred->current_queue);
        $this->assertDatabaseHas('patient_flow_events', [
            'queue_ticket_id' => $ticket->id,
            'event_type' => 'transferred',
            'from_queue' => 'triage',
            'to_queue' => 'consultation',
        ]);
    }

    public function test_ticket_lifecycle_can_call_start_service_and_complete()
    {
        [$patient, $facility, $staff] = $this->queueActors();
        $ticket = app(QueueService::class)->checkInWalkIn([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'destination_queue' => 'consultation',
            'visit_type' => 'outpatient',
            'actor_id' => $staff->id,
        ]);

        $called = app(QueueService::class)->callNext($facility->id, 'consultation', $staff->id);
        $inService = app(QueueService::class)->startService($called, $staff->id);
        $completed = app(QueueService::class)->complete($inService, 'Consultation complete', $staff->id);

        $this->assertEquals($ticket->id, $called->id);
        $this->assertEquals('in_service', $inService->status);
        $this->assertEquals('completed', $completed->status);
        $this->assertDatabaseHas('patient_flow_events', [
            'queue_ticket_id' => $ticket->id,
            'event_type' => 'completed',
        ]);
    }

    public function test_public_queue_display_masks_patient_names()
    {
        [$patient, $facility, $staff] = $this->queueActors();
        app(QueueService::class)->checkInWalkIn([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'destination_queue' => 'triage',
            'visit_type' => 'outpatient',
            'actor_id' => $staff->id,
        ]);

        $response = $this->withHeaders($this->clientHeadersFor($facility))
            ->getJson('/api/v1/queues/display?facility_id='.$facility->id.'&queue_name=triage');

        $response->assertOk()->assertJsonPath('data.0.masked_patient_name', 'A*** Q***');
        $this->assertStringNotContainsString('Amina', json_encode($response->json('data')));
    }

    public function test_staff_queue_list_is_scoped_to_facility()
    {
        [$patient, $facility, $staff] = $this->queueActors();
        $otherFacility = Facility::create(['name' => 'Other Clinic', 'type' => 'clinic', 'status' => 'active']);
        FacilityQueue::create(['facility_id' => $otherFacility->id, 'name' => 'triage', 'display_name' => 'Triage']);
        $service = app(QueueService::class);

        $service->checkInWalkIn([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'destination_queue' => 'triage',
            'visit_type' => 'outpatient',
            'actor_id' => $staff->id,
        ]);
        QueueTicket::create([
            'patient_id' => $patient->id,
            'facility_id' => $otherFacility->id,
            'current_queue' => 'triage',
            'queue_number' => 'TRI-999',
            'status' => 'waiting',
            'priority_level' => 5,
            'checked_in_at' => now(),
        ]);

        $response = $this->withHeaders($this->clientHeadersFor($facility))
            ->getJson('/api/v1/queues/tickets?facility_id='.$facility->id);

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame($facility->id, $response->json('data.0.facility_id'));
    }

    public function test_cancelled_ticket_writes_reason_and_audit_event()
    {
        [$patient, $facility, $staff] = $this->queueActors();
        $ticket = app(QueueService::class)->checkInWalkIn([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'destination_queue' => 'triage',
            'visit_type' => 'outpatient',
            'actor_id' => $staff->id,
        ]);

        $cancelled = app(QueueService::class)->cancel($ticket, 'Visit cancelled by patient', $staff->id);

        $this->assertEquals('cancelled', $cancelled->status);
        $this->assertEquals('Visit cancelled by patient', $cancelled->status_reason);
        $this->assertDatabaseHas('audit_events', [
            'resource_type' => 'queue_ticket',
            'resource_id' => $ticket->id,
            'action_type' => 'cancel',
        ]);
    }

    /**
     * Create an active IntegrationClient bound to the given facility so
     * VerifyIntegrationClient resolves facility_id to the test's facility.
     */
    private function clientHeadersFor(Facility $facility): array
    {
        $clientId = 'client_' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(12));
        \App\Models\IntegrationClient::factory()->create([
            'client_id'     => $clientId,
            'client_secret' => hash('sha256', 'integration_secret'),
            'facility_id'   => $facility->id,
        ]);

        return ['X-Client-ID' => $clientId, 'X-Client-Secret' => 'integration_secret'];
    }

    private function queueActors(): array
    {
        $patient = Patient::create(['health_id' => 'OC-QUEUE-001', 'first_name' => 'Amina', 'last_name' => 'Queue']);
        $facility = Facility::create(['name' => 'Flow Clinic', 'type' => 'clinic', 'status' => 'active']);
        $staff = User::create(['name' => 'Queue Staff', 'email' => 'queue@test.com', 'password' => 'password', 'primary_facility_id' => $facility->id]);

        foreach (['triage', 'consultation', 'lab', 'pharmacy', 'billing', 'discharge'] as $queue) {
            FacilityQueue::create([
                'facility_id' => $facility->id,
                'name' => $queue,
                'display_name' => str($queue)->headline()->toString(),
                'is_active' => true,
            ]);
        }

        return [$patient, $facility, $staff];
    }
}
