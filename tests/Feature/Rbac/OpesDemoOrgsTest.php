<?php

namespace Tests\Feature\Rbac;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\User;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\OpesDemoOrgsSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Verifies the OpesDemoOrgsSeeder builds 8 demo facilities (one per type) with
 * per-role demo accounts, and that a representative account per facility type
 * logs in and reaches its correct portal (not 403, not a /login redirect).
 */
class OpesDemoOrgsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);
        $this->seed(OpesDemoOrgsSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    /** name => type for the 8 expected demo facilities. */
    private array $expectedFacilities = [
        'OpesHospital'  => 'hospital',
        'OpesClinic'    => 'clinic',
        'OpesPharmacy'  => 'pharmacy',
        'OpesLab'       => 'laboratory',
        'OpesInsurance' => 'insurance',
        'OpesHealthOrg' => 'health_org',
        'OpesDeveloper' => 'developer',
        'OpesLite'      => 'lite',
    ];

    public function test_eight_demo_facilities_exist_with_correct_types(): void
    {
        $this->assertSame(
            8,
            Facility::withoutDemoIsolation()->whereIn('name', array_keys($this->expectedFacilities))->count()
        );

        foreach ($this->expectedFacilities as $name => $type) {
            $f = Facility::withoutDemoIsolation()->where('name', $name)->first();
            $this->assertNotNull($f, "Facility {$name} should exist");
            $this->assertSame($type, $f->type, "Facility {$name} should be type {$type}");
            $this->assertTrue((bool) $f->is_demo, "Facility {$name} should be is_demo");
            $this->assertSame('active_demo', $f->status, "Facility {$name} should be active_demo");
        }
    }

    public function test_demo_users_and_assignments_created_per_facility(): void
    {
        // Total expected role accounts across all 8 facilities (architecture §4).
        $expectedTotal = 17 + 7 + 6 + 6 + 5 + 4 + 6 + 4; // = 55
        $this->assertSame(
            $expectedTotal,
            User::withoutDemoIsolation()->where('email', 'like', '%.opes.test')->count()
        );

        $this->assertSame(
            $expectedTotal,
            FacilityRoleAssignment::query()->where('is_active', true)->count()
        );

        // Each facility must have at least one demo user + assignment.
        foreach (array_keys($this->expectedFacilities) as $name) {
            $facility = Facility::withoutDemoIsolation()->where('name', $name)->first();
            $userCount = User::withoutDemoIsolation()
                ->where('primary_facility_id', $facility->id)->count();
            $this->assertGreaterThan(0, $userCount, "{$name} should have demo users");

            $assignmentCount = FacilityRoleAssignment::query()
                ->where('facility_id', $facility->id)->where('is_active', true)->count();
            $this->assertGreaterThan(0, $assignmentCount, "{$name} should have role assignments");
        }
    }

    /**
     * One representative account per facility type → expected portal landing path.
     * Portals resolved from EnsurePortalAccess::correctPortalFor mapping.
     */
    public static function representativeAccounts(): array
    {
        return [
            'hospital doctor'    => ['doctor@hospital.opes.test',          '/portals/staff'],
            'clinic admin'       => ['clinic_admin@clinic.opes.test',      '/portals/admin'],
            'pharmacy pharmacist'=> ['pharmacist@pharmacy.opes.test',      '/portals/pharmacy'],
            'lab manager'        => ['lab_manager@laboratory.opes.test',   '/portals/lab'],
            'insurance admin'    => ['insurance_admin@insurance.opes.test','/portals/insurance'],
            'ngo admin'          => ['ngo_admin@healthorg.opes.test',      '/portals/healthorg'],
            'developer'          => ['developer@developer.opes.test',      '/portals/developer'],
            'lite facility'      => ['lite_facility@lite.opes.test',       '/portals/lite'],
        ];
    }

    #[DataProvider('representativeAccounts')]
    public function test_representative_account_reaches_its_portal(string $email, string $portalPath): void
    {
        $user = User::withoutDemoIsolation()->where('email', $email)->first();
        $this->assertNotNull($user, "Demo account {$email} should exist");

        // Mirror facility-context-dependent flows: set the active facility session
        // to the user's primary facility before hitting the portal.
        $res = $this->actingAs($user)
            ->withSession(['active_facility_id' => $user->primary_facility_id])
            ->get($portalPath);

        $status = $res->getStatusCode();

        // Must NOT be forbidden.
        $this->assertNotSame(403, $status, "{$email} should not be forbidden at {$portalPath}");

        // Must NOT be redirected to /login (i.e. they reach their portal).
        if ($res->isRedirect()) {
            $location = $res->headers->get('Location') ?? '';
            $this->assertStringNotContainsString('/login', $location,
                "{$email} should not be bounced to login from {$portalPath}");
            // Also must not be redirected to a *different* portal (wrong-portal bounce).
            $this->assertStringContainsString($portalPath, $location,
                "{$email} was redirected away from its own portal {$portalPath} (to {$location})");
        }
    }
}
