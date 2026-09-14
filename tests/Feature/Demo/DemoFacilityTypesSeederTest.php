<?php

namespace Tests\Feature\Demo;

use App\Models\CareFacility;
use App\Models\CareFacilityHour;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\User;
use Database\Seeders\DemoFacilityTypesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * DemoFacilityTypesSeeder: every facility type gets exactly one demo tenant,
 * one public listing linked to it, and operator logins whose posting is at
 * that tenant — and running it twice changes nothing. Each admin/manager
 * login must actually open its own dashboard.
 */
class DemoFacilityTypesSeederTest extends TestCase
{
    use RefreshDatabase;

    /** Admin/manager login per type → the page its portal lands on. */
    private const LANDING = [
        'demo.hospital.admin@opescare.test'     => '/facility/team/overview',
        'demo.clinic.admin@opescare.test'       => '/facility/team/overview',
        'demo.healthcenter.admin@opescare.test' => '/facility/team/overview',
        'demo.lab.manager@opescare.test'        => '/facility/lab/dashboard',
        'demo.dental.admin@opescare.test'       => '/facility/team/overview',
        'demo.imaging.admin@opescare.test'      => '/facility/team/overview',
        'demo.eye.admin@opescare.test'          => '/facility/team/overview',
        'demo.diagnostic.admin@opescare.test'   => '/facility/team/overview',
        'demo.maternity.admin@opescare.test'    => '/facility/team/overview',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    public function test_seeding_twice_yields_one_tenant_one_linked_listing_and_every_login_per_type(): void
    {
        // The hospital and pharmacy tenants pre-exist in every estate, the
        // hospital with an UNLINKED imported directory row the seeder must adopt.
        $hospital = Facility::create(['name' => 'Hôpital Central de Yaoundé', 'type' => 'hospital', 'status' => 'active']);
        Facility::create(['name' => 'Pharmacy ESSEC', 'type' => 'pharmacy', 'status' => 'active']);
        $imported = CareFacility::create([
            'facility_name' => 'Hôpital Central de Yaoundé', 'facility_type' => 'hospital',
            'country_code' => 'CM', 'region' => 'Centre', 'city' => 'Yaoundé', 'address' => 'Rue de l\'Hôpital',
            'phone_primary' => '+237222000000',
        ]);
        // A listing of the same name that belongs to SOMEONE ELSE is never taken over.
        $other = Facility::create(['name' => 'Someone else', 'type' => 'clinic', 'status' => 'active']);
        $taken = CareFacility::create([
            'facility_id' => $other->id, 'facility_name' => 'Clinique Demo Bonapriso', 'facility_type' => 'clinic',
            'country_code' => 'CM', 'region' => 'Littoral', 'city' => 'Douala', 'address' => 'x', 'phone_primary' => '+237600000000',
        ]);

        $this->seed(DemoFacilityTypesSeeder::class);
        $this->seed(DemoFacilityTypesSeeder::class);

        foreach (DemoFacilityTypesSeeder::catalogue() as $type => $spec) {
            $tenants = Facility::withoutGlobalScopes()->where('name', $spec['name'])->get();
            $this->assertCount(1, $tenants, "{$type}: tenant seeded more than once");
            $tenant = $tenants->first();
            $this->assertSame($type, $tenant->type, "{$type}: tenant type");

            $listings = CareFacility::where('facility_id', $tenant->id)->get();
            $this->assertCount(1, $listings, "{$type}: expected exactly one listing linked to the tenant");
            $this->assertSame($type, $listings->first()->facility_type);
            $this->assertSame(7, CareFacilityHour::where('facility_id', $listings->first()->id)->count(), "{$type}: opening hours");

            foreach ($spec['logins'] as $email => [$roleSpec, $label]) {
                $user = User::withoutGlobalScopes()->where('email', $email)->first();
                $this->assertNotNull($user, "{$email} missing");
                $this->assertTrue((bool) $user->is_demo);
                $this->assertNotNull($user->email_verified_at);
                $this->assertSame($tenant->id, $user->primary_facility_id, "{$email}: primary facility");
                $this->assertContains($user->role->name, explode('|', $roleSpec), "{$email}: role");
                $this->assertTrue(\Illuminate\Support\Facades\Hash::check('demo1234', $user->password));

                $postings = FacilityRoleAssignment::where('user_id', $user->id)->where('is_active', true)->get();
                $this->assertCount(1, $postings, "{$email}: exactly one active posting");
                $this->assertSame($tenant->id, $postings->first()->facility_id);
                $this->assertSame($user->role_id, $postings->first()->role_id);
            }
        }

        // The hospital adopted the imported row rather than creating a twin.
        $this->assertSame($hospital->id, $imported->fresh()->facility_id);
        $this->assertSame(1, CareFacility::where('facility_name', 'Hôpital Central de Yaoundé')->count());
        // The clinic did not steal the other tenant's listing.
        $this->assertSame($other->id, $taken->fresh()->facility_id);
        $this->assertSame(2, CareFacility::where('facility_name', 'Clinique Demo Bonapriso')->count());
    }

    public function test_every_admin_or_manager_login_opens_its_own_dashboard(): void
    {
        $this->seed(DemoFacilityTypesSeeder::class);

        foreach (self::LANDING as $email => $path) {
            $user = User::withoutGlobalScopes()->where('email', $email)->firstOrFail();

            $this->actingAs($user)
                ->withSession(['active_facility_id' => $user->primary_facility_id])
                ->get($path)
                ->assertOk();
            $this->app['auth']->forgetGuards();
        }
    }

    public function test_the_medicine_stock_login_reaches_the_pharmacy_desk(): void
    {
        Facility::create(['name' => 'Pharmacy ESSEC', 'type' => 'pharmacy', 'status' => 'active']);
        $this->seed(DemoFacilityTypesSeeder::class);

        $stock = User::withoutGlobalScopes()->where('email', 'demo.pharmacy.stock@opescare.test')->firstOrFail();

        $this->actingAs($stock)
            ->withSession(['active_facility_id' => $stock->primary_facility_id])
            ->get('/pharmacy')
            ->assertOk();
    }
}
