<?php

namespace Tests\Feature\SecurityOperations;

use App\Models\AccessReview;
use App\Models\AccessReviewSchedule;
use App\Models\AuditEvent;
use App\Models\AuditExport;
use App\Models\BreachReport;
use App\Models\Facility;
use App\Models\PermissionAudit;
use App\Models\SecurityIncident;
use App\Models\SuspiciousAccessFlag;
use App\Models\User;
use App\Modules\SecurityOperations\Services\AccessReviewService;
use App\Modules\SecurityOperations\Services\AuditExplorerService;
use App\Modules\SecurityOperations\Services\BreachWorkflowService;
use App\Modules\SecurityOperations\Services\ComplianceExportService;
use App\Modules\SecurityOperations\Services\SecurityIncidentService;
use App\Modules\SecurityOperations\Services\SuspiciousAccessDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the SecurityOperations module.
 *
 * Covers: AuditExplorerService, BreachWorkflowService, AccessReviewService,
 * SecurityIncidentService, ComplianceExportService, SuspiciousAccessDetectionService.
 */
class SecurityOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $securityOfficer;
    private User $targetUser;
    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->securityOfficer = User::create([
            'name'     => 'Security Officer',
            'email'    => 'security@opescare.test',
            'password' => bcrypt('password'),
        ]);

        $this->targetUser = User::create([
            'name'     => 'Clinical User',
            'email'    => 'clinical@opescare.test',
            'password' => bcrypt('password'),
        ]);

        $this->facility = Facility::create([
            'name' => 'Test Hospital',
            'type' => 'hospital',
        ]);
    }

    // -------------------------------------------------------------------------
    // AuditExplorerService
    // -------------------------------------------------------------------------

    public function test_audit_explorer_search_returns_paginated_results(): void
    {
        AuditEvent::create(['actor_id' => $this->targetUser->id, 'action_type' => 'read', 'resource_type' => 'patient']);
        AuditEvent::create(['actor_id' => $this->targetUser->id, 'action_type' => 'update', 'resource_type' => 'encounter']);

        $service = $this->app->make(AuditExplorerService::class);
        $result  = $service->search($this->securityOfficer->id, [
            'actor_id' => $this->targetUser->id,
        ]);

        $this->assertGreaterThanOrEqual(2, $result->total());
    }

    public function test_audit_explorer_search_logs_its_own_access(): void
    {
        $service = $this->app->make(AuditExplorerService::class);
        $service->search($this->securityOfficer->id, ['resource_type' => 'encounter']);

        $this->assertDatabaseHas('access_logs', [
            'actor_id'      => $this->securityOfficer->id,
            'resource_type' => 'audit_explorer',
            'access_type'   => 'search',
        ]);
    }

    public function test_audit_explorer_filters_by_resource_type(): void
    {
        AuditEvent::create(['actor_id' => $this->targetUser->id, 'action_type' => 'create', 'resource_type' => 'invoice']);
        AuditEvent::create(['actor_id' => $this->targetUser->id, 'action_type' => 'read', 'resource_type' => 'patient']);

        $service = $this->app->make(AuditExplorerService::class);
        $result  = $service->search($this->securityOfficer->id, ['resource_type' => 'invoice']);

        foreach ($result->items() as $item) {
            $this->assertEquals('invoice', $item->resource_type);
        }
    }

    // -------------------------------------------------------------------------
    // BreachWorkflowService
    // -------------------------------------------------------------------------

    public function test_can_open_breach_report(): void
    {
        $service = $this->app->make(BreachWorkflowService::class);

        $breach = $service->openBreach([
            'description'  => 'Suspected unauthorised access to patient records.',
            'severity'     => 'high',
            'breach_type'  => 'unauthorized_access',
        ], $this->securityOfficer->id);

        $this->assertNotNull($breach->id);
        $this->assertEquals('open', $breach->status);
        $this->assertNotNull($breach->title);
        $this->assertDatabaseHas('audit_events', [
            'actor_id'      => $this->securityOfficer->id,
            'action_type'   => 'create',
            'resource_type' => 'breach_report',
        ]);
    }

    public function test_breach_workflow_full_lifecycle(): void
    {
        $service = $this->app->make(BreachWorkflowService::class);

        $breach = $service->openBreach([
            'title'       => 'Laptop stolen with unencrypted patient data',
            'description' => 'Laptop with unencrypted patient data stolen from staff car.',
            'severity'    => 'critical',
            'breach_type' => 'lost_device',
        ], $this->securityOfficer->id);

        $contained = $service->contain($breach->id, $this->securityOfficer->id, 'Device remotely wiped.');
        $this->assertEquals('contained', $contained->status);

        $notified = $service->markNotified($breach->id, $this->securityOfficer->id);
        $this->assertNotNull($notified->reported_to_authority_at);

        $closed = $service->closeBreach($breach->id, $this->securityOfficer->id, 'Incident resolved, staff retrained.');
        $this->assertEquals('closed', $closed->status);
    }

    public function test_get_breaches_requiring_regulatory_action_returns_overdue(): void
    {
        $service = $this->app->make(BreachWorkflowService::class);

        // Breach opened > 48 hours ago with no regulatory notification
        BreachReport::create([
            'title'                     => 'Old unreported breach',
            'description'               => 'Old breach with no regulatory notification.',
            'reported_by'               => $this->securityOfficer->id,
            'status'                    => 'open',
            'severity'                  => 'high',
            'breach_type'               => 'unauthorized_access',
            'discovered_at'             => now()->subHours(55),
            'reported_to_authority_at'  => null,
            'created_at'                => now()->subHours(50),
            'updated_at'                => now()->subHours(50),
        ]);

        // Recent breach — should NOT appear
        BreachReport::create([
            'title'                     => 'Recent breach',
            'description'               => 'Recent breach under investigation.',
            'reported_by'               => $this->securityOfficer->id,
            'status'                    => 'open',
            'severity'                  => 'low',
            'breach_type'               => 'data_leak',
            'discovered_at'             => now()->subHours(5),
            'reported_to_authority_at'  => null,
        ]);

        $overdue = $service->getBreachesRequiringRegulatoryAction();
        $this->assertCount(1, $overdue);
        $this->assertEquals('Old unreported breach', $overdue->first()->title);
    }

    // -------------------------------------------------------------------------
    // AccessReviewService
    // -------------------------------------------------------------------------

    public function test_can_initiate_access_review(): void
    {
        $service = $this->app->make(AccessReviewService::class);

        $review = $service->initiateReview(
            $this->targetUser->id,
            $this->securityOfficer->id,
            'Routine quarterly access review.'
        );

        $this->assertNotNull($review->id);
        $this->assertEquals('in_progress', $review->status);
        $this->assertDatabaseHas('audit_events', [
            'actor_id'      => $this->securityOfficer->id,
            'action_type'   => 'create',
            'resource_type' => 'access_review',
        ]);
    }

    public function test_initiating_review_creates_permission_audit_record(): void
    {
        $service = $this->app->make(AccessReviewService::class);

        $service->initiateReview(
            $this->targetUser->id,
            $this->securityOfficer->id,
            'Post-incident review.'
        );

        $this->assertDatabaseHas('permission_audits', [
            'actor_id'       => $this->securityOfficer->id,
            'target_user_id' => $this->targetUser->id,
            'action'         => 'review',
        ]);
    }

    public function test_can_complete_access_review(): void
    {
        $service = $this->app->make(AccessReviewService::class);

        $review = $service->initiateReview(
            $this->targetUser->id,
            $this->securityOfficer->id,
            'Periodic review.'
        );

        $completed = $service->completeReview(
            $review->id,
            $this->securityOfficer->id,
            'no_change',
            'Access confirmed appropriate.'
        );

        $this->assertEquals('completed', $completed->status);
        $this->assertEquals('no_change', $completed->outcome);
    }

    public function test_can_complete_access_review_schedule(): void
    {
        $service  = $this->app->make(AccessReviewService::class);
        $schedule = AccessReviewSchedule::create([
            'review_frequency' => 'quarterly',
            'next_review_due'  => now()->subDays(1)->toDateString(),
            'status'           => 'pending',
        ]);

        $completed = $service->completeSchedule(
            $schedule->id,
            $this->securityOfficer->id,
            'Q2 review completed.'
        );

        $this->assertNotNull($completed->last_reviewed_at);
        $this->assertEquals('completed', $completed->status);
        $this->assertGreaterThan(now()->toDateString(), $completed->next_review_due);
    }

    // -------------------------------------------------------------------------
    // SecurityIncidentService
    // -------------------------------------------------------------------------

    public function test_can_open_security_incident(): void
    {
        $service = $this->app->make(SecurityIncidentService::class);

        $incident = $service->openIncident([
            'incident_type' => 'unauthorized_access',
            'severity'      => 'high',
            'summary'       => 'User accessed records outside their department.',
        ], $this->securityOfficer->id);

        $this->assertNotNull($incident->id);
        $this->assertEquals('new', $incident->status);
        $this->assertDatabaseHas('audit_events', [
            'actor_id'      => $this->securityOfficer->id,
            'action_type'   => 'create',
            'resource_type' => 'security_incident',
        ]);
    }

    public function test_security_incident_description_used_as_summary_fallback(): void
    {
        $service = $this->app->make(SecurityIncidentService::class);

        $incident = $service->openIncident([
            'incident_type' => 'malware',
            'severity'      => 'medium',
            'description'   => 'Malware detected on admin workstation.',
        ], $this->securityOfficer->id);

        $this->assertEquals('Malware detected on admin workstation.', $incident->summary);
    }

    public function test_security_incident_escalation(): void
    {
        $service  = $this->app->make(SecurityIncidentService::class);
        $incident = $service->openIncident([
            'incident_type' => 'credential_compromise',
            'severity'      => 'critical',
            'summary'       => 'Admin credentials leaked via phishing.',
        ], $this->securityOfficer->id);

        $escalated = $service->escalate($incident->id, $this->securityOfficer->id, 'Escalated to CISO and DPO.');
        $this->assertEquals('triaging', $escalated->status);
    }

    public function test_security_incident_resolution(): void
    {
        $service  = $this->app->make(SecurityIncidentService::class);
        $incident = $service->openIncident([
            'incident_type' => 'malware',
            'severity'      => 'medium',
            'summary'       => 'Ransomware detected on workstation.',
        ], $this->securityOfficer->id);

        $resolved = $service->resolve($incident->id, $this->securityOfficer->id, 'Workstation re-imaged and scanned clean.');
        $this->assertEquals('resolved', $resolved->status);
        $this->assertNotNull($resolved->resolved_at);
    }

    // -------------------------------------------------------------------------
    // ComplianceExportService
    // -------------------------------------------------------------------------

    public function test_can_request_compliance_export(): void
    {
        $service = $this->app->make(ComplianceExportService::class);

        $export = $service->requestExport(
            'audit_log',
            $this->securityOfficer->id,
            ['from' => now()->subMonth()->toDateString(), 'to' => now()->toDateString()],
            'csv'
        );

        $this->assertNotNull($export->id);
        $this->assertEquals('pending', $export->status);
        $this->assertEquals('csv', $export->format);
        $this->assertDatabaseHas('audit_events', [
            'actor_id'      => $this->securityOfficer->id,
            'action_type'   => 'create',
            'resource_type' => 'compliance_export',
        ]);
    }

    public function test_compliance_export_mark_ready_sets_expiry(): void
    {
        $service = $this->app->make(ComplianceExportService::class);

        $export = $service->requestExport('breach_reports', $this->securityOfficer->id, [], 'pdf');
        $ready  = $service->markReady($export->id, 'exports/breach_report_2026.pdf');

        $this->assertEquals('ready', $ready->status);
        $this->assertNotNull($ready->expires_at);
        $this->assertGreaterThan(now(), $ready->expires_at);
    }

    public function test_compliance_export_mark_failed(): void
    {
        $service = $this->app->make(ComplianceExportService::class);

        $export = $service->requestExport('audit_log', $this->securityOfficer->id, [], 'json');
        $failed = $service->markFailed($export->id, 'Database timeout during export generation.');

        $this->assertEquals('failed', $failed->status);
    }

    public function test_get_ready_exports_for_user(): void
    {
        $service = $this->app->make(ComplianceExportService::class);

        $export = $service->requestExport('access_review', $this->securityOfficer->id, [], 'csv');
        $service->markReady($export->id, 'exports/access_review_2026.csv');

        $ready = $service->getReadyExportsFor($this->securityOfficer->id);
        $this->assertGreaterThanOrEqual(1, $ready->count());
    }

    // -------------------------------------------------------------------------
    // SuspiciousAccessDetectionService
    // -------------------------------------------------------------------------

    public function test_high_volume_access_detection_creates_flag_when_threshold_met(): void
    {
        // Create 50 patient-resource access events within the last hour
        for ($i = 0; $i < 50; $i++) {
            AuditEvent::create([
                'actor_id'      => $this->targetUser->id,
                'facility_id'   => $this->facility->id,
                'action_type'   => 'read',
                'resource_type' => 'patient',
                'created_at'    => now()->subMinutes(30),
            ]);
        }

        $service = $this->app->make(SuspiciousAccessDetectionService::class);
        $flag    = $service->detectHighVolumeAccess($this->targetUser->id, $this->facility->id);

        $this->assertNotNull($flag);
        $this->assertEquals('high_volume_access', $flag->flag_type);
        $this->assertEquals('high', $flag->severity);
    }

    public function test_high_volume_access_detection_returns_null_below_threshold(): void
    {
        // Only 5 events — well below threshold of 50
        for ($i = 0; $i < 5; $i++) {
            AuditEvent::create([
                'actor_id'      => $this->targetUser->id,
                'facility_id'   => $this->facility->id,
                'action_type'   => 'read',
                'resource_type' => 'patient',
                'created_at'    => now()->subMinutes(30),
            ]);
        }

        $service = $this->app->make(SuspiciousAccessDetectionService::class);
        $flag    = $service->detectHighVolumeAccess($this->targetUser->id, $this->facility->id);

        $this->assertNull($flag);
    }

    // -------------------------------------------------------------------------
    // PermissionAudit append-only invariant
    // -------------------------------------------------------------------------

    public function test_permission_audit_record_creates_append_only_entry(): void
    {
        PermissionAudit::record(
            $this->securityOfficer->id,
            $this->targetUser->id,
            'grant',
            ['permission_key' => 'emergency_access.use', 'reason' => 'On-call provider']
        );

        $this->assertDatabaseHas('permission_audits', [
            'actor_id'       => $this->securityOfficer->id,
            'target_user_id' => $this->targetUser->id,
            'action'         => 'grant',
        ]);
    }

    public function test_permission_audit_update_throws_logic_exception(): void
    {
        $this->expectException(\LogicException::class);

        PermissionAudit::record(
            $this->securityOfficer->id,
            $this->targetUser->id,
            'revoke',
            ['permission_key' => 'admin.platform_settings']
        );

        $audit = PermissionAudit::first();
        $audit->update(['action' => 'grant']); // must throw LogicException
    }

    public function test_permission_audit_delete_throws_logic_exception(): void
    {
        $this->expectException(\LogicException::class);

        PermissionAudit::record(
            $this->securityOfficer->id,
            $this->targetUser->id,
            'revoke',
            ['permission_key' => 'admin.platform_settings']
        );

        $audit = PermissionAudit::first();
        $audit->delete(); // must throw LogicException
    }
}
