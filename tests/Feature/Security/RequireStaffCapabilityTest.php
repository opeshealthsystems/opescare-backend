<?php

namespace Tests\Feature\Security;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use App\Support\StaffCapabilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The one role → capability map, and the middleware that enforces it on the
 * web and on the clinician API.
 */
class RequireStaffCapabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'capability:prescribe'])->get('/_test/prescribe', fn () => 'desk');
        Route::middleware(['web', 'auth', 'capability:clinical_chart,lab_results'])->get('/_test/results', fn () => 'results');
        Route::middleware(['api', 'capability:prescribe'])->get('/api/_test/prescribe', fn () => ['ok' => true]);
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)])->id,
        ]);
    }

    public function test_the_map_reads_as_intended(): void
    {
        $this->assertTrue(StaffCapabilities::roleCan('doctor', 'prescribe'));
        $this->assertTrue(StaffCapabilities::roleCan('doctor', 'clinical_chart'));
        $this->assertTrue(StaffCapabilities::roleCan('nurse', 'clinical_chart'));
        $this->assertFalse(StaffCapabilities::roleCan('nurse', 'prescribe'));
        $this->assertTrue(StaffCapabilities::roleCan('pharmacist', 'dispense'));
        $this->assertFalse(StaffCapabilities::roleCan('pharmacist', 'clinical_chart'));
        $this->assertTrue(StaffCapabilities::roleCan('labtech', 'lab_results'));
        $this->assertFalse(StaffCapabilities::roleCan('labtech', 'prescribe'));
        $this->assertTrue(StaffCapabilities::roleCan('receptionist', 'front_desk'));
        $this->assertFalse(StaffCapabilities::roleCan('receptionist', 'clinical_chart'));
        $this->assertFalse(StaffCapabilities::roleCan('receptionist', 'lab_results'));
        $this->assertTrue(StaffCapabilities::roleCan('receptionist', 'identify_patient'));
        $this->assertFalse(StaffCapabilities::roleCan('patient', 'identify_patient'));
        $this->assertFalse(StaffCapabilities::roleCan(null, 'messaging'));
        $this->assertContains('prescribe', StaffCapabilities::forRole('doctor'));
        $this->assertSame([], StaffCapabilities::forRole('patient'));
        $this->assertNotEmpty(StaffCapabilities::all());
    }

    public function test_web_route_admits_the_capability_and_refuses_others_with_403(): void
    {
        $this->actingAs($this->userWithRole('doctor'))->get('/_test/prescribe')->assertOk();
        $this->actingAs($this->userWithRole('nurse'))->get('/_test/prescribe')->assertForbidden();
        $this->actingAs($this->userWithRole('receptionist'))->get('/_test/prescribe')->assertForbidden();

        // Any-of: results are for clinicians AND for the lab.
        $this->actingAs($this->userWithRole('labtech'))->get('/_test/results')->assertOk();
        $this->actingAs($this->userWithRole('nurse'))->get('/_test/results')->assertOk();
        $this->actingAs($this->userWithRole('pharmacist'))->get('/_test/results')->assertForbidden();
    }

    public function test_api_refusal_is_json_with_a_reason(): void
    {
        $this->actingAs($this->userWithRole('pharmacist'))
            ->getJson('/api/_test/prescribe')
            ->assertForbidden()
            ->assertJsonPath('error_code', 'role_forbidden')
            ->assertJsonPath('required.0', 'prescribe');
    }

    // ── Facility-scoped role ──────────────────────────────────────────────────

    private function postAs(User $user, Facility $facility, string $roleName): void
    {
        FacilityRoleAssignment::create([
            'user_id'     => $user->id,
            'facility_id' => $facility->id,
            'role_id'     => Role::firstOrCreate(['name' => $roleName], ['description' => ucfirst($roleName)])->id,
            'is_active'   => true,
            'assigned_at' => now(),
        ]);
    }

    public function test_the_role_at_the_active_facility_wins_over_the_global_role(): void
    {
        // A doctor globally, posted as a NURSE at the facility the session is
        // acting for: EnsurePortalAccess judges the nurse, so must we.
        $doctorHere = $this->userWithRole('doctor');
        $facility   = Facility::factory()->create();
        $this->postAs($doctorHere, $facility, 'nurse');

        $this->assertSame('nurse', StaffCapabilities::roleFor($doctorHere, $facility->id));
        $this->assertSame('doctor', StaffCapabilities::roleFor($doctorHere, null));
        $this->assertFalse(StaffCapabilities::canAt($doctorHere, $facility->id, 'prescribe'));
        $this->assertTrue(StaffCapabilities::canAt($doctorHere, null, 'prescribe'));

        $this->actingAs($doctorHere)
            ->withSession(['active_facility_id' => $facility->id])
            ->get('/_test/prescribe')
            ->assertForbidden();

        // The mirror image: a nurse globally who is the DOCTOR at this posting.
        $nurseHere = $this->userWithRole('nurse');
        $other     = Facility::factory()->create();
        $this->postAs($nurseHere, $other, 'doctor');

        $this->actingAs($nurseHere)->get('/_test/prescribe')->assertForbidden();
        $this->actingAs($nurseHere)
            ->withSession(['active_facility_id' => $other->id])
            ->get('/_test/prescribe')
            ->assertOk();
    }

    public function test_without_a_session_the_primary_facility_then_the_global_role_decide(): void
    {
        $facility = Facility::factory()->create();
        $user     = User::factory()->create([
            'role_id'             => Role::firstOrCreate(['name' => 'nurse'], ['description' => 'Nurse'])->id,
            'primary_facility_id' => $facility->id,
        ]);
        $this->postAs($user, $facility, 'doctor');

        // No active_facility_id in the session: the primary facility's posting
        // is the fallback (same order as EnsurePortalAccess), so this nurse
        // prescribes at her own hospital.
        $this->actingAs($user)->get('/_test/prescribe')->assertOk();

        // A malformed facility id (a tampered session) is treated as "no
        // posting there" and falls back to the global role — never a 500.
        $this->assertSame('nurse', StaffCapabilities::roleFor($user, 'not-a-uuid'));
        $this->assertFalse(StaffCapabilities::canAt($user, 'not-a-uuid', 'prescribe'));

        // Nobody signed in: nothing is held.
        $this->assertFalse(StaffCapabilities::can(null, 'identify_patient'));
        $this->assertFalse(StaffCapabilities::canAt(null, $facility->id, 'identify_patient'));
    }

    public function test_the_clinician_api_judges_the_role_at_the_tokens_facility(): void
    {
        // The API has no session: the facility comes from the request attribute
        // AuthenticateMobileProvider sets from the bearer token. A doctor whose
        // token was issued for a posting where she is a nurse may not prescribe
        // through that token.
        $user     = $this->userWithRole('doctor');
        $facility = Facility::factory()->create();
        $this->postAs($user, $facility, 'nurse');

        ProviderAttributesStub::$userId     = $user->id;
        ProviderAttributesStub::$facilityId = $facility->id;
        Route::middleware(['api', ProviderAttributesStub::class, 'capability:prescribe'])->get('/api/_test/token-prescribe', fn () => ['ok' => true]);

        $this->getJson('/api/_test/token-prescribe')
            ->assertForbidden()
            ->assertJsonPath('error_code', 'role_forbidden');

        // A token not bound to a posting falls back to the global role.
        ProviderAttributesStub::$facilityId = null;
        $this->getJson('/api/_test/token-prescribe')->assertOk();
    }

    // ── The real routes carry the gate ───────────────────────────────────────

    /**
     * The middleware only protects what it is attached to. This pins the
     * capability on every named web route the map speaks about, so removing a
     * gate (or moving a route out of its group) fails here instead of in prod.
     */
    public function test_every_mapped_web_route_carries_its_capability(): void
    {
        $expected = [
            'portals.staff.appointments'           => 'capability:front_desk,schedule',
            'portals.staff.appointments.create'    => 'capability:front_desk,schedule',
            'portals.staff.appointments.store'     => 'capability:front_desk,schedule',
            'portals.staff.appointments.confirm'   => 'capability:front_desk,schedule',
            'portals.staff.appointments.cancel'    => 'capability:front_desk,schedule',
            'portals.staff.appointments.check-in'  => 'capability:front_desk,schedule',
            'portals.staff.appointments.no-show'   => 'capability:front_desk,schedule',
            'portals.staff.inventory.blood'        => 'capability:blood_inventory',
            'portals.staff.inventory.blood.upsert' => 'capability:blood_inventory',
            'portals.staff.inventory.blood.adjust' => 'capability:blood_inventory',
            'portals.staff.inventory.blood.flag'   => 'capability:blood_inventory',
            'portals.staff.messages'               => 'capability:messaging',
            'portals.staff.messages.show'          => 'capability:messaging',
            'portals.staff.messages.send'          => 'capability:messaging',
            'portals.facility.team'                => 'capability:facility_team',
            'portals.facility.team.invite'         => 'capability:facility_team',
            'portals.facility.team.join.approve'   => 'capability:facility_team',
            'portals.prescriber.index'             => 'capability:prescribe',
            'portals.prescriber.create'            => 'capability:prescribe',
            'portals.prescriber.store'             => 'capability:prescribe',
            'portals.prescriber.show'              => 'capability:prescribe',
            'portals.prescriber.print'             => 'capability:prescribe',
            'portals.prescriber.void'              => 'capability:prescribe',
            'portals.pharmacy.dashboard'           => 'capability:dispense,pharmacy_stock',
            'portals.pharmacy.prescriptions'       => 'capability:dispense',
            'portals.pharmacy.prescriptions.lookup' => 'capability:dispense',
            'portals.pharmacy.dispense'            => 'capability:dispense',
            'portals.pharmacy.stock'               => 'capability:pharmacy_stock',
            'portals.pharmacy.stock.report'        => 'capability:pharmacy_stock',
            'portals.pharmacy.controlled'          => 'capability:pharmacy_stock',
        ];

        $routes  = Route::getRoutes();
        $missing = [];

        foreach ($expected as $name => $gate) {
            $route = $routes->getByName($name);
            if (! $route) {
                $missing[] = "{$name}: route does not exist";
                continue;
            }

            $stack = $route->gatherMiddleware();
            if (! in_array($gate, $stack, true)) {
                $missing[] = "{$name}: expected {$gate}, stack is [" . implode(', ', $stack) . ']';
            }

            // The gate must come AFTER portal.access + facility.context, so it
            // judges the facility the session has just resolved.
            $capAt = array_search($gate, $stack, true);
            foreach (['portal.access', 'facility.context'] as $before) {
                $beforeAt = array_search($before, $stack, true);
                if ($beforeAt !== false && $beforeAt > $capAt) {
                    $missing[] = "{$name}: {$gate} runs before {$before}";
                }
            }
        }

        // The landing page must stay open to every facility role.
        $landing = $routes->getByName('portals.staff');
        $this->assertNotNull($landing);
        foreach ($landing->gatherMiddleware() as $m) {
            $this->assertStringStartsNotWith('capability:', $m, '/facility is the landing page for roles with no mapped surface; do not gate it');
        }

        $this->assertSame([], $missing, "\n" . implode("\n", $missing) . "\n");
    }
}

/**
 * Stands in for AuthenticateMobileProvider: sets the request attributes a
 * bearer token would, so the capability middleware can be exercised on an
 * `api` route without minting a real provider token.
 */
class ProviderAttributesStub
{
    public static ?string $userId = null;
    public static ?string $facilityId = null;

    public function handle($request, \Closure $next)
    {
        $request->attributes->set('provider_user_id', self::$userId);
        $request->attributes->set('provider_facility_id', self::$facilityId);

        return $next($request);
    }
}
