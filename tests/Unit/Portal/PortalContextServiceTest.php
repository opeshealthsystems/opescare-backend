<?php

namespace Tests\Unit\Portal;

use App\Models\AuditEvent;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Services\Portal\PortalContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Unit tests for PortalContextService.
 *
 * Covers: actorId, facilityId, isDemo, scopeToFacility, scopeToDemo,
 * auditPatientAccess (success and silent-fail paths).
 */
class PortalContextServiceTest extends TestCase
{
    use RefreshDatabase;

    private PortalContextService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        // Disable demo isolation so models are visible without is_demo filter
        config(['demo.enabled' => false]);
        $this->svc = new PortalContextService();
    }

    // ── actorId ─────────────────────────────────────────────────────────────

    public function test_actor_id_returns_null_when_unauthenticated(): void
    {
        $this->assertNull($this->svc->actorId());
    }

    public function test_actor_id_returns_user_id_when_authenticated(): void
    {
        $user = User::forceCreate([
            'name'     => 'Test User',
            'email'    => 'ctx_actor@test.com',
            'password' => bcrypt('secret'),
            'is_demo'  => false,
        ]);

        $this->actingAs($user);

        $this->assertEquals($user->id, $this->svc->actorId());
    }

    // ── facilityId ──────────────────────────────────────────────────────────

    public function test_facility_id_returns_null_when_unauthenticated_and_no_session(): void
    {
        $this->assertNull($this->svc->facilityId());
    }

    public function test_facility_id_prefers_session_over_primary_facility(): void
    {
        $facility1 = Facility::forceCreate(['id' => 'fac00000-0000-0000-0000-000000000001', 'name' => 'Primary', 'type' => 'hospital', 'is_demo' => false]);
        $facility2 = Facility::forceCreate(['id' => 'fac00000-0000-0000-0000-000000000002', 'name' => 'Session',  'type' => 'clinic',   'is_demo' => false]);

        $user = User::forceCreate([
            'name'                => 'Facility User',
            'email'               => 'ctx_fac@test.com',
            'password'            => bcrypt('secret'),
            'primary_facility_id' => $facility1->id,
            'is_demo'             => false,
        ]);

        $this->actingAs($user);
        session(['active_facility_id' => $facility2->id]);

        $this->assertEquals($facility2->id, $this->svc->facilityId());
    }

    public function test_facility_id_falls_back_to_primary_facility_when_no_session(): void
    {
        $facility = Facility::forceCreate(['id' => 'fac00000-0000-0000-0000-000000000003', 'name' => 'My Hospital', 'type' => 'hospital', 'is_demo' => false]);

        $user = User::forceCreate([
            'name'                => 'Primary User',
            'email'               => 'ctx_prim@test.com',
            'password'            => bcrypt('secret'),
            'primary_facility_id' => $facility->id,
            'is_demo'             => false,
        ]);

        $this->actingAs($user);

        $this->assertEquals($facility->id, $this->svc->facilityId());
    }

    public function test_facility_id_returns_null_for_user_with_no_facility(): void
    {
        $user = User::forceCreate([
            'name'     => 'Platform Admin',
            'email'    => 'ctx_admin@test.com',
            'password' => bcrypt('secret'),
            'is_demo'  => false,
        ]);

        $this->actingAs($user);

        $this->assertNull($this->svc->facilityId());
    }

    // ── isDemo ───────────────────────────────────────────────────────────────

    public function test_is_demo_returns_false_for_real_user(): void
    {
        $user = User::forceCreate([
            'name'     => 'Real User',
            'email'    => 'ctx_real@test.com',
            'password' => bcrypt('secret'),
            'is_demo'  => false,
        ]);

        $this->actingAs($user);

        $this->assertFalse($this->svc->isDemo());
    }

    public function test_is_demo_returns_true_for_demo_user(): void
    {
        // Temporarily enable demo so forceCreate doesn't get blocked
        config(['demo.enabled' => true]);

        $user = User::forceCreate([
            'name'     => 'Demo User',
            'email'    => 'ctx_demo@test.com',
            'password' => bcrypt('secret'),
            'is_demo'  => true,
        ]);

        $this->actingAs($user);

        $this->assertTrue($this->svc->isDemo());
    }

    public function test_is_demo_returns_false_when_unauthenticated(): void
    {
        $this->assertFalse($this->svc->isDemo());
    }

    // ── scopeToFacility ─────────────────────────────────────────────────────

    public function test_scope_to_facility_filters_by_active_facility(): void
    {
        $fac1 = Facility::forceCreate(['id' => 'fac00000-0000-0000-0000-000000000011', 'name' => 'Alpha', 'type' => 'hospital', 'is_demo' => false]);
        $fac2 = Facility::forceCreate(['id' => 'fac00000-0000-0000-0000-000000000012', 'name' => 'Beta',  'type' => 'clinic',   'is_demo' => false]);

        $user = User::forceCreate([
            'name'                => 'Scoped User',
            'email'               => 'ctx_scope@test.com',
            'password'            => bcrypt('secret'),
            'primary_facility_id' => $fac1->id,
            'is_demo'             => false,
        ]);

        $this->actingAs($user);

        $facilities = $this->svc->scopeToFacility(Facility::withoutGlobalScope('isolate_demo'), 'id')->get();

        $this->assertCount(1, $facilities);
        $this->assertEquals('Alpha', $facilities->first()->name);
    }

    public function test_scope_to_facility_returns_unscoped_query_when_no_facility(): void
    {
        Facility::forceCreate(['id' => 'fac00000-0000-0000-0000-000000000021', 'name' => 'X', 'type' => 'hospital', 'is_demo' => false]);
        Facility::forceCreate(['id' => 'fac00000-0000-0000-0000-000000000022', 'name' => 'Y', 'type' => 'clinic',   'is_demo' => false]);

        $user = User::forceCreate([
            'name'     => 'Platform Admin',
            'email'    => 'ctx_unscoped@test.com',
            'password' => bcrypt('secret'),
            'is_demo'  => false,
        ]);

        $this->actingAs($user);

        // No facility → query unscoped, should see all facilities
        $facilities = $this->svc->scopeToFacility(Facility::withoutGlobalScope('isolate_demo'), 'id')->get();

        $this->assertGreaterThanOrEqual(2, $facilities->count());
    }

    // ── scopeToDemo ─────────────────────────────────────────────────────────

    public function test_scope_to_demo_filters_real_records_for_real_user(): void
    {
        config(['demo.enabled' => false]);
        Facility::forceCreate(['id' => 'fac00000-0000-0000-0000-000000000031', 'name' => 'Real', 'type' => 'hospital', 'is_demo' => false]);

        config(['demo.enabled' => true]);
        Facility::forceCreate(['id' => 'fac00000-0000-0000-0000-000000000032', 'name' => 'Demo', 'type' => 'clinic', 'is_demo' => true]);

        // Real user — isDemo() returns false
        config(['demo.enabled' => false]);
        $user = User::forceCreate([
            'name'     => 'Real Only',
            'email'    => 'ctx_scopedemo@test.com',
            'password' => bcrypt('secret'),
            'is_demo'  => false,
        ]);
        $this->actingAs($user);

        $results = $this->svc->scopeToDemo(Facility::withoutGlobalScope('isolate_demo'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Real', $results->first()->name);
    }

    // ── auditPatientAccess ───────────────────────────────────────────────────

    public function test_audit_patient_access_creates_event_with_correct_fields(): void
    {
        config(['demo.enabled' => false]);

        $user = User::forceCreate([
            'name'     => 'Auditor',
            'email'    => 'ctx_audit@test.com',
            'password' => bcrypt('secret'),
            'is_demo'  => false,
        ]);

        $facility = Facility::forceCreate(['id' => 'fac00000-0000-0000-0000-000000000041', 'name' => 'Audit Hospital', 'type' => 'hospital', 'is_demo' => false]);

        $this->actingAs($user);
        session(['active_facility_id' => $facility->id]);

        $patientId  = '9a700000-0000-0000-0000-000000000001';
        $resourceId = '4e500000-0000-0000-0000-000000000001';

        $this->svc->auditPatientAccess(
            'patient_dashboard_view',
            'Patient',
            $resourceId,
            $patientId
        );

        $this->assertDatabaseHas('audit_events', [
            'actor_id'      => $user->id,
            'facility_id'   => $facility->id,
            'patient_id'    => $patientId,
            'action_type'   => 'patient_dashboard_view',
            'resource_type' => 'Patient',
            'resource_id'   => $resourceId,
            'source_system' => 'portal',
        ]);
    }

    public function test_audit_patient_access_does_not_throw_on_failure(): void
    {
        config(['demo.enabled' => false]);

        // Do NOT authenticate — auditPatientAccess must handle gracefully.
        // SQLite (used in tests) doesn't enforce FK constraints, so the insert
        // with actor_id='anonymous' may succeed silently rather than throw.
        // The key contract is simply: no exception leaks out of the method.

        try {
            $this->svc->auditPatientAccess('test_action', 'TestResource');
            $this->assertTrue(true, 'auditPatientAccess must not throw — silent success or silent catch');
        } catch (\Throwable $e) {
            $this->fail('auditPatientAccess must not throw, but got: ' . $e->getMessage());
        }
    }
}
