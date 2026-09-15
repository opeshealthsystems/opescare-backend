<?php

namespace Tests\Feature\Imports;

use App\Models\AccessReview;
use App\Models\AuditEvent;
use App\Models\AuditExport;
use App\Models\BreachReport;
use App\Models\EmergencyAccessEvent;
use App\Models\EmergencyReviewCase;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Role;
use App\Models\SuspiciousAccessFlag;
use App\Models\User;
use App\Modules\SecurityOperations\Services\SuspiciousAccessDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Group D import — access review, suspicious access, break-glass review, breaches, compliance export. */
class SecurityOpsTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role, ?string $facilityId = null): User
    {
        return User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)])->id,
            'primary_facility_id' => $facilityId,
        ]);
    }

    public function test_every_new_page_is_platform_tier(): void
    {
        $facility = Facility::factory()->create();
        $facilityAdmin = $this->userWithRole('clinic_admin', $facility->id);
        $officer = $this->userWithRole('security_officer');

        foreach (['/admin/security/access-review', '/admin/security/suspicious-access', '/admin/security/breaches', '/admin/security/compliance-export'] as $url) {
            $this->actingAs($facilityAdmin)->get($url)->assertForbidden();
            $this->actingAs($officer)->get($url)->assertOk();
        }
    }

    public function test_a_burst_of_patient_record_views_flags_suspicious_access(): void
    {
        $facility = Facility::factory()->create();
        $snooper = $this->userWithRole('doctor', $facility->id);
        $normal = $this->userWithRole('nurse', $facility->id);
        $officer = $this->userWithRole('security_officer');

        $patients = Patient::factory()->count(SuspiciousAccessDetectionService::HIGH_VOLUME_THRESHOLD)->create();
        $rows = [];
        foreach ($patients as $p) {
            $rows[] = [
                'id' => (string) Str::uuid(), 'patient_id' => $p->id, 'actor_id' => $snooper->id, 'actor_type' => 'user',
                'facility_id' => $facility->id, 'purpose' => 'treatment', 'data_category' => 'clinical', 'resource_type' => 'patient_chart',
                'access_type' => 'read', 'emergency_access' => false, 'created_at' => now()->subMinutes(5),
            ];
        }
        // A normal day: a handful of charts.
        foreach ($patients->take(3) as $p) {
            $rows[] = array_merge(end($rows), ['id' => (string) Str::uuid(), 'patient_id' => $p->id, 'actor_id' => $normal->id]);
        }
        DB::table('access_logs')->insert($rows);
        $logCount = DB::table('access_logs')->count();

        $this->actingAs($officer)->post('/admin/security/suspicious-access/scan')->assertRedirect();

        $flags = SuspiciousAccessFlag::all();
        $this->assertCount(1, $flags);
        $this->assertSame($snooper->id, $flags[0]->user_id);
        $this->assertSame('high_volume_access', $flags[0]->flag_type);

        // Re-running does not duplicate an open flag, and never touches the logs.
        $this->actingAs($officer)->post('/admin/security/suspicious-access/scan');
        $this->assertSame(1, SuspiciousAccessFlag::count());
        $this->assertSame($logCount, DB::table('access_logs')->count());

        $this->actingAs($officer)->get('/admin/security/suspicious-access')->assertOk()->assertSee($snooper->email);

        $this->actingAs($officer)->post("/admin/security/suspicious-access/{$flags[0]->id}/review", [
            'decision' => 'reviewed', 'notes' => 'Mass-casualty triage, confirmed with ward lead',
        ])->assertRedirect();

        $flag = $flags[0]->fresh();
        $this->assertSame('reviewed', $flag->status);
        $this->assertSame($officer->id, $flag->reviewed_by);
        $this->assertNotNull($flag->reviewed_at);
        $this->assertSame($logCount, DB::table('access_logs')->count());
    }

    public function test_break_glass_review_records_the_reviewer_and_keeps_the_log(): void
    {
        $facility = Facility::factory()->create();
        $provider = $this->userWithRole('doctor', $facility->id);
        $officer = $this->userWithRole('security_officer');
        $patient = Patient::factory()->create(['is_demo' => false]);

        $event = new EmergencyAccessEvent();
        $event->forceFill([
            'patient_id' => $patient->id, 'facility_id' => $facility->id, 'provider_id' => $provider->id,
            'reason' => 'Unconscious on arrival', 'is_demo' => false,
        ])->save();

        DB::table('access_logs')->insert([
            'id' => (string) Str::uuid(), 'patient_id' => $patient->id, 'actor_id' => $provider->id, 'actor_type' => 'user',
            'facility_id' => $facility->id, 'purpose' => 'emergency', 'data_category' => 'clinical', 'resource_type' => 'patient_chart',
            'access_type' => 'read', 'emergency_access' => true, 'created_at' => now(),
        ]);

        // Break-glass with no clinical documentation is itself flagged.
        app(SuspiciousAccessDetectionService::class)->scan();
        $this->assertTrue(SuspiciousAccessFlag::where('flag_type', 'undocumented_emergency_access')->where('user_id', $provider->id)->exists());

        $this->actingAs($officer)->post("/admin/security/emergency-access/{$event->id}/review", [
            'status' => 'approved', 'notes' => 'Justified: patient unconscious',
        ])->assertRedirect();

        $case = EmergencyReviewCase::withoutGlobalScope('isolate_demo')->where('emergency_access_event_id', $event->id)->firstOrFail();
        $this->assertSame($officer->id, $case->reviewed_by);
        $this->assertNotNull($case->reviewed_at);
        $this->assertSame('Justified: patient unconscious', $case->reviewer_notes);

        $this->assertSame(1, EmergencyAccessEvent::withoutGlobalScope('isolate_demo')->whereKey($event->id)->count());
        $this->assertSame(1, DB::table('access_logs')->where('emergency_access', true)->count());
    }

    public function test_access_review_and_breach_workflow_record_the_actor(): void
    {
        $officer = $this->userWithRole('security_officer');
        $target = $this->userWithRole('doctor');

        $this->actingAs($officer)->post('/admin/security/access-review', ['email' => $target->email, 'reason' => 'Role change'])->assertRedirect();
        $review = AccessReview::firstOrFail();
        $this->actingAs($officer)->post("/admin/security/access-review/{$review->id}/complete", ['outcome' => 'no_change'])->assertRedirect();
        $this->assertSame('completed', $review->fresh()->status);
        $this->assertSame($officer->id, $review->fresh()->reviewed_by);

        $this->actingAs($officer)->post('/admin/security/breaches', [
            'description' => 'Laptop left in a taxi', 'breach_type' => 'lost_device', 'severity' => 'high',
        ])->assertRedirect();
        $breach = BreachReport::firstOrFail();
        $this->actingAs($officer)->post("/admin/security/breaches/{$breach->id}/contain", ['notes' => 'Remote wipe'])->assertRedirect();
        $this->actingAs($officer)->post("/admin/security/breaches/{$breach->id}/notify")->assertRedirect();
        $this->actingAs($officer)->post("/admin/security/breaches/{$breach->id}/close", ['resolution' => 'Wiped'])->assertRedirect();

        $breach->refresh();
        $this->assertSame('closed', $breach->status);
        $this->assertNotNull($breach->contained_at);
        $this->assertNotNull($breach->reported_to_authority_at);
        $this->assertSame(4, AuditEvent::where('resource_type', 'breach_report')->where('actor_id', $officer->id)->count());
    }

    public function test_compliance_export_has_no_clinical_content(): void
    {
        $officer = $this->userWithRole('security_officer');
        $patient = Patient::factory()->create();

        AuditEvent::create([
            'actor_id' => $officer->id, 'patient_id' => $patient->id, 'action_type' => 'read', 'resource_type' => 'lab_result',
            'reason' => 'HIV viral load 12000 copies', 'before_state' => ['diagnosis' => 'B20 HIV disease'],
            'after_state' => ['diagnosis' => 'B20 HIV disease'], 'created_at' => now(),
        ]);

        foreach (['audit_log', 'audit_aggregate'] as $type) {
            $response = $this->actingAs($officer)->post('/admin/security/compliance-export', ['export_type' => $type]);
            $response->assertOk();
            $csv = $response->streamedContent();

            $this->assertStringContainsString('lab_result', $csv);
            $this->assertStringNotContainsString('HIV', $csv);
            $this->assertStringNotContainsString('B20', $csv);
            $this->assertStringNotContainsString($patient->id, $csv);
            $this->assertStringNotContainsString('patient_id', $csv);
        }

        $this->assertSame(2, AuditExport::where('requested_by', $officer->id)->count());
    }
}
