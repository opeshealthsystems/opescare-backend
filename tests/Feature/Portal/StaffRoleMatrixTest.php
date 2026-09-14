<?php

namespace Tests\Feature\Portal;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use App\Services\Dashboard\DashboardProfileService;
use App\Support\StaffCapabilities;
use Database\Seeders\Support\DemoCareTeam;
use Database\Seeders\Support\DemoFacilityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The role → surface matrix for the web portals, frozen into a test.
 *
 * config/staff_capabilities.php is the one map; `capability:` on the routes
 * and @capability in the sidebars are its two enforcement points. This test
 * signs in as each professional role — posted at a facility, with the session
 * resolved, exactly as RequireFacilityContext leaves a real user — and checks
 * that every representative route answers the way the map says:
 *
 *   200  the role holds the capability and the page renders
 *   403  the role is inside the portal (portal.access lets it through) but
 *        does not hold the capability — RequireStaffCapability refuses it
 *   302  portal.access sent it to its own portal first (a different portal
 *        bucket altogether); the capability layer never even ran
 *
 * The audit this freezes found four roles able to WRITE prescriptions, the
 * pharmacist inside the appointment book and the blood bank, and the lab tech
 * inside the appointment book. Every one of those is a 403 below.
 *
 * The inverse is tested too: for every capability a role holds that has a web
 * page, the landing page must LINK to it. A page reachable only by URL is a
 * feature that does not exist for that role.
 */
class StaffRoleMatrixTest extends TestCase
{
    use RefreshDatabase;

    /** Sentinel: the route must not be refused by the capability layer (any status but 403). */
    private const NOT_403 = 'not-403';

    protected function setUp(): void
    {
        parent::setUp();

        // Same order DatabaseSeeder uses — roles carry FKs onto both.
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{0: User, 1: Facility} a professional posted at one facility */
    private function staff(string $roleName): array
    {
        $role     = Role::where('name', $roleName)->firstOrFail();
        $facility = Facility::factory()->create(['type' => 'hospital']);

        $user = User::factory()->create([
            'role_id'             => $role->id,
            'primary_facility_id' => $facility->id,
            'status'              => 'active',
        ]);

        FacilityRoleAssignment::create([
            'user_id'     => $user->id,
            'facility_id' => $facility->id,
            'role_id'     => $role->id,
            'is_active'   => true,
            'assigned_at' => now(),
        ]);

        return [$user, $facility];
    }

    private function actingAsStaff(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    /**
     * Per role: "METHOD /path" => expected status, or a redirect target as
     * "302 /where", or NOT_403.
     */
    public static function matrix(): array
    {
        $uuid = '11111111-2222-4333-8444-555555555555';

        // Everything under admin/* except bare /admin is platform-tier only:
        // RequirePlatformAdmin is global on the `web` group and runs BEFORE
        // portal.access, so a facility role gets a 403 there, not a redirect.
        $facilityOnly = [
            'GET /pharmacy'                      => '302 /facility',
            'GET /pharmacy/prescriptions'        => '302 /facility',
            'GET /pharmacy/prescriptions/lookup' => '302 /facility',
            'GET /pharmacy/stock'                => '302 /facility',
            'GET /pharmacy/controlled'           => '302 /facility',
            'GET /facility/team'                 => '302 /facility',
            'GET /admin/appointments'            => 403,
        ];

        return [
            'doctor' => ['doctor', [
                'GET /facility'                      => 200,
                'GET /facility/appointments'         => 200,
                'GET /facility/appointments/create'  => 200,
                "POST /facility/appointments/{$uuid}/check-in" => self::NOT_403,
                'GET /facility/prescriptions'        => 200,
                'GET /facility/prescriptions/new'    => 200,
                'POST /facility/prescriptions'       => self::NOT_403,
                'GET /facility/inventory/blood'      => 200,
                'GET /facility/messages'             => 200,
            ] + $facilityOnly],

            'nurse' => ['nurse', [
                'GET /facility'                      => 200,
                'GET /facility/appointments'         => 200,
                'GET /facility/appointments/create'  => 200,
                "POST /facility/appointments/{$uuid}/check-in" => self::NOT_403,
                'GET /facility/prescriptions'        => 403,
                'GET /facility/prescriptions/new'    => 403,
                'POST /facility/prescriptions'       => 403,
                'GET /facility/inventory/blood'      => 200,
                'GET /facility/messages'             => 200,
            ] + $facilityOnly],

            'receptionist' => ['receptionist', [
                'GET /facility'                      => 200,
                'GET /facility/appointments'         => 200,
                'GET /facility/appointments/create'  => 200,
                "POST /facility/appointments/{$uuid}/check-in" => self::NOT_403,
                'GET /facility/prescriptions'        => 403,
                'GET /facility/prescriptions/new'    => 403,
                'POST /facility/prescriptions'       => 403,
                'GET /facility/inventory/blood'      => 200,
                'GET /facility/messages'             => 200,
            ] + $facilityOnly],

            'labtech' => ['labtech', [
                'GET /facility'                      => 200,
                'GET /facility/appointments'         => 403,
                'GET /facility/appointments/create'  => 403,
                "POST /facility/appointments/{$uuid}/check-in" => 403,
                'GET /facility/prescriptions'        => 403,
                'GET /facility/prescriptions/new'    => 403,
                'POST /facility/prescriptions'       => 403,
                'GET /facility/inventory/blood'      => 200,
                'GET /facility/messages'             => 200,
                // Lab roles are in the facility/team portal bucket now (a lab
                // manager runs its own team), so a labtech is refused by the
                // capability layer — a 403 that names the reason, not a bounce.
                'GET /facility/team'                 => 403,
            ] + $facilityOnly],

            // The pharmacist lives in TWO portal buckets (facility + pharmacy):
            // portal.access lets it into /facility/*, so every refusal there is
            // the capability layer's — and its own portal answers 200.
            'pharmacist' => ['pharmacist', [
                'GET /facility'                      => 200,
                'GET /facility/appointments'         => 403,
                'GET /facility/appointments/create'  => 403,
                "POST /facility/appointments/{$uuid}/check-in" => 403,
                'GET /facility/prescriptions'        => 403,
                'GET /facility/prescriptions/new'    => 403,
                'POST /facility/prescriptions'       => 403,
                'GET /facility/inventory/blood'      => 403,
                'GET /facility/messages'             => 200,
                'GET /pharmacy'                      => 200,
                'GET /pharmacy/prescriptions'        => 200,
                'GET /pharmacy/prescriptions/lookup' => 200,
                'GET /pharmacy/stock'                => 200,
                'GET /pharmacy/controlled'           => 200,
                // Same reasoning as labtech: in the bucket, refused by capability.
                'GET /facility/team'                 => 403,
                'GET /admin/appointments'            => 403,
            ]],

            // Stock only: reports availability, never dispenses.
            'medicine_stock' => ['medicine_stock', [
                'GET /pharmacy'                      => 200,
                'GET /pharmacy/stock'                => 200,
                'GET /pharmacy/controlled'           => 200,
                'GET /pharmacy/prescriptions'        => 403,
                'GET /pharmacy/prescriptions/lookup' => 403,
                "POST /pharmacy/prescriptions/{$uuid}/dispense" => 403,
                'GET /facility/prescriptions'        => 403,
                'GET /facility/appointments'         => 403,
                'GET /facility/inventory/blood'      => 403,
                'GET /facility/messages'             => 403,
                'GET /facility/team'                 => 403,
            ]],

            'pharmacy_manager' => ['pharmacy_manager', [
                'GET /pharmacy'                      => 200,
                'GET /pharmacy/prescriptions'        => 200,
                'GET /pharmacy/prescriptions/lookup' => 200,
                'GET /pharmacy/stock'                => 200,
                'GET /pharmacy/controlled'           => 200,
                'GET /facility/messages'             => 200,
                'GET /facility/prescriptions'        => 403,
                // The manager runs the pharmacy's own team (facility_team).
                'GET /facility/team'                 => 200,
            ]],

            'records_officer' => ['records_officer', [
                'GET /facility'                      => 200,
                'GET /facility/appointments'         => 200,
                'GET /facility/inventory/blood'      => 200,
                'GET /facility/messages'             => 200,
                'GET /facility/prescriptions'        => 403,
            ]],

            // Billing roles land on /facility and hold no mapped web surface.
            'cashier' => ['cashier', [
                'GET /facility'                      => 200,
                'GET /facility/appointments'         => 403,
                'GET /facility/inventory/blood'      => 403,
                'GET /facility/messages'             => 403,
                'GET /facility/prescriptions'        => 403,
            ]],

            // NOT in the matrix: medical_supervisor. The capability map gives it
            // prescribe/schedule/blood/messaging, but EnsurePortalAccess lists it
            // in no portal bucket, so portal.access bounces it to /login before
            // any capability is judged. Its sidebar is ready (@capability-gated);
            // adding the role to PORTAL_ROLES['facility'] is a separate change.
        ];
    }

    #[DataProvider('matrix')]
    public function test_a_role_reaches_exactly_the_surfaces_its_capabilities_allow(string $roleName, array $expectations): void
    {
        [$user, $facility] = $this->staff($roleName);

        $failures = [];

        foreach ($expectations as $request => $expected) {
            [$method, $path] = explode(' ', $request, 2);

            $response = $this->actingAsStaff($user, $facility)->call($method, $path);
            $status   = $response->status();

            if ($expected === self::NOT_403) {
                if ($status === 403) {
                    $failures[] = "{$roleName} {$request}: refused with 403, but the role holds the capability";
                }
                continue;
            }

            if (is_string($expected) && str_starts_with($expected, '302 ')) {
                $target = substr($expected, 4);
                $where  = $response->headers->get('Location');
                if ($status !== 302 || parse_url((string) $where, PHP_URL_PATH) !== $target) {
                    $failures[] = "{$roleName} {$request}: expected 302 to {$target}, got {$status}" . ($where ? " to {$where}" : '');
                }
                continue;
            }

            if ($status !== $expected) {
                $failures[] = "{$roleName} {$request}: expected {$expected}, got {$status}";
            }
        }

        $this->assertSame([], $failures, "\n" . implode("\n", $failures) . "\n");
    }

    /**
     * A refusal is a 403 the professional can read — not a 404 (the page is no
     * secret to an authenticated colleague) and not a redirect. On the web it
     * renders the standard 403 page; as JSON it names the capability.
     */
    public function test_a_refusal_is_a_readable_403_not_a_404_or_a_redirect(): void
    {
        [$nurse, $facility] = $this->staff('nurse');

        $this->actingAsStaff($nurse, $facility)
            ->get('/facility/prescriptions')
            ->assertForbidden()
            ->assertSee('403');

        $this->actingAsStaff($nurse, $facility)
            ->getJson('/facility/prescriptions')
            ->assertForbidden()
            ->assertJsonPath('error_code', 'role_forbidden')
            ->assertJsonPath('required.0', 'prescribe');
    }

    // ── The inverse: every held capability with a web page is LINKED ─────────

    /** capability (any-of) => the named route its web page lives at */
    private const SURFACES = [
        'front_desk|schedule' => 'portals.staff.appointments',
        'prescribe'           => 'portals.prescriber.index',
        'dispense'            => 'portals.pharmacy.prescriptions',
        'pharmacy_stock'      => 'portals.pharmacy.stock',
        'blood_inventory'     => 'portals.staff.inventory.blood',
        'messaging'           => 'portals.staff.messages',
        'facility_team'       => 'portals.facility.team',
        'service_catalogue'   => 'portals.facility.catalogue',
        // The desks built in the dashboard pass. refund_approve is not here: it
        // gates buttons inside the refunds page, not a landing-page link.
        'triage'              => 'portals.triage.index',
        'queue'               => 'portals.queue.index',
        'lab_results'         => 'portals.lab.index',
        'sample_collection'   => 'portals.lab.collection',
        'clinical_chart'      => 'portals.chart.index',
        'billing'             => 'portals.billing.index',
    ];

    public static function linkedRoles(): array
    {
        // medical_supervisor: see the note in matrix() — no landing page today.
        return array_map(fn ($r) => [$r], [
            'doctor', 'nurse', 'pharmacist', 'receptionist', 'labtech',
            'midwife', 'triage_nurse', 'ward_nurse', 'nurse_supervisor',
            'records_officer', 'pharmacy_manager', 'medicine_stock',
            'lab_manager', 'sample_collection', 'student_doctor',
            'specialist', 'resident', 'facility_admin',
        ]);
    }

    #[DataProvider('linkedRoles')]
    public function test_every_web_surface_a_role_holds_is_linked_from_its_landing_page(string $roleName): void
    {
        [$user, $facility] = $this->staff($roleName);

        // Follow portal.access to wherever this role's portal is (the sidebar
        // renders on every page of it).
        $home = $this->actingAsStaff($user, $facility)->get('/facility');
        for ($hops = 0; $home->isRedirect() && $hops < 5; $hops++) {
            $home = $this->actingAsStaff($user, $facility)->get($home->headers->get('Location'));
        }
        $home->assertOk();

        preg_match_all('/<a\b[^>]*href="([^"]+)"/i', $home->getContent(), $m);
        $linked = array_map(fn ($h) => parse_url($h, PHP_URL_PATH), $m[1]);

        $missing = [];
        $present = [];
        foreach (self::SURFACES as $capabilities => $routeName) {
            $held = StaffCapabilities::roleCan($roleName, ...explode('|', $capabilities));
            $path = parse_url(route($routeName), PHP_URL_PATH);
            $has  = in_array($path, $linked, true);

            if ($held && ! $has) {
                $missing[] = "{$roleName} holds {$capabilities} but its landing page has no link to {$path}";
            }
            if (! $held && $has) {
                $present[] = "{$roleName} does NOT hold {$capabilities} yet its landing page links to {$path}";
            }
        }

        $this->assertSame([], array_merge($missing, $present), "\n" . implode("\n", array_merge($missing, $present)) . "\n");
    }

    // ── Demo accounts carry the role their name says ─────────────────────────

    /**
     * demo.labtech on production was a NURSE: DemoCareTeam listed a role name
     * that is seeded nowhere (`lab_technician`), fell through to its second
     * choice, and wrote `nurse` onto users.role_id AND the facility assignment
     * the portal gate reads. ensure() must now realign an existing demo member
     * — both rows — and must never rewrite a real person's account.
     */
    public function test_the_demo_care_team_seeder_repairs_a_drifted_demo_role_but_never_a_real_account(): void
    {
        $hospital = $this->realHospitalForTheResolver();

        $nurse      = Role::where('name', 'nurse')->firstOrFail();
        $doctor     = Role::where('name', 'doctor')->firstOrFail();
        $labtech    = Role::where('name', 'labtech')->firstOrFail();
        $specialist = Role::where('name', 'specialist')->firstOrFail();

        // The production shape: created by the OLD seeder as a nurse.
        $drifted = $this->demoAccount(DemoCareTeam::LAB_TECH['email'], $nurse, $hospital, isDemo: true);
        $this->demoAccount(DemoCareTeam::SPECIALIST['email'], $doctor, $hospital, isDemo: true);

        // A real clinician who happens to use the nurse demo address: NOT ours.
        $real = $this->demoAccount(DemoCareTeam::NURSE['email'], $doctor, $hospital, isDemo: false);

        DemoCareTeam::flush();
        DemoFacilityResolver::flush();
        DemoCareTeam::ensure();

        $drifted->refresh();
        $this->assertSame('labtech', $drifted->role?->name, 'users.role_id was not realigned');
        $this->assertSame('labtech', $drifted->roleAtFacility($hospital->id)?->name, 'the facility assignment the portal gate reads was not realigned');
        $this->assertSame(
            1,
            FacilityRoleAssignment::where('user_id', $drifted->id)->where('facility_id', $hospital->id)->active()->count(),
            'the retired nurse assignment must not stay active beside the labtech one'
        );
        $this->assertSame('labtech', StaffCapabilities::roleFor($drifted, $hospital->id));
        $this->assertSame($labtech->id, $drifted->role_id);

        // Demo rows are hidden by the IsDemoRecord global scope while demo mode
        // is off (it is, in the test environment), so look them up unscoped —
        // exactly what the seeder's own DB::table() reads do.
        $this->assertSame($specialist->id, User::withoutGlobalScopes()->where('email', DemoCareTeam::SPECIALIST['email'])->value('role_id'), 'demo.specialist should be a specialist, not a plain doctor');

        $real->refresh();
        $this->assertSame($doctor->id, $real->role_id, 'a non-demo account must never be touched');
        $this->assertSame('doctor', $real->roleAtFacility($hospital->id)?->name);

        // Members that did not exist are still created, with the right role.
        $created = User::withoutGlobalScopes()->where('email', DemoCareTeam::DOCTOR['email'])->first();
        $this->assertNotNull($created);
        $this->assertTrue((bool) $created->is_demo);
        $this->assertSame('doctor', $created->role?->name);
        $this->assertSame('doctor', $created->roleAtFacility($hospital->id)?->name);

        // Running it again changes nothing (idempotent).
        DemoCareTeam::flush();
        DemoCareTeam::ensure();
        $this->assertSame(1, FacilityRoleAssignment::where('user_id', $drifted->id)->where('facility_id', $hospital->id)->active()->count());

        // And every demo clinician lands on a page that answers 200.
        $landing = app(DashboardProfileService::class);
        foreach ([DemoCareTeam::DOCTOR, DemoCareTeam::SPECIALIST, DemoCareTeam::LAB_TECH] as $member) {
            $user = User::withoutGlobalScopes()->where('email', $member['email'])->firstOrFail();
            $url  = $landing->landingUrlForUser($user);
            $this->actingAsStaff($user, $hospital)->get(parse_url($url, PHP_URL_PATH))->assertOk();
        }
    }

    /**
     * DemoFacilityResolver resolves the primary hospital through the
     * care_facilities directory; give it the one real row it looks for.
     */
    private function realHospitalForTheResolver(): Facility
    {
        $hospital = Facility::factory()->create(['name' => DemoFacilityResolver::PRIMARY_HOSPITAL, 'type' => 'hospital']);

        DB::table('care_facilities')->insert([
            'id'            => (string) Str::uuid(),
            'facility_id'   => $hospital->id,
            'facility_name' => DemoFacilityResolver::PRIMARY_HOSPITAL,
            'facility_type' => 'hospital',
            'city'          => 'Yaoundé',
            'address'       => 'Rue Henri Dunant',
            'phone_primary' => '+237222234010',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return $hospital;
    }

    private function demoAccount(string $email, Role $role, Facility $facility, bool $isDemo): User
    {
        $user = User::factory()->create([
            'email'               => $email,
            'role_id'             => $role->id,
            'primary_facility_id' => $facility->id,
            'status'              => 'active',
            'is_demo'             => $isDemo,
        ]);

        FacilityRoleAssignment::create([
            'user_id'     => $user->id,
            'facility_id' => $facility->id,
            'role_id'     => $role->id,
            'is_active'   => true,
            'assigned_at' => now(),
        ]);

        return $user;
    }
}
