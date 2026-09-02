<?php

namespace Tests\Feature\OperationalFlow;

use App\Models\ClinicalAlert;
use App\Models\ClinicalNote;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\QueueTicket;
use App\Models\User;
use App\Models\Visit;
use App\Modules\OperationalFlow\Services\VisitManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GAP-006 — visit-closure safety guards.
 *
 * A visit must not be completable while a patient-safety risk is open:
 *   - an unacknowledged critical clinical alert,
 *   - no consultation note (for consultation-bearing visit types),
 *   - an open queue ticket.
 */
class VisitClosureGuardsTest extends TestCase
{
    use RefreshDatabase;

    private function makeVisit(array $overrides = []): Visit
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();

        return Visit::create(array_merge([
            'patient_id'  => $patient->id,
            'facility_id' => $facility->id,
            'visit_type'  => 'general',
            'status'      => 'in_consultation',
            'started_at'  => now(),
        ], $overrides));
    }

    private function addConsultNote(Visit $visit): void
    {
        ClinicalNote::create([
            'visit_id'    => $visit->id,
            'provider_id' => User::factory()->create()->id,
            'status'      => 'signed',
        ]);
    }

    private function addCriticalAlert(Visit $visit, string $status): void
    {
        ClinicalAlert::create([
            'facility_id'   => $visit->facility_id,
            'patient_id'    => $visit->patient_id,
            'visit_id'      => $visit->id,
            'alert_type'    => 'drug_interaction',
            'severity'      => 'critical',
            'alert_message' => 'Major drug interaction detected',
            'status'        => $status,
            'triggered_at'  => now(),
        ]);
    }

    private function addQueueTicket(Visit $visit, string $status): void
    {
        QueueTicket::create([
            'patient_id'    => $visit->patient_id,
            'facility_id'   => $visit->facility_id,
            'visit_id'      => $visit->id,
            'queue_number'  => 'Q-001',
            'current_queue' => 'general',
            'status'        => $status,
        ]);
    }

    private function service(): VisitManagementService
    {
        return app(VisitManagementService::class);
    }

    public function test_cannot_complete_with_unacknowledged_critical_alert(): void
    {
        $visit = $this->makeVisit();
        $this->addConsultNote($visit);
        $this->addCriticalAlert($visit, 'active');

        $this->expectExceptionMessage('VISIT_BLOCKED_CRITICAL_ALERT');
        $this->service()->complete($visit->id, 'tester');
    }

    public function test_cannot_complete_without_consult_note(): void
    {
        $visit = $this->makeVisit();

        $this->expectExceptionMessage('VISIT_BLOCKED_NO_CONSULT_NOTE');
        $this->service()->complete($visit->id, 'tester');
    }

    public function test_cannot_complete_with_open_queue_ticket(): void
    {
        $visit = $this->makeVisit();
        $this->addConsultNote($visit);
        $this->addQueueTicket($visit, 'waiting');

        $this->expectExceptionMessage('VISIT_BLOCKED_OPEN_QUEUE_TICKET');
        $this->service()->complete($visit->id, 'tester');
    }

    public function test_transition_to_completed_is_also_guarded(): void
    {
        $visit = $this->makeVisit(['status' => 'awaiting_discharge']);

        $this->expectExceptionMessage('VISIT_BLOCKED_NO_CONSULT_NOTE');
        $this->service()->transition($visit->id, 'completed', 'tester');
    }

    public function test_completes_when_all_guards_satisfied(): void
    {
        $visit = $this->makeVisit();
        $this->addConsultNote($visit);
        $this->addCriticalAlert($visit, 'acknowledged'); // handled, not active
        $this->addQueueTicket($visit, 'completed');       // terminal, not open

        $result = $this->service()->complete($visit->id, 'tester');

        $this->assertSame('completed', $result->status);
        $this->assertNotNull($result->ended_at);
    }

    public function test_lab_only_visit_completes_without_consult_note(): void
    {
        $visit = $this->makeVisit(['visit_type' => 'lab-only']);

        $result = $this->service()->complete($visit->id, 'tester');

        $this->assertSame('completed', $result->status);
    }
}
