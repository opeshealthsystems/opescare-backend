<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Facility;
use App\Models\User;
use App\Models\ReportType;
use App\Models\ReportingRule;
use App\Models\PublicHealthReport;
use App\Models\ReportItem;
use App\Models\PharmacyInventory;
use App\Models\BloodInventory;
use App\Models\Visit;
use App\Models\Diagnosis;
use App\Models\SubmissionProfile;
use App\Models\PublicHealthBaseline;
use App\Models\PublicHealthSignal;

class PublicHealthReportingTest extends TestCase
{
    use RefreshDatabase;

    private $facility;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create a mock user
        $this->user = new User();
        $this->user->id = '00000000-0000-0000-0000-000000000001';
        $this->user->name = 'Dr. Jane PublicHealth';
        $this->user->email = 'jane@opescare.org';
        $this->user->password = bcrypt('secret');
        $this->user->save();

        // 2. Create a mock facility
        $this->facility = new Facility();
        $this->facility->id = '00000000-0000-0000-0000-000000000002';
        $this->facility->name = 'Centra Health Center';
        $this->facility->type = 'clinic';
        $this->facility->status = 'active';
        $this->facility->save();

        // 3. Seed report types config catalog using new-save style to retain custom primary keys
        $type1 = new ReportType();
        $type1->id = '00000000-0000-0000-0000-000000000101';
        $type1->code = 'notifiable_disease';
        $type1->name = 'Notifiable Disease Report';
        $type1->sensitivity_level = 'aggregate';
        $type1->save();

        $type2 = new ReportType();
        $type2->id = '00000000-0000-0000-0000-000000000102';
        $type2->code = 'pharmacy_stockout';
        $type2->name = 'Pharmacy Stockout Surveillance';
        $type2->sensitivity_level = 'aggregate';
        $type2->save();

        $type3 = new ReportType();
        $type3->id = '00000000-0000-0000-0000-000000000103';
        $type3->code = 'blood_shortage';
        $type3->name = 'Blood Shortage Monitoring';
        $type3->sensitivity_level = 'aggregate';
        $type3->save();

        // 4. Seed active reporting rules
        $rule1 = new ReportingRule();
        $rule1->id = '00000000-0000-0000-0000-000000000201';
        $rule1->report_type_id = $type1->id;
        $rule1->trigger_source = 'diagnoses';
        $rule1->requires_review = true;
        $rule1->save();

        $rule2 = new ReportingRule();
        $rule2->id = '00000000-0000-0000-0000-000000000202';
        $rule2->report_type_id = $type2->id;
        $rule2->trigger_source = 'pharmacy_stock';
        $rule2->requires_review = true;
        $rule2->save();

        $rule3 = new ReportingRule();
        $rule3->id = '00000000-0000-0000-0000-000000000203';
        $rule3->report_type_id = $type3->id;
        $rule3->trigger_source = 'blood_stock';
        $rule3->requires_review = true;
        $rule3->save();
    }

    /** @test */
    public function test_it_can_generate_notifiable_disease_drafts_from_clinical_encounters()
    {
        // Mock patient to satisfy foreign key constraints
        $patient = new \App\Models\Patient();
        $patient->id = '00000000-0000-0000-0000-000000000003';
        $patient->first_name = 'John';
        $patient->last_name = 'Doe';
        $patient->sex = 'male';
        $patient->date_of_birth = '1990-01-01';
        $patient->health_id = 'OC-CMR-7KQ9-MP42-X8D1';
        $patient->save();

        // Create clinical visits and diagnoses matching trigger conditions
        $visit = Visit::create([
            'facility_id' => $this->facility->id,
            'visit_type' => 'outpatient',
            'status' => 'completed',
            'started_at' => now()->subDay(),
            'patient_id' => $patient->id
        ]);

        Diagnosis::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'provider_id' => $this->user->id,
            'code' => 'B54',
            'code_system' => 'ICD-10',
            'display_name' => 'Malaria',
            'status' => 'active'
        ]);

        $response = $this->withHeaders([
            'X-Client-ID'     => 'test_client_id',
            'X-Client-Secret' => 'test_client_secret',
        ])->postJson('/api/v1/public-health/reports/generate-drafts', [
            'facility_id' => $this->facility->id,
            'period_start' => now()->subDays(3)->toDateString(),
            'period_end' => now()->addDay()->toDateString()
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['generated_count' => 1]);

        $this->assertDatabaseHas('public_health_reports', [
            'facility_id' => $this->facility->id,
            'status' => 'draft'
        ]);

        $this->assertDatabaseHas('public_health_report_items', [
            'indicator_code' => 'DISEASE_MALARIA',
            'value' => 1
        ]);
    }

    /** @test */
    public function test_it_can_generate_pharmacy_stockout_drafts_excluding_expired_items()
    {
        // Create active stockout pharmacy items, and an expired stockout item
        PharmacyInventory::create([
            'facility_id' => $this->facility->id,
            'medicine_name' => 'Amoxicillin 500mg',
            'generic_name' => 'Amoxicillin',
            'form' => 'capsule',
            'strength' => '500mg',
            'stock_status' => 'out_of_stock',
            'available_quantity' => 0,
            'is_expired' => false
        ]);

        PharmacyInventory::create([
            'facility_id' => $this->facility->id,
            'medicine_name' => 'Artemether-Lumefantrine',
            'generic_name' => 'ACT',
            'form' => 'tablet',
            'strength' => '20/120mg',
            'stock_status' => 'out_of_stock',
            'available_quantity' => 0,
            'is_expired' => true // Expired items should be excluded from shortages
        ]);

        $response = $this->withHeaders([
            'X-Client-ID'     => 'test_client_id',
            'X-Client-Secret' => 'test_client_secret',
        ])->postJson('/api/v1/public-health/reports/generate-drafts', [
            'facility_id' => $this->facility->id,
            'period_start' => now()->subDays(3)->toDateString(),
            'period_end' => now()->addDay()->toDateString()
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('public_health_report_items', [
            'indicator_code' => 'STOCKOUT_AMOXICILLIN'
        ]);

        $this->assertDatabaseMissing('public_health_report_items', [
            'indicator_code' => 'STOCKOUT_ACT'
        ]);
    }

    /** @test */
    public function test_it_verifies_two_person_workflow_governance_reviews()
    {
        $report = new PublicHealthReport();
        $report->id = '00000000-0000-0000-0000-000000000301';
        $report->report_type_id = '00000000-0000-0000-0000-000000000101';
        $report->facility_id = $this->facility->id;
        $report->reporting_period_start = now();
        $report->reporting_period_end = now();
        $report->status = 'draft';
        $report->save();

        $clientHeaders = ['X-Client-ID' => 'test_client_id', 'X-Client-Secret' => 'test_client_secret'];

        // Submit for review
        $response = $this->withHeaders($clientHeaders)->postJson("/api/v1/public-health/reports/{$report->id}/submit-for-review");
        $response->assertStatus(200);
        $response->assertJsonPath('status', 'pending_review');

        // Approve
        $response = $this->withHeaders($clientHeaders)->postJson("/api/v1/public-health/reports/{$report->id}/approve", [
            'comment' => 'Validated.'
        ]);
        $response->assertStatus(200);
        $response->assertJsonPath('status', 'approved_for_submission');

        $this->assertDatabaseHas('public_health_reports', [
            'id' => $report->id,
            'status' => 'approved_for_submission'
        ]);
    }

    /** @test */
    public function test_it_applies_versioning_upon_report_corrections()
    {
        $report = new PublicHealthReport();
        $report->id = '00000000-0000-0000-0000-000000000302';
        $report->report_type_id = '00000000-0000-0000-0000-000000000101';
        $report->facility_id = $this->facility->id;
        $report->reporting_period_start = now();
        $report->reporting_period_end = now();
        $report->status = 'requires_correction';
        $report->payload_json = ['disease' => 'malaria', 'count' => 1];
        $report->save();

        $response = $this->withHeaders(['X-Client-ID' => 'test_client_id', 'X-Client-Secret' => 'test_client_secret'])
            ->postJson("/api/v1/public-health/reports/{$report->id}/correct", [
            'payload' => ['disease' => 'malaria', 'count' => 3],
            'reason' => 'Updated clinical counts.'
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'draft');
        $response->assertJsonPath('version', 1);

        $this->assertDatabaseHas('public_health_report_versions', [
            'report_id' => $report->id,
            'version_number' => 1,
            'change_reason' => 'Updated clinical counts.'
        ]);
    }

    /** @test */
    public function test_it_applies_small_cell_privacy_suppression_on_aggregate_csv_exports()
    {
        $report = new PublicHealthReport();
        $report->id = '00000000-0000-0000-0000-000000000303';
        $report->report_type_id = '00000000-0000-0000-0000-000000000101';
        $report->facility_id = $this->facility->id;
        $report->reporting_period_start = now();
        $report->reporting_period_end = now();
        $report->status = 'approved_for_submission';
        $report->save();

        // Add an item with value 3 (which falls under the small-cell suppression range of < 5)
        ReportItem::create([
            'report_id' => $report->id,
            'indicator_code' => 'DISEASE_MALARIA',
            'indicator_name' => 'Malaria',
            'value' => 3
        ]);

        $response = $this->withHeaders(['X-Client-ID' => 'test_client_id', 'X-Client-Secret' => 'test_client_secret'])
            ->postJson("/api/v1/public-health/reports/{$report->id}/export");
        $response->assertStatus(200);

        $exportId = $response->json('export_id');
        $this->assertNotNull($exportId);

        // Download and read export contents
        $downloadResponse = $this->get("/api/v1/public-health/exports/{$exportId}/download");
        $downloadResponse->assertStatus(200);

        $csvContent = $downloadResponse->streamedContent();
        $this->assertStringContainsString('DISEASE_MALARIA', $csvContent);
        $this->assertStringContainsString('< 5', $csvContent); // Suppressed
        $this->assertStringNotContainsString(',3', $csvContent); // Original unsuppressed count should be hidden
    }

    /** @test */
    public function test_it_can_generate_outbreak_alarm_signals_when_thresholds_are_crossed()
    {
        // 1. Create a baseline
        PublicHealthBaseline::create([
            'scope_type' => 'facility',
            'scope_id' => $this->facility->id,
            'indicator_code' => 'DISEASE_CHOLERA',
            'period_type' => 'weekly',
            'baseline_value' => 2.00
        ]);

        // 2. Generate a report in the last week with high count
        $report = new PublicHealthReport();
        $report->id = '00000000-0000-0000-0000-000000000304';
        $report->report_type_id = '00000000-0000-0000-0000-000000000101';
        $report->facility_id = $this->facility->id;
        $report->reporting_period_start = now()->subDay();
        $report->reporting_period_end = now();
        $report->status = 'approved_for_submission';
        $report->save();

        ReportItem::create([
            'report_id' => $report->id,
            'indicator_code' => 'DISEASE_CHOLERA',
            'indicator_name' => 'Cholera',
            'value' => 12
        ]);

        // 3. Trigger Outbreak Signal Detection
        $response = $this->withHeaders([
            'X-Client-ID'     => 'test_client_id',
            'X-Client-Secret' => 'test_client_secret',
        ])->postJson('/api/v1/public-health/signals/trigger-detection', [
            'facility_id' => $this->facility->id,
            'indicator_code' => 'DISEASE_CHOLERA'
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'signal_detected');

        $this->assertDatabaseHas('public_health_signals', [
            'facility_id' => $this->facility->id,
            'indicator_code' => 'DISEASE_CHOLERA',
            'severity' => 'medium'
        ]);
    }
}
