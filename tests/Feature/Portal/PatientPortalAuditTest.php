<?php

namespace Tests\Feature\Portal;

use App\Models\AuditEvent;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Services\Portal\PortalContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Feature tests for patient portal audit event creation.
 *
 * Verifies that PortalContextService::auditPatientAccess() correctly
 * records audit events when patient data is accessed via the portal.
 *
 * Tests are kept at the service level (bypassing the HTTP stack) because
 * the controller's role is thin (just calls auditPatientAccess); the
 * HTTP integration is covered by DemoRoutesTest and middleware tests.
 */
class PatientPortalAuditTest extends TestCase
{
    use RefreshDatabase;

    private PortalContextService $ctx;

    protected function setUp(): void
    {
        parent::setUp();
        config(['demo.enabled' => false]);
        $this->ctx = new PortalContextService();
    }

    // ── Successful audit creation ────────────────────────────────────────────

    public function test_patient_dashboard_access_creates_audit_event(): void
    {
        $facility = Facility::forceCreate([
            'id'      => '99a00000-0000-0000-0000-000000000001',
            'name'    => 'Audit Test Hospital',
            'type'    => 'hospital',
            'is_demo' => false,
        ]);

        $user = User::forceCreate([
            'name'     => 'Test Patient User',
            'email'    => 'ppa_patient@test.com',
            'password' => bcrypt('secret'),
            'is_demo'  => false,
        ]);

        $this->actingAs($user);
        session(['active_facility_id' => $facility->id]);

        $patientId = '9a700000-0000-0000-0000-000000000001';

        $this->ctx->auditPatientAccess(
            actionType:   'patient_dashboard_view',
            resourceType: 'Patient',
            resourceId:   $patientId,
            patientId:    $patientId,
        );

        $this->assertDatabaseHas('audit_events', [
            'actor_id'      => $user->id,
            'facility_id'   => $facility->id,
            'patient_id'    => $patientId,
            'action_type'   => 'patient_dashboard_view',
            'resource_type' => 'Patient',
            'resource_id'   => $patientId,
            'source_system' => 'portal',
        ]);
    }

    public function test_qr_generation_creates_distinct_audit_event(): void
    {
        $facility = Facility::forceCreate([
            'id'      => '99a00000-0000-0000-0000-000000000002',
            'name'    => 'QR Audit Hospital',
            'type'    => 'hospital',
            'is_demo' => false,
        ]);

        $user = User::forceCreate([
            'name'     => 'QR Patient User',
            'email'    => 'ppa_qr@test.com',
            'password' => bcrypt('secret'),
            'is_demo'  => false,
        ]);

        $this->actingAs($user);
        session(['active_facility_id' => $facility->id]);

        $patientId  = '9a700000-0000-0000-0000-000000000002';
        $resourceId = 'c4700000-0000-0000-0000-000000000001';

        $this->ctx->auditPatientAccess(
            actionType:   'temporary_qr_generated',
            resourceType: 'HealthIdQrToken',
            resourceId:   $resourceId,
            patientId:    $patientId,
        );

        $this->assertDatabaseHas('audit_events', [
            'actor_id'      => $user->id,
            'facility_id'   => $facility->id,
            'patient_id'    => $patientId,
            'action_type'   => 'temporary_qr_generated',
            'resource_type' => 'HealthIdQrToken',
            'resource_id'   => $resourceId,
            'source_system' => 'portal',
        ]);
    }

    public function test_access_log_view_creates_audit_event(): void
    {
        $facility = Facility::forceCreate([
            'id'      => '99a00000-0000-0000-0000-000000000003',
            'name'    => 'Log Audit Hospital',
            'type'    => 'hospital',
            'is_demo' => false,
        ]);

        $user = User::forceCreate([
            'name'     => 'Log Viewer',
            'email'    => 'ppa_logs@test.com',
            'password' => bcrypt('secret'),
            'is_demo'  => false,
        ]);

        $this->actingAs($user);
        session(['active_facility_id' => $facility->id]);

        $patientId = '9a700000-0000-0000-0000-000000000003';

        $this->ctx->auditPatientAccess(
            actionType:   'patient_access_log_view',
            resourceType: 'Patient',
            resourceId:   $patientId,
            patientId:    $patientId,
        );

        $this->assertDatabaseHas('audit_events', [
            'actor_id'    => $user->id,
            'facility_id' => $facility->id,
            'patient_id'  => $patientId,
            'action_type' => 'patient_access_log_view',
        ]);
    }

    // ── Audit count and isolation ─────────────────────────────────────────────

    public function test_multiple_actions_create_separate_audit_events(): void
    {
        $facility = Facility::forceCreate([
            'id'      => '99a00000-0000-0000-0000-000000000004',
            'name'    => 'Multi Audit Hospital',
            'type'    => 'hospital',
            'is_demo' => false,
        ]);

        $user = User::forceCreate([
            'name'     => 'Multi Action User',
            'email'    => 'ppa_multi@test.com',
            'password' => bcrypt('secret'),
            'is_demo'  => false,
        ]);

        $this->actingAs($user);
        session(['active_facility_id' => $facility->id]);

        $patientId = '9a700000-0000-0000-0000-000000000004';

        $this->ctx->auditPatientAccess('patient_dashboard_view', 'Patient', $patientId, $patientId);
        $this->ctx->auditPatientAccess('temporary_qr_generated', 'HealthIdQrToken', '1b1dc491-0000-4000-8000-000000000123', $patientId);

        $count = AuditEvent::where('actor_id', $user->id)
                            ->where('patient_id', $patientId)
                            ->count();

        $this->assertEquals(2, $count, 'Each action must create a separate audit event');
    }

    // ── Demo user audit isolation ────────────────────────────────────────────

    public function test_demo_user_access_creates_audit_event_in_demo_context(): void
    {
        // Enable demo to create demo user and facility
        config(['demo.enabled' => true]);

        $facility = Facility::forceCreate([
            'id'      => '99a00000-0000-0000-0000-000000000005',
            'name'    => 'Demo Hospital',
            'type'    => 'hospital',
            'is_demo' => true,
        ]);

        $user = User::forceCreate([
            'name'     => 'Demo Patient',
            'email'    => 'ppa_demo@demo.test',
            'password' => bcrypt('DemoPass!2026'),
            'is_demo'  => true,
        ]);

        $this->actingAs($user);
        session(['active_facility_id' => $facility->id]);

        $patientId = '9a700000-0000-0000-0000-000000000005';

        $this->ctx->auditPatientAccess('patient_dashboard_view', 'Patient', $patientId, $patientId);

        // Audit event must still be written (demo users generate real audit trails)
        $this->assertDatabaseHas('audit_events', [
            'actor_id'    => $user->id,
            'patient_id'  => $patientId,
            'action_type' => 'patient_dashboard_view',
            'source_system' => 'portal',
        ]);
    }

    // ── Silent failure on invalid state ──────────────────────────────────────

    public function test_audit_does_not_throw_when_unauthenticated(): void
    {
        // No Auth::setUser — anonymous context
        Auth::logout();

        try {
            $this->ctx->auditPatientAccess(
                'patient_dashboard_view',
                'Patient',
                'pat-xxx',
                'pat-xxx',
            );
            $this->assertTrue(true, 'Must not throw for unauthenticated access');
        } catch (\Throwable $e) {
            $this->fail('auditPatientAccess must silently handle errors: ' . $e->getMessage());
        }
    }
}
