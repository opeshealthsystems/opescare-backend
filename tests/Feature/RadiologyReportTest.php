<?php
namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\RadiologyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RadiologyReportTest extends TestCase {
    use RefreshDatabase;

    private array $headers = ['X-Client-ID' => 'test_client_id', 'X-Client-Secret' => 'test_client_secret'];
    private User $user;
    private Facility $facility;
    private Patient $patient;

    protected function setUp(): void {
        parent::setUp();
        $this->user     = User::factory()->create();
        $this->facility = Facility::factory()->create();
        $this->patient  = Patient::factory()->create();
    }

    private function draftPayload(): array {
        return [
            'patient_id'          => $this->patient->id,
            'facility_id'         => $this->facility->id,
            'ordered_by'          => $this->user->id,
            'reported_by'         => $this->user->id,
            'modality'            => 'ct',
            'body_part'           => 'Chest',
            'study_date'          => now()->format('Y-m-d H:i:s'),
            'clinical_indication' => 'Chest pain evaluation',
            'findings'            => 'No acute findings.',
            'impression'          => 'Normal CT chest.',
        ];
    }

    public function test_create_draft_report(): void {
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v1/radiology/reports', $this->draftPayload());
        $response->assertCreated();
        $response->assertJsonPath('data.status', 'draft');
        $this->assertDatabaseHas('radiology_reports', ['modality' => 'ct', 'status' => 'draft']);
    }

    public function test_finalize_report(): void {
        $report = RadiologyReport::factory()->create(array_merge(
            $this->draftPayload(),
            ['patient_id' => $this->patient->id, 'facility_id' => $this->facility->id,
             'ordered_by' => $this->user->id, 'reported_by' => $this->user->id, 'status' => 'draft']
        ));
        $response = $this->withHeaders($this->headers)
            ->patchJson("/api/v1/radiology/reports/{$report->id}/finalize", [
                'radiologist_id' => $this->user->id,
            ]);
        $response->assertOk();
        $response->assertJsonPath('data.status', 'final');
        $this->assertNotNull($response->json('data.finalized_at'));
    }

    public function test_amend_finalized_report(): void {
        $report = RadiologyReport::factory()->create(array_merge(
            $this->draftPayload(),
            ['patient_id' => $this->patient->id, 'facility_id' => $this->facility->id,
             'ordered_by' => $this->user->id, 'reported_by' => $this->user->id,
             'status' => 'final', 'finalized_at' => now()]
        ));
        $response = $this->withHeaders($this->headers)
            ->patchJson("/api/v1/radiology/reports/{$report->id}/amend", [
                'reason'   => 'Measurement correction',
                'findings' => 'Revised findings.',
            ]);
        $response->assertOk();
        $response->assertJsonPath('data.status', 'amended');
        $response->assertJsonPath('data.amendment_reason', 'Measurement correction');
    }

    public function test_distribute_report_sets_distributed_to_and_at(): void {
        $recipient = User::factory()->create();
        $report = RadiologyReport::factory()->create(array_merge(
            $this->draftPayload(),
            ['patient_id' => $this->patient->id, 'facility_id' => $this->facility->id,
             'ordered_by' => $this->user->id, 'reported_by' => $this->user->id,
             'status' => 'final', 'finalized_at' => now()]
        ));
        $response = $this->withHeaders($this->headers)
            ->postJson("/api/v1/radiology/reports/{$report->id}/distribute", [
                'user_ids' => [$recipient->id],
            ]);
        $response->assertOk();
        $this->assertContains($recipient->id, $response->json('data.distributed_to'));
        $this->assertNotNull($response->json('data.distributed_at'));
    }

    public function test_cannot_distribute_draft_report(): void {
        $report = RadiologyReport::factory()->create(array_merge(
            $this->draftPayload(),
            ['patient_id' => $this->patient->id, 'facility_id' => $this->facility->id,
             'ordered_by' => $this->user->id, 'reported_by' => $this->user->id, 'status' => 'draft']
        ));
        $response = $this->withHeaders($this->headers)
            ->postJson("/api/v1/radiology/reports/{$report->id}/distribute", [
                'user_ids' => [$this->user->id],
            ]);
        $response->assertStatus(500);
    }
}
