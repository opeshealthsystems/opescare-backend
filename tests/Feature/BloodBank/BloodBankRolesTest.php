<?php

namespace Tests\Feature\BloodBank;

use App\Models\CareFacility;
use App\Models\DashboardProfile;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * DX-1 / DX-5 / DX-2 (2026-10-20).
 *
 *   1. A blood bank's administrators (the facility-admin family) and its own
 *      roles (blood_bank_officer, blood_bank_manager) reach the shelf and the
 *      requests desk.
 *   2. The blood bank roles are seeded with their own sidebar profile, which
 *      links both desks plus Tasks and Help.
 *   3. A tenant with no linked directory listing is WARNED on its desk, and
 *      the condition is logged. Nothing about the allow-list changes.
 */
class BloodBankRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function staff(string $roleName, ?Facility $facility = null): array
    {
        $role     = Role::where('name', $roleName)->firstOrFail();
        $facility ??= Facility::factory()->create(['type' => 'blood_bank']);

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

    private function as(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id, 'mfa.verified' => true]);
    }

    private function link(Facility $facility): void
    {
        CareFacility::create([
            'facility_id'    => $facility->id,
            'facility_name'  => 'Test Blood Bank',
            'facility_type'  => 'blood_bank',
            'listing_status' => 'active',
            'country_code'   => 'CM',
            'region'         => 'Centre',
            'city'           => 'Yaounde',
            'address'        => 'Rue Test',
            'phone_primary'  => '+237222000000',
        ]);
    }

    public static function bloodDeskRoles(): array
    {
        return array_map(fn ($r) => [$r], [
            'blood_bank_officer', 'blood_bank_manager',
            'facility_admin', 'hospital_admin', 'clinic_admin',
        ]);
    }

    #[DataProvider('bloodDeskRoles')]
    public function test_role_reaches_the_blood_shelf_and_the_requests_desk(string $roleName): void
    {
        [$user, $facility] = $this->staff($roleName);
        $this->link($facility);

        $this->as($user, $facility)->get('/facility/inventory/blood')->assertOk();
        $this->as($user, $facility)->get('/facility/blood-requests')->assertOk();
    }

    public function test_blood_bank_roles_are_seeded_with_their_own_sidebar_profile(): void
    {
        $profile = DashboardProfile::where('key', 'blood_bank')->first();
        $this->assertNotNull($profile);

        foreach (['blood_bank_officer', 'blood_bank_manager'] as $name) {
            $role = Role::where('name', $name)->firstOrFail();
            $this->assertSame($profile->id, $role->dashboard_profile_id, "{$name} renders the blood bank sidebar");
        }
    }

    public function test_blood_bank_officer_lands_on_the_desk_with_both_blood_links_tasks_and_help(): void
    {
        [$user, $facility] = $this->staff('blood_bank_officer');
        $this->link($facility);

        $this->as($user, $facility)->get('/facility')
            ->assertOk()
            ->assertSee(route('portals.staff.inventory.blood'), false)
            ->assertSee(route('portals.staff.blood_requests.index'), false)
            ->assertSee(route('portals.staff.tasks'), false)
            ->assertSee(route('portals.staff.support'), false);
    }

    public function test_officer_is_kept_out_of_clinical_desks(): void
    {
        [$user, $facility] = $this->staff('blood_bank_officer');

        $this->as($user, $facility)->get('/facility/prescriptions')->assertForbidden();
        $this->as($user, $facility)->get('/facility/imaging')->assertForbidden();
    }

    public function test_unlinked_blood_bank_is_warned_and_the_condition_is_logged(): void
    {
        Log::spy();
        [$user, $facility] = $this->staff('blood_bank_manager');

        $this->as($user, $facility)->get('/facility/inventory/blood')
            ->assertOk()
            ->assertSee(__('public.stf_inv_blood_no_listing_notice'), false);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($msg, $ctx = []) => $msg === 'blood_inventory.unlisted_facility'
                && ($ctx['facility_id'] ?? null) === $facility->id)
            ->atLeast()->once();
    }

    public function test_linked_blood_bank_is_not_warned(): void
    {
        [$user, $facility] = $this->staff('blood_bank_manager');
        $this->link($facility);

        $this->as($user, $facility)->get('/facility/inventory/blood')
            ->assertOk()
            ->assertDontSee(__('public.stf_inv_blood_no_listing_notice'), false)
            ->assertSee(__('public.stf_inv_blood_publishes_notice'), false);
    }

    public function test_facility_admin_landing_links_the_blood_desk(): void
    {
        [$user, $facility] = $this->staff('facility_admin');

        $this->as($user, $facility)->get('/facility/team/overview')
            ->assertOk()
            ->assertSee(route('portals.staff.inventory.blood'), false)
            ->assertSee(route('portals.staff.blood_requests.index'), false);
    }
}
